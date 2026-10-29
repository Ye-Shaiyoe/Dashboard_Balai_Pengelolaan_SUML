@extends('layouts.admin')

@section('title', 'Detail Pegawai')

@section('content')
<div style="display: flex; flex-direction: column; gap: 24px;">

    {{-- FLASH NOTIFIKASI RESET PASSWORD --}}
    @if(session('reset_success'))
        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 24px; line-height: 1;">✅</span>
                    <div>
                        <h4 style="margin: 0 0 6px 0; color: #15803d; font-size: 15px; font-weight: 700;">Password Berhasil Direset!</h4>
                        <p style="margin: 0 0 8px 0; font-size: 13px; color: #166534;">
                            Password untuk <strong>{{ session('reset_name') }}</strong> ({{ session('reset_email') }}) telah diperbarui. Silakan berikan password baru ini kepada pegawai:
                        </p>
                        <div style="display: inline-flex; align-items: center; gap: 10px; background: white; border: 2px dashed #22c55e; border-radius: 8px; padding: 8px 14px;">
                            <span style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #6b7280; letter-spacing: 0.05em;">Password Baru:</span>
                            <code id="passwordResetBaru" style="font-size: 15px; font-weight: 700; color: #047857; letter-spacing: 1px;">{{ session('reset_password') }}</code>
                            <button type="button" onclick="salinPasswordReset()" style="background: #15803d; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                📋 Salin Password
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="this.parentElement.parentElement.remove()" style="background: transparent; border: none; color: #9ca3af; font-size: 20px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
        </div>
    @endif

    {{-- BACK BUTTON & HEADER --}}
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; align-items: center; gap: 20px;">
            @if ($user->profile_photo)
                <img src="{{ Storage::url($user->profile_photo) }}" alt="{{ $user->name }}" 
                     style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
            @else
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); display: flex; align-items: center; justify-content: center; font-size: 32px; color: white; font-weight: bold; border: 4px solid white; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div>
                <h1 style="margin: 0; font-size: 32px; letter-spacing: -0.025em;">{{ $user->name }}</h1>
                <p style="color: #6b7280; margin-top: 4px; font-size: 14px;">
                    <strong>Email:</strong> {{ $user->email }} | 
                    <strong>Role:</strong> <span class="badge {{ $user->role === 'admin' ? 'badge-blue' : 'badge-gray' }}" style="font-size: 11px;">{{ ucfirst($user->role) }}</span> |
                    <strong>Daftar:</strong> {{ $user->created_at->format('d M Y') }}
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            @if(in_array(auth()->user()->role, ['admin_kasubbag_tu', 'admin']) && $user->id !== auth()->id())
                <button type="button" 
                        onclick="bukaModalResetPassword('{{ $user->uuid }}', '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}')"
                        class="btn" 
                        style="background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 12px; padding: 10px 18px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    🔄 Reset Password
                </button>
            @endif
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="border-radius: 12px; padding: 10px 20px;">
                ← Kembali
            </a>
        </div>
    </div>

    {{-- USER STATISTICS --}}
    <div class="stat-grid" style="grid-template-columns: repeat(5, 1fr) !important;">
        <div class="stat-card blue">
            <div class="stat-label">Total Surat</div>
            <div class="stat-value">{{ $stats['total_surats'] }}</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Selesai</div>
            <div class="stat-value">{{ $stats['surats_selesai'] }}</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Dalam Proses</div>
            <div class="stat-value">{{ $stats['surats_proses'] }}</div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Ditolak</div>
            <div class="stat-value">{{ $stats['surats_ditolak'] }}</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Rata-rata Hari Proses</div>
            <div class="stat-value">{{ $stats['avg_processing_days'] }}</div>
            <div class="stat-sub">Untuk surat selesai</div>
        </div>
    </div>

    {{-- DIGITAL SIGNATURE (TTE) SECTION --}}
    @if($user->signature_path)
        <div class="card" style="background: linear-gradient(135deg, #f0f4ff 0%, #f9f5ff 100%); border: 1px solid #e0e7ff;">
            <div class="section-header" style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 24px;">✍️</span>
                <h2 style="margin: 0;">Tanda Tangan Elektronik (TTE)</h2>
                <span class="badge badge-green" style="font-size: 11px; padding: 4px 8px;">Terverifikasi</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                {{-- Signature Image --}}
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <label style="font-weight: 600; font-size: 13px; color: #4b5563;">Pratinjau Tanda Tangan</label>
                    <div style="border: 2px dashed #cbd5e1; border-radius: 8px; padding: 16px; background: white; display: flex; align-items: center; justify-content: center; min-height: 180px;">
                        <img src="{{ Storage::url($user->signature_path) }}" alt="Tanda Tangan {{ $user->name }}" 
                             style="max-width: 280px; max-height: 160px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));">
                    </div>
                    <p style="font-size: 12px; color: #6b7280; margin: 0;">
                        <strong>Pemilik:</strong> {{ $user->name }}<br>
                        <strong>Tanggal Daftar:</strong> {{ $user->created_at->format('d M Y H:i') }}<br>
                        <strong>Terakhir Diperbarui:</strong> {{ $user->updated_at->format('d M Y H:i') }}
                    </p>
                </div>

                {{-- Signature Info & Security Status --}}
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="background: white; border-radius: 8px; padding: 12px; border-left: 4px solid #4361ee;">
                        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937;">🔒 Status Keamanan</h4>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></span>
                            <span style="font-size: 12px; color: #059669;"><strong>TTD Terverifikasi</strong></span>
                        </div>
                        <p style="font-size: 12px; color: #6b7280; margin: 0;">
                            Tanda tangan digital telah tersimpan dan siap digunakan untuk penandatanganan dokumen resmi.
                        </p>
                    </div>

                    <div style="background: white; border-radius: 8px; padding: 12px; border-left: 4px solid #f59e0b;">
                        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937;">🔑 PIN Perlindungan</h4>
                        <p style="font-size: 12px; color: #6b7280; margin: 0;">
                            TTD ini dilindungi dengan PIN terenkripsi. Admin tidak dapat melihat PIN pengguna untuk keamanan maksimal.
                        </p>
                        <div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-radius: 4px; border-left: 2px solid #f59e0b;">
                            <span style="font-size: 11px; color: #92400e;">⚠️ PIN hanya diketahui oleh pengguna untuk autentikasi dokumen</span>
                        </div>
                    </div>

                    <div style="background: white; border-radius: 8px; padding: 12px; border-left: 4px solid #6366f1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937;">📋 Informasi File</h4>
                        <p style="font-size: 12px; color: #6b7280; margin: 0;">
                            <strong>Format:</strong> PNG (Transparent)<br>
                            <strong>Path:</strong> <code style="background: #f3f4f6; padding: 2px 4px; border-radius: 3px; font-size: 11px;">{{ $user->signature_path }}</code><br>
                            <strong>Ukuran:</strong> Variable (disesuaikan pengguna)<br>
                            <strong>Keamanan:</strong> Private Storage (tidak accessible publik)
                        </p>
                    </div>
                </div>
            </div>

            {{-- Usage Info --}}
            <div style="margin-top: 16px; padding: 12px; background: white; border-radius: 8px; border-left: 4px solid #8b5cf6;">
                <h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1f2937;">📝 Penggunaan TTD</h4>
                <p style="font-size: 12px; color: #6b7280; margin: 0;">
                    Tanda tangan ini akan ditampilkan pada:
                </p>
                <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 12px; color: #6b7280;">
                    <li>Dokumen yang ditandatangani di Tahap 6 (Tanda Tangan DS)</li>
                    <li>Laporan dan sertifikat resmi dari BP Suml</li>
                    <li>Dokumen publik yang diverifikasi</li>
                </ul>
            </div>
        </div>
    @else
        <div class="card" style="border: 1px solid #fee2e2; background: #fef2f2;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 16px; text-align: center;">
                <span style="font-size: 32px;">✍️</span>
                <div>
                    <h3 style="margin: 0 0 4px 0; color: #991b1b; font-size: 14px;"><strong>Tanda Tangan Belum Ditambahkan</strong></h3>
                    <p style="margin: 0; font-size: 12px; color: #7f1d1d;">
                        Pengguna belum mendaftarkan tanda tangan elektronik (TTE). TTD dapat ditambahkan melalui halaman profil pengguna.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- SURAT HISTORY --}}
    <div class="card">
        <div class="section-header" style="margin-bottom: 16px;">
            <h2>📄 Riwayat Surat</h2>
            <small>Total: {{ $user->surats->count() }} surat</small>
        </div>

        @if($user->surats->count() > 0)
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Judul</th>
                            <th style="width: 15%;">Jenis</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 12%; text-align: center;">Tahap</th>
                            <th style="width: 12%; text-align: center;">Deadline</th>
                            <th style="width: 10%; text-align: center;">Dibuat</th>
                            <th style="width: 9%; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->surats as $surat)
                            <tr>
                                <td>
                                    <strong>{{ Str::limit($surat->judul, 30) }}</strong>
                                </td>
                                <td>
                                    <span style="font-size: 11px; color: #6b7280;">
                                        {{ str_replace('_', ' ', $surat->jenis) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ match($surat->status) {
                                        'selesai' => 'badge-green',
                                        'proses' => 'badge-blue',
                                        'ditolak' => 'badge-red',
                                        default => 'badge-gray'
                                    } }}">
                                        {{ ucfirst($surat->status) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="font-size: 12px; color: #6b7280;">
                                        {{ $surat->tahap_sekarang }}/10
                                    </span>
                                </td>
                                <td style="text-align: center; font-size: 12px;">
                                    @if($surat->deadline_sla)
                                        <span style="color: {{ now()->diffInDays($surat->deadline_sla) <= 3 ? '#b91c1c' : '#6b7280' }};">
                                            {{ $surat->deadline_sla->format('d M Y') }}
                                        </span>
                                    @else
                                        <span style="color: #9ca3af;">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center; font-size: 12px; color: #6b7280;">
                                    {{ $surat->created_at->format('d M Y') }}
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('admin.surat.show', $surat) }}" class="btn btn-sm" title="Lihat detail">
                                        👁️
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div style="text-align: center; padding: 40px 20px; color: #9ca3af;">
                <p style="font-size: 14px;">Pengguna ini belum mengajukan surat apapun</p>
            </div>
        @endif
    </div>

    {{-- MODAL RESET PASSWORD (KHUSUS KASUBBAG TU / ADMIN) --}}
    @if(in_array(auth()->user()->role, ['admin_kasubbag_tu', 'admin']))
        <div id="modalResetPassword" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
            <div style="background: white; border-radius: 16px; max-width: 460px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); overflow: hidden; animation: modalPop 0.2s ease-out;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 20px;">🔐</span>
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #111827;">Reset Password Pegawai</h3>
                    </div>
                    <button type="button" onclick="tutupModalResetPassword()" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #9ca3af; line-height: 1;">&times;</button>
                </div>
                <form id="formResetPassword" method="POST" action="">
                    @csrf
                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                        <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; font-size: 12px; color: #92400e; line-height: 1.5;">
                            ⚠️ <strong>Otoritas Kasubbag TU</strong>: Password pegawai akan langsung diganti di database tanpa verifikasi email (solusi SMTP LAN terblokir).
                        </div>
                        <div>
                            <label style="font-size: 12px; font-weight: 600; color: #4b5563;">Pegawai yang di-reset:</label>
                            <div style="margin-top: 4px; padding: 8px 12px; background: #f3f4f6; border-radius: 6px; font-size: 13px;">
                                <strong id="resetTargetName">-</strong>
                                <div id="resetTargetEmail" style="color: #6b7280; font-size: 12px;">-</div>
                            </div>
                        </div>
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                <label for="inputPasswordBaru" style="font-size: 12px; font-weight: 600; color: #374151;">Password Baru:</label>
                                <button type="button" onclick="isiPasswordDefault()" style="background: none; border: none; color: #4361ee; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: underline; padding: 0;">
                                    Pakai Default (Bpsuml2026!)
                                </button>
                            </div>
                            <div style="position: relative;">
                                <input type="password" id="inputPasswordBaru" name="password" required minlength="8"
                                       placeholder="Minimal 8 karakter"
                                       style="width: 100%; box-sizing: border-box; padding: 10px 40px 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; font-family: monospace;">
                                <button type="button" onclick="toggleLihatResetPassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 16px; color: #6b7280;">
                                    👁️
                                </button>
                            </div>
                            <small style="color: #6b7280; font-size: 11px; margin-top: 4px; display: block;">
                                Sarankan pegawai untuk langsung mengganti password setelah berhasil login.
                            </small>
                        </div>
                    </div>
                    <div style="padding: 16px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; gap: 10px;">
                        <button type="button" onclick="tutupModalResetPassword()" class="btn btn-secondary" style="border-radius: 8px; padding: 8px 16px; font-size: 13px;">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger" style="border-radius: 8px; padding: 8px 18px; font-size: 13px; font-weight: 600; background: #dc2626; border-color: #dc2626;">
                            Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

