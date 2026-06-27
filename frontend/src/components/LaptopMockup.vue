<template>
  <!--
    Mockup laptop entièrement en HTML/CSS.
    Aucune image externe. L'écran simule l'interface DocVerify (VerifyView).
  -->
  <div class="laptop-wrap">

    <!-- ══ ÉCRAN ══ -->
    <div class="laptop-screen">

      <!-- Biseau / bezel -->
      <div class="laptop-bezel">

        <!-- Barre de navigation simulée -->
        <div class="sim-navbar">
          <!-- Dots macOS -->
          <div class="sim-dots">
            <span class="dot dot-red"></span>
            <span class="dot dot-yellow"></span>
            <span class="dot dot-green"></span>
          </div>
          <!-- URL bar -->
          <div class="sim-urlbar">
            <span class="sim-lock">
              <svg width="8" height="9" viewBox="0 0 8 9" fill="none">
                <rect x="1" y="4" width="6" height="5" rx="1" fill="#7C9070"/>
                <path d="M2 4V2.5a2 2 0 1 1 4 0V4" stroke="#7C9070" stroke-width="1.2" fill="none"/>
              </svg>
            </span>
            <span class="sim-url">docverify.app/verify/d4f9…</span>
          </div>
        </div>

        <!-- Contenu de l'écran — simulation VerifyView -->
        <div class="sim-content">

          <!-- Fond beige + filigrane micro -->
          <div class="sim-bg">
            <span class="sim-watermark" aria-hidden="true">AUTHENTIQUE</span>
          </div>

          <!-- Orbs -->
          <div class="sim-orb sim-orb-1"></div>
          <div class="sim-orb sim-orb-2"></div>

          <!-- Contenu principal -->
          <div class="sim-body">

            <!-- Logo -->
            <div class="sim-logo">DocVerify</div>

            <!-- Status banner vert -->
            <div class="sim-status-banner">
              <div class="sim-status-icon">✓</div>
              <div>
                <div class="sim-status-label">Document authentique et valide</div>
                <div class="sim-status-sub">Vérifié le 24 juin 2025 à 14h32</div>
              </div>
              <div class="sim-status-scans">
                <span class="sim-scans-num">12</span>
                <span class="sim-scans-txt">scans</span>
              </div>
            </div>

            <!-- Card document -->
            <div class="sim-card">
              <div class="sim-card-accent"></div>
              <div class="sim-card-body">
                <div class="sim-eyebrow">Document</div>
                <div class="sim-doc-title">Diplôme de Master en Informatique</div>
                <div class="sim-doc-type">Diplôme · Université de Dakar</div>
                <div class="sim-meta-grid">
                  <div class="sim-meta-item">
                    <div class="sim-meta-key">Date d'émission</div>
                    <div class="sim-meta-val">15 juin 2025</div>
                  </div>
                  <div class="sim-meta-item">
                    <div class="sim-meta-key">Scans</div>
                    <div class="sim-meta-val sim-meta-big">12</div>
                  </div>
                </div>
                <!-- Bouton -->
                <div class="sim-btn">
                  <span>↓</span> Télécharger le rapport PDF
                </div>
              </div>
            </div>

            <!-- Card émetteur -->
            <div class="sim-card sim-card-emetteur">
              <div class="sim-card-accent sim-card-accent-brown"></div>
              <div class="sim-card-body">
                <div class="sim-eyebrow">Émetteur</div>
                <div class="sim-emetteur-row">
                  <div class="sim-avatar">UA</div>
                  <div>
                    <div class="sim-emetteur-name">Université de Dakar</div>
                    <div class="sim-emetteur-inst">Établissement d'enseignement supérieur</div>
                  </div>
                  <div class="sim-certified-pill">✓ Certifié</div>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>

    <!-- ══ CHARNIÈRE ══ -->
    <div class="laptop-hinge"></div>

    <!-- ══ BASE / CLAVIER ══ -->
    <div class="laptop-base">
      <!-- Clavier simulé -->
      <div class="laptop-keyboard">
        <div v-for="row in keyboardRows" :key="row.id" class="kb-row">
          <div v-for="k in row.keys" :key="k"
               class="kb-key"
               :style="k > 1 ? `flex:${k}` : ''">
          </div>
        </div>
      </div>
      <!-- Trackpad -->
      <div class="laptop-trackpad"></div>
    </div>

    <!-- ══ OMBRE SOL ══ -->
    <div class="laptop-shadow"></div>

  </div>
