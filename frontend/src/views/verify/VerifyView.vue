<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/api/axios'
import BadgeStatut from '@/components/BadgeStatut.vue'

const route = useRoute()
const token = route.params.token

const data    = ref(null)
const loading = ref(true)
const error   = ref(null)

const showBadge    = ref(false)
const showDocument = ref(false)
const showEmetteur = ref(false)
const showViewer   = ref(false)

onMounted(async () => {
  try {
    const res  = await api.get(`/verify/${token}`)
    data.value = res.data
    setTimeout(() => { showBadge.value    = true }, 150)
    setTimeout(() => { showDocument.value = true }, 650)
    setTimeout(() => { showEmetteur.value = true }, 950)
  } catch (e) {
    error.value = e.response?.data?.message ?? 'QR Code invalide ou expiré.'
  } finally {
    loading.value = false
  }
})

const statusConfig = computed(() => {
  if (!data.value) return {}
  return {
    valide:  { gradient: 'linear-gradient(135deg,#5a7a4e 0%,#7C9070 100%)', icon: '✓', glow: 'rgba(124,144,112,0.35)', bg: 'rgba(124,144,112,0.08)', border: 'rgba(124,144,112,0.3)', text: '#4a6640' },
    revoque: { gradient: 'linear-gradient(135deg,#8c3520 0%,#B5533C 100%)', icon: '✕', glow: 'rgba(181,83,60,0.35)',  bg: 'rgba(181,83,60,0.08)',  border: 'rgba(181,83,60,0.3)',  text: '#8c3520' },
    expire:  { gradient: 'linear-gradient(135deg,#8a6010 0%,#C99A3C 100%)', icon: '⚠', glow: 'rgba(201,154,60,0.35)', bg: 'rgba(201,154,60,0.08)', border: 'rgba(201,154,60,0.3)', text: '#8a6010' },
  }[data.value.statut] ?? { gradient: 'linear-gradient(135deg,#5a4a3e 0%,#8C7A6B 100%)', icon: '?', glow: 'rgba(140,122,107,0.3)', bg: 'rgba(140,122,107,0.08)', border: 'rgba(140,122,107,0.3)', text: '#5a4a3e' }
})

const watermarkWord = computed(() => {
  if (!data.value) return 'DOCVERIFY'
  return { valide: 'AUTHENTIQUE', revoque: 'RÉVOQUÉ', expire: 'EXPIRÉ' }[data.value.statut] ?? 'DOCVERIFY'
})

function formatDate(d, withTime = false) {
  if (!d) return '—'
  const opts = { day: '2-digit', month: 'long', year: 'numeric' }
  if (withTime) Object.assign(opts, { hour: '2-digit', minute: '2-digit' })
  return new Intl.DateTimeFormat('fr-FR', opts).format(new Date(d))
}
</script>