<script>
    function bukaModalResetPassword(userUuid, userName, userEmail) {
        var modal = document.getElementById('modalResetPassword');
        var form = document.getElementById('formResetPassword');
        var nameSpan = document.getElementById('resetTargetName');
        var emailSpan = document.getElementById('resetTargetEmail');
        var passInput = document.getElementById('inputPasswordBaru');

        if (!modal || !form) return;

        form.action = "{{ url('Admin/Settings/Users') }}/" + userUuid + "/reset-password";
        nameSpan.textContent = userName;
        emailSpan.textContent = userEmail;
        passInput.value = 'Bpsuml2026!';
        passInput.type = 'text';

        modal.style.display = 'flex';
    }

    function tutupModalResetPassword() {
        var modal = document.getElementById('modalResetPassword');
        if (modal) modal.style.display = 'none';
    }

    function isiPasswordDefault() {
        var passInput = document.getElementById('inputPasswordBaru');
        if (passInput) passInput.value = 'Bpsuml2026!';
    }

    function toggleLihatResetPassword() {
        var passInput = document.getElementById('inputPasswordBaru');
        if (!passInput) return;
        passInput.type = passInput.type === 'password' ? 'text' : 'password';
    }

    function salinPasswordReset() {
        var el = document.getElementById('passwordResetBaru');
        if (!el) return;
        navigator.clipboard.writeText(el.textContent.trim()).then(function() {
            alert('Password berhasil disalin ke clipboard!');
        }).catch(function() {
            var temp = document.createElement('textarea');
            temp.value = el.textContent.trim();
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            alert('Password berhasil disalin ke clipboard!');
        });
    }

    window.onclick = function(event) {
        var modal = document.getElementById('modalResetPassword');
        if (event.target === modal) {
            tutupModalResetPassword();
        }
    };
</script>

<style>
    @media (max-width: 768px) {
        div[style*="display: flex"] {
            flex-direction: column !important;
            gap: 16px !important;
        }
        
        table { font-size: 11px; }
        thead th { padding: 8px 6px; }
        tbody td { padding: 8px 6px; }
    }

    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }

    @media (max-width: 1024px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection
