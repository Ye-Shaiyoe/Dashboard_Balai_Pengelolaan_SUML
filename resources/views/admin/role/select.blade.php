<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Role Admin — Surat Metrologi</title>
    <link rel="icon" href="{{ asset('images/metrologi.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a8e 100%);
            font-family: 'Figtree', sans-serif;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 900px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .header img {
            max-width: 80px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        .header p {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 768px) {
            .roles-grid { grid-template-columns: 1fr; }
        }
        .role-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .role-card:hover {
            border-color: #1e3a5f;
            box-shadow: 0 4px 16px rgba(30, 58, 95, 0.15);
            transform: translateY(-2px);
        }
        .role-card.selected {
            border-color: #1e3a5f;
            background: #f0f5ff;
        }
        .role-card input[type="radio"] {
            display: none;
        }
        .role-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .role-name {
            font-size: 16px;
            font-weight: 600;
            color: #1e3a5f;
            margin-bottom: 8px;
        }
        .role-desc {
            font-size: 12px;
            color: #6b7280;
            line-height: 1.5;
        }
        .role-tahap {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #15803d;
            font-weight: 500;
        }
        .btn-submit {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
            padding: 14px 24px;
            background: #1e3a5f;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover {
            background: #16304f;
        }
        .btn-submit:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .form-actions {
            text-align: center;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        .alert-info {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/metrologi.png') }}" alt="Logo">
            <h1>Pilih Role Admin</h1>
            <p>
                Selamat datang, <strong>{{ Auth::user()->name }}</strong>!<br>
                Silakan pilih role admin Anda. Role ini menentukan tahap surat yang bisa Anda proses.
            </p>
        </div>

        <div class="alert alert-info">
            <i class="bi bi-info-circle-fill" style="margin-right: 6px;"></i>Role yang sudah dipilih <strong>tidak dapat diubah sendiri</strong>. Hubungi administrator jika perlu mengubah role.
        </div>

        <form action="{{ route('admin.role.store') }}" method="POST" id="role-form">
            @csrf

            <div class="roles-grid">
                {{-- Admin Aspirasi --}}
                <label class="role-card" id="card-admin_aspirasi">
                    <input type="radio" name="role" value="admin_aspirasi" required>
                    <div class="role-icon"><i class="bi bi-folder2-open" style="color: #1e3a5f;"></i></div>
                    <div class="role-name">Arsiparis</div>
                    <div class="role-desc">
                        Bertanggung jawab verifikasi awal dan pengarsipan surat
                    </div>
                    <div class="role-tahap">
                        Tahap: 2 (Verifikasi Arsiparis) & 5-10
                    </div>
                </label>

                {{-- Admin Kasubbag TU --}}
                <label class="role-card" id="card-admin_kasubbag_tu">
                    <input type="radio" name="role" value="admin_kasubbag_tu" required>
                    <div class="role-icon"><i class="bi bi-clipboard-check" style="color: #1e3a5f;"></i></div>
                    <div class="role-name">Kasubbag TU</div>
                    <div class="role-desc">
                        Verifikasi surat sebagai Kasubbag Tata Usaha
                    </div>
                    <div class="role-tahap">
                        Tahap: 3 (Verifikasi Kasubbag TU)
                    </div>
                </label>

                {{-- Admin Kepala Balai --}}
                <label class="role-card" id="card-admin_kepala_balai">
                    <input type="radio" name="role" value="admin_kepala_balai" required>
                    <div class="role-icon"><i class="bi bi-building" style="color: #1e3a5f;"></i></div>
                    <div class="role-name">Kepala Balai</div>
                    <div class="role-desc">
                        Persetujuan akhir sebagai Kepala Balai
                    </div>
                    <div class="role-tahap">
                        Tahap: 4 (Persetujuan Kepala Balai)
                    </div>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-submit" id="submit-btn" disabled>
                    Simpan Role
                </button>
            </div>
        </form>
    </div>

    <script>
        // Handle role card selection
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.role-card');
            const submitBtn = document.getElementById('submit-btn');

            cards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    cards.forEach(c => c.classList.remove('selected'));

                    // Add selected class to clicked card
                    this.classList.add('selected');

                    // Check the radio
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;

                    // Enable submit button
                    submitBtn.disabled = false;
                });
            });
        });
    </script>
</body>
</html>
