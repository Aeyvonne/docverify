<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <title>Rapport de vérification — DocVerify</title>
  <style>
    @page { margin: 0; size: A4; }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: DejaVu Sans, Arial, sans-serif;
      background: #F2E9DE;
      color: #3A2E26;
      font-size: 11px;
      line-height: 1.5;
    }

    /* ── Header bande sombre ── */
    .header {
      background: #4A372C;
      padding: 32px 40px 28px;
      position: relative;
      overflow: hidden;
    }
    .header-bg-text {
      position: absolute;
      top: 50%;
      right: -10px;
      transform: translateY(-50%);
      font-size: 80px;
      font-weight: 900;
      color: rgba(251,247,240,0.05);
      letter-spacing: -2px;
      white-space: nowrap;
    }
    .header-brand {
      font-size: 26px;
      font-weight: 300;
      letter-spacing: 6px;
      color: #FBF7F0;
      text-transform: uppercase;
    }
    .header-subtitle {
      font-size: 9px;
      letter-spacing: 3px;
      color: rgba(251,247,240,0.5);
      text-transform: uppercase;
      margin-top: 4px;
    }
    .header-date {
      position: absolute;
      top: 32px;
      right: 40px;
      text-align: right;
      color: rgba(251,247,240,0.55);
      font-size: 9px;
      letter-spacing: 1px;
      text-transform: uppercase;
    }
    .header-date strong {
      display: block;
      font-size: 13px;
      color: rgba(251,247,240,0.85);
      font-weight: 400;
      letter-spacing: 0;
      margin-top: 2px;
    }

    /* ── Bande statut ── */
    .statut-band {
      padding: 18px 40px;
      display: table;
      width: 100%;
    }
    .statut-band-valide  { background: linear-gradient(135deg, #5a7a4e 0%, #7C9070 100%); }
    .statut-band-revoque { background: linear-gradient(135deg, #8c3520 0%, #B5533C 100%); }
    .statut-band-expire  { background: linear-gradient(135deg, #8a6010 0%, #C99A3C 100%); }
    .statut-band-default { background: linear-gradient(135deg, #5a4a3e 0%, #8C7A6B 100%); }

    .statut-band-inner { display: table-cell; vertical-align: middle; }
    .statut-label {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      letter-spacing: 2px;
      text-transform: uppercase;
    }
    .statut-icon {
      display: inline-block;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: rgba(255,255,255,0.25);
      text-align: center;
      line-height: 22px;
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      margin-right: 10px;
      vertical-align: middle;
    }
    .statut-sub {
      font-size: 9px;
      color: rgba(255,255,255,0.65);
      letter-spacing: 2px;
      text-transform: uppercase;
      margin-top: 3px;
    }
    .statut-qr-cell {
      display: table-cell;
      vertical-align: middle;
      text-align: right;
      width: 90px;
    }
    .statut-qr-cell img {
      width: 74px;
      height: 74px;
      border: 3px solid rgba(255,255,255,0.3);
      border-radius: 8px;
      display: block;
      margin-left: auto;
    }

    /* ── Corps principal ── */
    .body-wrap {
      padding: 28px 40px 32px;
    }

    /* ── Grille 2 colonnes ── */
    .grid-2 { display: table; width: 100%; border-collapse: separate; border-spacing: 0 0; }
    .col-left  { display: table-cell; width: 62%; vertical-align: top; padding-right: 14px; }
    .col-right { display: table-cell; width: 38%; vertical-align: top; padding-left: 14px; }

    /* ── Section card ── */
    .card {
      background: #FBF7F0;
      border: 1px solid #E8DCCB;
      border-radius: 10px;
      padding: 18px 20px;
      margin-bottom: 14px;
    }
    .card-accent-valide  { border-left: 4px solid #7C9070; }
    .card-accent-revoque { border-left: 4px solid #B5533C; }
    .card-accent-expire  { border-left: 4px solid #C99A3C; }
    .card-accent-brown   { border-left: 4px solid #6B4F3F; }

    .section-label {
      font-size: 7.5px;
      font-weight: 700;
      letter-spacing: 2.5px;
      text-transform: uppercase;
      color: #8C7A6B;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 1px solid #E8DCCB;
    }

    /* ── Ligne de métadonnée ── */
    .meta-row { margin-bottom: 9px; }
    .meta-key {
      font-size: 8px;
      color: #8C7A6B;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 2px;
    }
    .meta-val {
      font-size: 11.5px;
      color: #3A2E26;
      font-weight: 600;
    }
    .meta-val-light {
      font-size: 11px;
      color: #4A372C;
      font-weight: 400;
    }

    /* ── Badge pill ── */
    .pill {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 9px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
    }
    .pill-valide  { background: rgba(124,144,112,0.15); color: #4a6640; border: 1px solid rgba(124,144,112,0.35); }
    .pill-revoque { background: rgba(181,83,60,0.12);   color: #8c3520; border: 1px solid rgba(181,83,60,0.3);  }
    .pill-expire  { background: rgba(201,154,60,0.12);  color: #8a6010; border: 1px solid rgba(201,154,60,0.3); }
    .pill-certified { background: rgba(74,55,44,0.1); color: #4A372C; border: 1px solid rgba(74,55,44,0.2); }

    /* ── Hash mono ── */
    .hash-box {
      background: #F2E9DE;
      border: 1px solid #D9C6A8;
      border-radius: 7px;
      padding: 10px 13px;
    }
    .hash-label {
      font-size: 7.5px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #8C7A6B;
      margin-bottom: 5px;
    }
    .hash-val {
      font-family: DejaVu Sans Mono, Menlo, monospace;
      font-size: 7.5px;
      color: #4A372C;
      word-break: break-all;
      line-height: 1.6;
    }

    /* ── Token box ── */
    .token-box {
      background: #4A372C;
      border-radius: 7px;
      padding: 10px 13px;
    }
    .token-label {
      font-size: 7.5px;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: rgba(251,247,240,0.5);
      margin-bottom: 5px;
    }
    .token-val {
      font-family: DejaVu Sans Mono, Menlo, monospace;
      font-size: 8.5px;
      color: #FBF7F0;
      word-break: break-all;
      font-weight: 600;
    }

    /* ── Alerte révocation ── */
    .revoke-box {
      background: rgba(181,83,60,0.07);
      border: 1px solid rgba(181,83,60,0.25);
      border-radius: 7px;
      padding: 10px 13px;
      margin-top: 10px;
    }
    .revoke-title {
      font-size: 8px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #8c3520;
      margin-bottom: 4px;
    }
    .revoke-text { font-size: 10.5px; color: #3A2E26; }

    /* ── Émetteur avatar ── */
    .avatar {
      display: inline-block;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: #E8DCCB;
      text-align: center;
      line-height: 34px;
      font-size: 12px;
      font-weight: 700;
      color: #4A372C;
      vertical-align: middle;
      margin-right: 10px;
    }
    .emetteur-name {
      font-size: 12.5px;
      font-weight: 700;
      color: #3A2E26;
    }
    .emetteur-inst {
      font-size: 10px;
      color: #6B4F3F;
      margin-top: 1px;
    }

    /* ── Séparateur ── */
    .divider {
      height: 1px;
      background: linear-gradient(to right, #D9C6A8, transparent);
      margin: 12px 0;
    }

    /* ── Sceau / watermark ── */
    .seal-row {
      margin-top: 4px;
      text-align: center;
    }
    .seal-circle {
      display: inline-block;
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: #F2E9DE;
      border: 2px solid #D9C6A8;
      text-align: center;
      padding-top: 18px;
    }
    .seal-inner-text {
      font-size: 7px;
      font-weight: 700;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #8C7A6B;
    }
    .seal-brand {
      font-size: 14px;
      font-weight: 700;
      color: #4A372C;
      letter-spacing: 2px;
      display: block;
      margin: 4px 0;
    }
    .seal-year {
      font-size: 8px;
      color: #8C7A6B;
    }

    /* ── Ligne accent top ── */
    .accent-stripe {
      height: 4px;
      background: linear-gradient(to right, #7C9070, #C99A3C, #B5533C, #6B4F3F);
    }
  </style>
</head>
<body>

  <!-- Bande accent colorée -->
  <div class="accent-stripe"></div>

  <!-- ── HEADER ── -->
  <div class="header">
    <div class="header-bg-text">DOCVERIFY</div>
    <div class="header-brand">DocVerify</div>
    <div class="header-subtitle">Rapport de vérification officiel</div>
    <div class="header-date">
      Généré le
      <strong>
        @php
          $d = new DateTime($meta['rapport_genere_le']);
          echo $d->format('d/m/Y à H:i');
        @endphp
      </strong>
    </div>
  </div>

  <!-- ── BANDE STATUT ── -->
  @php
    $statut = $meta['statut'] ?? 'inconnu';
    $bandClass = match($statut) {
      'valide'  => 'statut-band-valide',
      'revoque' => 'statut-band-revoque',
      'expire'  => 'statut-band-expire',
      default   => 'statut-band-default',
    };
    $statutLabel = match($statut) {
      'valide'  => 'Document authentique et valide',
      'revoque' => 'Document révoqué',
      'expire'  => 'Document expiré',
      default   => 'Statut inconnu',
    };
    $statutIcon = match($statut) {
      'valide'  => '✓',
      'revoque' => '✕',
      'expire'  => '⚠',
      default   => '?',
    };
    $accentClass = match($statut) {
      'valide'  => 'card-accent-valide',
      'revoque' => 'card-accent-revoque',
      'expire'  => 'card-accent-expire',
      default   => 'card-accent-brown',
    };
    $pillClass = match($statut) {
      'valide'  => 'pill-valide',
      'revoque' => 'pill-revoque',
      'expire'  => 'pill-expire',
      default   => 'pill-certified',
    };
    $initiales = strtoupper(
      substr($meta['emetteur'] ?? 'D', 0, 1) .
      (strpos($meta['emetteur'], ' ') !== false
        ? substr($meta['emetteur'], strpos($meta['emetteur'], ' ') + 1, 1)
        : '')
    );
  @endphp

  <div class="statut-band {{ $bandClass }}">
    <div class="statut-band-inner">
      <div class="statut-label">
        <span class="statut-icon">{{ $statutIcon }}</span>
        {{ $statutLabel }}
      </div>
      <div class="statut-sub">Vérification effectuée · {{ $meta['nb_verifications'] ?? 0 }} scan(s) enregistré(s)</div>
    </div>
    <div class="statut-qr-cell">
      <img src="{{ $qrImage }}" alt="QR Code de vérification" />
    </div>
  </div>

  <!-- ── CORPS ── -->
  <div class="body-wrap">
    <div class="grid-2">

      <!-- Colonne gauche -->
      <div class="col-left">

        <!-- Card document -->
        <div class="card {{ $accentClass }}">
          <div class="section-label">Informations du document</div>

          <div class="meta-row">
            <div class="meta-key">Titre</div>
            <div class="meta-val">{{ $meta['titre'] ?? '—' }}</div>
          </div>

          <div class="divider"></div>

          <div style="display:table; width:100%;">
            <div style="display:table-cell; width:50%; vertical-align:top; padding-right:10px;">
              <div class="meta-row">
                <div class="meta-key">Type de document</div>
                <div class="meta-val-light" style="text-transform:capitalize;">{{ $meta['type'] ?? '—' }}</div>
              </div>
              <div class="meta-row">
                <div class="meta-key">Date d'émission</div>
                <div class="meta-val-light">{{ $meta['date_emission'] ?? '—' }}</div>
              </div>
            </div>
            <div style="display:table-cell; width:50%; vertical-align:top;">
              <div class="meta-row">
                <div class="meta-key">Statut actuel</div>
                <div><span class="pill {{ $pillClass }}">{{ strtoupper($statut) }}</span></div>
              </div>
              <div class="meta-row">
                <div class="meta-key">Date d'expiration</div>
                <div class="meta-val-light">{{ $meta['date_expiration'] ?? 'Sans expiration' }}</div>
              </div>
            </div>
          </div>

          @if($statut === 'revoque' && !empty($meta['motif_revocation']))
          <div class="revoke-box">
            <div class="revoke-title">⚠ Motif de révocation</div>
            <div class="revoke-text">{{ $meta['motif_revocation'] }}</div>
            @if(!empty($meta['revoked_at']))
            <div style="font-size:8.5px; color:#8C7A6B; margin-top:5px;">
              @php
                $r = new DateTime($meta['revoked_at']);
                echo 'Révoqué le ' . $r->format('d/m/Y à H:i');
              @endphp
            </div>
            @endif
          </div>
          @endif
        </div>

        <!-- Card émetteur -->
        <div class="card card-accent-brown">
          <div class="section-label">Émetteur du document</div>
          <div style="display:table; width:100%;">
            <div style="display:table-cell; vertical-align:middle; width:44px;">
              <span class="avatar">{{ $initiales }}</span>
            </div>
            <div style="display:table-cell; vertical-align:middle;">
              <div class="emetteur-name">{{ $meta['emetteur'] ?? 'Inconnu' }}</div>
              <div class="emetteur-inst">{{ $meta['institution'] ?? '—' }}</div>
            </div>
            <div style="display:table-cell; vertical-align:middle; text-align:right;">
              <span class="pill pill-certified">✓ Certifié</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Colonne droite -->
      <div class="col-right">

        <!-- QR grand -->
        <div class="card" style="text-align:center; padding:20px 16px;">
          <div class="section-label" style="text-align:left;">QR Code de vérification</div>
          <img src="{{ $qrImage }}" alt="QR Code"
               style="width:120px; height:120px; margin:6px auto 10px; display:block;
                      border:2px solid #E8DCCB; border-radius:8px;" />
          <div style="font-size:8px; color:#8C7A6B; letter-spacing:0.5px; line-height:1.6;">
            Scannez ce QR Code<br>pour vérifier ce document<br>en temps réel
          </div>
        </div>

        <!-- Sceau DocVerify -->
        <div class="seal-row">
          <div class="seal-circle">
            <div class="seal-inner-text">Certifié par</div>
            <span class="seal-brand">DocVerify</span>
            <div class="seal-year">{{ date('Y') }}</div>
          </div>
          <div style="font-size:7.5px; color:#8C7A6B; margin-top:8px; letter-spacing:1px; text-align:center;">
            Document vérifié authentiquement<br>via plateforme cryptographique
          </div>
        </div>

        <!-- Stats scans -->
        <div class="card" style="margin-top:14px; text-align:center; padding:14px;">
          <div class="section-label" style="text-align:left;">Statistiques</div>
          <div style="font-size:28px; font-weight:900; color:#4A372C; line-height:1;">
            {{ $meta['nb_verifications'] ?? 0 }}
          </div>
          <div style="font-size:8px; color:#8C7A6B; text-transform:uppercase; letter-spacing:1.5px; margin-top:3px;">
            Vérification(s) effectuée(s)
          </div>
        </div>

      </div>
    </div>

    <!-- Note légale -->
    <div style="margin-top:6px; padding-top:12px; border-top:1px solid #E8DCCB;">
      <div style="font-size:8px; color:#8C7A6B; line-height:1.7; text-align:center;">
        Ce rapport a été généré automatiquement par la plateforme DocVerify. L'authenticité du document
        peut être vérifiée à tout moment en scannant le QR Code ci-dessus.
        L'authenticité du document peut être vérifiée à tout moment en scannant le QR Code ci-dessus.
      </div>
    </div>
  </div>



</body>
</html>
