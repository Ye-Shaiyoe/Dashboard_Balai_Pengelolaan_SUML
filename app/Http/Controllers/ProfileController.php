<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Carbon\Carbon;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $sessions = [];
        if (config('session.driver') === 'database') {
            $sessions = DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->orderBy('last_activity', 'desc')
                ->get()
                ->map(function ($session) use ($request) {
                    $agent = $this->parseUserAgent($session->user_agent);
                    return (object) [
                        'id' => $session->id,
                        'ip_address' => $session->ip_address,
                        'is_current_device' => $session->id === $request->session()->getId(),
                        'agent' => $agent,
                        'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    ];
                });
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'sessions' => $sessions,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Fill name & email (NIP handled separately due to encrypted cast)
        $user->fill($request->safe()->only(['name', 'email']));

        // Set NIP explicitly (encrypted cast handles encryption automatically)
        if ($request->has('nip')) {
            $nipValue = $request->input('nip') ?: null;
            $user->nip = $nipValue;
            // Selalu sync nip_hash agar lookup via NIP tetap akurat
            $user->nip_hash = $nipValue ? \App\Models\User::hashNip($nipValue) : null;
        }

        // Handle profile photo (base64 dari cropper maupun upload langsung).
        // Gambar selalu di-decode ulang menjadi PNG oleh ImageUploadService,
        // sehingga ekstensi & isi file tidak lagi ditentukan oleh client.
        $imageUploader = app(ImageUploadService::class);

        if ($request->filled('cropped_photo')) {
            $this->storeProfilePhoto(
                $user,
                $imageUploader->fromDataUri($request->input('cropped_photo'), 'profile_photo')
            );
        } elseif ($request->hasFile('profile_photo') || $request->hasFile('profile_photo_raw')) {
            $file = $request->file('profile_photo') ?? $request->file('profile_photo_raw');

            $this->storeProfilePhoto(
                $user,
                $imageUploader->fromUploadedFile($file, 'profile_photo')
            );
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Simpan foto profil (PNG yang sudah dinormalisasi) ke disk public.
     * Nama file selalu dibuat server, ekstensi selalu `.png`.
     */
    private function storeProfilePhoto(User $user, string $png): void
    {
        $fileName = 'profile_photos/' . Str::uuid() . '.png';

        Storage::disk('public')->put($fileName, $png);

        // Hapus foto lama setelah foto baru berhasil disimpan.
        if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->profile_photo = $fileName;
    }

    /**
     * Simpan tanda tangan TTE (PNG transparan) ke disk private.
     *
     * TTE adalah data biometrik yang sensitif, jadi tidak boleh berada di
     * disk `public`. File lama (dari sebelum perbaikan) tetap dihapus dari
     * kedua disk agar tidak ada salinan yang tertinggal.
     */
    private function storeSignature(User $user, string $png): void
    {
        $fileName = 'signatures/' . Str::uuid() . '.png';

        Storage::disk('private')->put($fileName, $png);

        if ($user->signature_path) {
            foreach (['private', 'public'] as $disk) {
                if (Storage::disk($disk)->exists($user->signature_path)) {
                    Storage::disk($disk)->delete($user->signature_path);
                }
            }
        }

        $user->signature_path = $fileName;
    }

    /**
     * Tampilkan tanda tangan TTE milik sendiri, atau milik user lain bagi admin.
     */
    public function signature(Request $request, ?User $user = null)
    {
        $user ??= $request->user();
        $viewer = $request->user();

        if ($viewer->id !== $user->id && !$viewer->isAdmin()) {
            abort(403, 'Anda tidak berhak melihat tanda tangan ini.');
        }

        if (!$user->signature_path) {
            abort(404, 'Tanda tangan tidak ditemukan.');
        }

        $disk = Storage::disk('private');

        if (!$disk->exists($user->signature_path)) {
            // Kompatibilitas: file lama masih tersimpan di disk public.
            $disk = Storage::disk('public');
        }

        if (!$disk->exists($user->signature_path)) {
            abort(404, 'Tanda tangan tidak ditemukan.');
        }

        $extension = strtolower(pathinfo($user->signature_path, PATHINFO_EXTENSION));

        $mimeTypes = [
            'png'  => 'image/png',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];

        if (!isset($mimeTypes[$extension])) {
            abort(404, 'Tanda tangan tidak ditemukan.');
        }

        return response($disk->get($user->signature_path), 200, [
            'Content-Type'        => $mimeTypes[$extension],
            'Content-Disposition' => 'inline; filename="signature.' . $extension . '"',
            'Cache-Control'       => 'private, no-store, max-age=0',
        ]);
    }

    /**
     * Revoke a specific active session.
     */
    public function revokeSession(Request $request, string $sessionId): RedirectResponse
    {
        if (config('session.driver') === 'database') {
            // Ensure the session belongs to the authenticated user and is not the current session
            if ($sessionId !== $request->session()->getId()) {
                DB::table('sessions')
                    ->where('user_id', $request->user()->id)
                    ->where('id', $sessionId)
                    ->delete();
            }
        }

        return Redirect::route('profile.edit')->with('status', 'session-revoked');
    }

    /**
     * Revoke all other active sessions (logout other devices).
     */
    public function revokeAllOtherSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        if (config('session.driver') === 'database') {
            DB::table('sessions')
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // Laravel standard logout other devices
        Auth::logoutOtherDevices($request->input('current_password'));

        return Redirect::route('profile.edit')->with('status', 'all-other-sessions-revoked');
    }

    /**
     * Parse User Agent helper.
     */
    private function parseUserAgent(string $userAgent): array
    {
        $platform = 'Unknown OS';
        $browser = 'Unknown Browser';
        $isMobile = false;

        // Platform detection
        if (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
            $isMobile = true;
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
            $isMobile = true;
        }

        // Browser detection
        if (preg_match('/chrome/i', $userAgent) && !preg_match('/edge|edg/i', $userAgent) && !preg_match('/opr/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge|edg/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/opera|opr/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/msie|trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/vivaldi/i', $userAgent)) {
            $browser = 'Vivaldi';
        } elseif (preg_match('/yabrowser/i', $userAgent)) {
            $browser = 'Yandex Browser';
        } elseif (preg_match('/samsungbrowser/i', $userAgent)) {
            $browser = 'Samsung Internet';
        } elseif (preg_match('/ucbrowser/i', $userAgent)) {
            $browser = 'UC Browser';
        } else {
            $browser = 'Unknown';
        }

        return [
            'platform' => $platform,
            'browser' => $browser,
            'is_mobile' => $isMobile,
            'icon' => $isMobile ? 'bi-phone' : 'bi-laptop'
        ];
    }

    /**
     * Update the user's digital signature and TTE PIN.
     */
    public function updateTte(Request $request): RedirectResponse
    {
        $user = $request->user();

        // 1. Validasi PIN lama jika sudah ada
        if ($user->signature_pin && ($request->filled('signature_pin') || $request->filled('cropped_signature') || $request->hasFile('signature_file'))) {
            $request->validate([
                'current_signature_pin' => ['required', 'string'],
            ]);

            if (!\Illuminate\Support\Facades\Hash::check($request->current_signature_pin, $user->signature_pin)) {
                return Redirect::to(route('profile.edit') . '#tte-section')->withErrors(['current_signature_pin' => 'PIN TTE saat ini tidak valid.']);
            }
        }

        // 2. Validasi input PIN baru & file
        $request->validate([
            'signature_pin' => ['nullable', 'string', 'min:6', 'max:20', 'confirmed'],
            'signature_file' => ['nullable', 'image', 'mimes:png', 'max:2048'], // png disarankan karena transparansi
            'cropped_signature' => ['nullable', 'string', 'max:3000000'],
        ]);

        // 3. Proses Upload/Crop Tanda Tangan.
        // Gambar di-decode ulang menjadi PNG dan disimpan di disk `private`,
        // bukan `public`, karena tanda tangan adalah data biometrik sensitif.
        $imageUploader = app(ImageUploadService::class);

        try {
            if ($request->filled('cropped_signature')) {
                $this->storeSignature(
                    $user,
                    $imageUploader->fromDataUri($request->input('cropped_signature'), 'signature_file')
                );
            } elseif ($request->hasFile('signature_file')) {
                $this->storeSignature(
                    $user,
                    $imageUploader->fromUploadedFile($request->file('signature_file'), 'signature_file')
                );
            }
        } catch (ValidationException $e) {
            return Redirect::to(route('profile.edit') . '#tte-section')->withErrors($e->errors());
        }

        // 4. Proses PIN Baru
        if ($request->filled('signature_pin')) {
            $user->signature_pin = $request->signature_pin;
        }

        $user->save();

        return Redirect::to(route('profile.edit') . '#tte-section')->with('status', 'tte-updated');
    }
}