<template>
  <div class="relative min-h-screen overflow-hidden" style="background:#F2E9DE;">

    <!-- Filigrane géant -->
    <span class="watermark-text" :style="{ color: statusConfig.text ?? '#8C7A6B' }" aria-hidden="true">
      {{ watermarkWord }}
    </span>

    <!-- Orbs décoratifs arrière-plan -->
    <div class="orb orb-1" aria-hidden="true"></div>
    <div class="orb orb-2" aria-hidden="true"></div>

    <div class="relative z-10 max-w-lg mx-auto px-4 py-14 md:py-20">

      <!-- Logo -->
      <div class="text-center mb-10">
        <span style="font-family:'Cormorant',Georgia,serif; font-size:1.9rem;
                     font-weight:300; letter-spacing:0.18em; color:#4A372C;">
          DocVerify
        </span>
        <p style="font-size:0.65rem; letter-spacing:0.25em; color:#8C7A6B; text-transform:uppercase; margin-top:2px;">
          Vérification de document
        </p>
      </div>

      <!-- ══ Chargement ══ -->
      <div v-if="loading" class="flex flex-col items-center justify-center min-h-[50vh] gap-5">
        <div class="spinner"></div>
        <p style="font-size:0.8rem; color:#8C7A6B; letter-spacing:0.1em;">Vérification en cours…</p>
      </div>

      <!-- ══ Erreur ══ -->
      <Transition name="reveal">
        <div v-if="!loading && error" class="card-v p-10 text-center">
          <div class="mx-auto mb-5 flex items-center justify-center rounded-full"
               style="width:64px;height:64px;background:rgba(181,83,60,0.1);">
            <span style="color:#B5533C;font-size:1.75rem;font-weight:700;">✕</span>
          </div>
          <h1 style="font-family:'Poppins',sans-serif;font-weight:600;font-size:1.2rem;color:#4A372C;margin-bottom:0.5rem;">
            Document introuvable
          </h1>
          <p style="font-size:0.875rem;color:#8C7A6B;line-height:1.6;">{{ error }}</p>
        </div>
      </Transition>

      <!-- ══ Résultat ══ -->
      <template v-if="!loading && data">

        <!-- 1. BADGE HÉRO avec bande de statut colorée -->
        <Transition name="badge-pop">
          <div v-if="showBadge" class="badge-hero-wrap mb-6">

            <!-- Bande gradient statut -->
            <div class="status-banner" :style="{ background: statusConfig.gradient }">
              <div class="status-banner-icon">{{ statusConfig.icon }}</div>
              <div>
                <div class="status-banner-label">{{ data.statut_label }}</div>
                <div class="status-banner-sub">Vérifié le {{ formatDate(data.verifie_le, true) }}</div>
              </div>
              <div class="status-banner-scans">
                <span class="status-scans-num">{{ data.total_verifications }}</span>
                <span class="status-scans-txt">scan{{ data.total_verifications > 1 ? 's' : '' }}</span>
              </div>
            </div>
          </div>
        </Transition>

        <!-- 2. DOCUMENT -->
        <Transition name="slide-up">
          <div v-if="showDocument" class="card-v mb-4 overflow-hidden">

            <!-- Top accent coloré -->
            <div class="card-top-accent" :style="{ background: statusConfig.gradient }"></div>

            <div class="p-6 md:p-7">
              <p class="section-eyebrow">Document</p>

              <h1 style="font-family:'Cormorant',Georgia,serif; font-size:1.7rem; font-weight:600;
                         color:#3A2E26; line-height:1.15; margin-bottom:0.25rem;">
                {{ data.document.titre }}
              </h1>
              <p style="font-size:0.8rem; color:#8C7A6B; text-transform:capitalize; margin-bottom:1.25rem;">
                {{ data.document.type }}
              </p>

              <!-- Grille méta -->
              <div class="meta-grid">
                <div class="meta-item">
                  <dt class="meta-key">Date d'émission</dt>
                  <dd class="meta-val">{{ formatDate(data.document.date_emission) }}</dd>
                </div>
                <div v-if="data.document.date_expiration" class="meta-item">
                  <dt class="meta-key">Expiration</dt>
                  <dd class="meta-val">{{ formatDate(data.document.date_expiration) }}</dd>
                </div>
                <div class="meta-item">
                  <dt class="meta-key">Nombre de scans</dt>
                  <dd class="meta-val" style="font-size:1.4rem; font-weight:700; color:#4A372C;">
                    {{ data.total_verifications }}
                  </dd>
                </div>
                <div class="meta-item">
                  <dt class="meta-key">Statut</dt>
                  <dd>
                    <span class="status-pill"
                          :style="{ background: statusConfig.bg, color: statusConfig.text, border: `1px solid ${statusConfig.border}` }">
                      {{ data.statut_label }}
                    </span>
                  </dd>
                </div>
              </div>

              <!-- Révocation -->
              <Transition name="reveal">
                <div v-if="data.statut === 'revoque' && data.document.motif_revocation"
                     class="revoke-box mt-5">
                  <p class="revoke-title">Motif de révocation</p>
                  <p style="font-size:0.875rem; color:#3A2E26; line-height:1.6;">
                    {{ data.document.motif_revocation }}
                  </p>
                  <p style="font-size:0.75rem; color:#8C7A6B; margin-top:0.5rem;">
                    Révoqué le {{ formatDate(data.document.revoked_at, true) }}
                  </p>
                </div>
              </Transition>

              <!-- Actions -->
              <div class="mt-6 flex flex-col gap-3">
                <a :href="data.rapport_url" target="_blank" class="btn-primary-v flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                  </svg>
                  Télécharger le rapport PDF
                </a>

                <button v-if="data.pdf_original_url"
                        @click="showViewer = !showViewer"
                        class="btn-secondary-v flex items-center justify-center gap-2">
                  <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                  </svg>
                  {{ showViewer ? 'Masquer le document' : 'Voir le document original' }}
                </button>
              </div>

              <!-- Visionneuse -->
              <Transition name="reveal">
                <div v-if="showViewer && data.pdf_original_url" class="mt-4">
                  <div class="relative rounded-xl overflow-hidden" style="height:500px; border:1px solid #D9C6A8;">
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none z-10">
                      <p style="font-family:'Poppins',sans-serif; font-weight:900; font-size:clamp(20px,5vw,40px);
                                opacity:0.1; color:#4A372C; transform:rotate(-25deg); white-space:nowrap;">
                        VÉRIFIÉ PAR DOCVERIFY
                      </p>
                    </div>
                    <iframe :src="data.pdf_original_url + '#toolbar=0&navpanes=0'"
                            class="w-full h-full border-0" title="Document original"></iframe>
                  </div>
                </div>
              </Transition>
            </div>
          </div>
        </Transition>

        <!-- 3. ÉMETTEUR -->
        <Transition name="slide-up">
          <div v-if="showEmetteur" class="card-v mb-4 overflow-hidden">
            <div class="card-top-accent" style="background:linear-gradient(135deg,#4A372C,#6B4F3F);"></div>
            <div class="p-6">
              <p class="section-eyebrow">Émetteur</p>

              <div class="flex items-center gap-4">
                <!-- Avatar -->
                <div class="avatar-circle" :style="{ background: statusConfig.gradient }">
                  {{ (data.emetteur.prenom?.[0] ?? '') + (data.emetteur.nom?.[0] ?? '') }}
                </div>

                <div class="flex-1 min-w-0">
                  <p style="font-family:'Poppins',sans-serif; font-weight:600; color:#3A2E26; font-size:1rem;">
                    {{ data.emetteur.nom_complet }}
                  </p>
                  <p style="font-size:0.8rem; color:#6B4F3F; margin-top:2px;">
                    {{ data.emetteur.nom_institution ?? '—' }}
                  </p>
                  <p v-if="data.emetteur.type_institution"
                     style="font-size:0.7rem; color:#8C7A6B; text-transform:capitalize; margin-top:1px;">
                    {{ data.emetteur.type_institution }}
                  </p>
                </div>

                <span v-if="data.emetteur.est_certifie && data.emetteur.type_institution !== 'particulier'" class="certified-badge">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
                  </svg>
                  Certifié
                </span>
              </div>
            </div>
          </div>
        </Transition>