</template>

<script setup>
const keyboardRows = [
  { id: 1, keys: [1,1,1,1,1,1,1,1,1,1,1,1,1] },
  { id: 2, keys: [1.4,1,1,1,1,1,1,1,1,1,1,1,1.2] },
  { id: 3, keys: [1.6,1,1,1,1,1,1,1,1,1,1,1.6] },
  { id: 4, keys: [2,1,1,1,1,1,1,1,1,1,2] },
  { id: 5, keys: [1.2,1,6,1,1.2] },
]
</script>

<style scoped>
/* ══ WRAP ══ */
.laptop-wrap {
  position: relative;
  width: 100%;
  max-width: 580px;
  margin: 0 auto;
  perspective: 1200px;
  animation: laptopFloat 5s ease-in-out infinite;
}
@keyframes laptopFloat {
  0%, 100% { transform: translateY(0px) rotateX(2deg); }
  50%       { transform: translateY(-12px) rotateX(2deg); }
}

/* ══ ÉCRAN ══ */
.laptop-screen {
  width: 100%;
  background: #2A1F18;
  border-radius: 14px 14px 0 0;
  padding: 10px 10px 0;
  box-shadow:
    0 0 0 1px #1a110a,
    0 -2px 0 2px #3a2a20,
    0 20px 60px rgba(0,0,0,0.4),
    0 40px 80px rgba(0,0,0,0.2);
}

.laptop-bezel {
  background: #0f0a07;
  border-radius: 8px 8px 0 0;
  overflow: hidden;
  width: 100%;
}

