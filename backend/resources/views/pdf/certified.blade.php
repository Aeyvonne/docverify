<!doctype html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Document certifié</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; }
        .box { border: 1px solid #ddd; padding: 16px; margin-top: 24px; }
        .row { display: flex; gap: 18px; }
        .meta { flex: 1; }
        .qr { width: 280px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .small { font-size: 12px; color: #444; }
        .mono { font-family: DejaVu Sans Mono, Menlo, Consolas, monospace; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Document certifié</h1>
    <div class="small">Site: {{ $siteUrl }}</div>

    <div class="box">
        <div class="row">
            <div class="meta">
                <div><strong>Titre:</strong> {{ $meta['titre'] ?? '' }}</div>
                <div><strong>Type:</strong> {{ $meta['type'] ?? '' }}</div>
                <div><strong>Hash SHA-256:</strong> <span class="mono">{{ $meta['hash_sha256'] ?? '' }}</span></div>
                <div><strong>QR token:</strong> <span class="mono">{{ $meta['qr_token'] ?? '' }}</span></div>
                <div><strong>Date émission:</strong> {{ $meta['date_emission'] ?? '' }}</div>
                <div><strong>Date expiration:</strong> {{ $meta['date_expiration'] ?? '-' }}</div>
                <div><strong>Statut:</strong> {{ $meta['statut'] ?? '' }}</div>
            </div>
            <div class="qr">
                {!! $qrSvg !!}
            </div>
        </div>
    </div>
</body>
</html>