</template>
    </div>
  </div>
</template>

<style scoped>
/* ── Orbs décoratifs ── */
.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
  z-index: 0;
}
.orb-1 {
  width: 400px; height: 400px;
  top: -100px; right: -100px;
  background: radial-gradient(circle, rgba(124,144,112,0.18) 0%, transparent 70%);
}
.orb-2 {
  width: 350px; height: 350px;
  bottom: 100px; left: -120px;
  background: radial-gradient(circle, rgba(201,154,60,0.12) 0%, transparent 70%);
}

/* ── Spinner ── */
.spinner {
  width: 44px; height: 44px;
  border: 2px solid #D9C6A8;
  border-top-color: #6B4F3F;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Status banner ── */
.status-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  border-radius: 14px;
  box-shadow: 0 8px 32px var(--glow, rgba(0,0,0,0.15));
}
.status-banner-icon {
  width: 38px; height: 38px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; font-weight: 700; color: #fff;
  flex-shrink: 0;
}
.status-banner-label {
  font-family: 'Poppins', sans-serif;
  font-size: 0.95rem; font-weight: 700;
  color: #fff; letter-spacing: 0.02em;
}
.status-banner-sub {
  font-size: 0.7rem; color: rgba(255,255,255,0.65);
  margin-top: 2px; letter-spacing: 0.02em;
}
.status-banner-scans {
  margin-left: auto;
  text-align: center;
  flex-shrink: 0;
}
.status-scans-num {
  display: block;
  font-family: 'Poppins', sans-serif;
  font-size: 1.5rem; font-weight: 800;
  color: #fff; line-height: 1;
}
.status-scans-txt {
  font-size: 0.65rem;
  color: rgba(255,255,255,0.6);
  text-transform: uppercase; letter-spacing: 1px;
}