/* ══ NAVBAR SIMULÉE ══ */
.sim-navbar {
  background: rgba(242,233,222,0.96);
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 5px 10px;
  border-bottom: 1px solid #E8DCCB;
}
.sim-dots { display: flex; gap: 4px; align-items: center; flex-shrink: 0; }
.dot {
  width: 7px; height: 7px; border-radius: 50%;
}
.dot-red    { background: #ff5f56; }
.dot-yellow { background: #ffbd2e; }
.dot-green  { background: #27c93f; }

.sim-urlbar {
  flex: 1;
  background: rgba(217,198,168,0.3);
  border: 1px solid rgba(217,198,168,0.6);
  border-radius: 4px;
  padding: 2px 7px;
  display: flex;
  align-items: center;
  gap: 4px;
}
.sim-lock { display: flex; align-items: center; }
.sim-url {
  font-size: 8px;
  color: #6B4F3F;
  font-family: monospace;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ══ CONTENU ÉCRAN ══ */
.sim-content {
  position: relative;
  background: #F2E9DE;
  height: 320px;
  overflow: hidden;
}
.sim-bg { position: absolute; inset: 0; }
.sim-watermark {
  position: absolute;
  font-family: 'Poppins', sans-serif;
  font-weight: 900;
  font-size: 52px;
  color: #7C9070;
  opacity: 0.07;
  white-space: nowrap;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  pointer-events: none;
  user-select: none;
  letter-spacing: -1px;
}

/* Orbs */
.sim-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(30px);
  pointer-events: none;
}
.sim-orb-1 {
  width: 200px; height: 200px;
  top: -60px; right: -60px;
  background: radial-gradient(circle, rgba(124,144,112,0.2) 0%, transparent 70%);
}
.sim-orb-2 {
  width: 150px; height: 150px;
  bottom: 20px; left: -40px;
  background: radial-gradient(circle, rgba(201,154,60,0.15) 0%, transparent 70%);
}

/* Corps */
.sim-body {
  position: relative;
  z-index: 1;
  padding: 10px 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

/* Logo */
.sim-logo {
  text-align: center;
  font-family: 'Cormorant', Georgia, serif;
  font-size: 13px;
  font-weight: 300;
  letter-spacing: 4px;
  color: #4A372C;
  margin-bottom: 2px;
}

/* Status banner */
.sim-status-banner {
  display: flex;
  align-items: center;
  gap: 7px;
  padding: 8px 10px;
  border-radius: 8px;
  background: linear-gradient(135deg, #5a7a4e, #7C9070);
  box-shadow: 0 4px 14px rgba(124,144,112,0.35);
}
.sim-status-icon {
  width: 20px; height: 20px; border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.sim-status-label {
  font-size: 8px; font-weight: 700; color: #fff;
  letter-spacing: 0.3px;
}
.sim-status-sub { font-size: 6px; color: rgba(255,255,255,0.65); margin-top: 1px; }
.sim-status-scans { margin-left: auto; text-align: center; flex-shrink: 0; }
.sim-scans-num { display: block; font-size: 14px; font-weight: 800; color: #fff; line-height: 1; }
.sim-scans-txt { font-size: 5.5px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 0.5px; }

/* Cards */
.sim-card {
  background: rgba(251,247,240,0.92);
  border: 1px solid rgba(217,198,168,0.5);
  border-radius: 9px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(74,55,44,0.06);
}
.sim-card-accent {
  height: 3px;
  background: linear-gradient(135deg, #5a7a4e, #7C9070);
}
.sim-card-accent-brown {
  background: linear-gradient(135deg, #4A372C, #6B4F3F);
}
.sim-card-body { padding: 7px 9px; }

.sim-eyebrow {
  font-size: 6px; font-weight: 700; letter-spacing: 1.5px;
  text-transform: uppercase; color: #8C7A6B; margin-bottom: 4px;
}
.sim-doc-title {
  font-family: 'Cormorant', Georgia, serif;
  font-size: 11px; font-weight: 600; color: #3A2E26;
  line-height: 1.2; margin-bottom: 1px;
}
.sim-doc-type { font-size: 7px; color: #8C7A6B; margin-bottom: 6px; }

.sim-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 8px; margin-bottom: 6px; }
.sim-meta-key { font-size: 6px; color: #8C7A6B; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 1px; }
.sim-meta-val { font-size: 8px; font-weight: 600; color: #3A2E26; }
.sim-meta-big { font-size: 13px !important; font-weight: 700 !important; color: #4A372C !important; }

.sim-btn {
  background: linear-gradient(135deg, #4A372C, #6B4F3F);
  color: #FBF7F0;
  font-size: 7px; font-weight: 500;
  padding: 5px 8px; border-radius: 5px;
  display: flex; align-items: center; gap: 4px;
  width: fit-content;
}

/* Émetteur */
.sim-emetteur-row {
  display: flex; align-items: center; gap: 6px;
}
.sim-avatar {
  width: 22px; height: 22px; border-radius: 50%;
  background: linear-gradient(135deg, #5a7a4e, #7C9070);
  display: flex; align-items: center; justify-content: center;
  font-size: 7px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.sim-emetteur-name { font-size: 8px; font-weight: 600; color: #3A2E26; }
.sim-emetteur-inst { font-size: 6px; color: #8C7A6B; }
.sim-certified-pill {
  margin-left: auto; flex-shrink: 0;
  font-size: 6px; font-weight: 600; color: #4a6640;
  background: rgba(124,144,112,0.12);
  border: 1px solid rgba(124,144,112,0.3);
  padding: 2px 6px; border-radius: 10px;
}

/* ══ CHARNIÈRE ══ */
.laptop-hinge {
  width: 100%;
  height: 6px;
  background: linear-gradient(to bottom, #1a110a, #2e1f14, #1a110a);
  box-shadow: 0 2px 8px rgba(0,0,0,0.5);
  position: relative;
  z-index: 2;
}

/* ══ BASE ══ */
.laptop-base {
  width: 100%;
  background: linear-gradient(to bottom, #2e2015, #3a2a1c);
  border-radius: 0 0 12px 12px;
  padding: 10px 16px 14px;
  box-shadow:
    0 4px 0 #1a110a,
    0 8px 24px rgba(0,0,0,0.3);
  position: relative;
}

/* Clavier */
.laptop-keyboard {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin-bottom: 8px;
}
.kb-row { display: flex; gap: 2.5px; }
.kb-key {
  flex: 1;
  height: 8px;
  background: linear-gradient(to bottom, #4a3525, #3a2818);
  border-radius: 2px;
  border-bottom: 1px solid #1a110a;
  box-shadow: 0 1px 0 rgba(255,255,255,0.06);
}

/* Trackpad */
.laptop-trackpad {
  width: 38%;
  height: 28px;
  background: linear-gradient(135deg, #3a2818, #2e1f14);
  border-radius: 6px;
  border: 1px solid #1a110a;
  margin: 0 auto;
  box-shadow: inset 0 1px 3px rgba(0,0,0,0.4);
}

/* ══ OMBRE SOL ══ */
.laptop-shadow {
  width: 70%;
  height: 18px;
  background: radial-gradient(ellipse, rgba(0,0,0,0.25) 0%, transparent 70%);
  margin: 0 auto;
  border-radius: 50%;
}
</style>
