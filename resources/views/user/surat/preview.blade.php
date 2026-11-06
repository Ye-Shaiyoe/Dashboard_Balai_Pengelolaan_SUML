@extends('layouts.user')
@section('title', 'Preview Dokumen')

@section('content')
<div class="card card-custom">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <h5 class="fw-bold mb-1" style="color:var(--text-primary);">
                    <i class="bi bi-file-earmark-text me-2"></i>Preview Dokumen - {{ $surat->judul }}
                </h5>
                <p class="text-muted mb-0" style="font-size:13px;">
                    File: <strong>{{ $fileName ?? '—' }}</strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('user.surat.show', $surat) }}" class="btn btn-sm btn-light" style="border-radius:8px;border:1px solid var(--border-color);background:var(--bg-tertiary);color:var(--text-primary);">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="{{ route('user.surat.download', [$surat, $tipe]) }}" class="btn btn-sm btn-primary" style="border-radius:8px;">
                    <i class="bi bi-download me-1"></i> Unduh File
                </a>
            </div>
        </div>

        {{-- Word Document HTML Preview --}}
        @if(isset($htmlContent))
            <div style="width:100%; background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:12px; padding:24px; overflow:auto; max-height:calc(100vh - 280px); min-height:400px;">
                <div class="word-document-content" style="max-width:850px; margin:0 auto; background:#fff; padding:40px; border-radius:4px; box-shadow:0 2px 12px rgba(0,0,0,0.05); font-family: 'Calibri', 'Arial', sans-serif; font-size:11pt; line-height:1.5; color:#000;">
                    {!! $htmlContent !!}
                </div>
            </div>

            <style>
            .word-document-content h1 { font-size: 24pt; font-weight: bold; margin: 20px 0 12px 0; color: #1f2937; }
            .word-document-content h2 { font-size: 18pt; font-weight: bold; margin: 16px 0 10px 0; color: #374151; }
            .word-document-content h3 { font-size: 14pt; font-weight: bold; margin: 12px 0 8px 0; color: #4b5563; }
            .word-document-content p { margin: 8px 0; }
            .word-document-content table { margin: 16px 0; width: 100%; border-collapse: collapse; }
            .word-document-content table th { font-weight: bold; background-color: #f9fafb; }
            .word-document-content table td, .word-document-content table th { vertical-align: top; border: 1px solid #e5e7eb; padding: 8px; }
            .word-document-content img { max-width: 100%; height: auto; margin: 12px 0; border-radius: 4px; }
            .word-document-content ul, .word-document-content ol { margin: 8px 0; padding-left: 24px; }
            .word-document-content a { color: #2563eb; text-decoration: underline; }
            </style>

        @else
            <div style="padding:60px; text-align:center; color:var(--text-secondary);">
                <div style="font-size:48px; margin-bottom:16px;"><i class="bi bi-file-earmark-text text-secondary"></i></div>
                <div style="font-size:14px; margin-bottom:20px;">Preview tidak tersedia untuk format ini.</div>
                <a href="{{ route('user.surat.download', [$surat, $tipe]) }}" class="btn btn-primary" style="border-radius:8px;">
                    <i class="bi bi-download me-1"></i> Unduh File Sekarang
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