/* ── Card ── */
.card-v {
  background: rgba(251,247,240,0.92);
  border: 1px solid rgba(217,198,168,0.55);
  border-radius: 18px;
  box-shadow:
    0 2px 6px rgba(74,55,44,0.05),
    0 12px 40px rgba(74,55,44,0.08);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}
.card-top-accent {
  height: 5px;
  border-radius: 18px 18px 0 0;
}

/* ── Eyebrow section ── */
.section-eyebrow {
  font-family: 'Poppins', sans-serif;
  font-size: 0.65rem; font-weight: 600;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: #8C7A6B;
  margin-bottom: 0.75rem;
}

/* ── Grille méta ── */
.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem 1.25rem;
  margin-bottom: 0.25rem;
}
.meta-item { }
.meta-key {
  font-size: 0.67rem;
  color: #8C7A6B;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 2px;
}
.meta-val {
  font-size: 0.9rem;
  font-weight: 600;
  color: #3A2E26;
}

/* ── Status pill ── */
.status-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.05em;
}

/* ── Révocation ── */
.revoke-box {
  background: rgba(181,83,60,0.06);
  border: 1px solid rgba(181,83,60,0.2);
  border-radius: 12px;
  padding: 14px 16px;
}
.revoke-title {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #8c3520;
  margin-bottom: 6px;
}

/* ── Boutons ── */
.btn-primary-v {
  background: linear-gradient(135deg, #4A372C, #6B4F3F);
  color: #FBF7F0;
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  padding: 0.8rem 1.5rem;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: opacity 0.18s, transform 0.1s;
  box-shadow: 0 4px 16px rgba(74,55,44,0.25);
}
.btn-primary-v:hover { opacity: 0.88; }
.btn-primary-v:active { transform: scale(0.98); }

.btn-secondary-v {
  background: transparent;
  color: #4A372C;
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  padding: 0.8rem 1.5rem;
  border-radius: 10px;
  border: 1.5px solid #D9C6A8;
  cursor: pointer;
  transition: all 0.18s;
}
.btn-secondary-v:hover { background: #E8DCCB; border-color: #8C7A6B; }

/* ── Avatar ── */
.avatar-circle {
  width: 46px; height: 46px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Poppins', sans-serif;
  font-size: 0.9rem; font-weight: 700;
  color: #fff;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(74,55,44,0.2);
}

/* ── Badge certifié ── */
.certified-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #4a6640;
  background: rgba(124,144,112,0.12);
  border: 1px solid rgba(124,144,112,0.3);
  flex-shrink: 0;
}

/* ── Transitions ── */
.badge-pop-enter-active { transition: all 0.7s cubic-bezier(0.34,1.56,0.64,1); }
.badge-pop-enter-from   { opacity:0; transform:scale(0.6) translateY(20px); }

.slide-up-enter-active { transition: all 0.45s cubic-bezier(0.22,1,0.36,1); }
.slide-up-enter-from   { opacity:0; transform:translateY(28px); }

.reveal-enter-active { transition: opacity 0.4s ease; }
.reveal-enter-from   { opacity:0; }

/* ── Filigrane ── */
.watermark-text {
  position: fixed;
  font-family: 'Poppins', sans-serif;
  font-weight: 900;
  font-size: clamp(80px,18vw,220px);
  opacity: 0.06;
  white-space: nowrap;
  top: 50%;
  left: 50%;
  transform: translate(-50%,-50%);
  z-index: 0;
  pointer-events: none;
  user-select: none;
  letter-spacing: -0.02em;
}
</style>
