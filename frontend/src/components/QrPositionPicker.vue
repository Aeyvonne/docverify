<script setup>
/**
 * Prévisualisation PDF + placement du QR Code par clic.
 *
 * Bugs corrigés :
 *  - watch({ immediate: true }) + nextTick pour attendre que le canvas soit dans le DOM
 *  - Conversion coordonnées pixel → mm basée sur getBoundingClientRect (taille CSS réelle)
 *    et non sur canvas.width (résolution interne) — évite le décalage sur écrans HiDPI
 *  - Gestion d'erreur explicite avec message utilisateur
 *
 * Événements émis :
 *   @position-selected  { x_mm: float, y_mm: float }
 */
import { ref, watch, nextTick } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'

// Worker pdf.js — chemin absolu requis par Vite
pdfjsLib.GlobalWorkerOptions.workerSrc =
  new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString()

const props = defineProps({
  file:     { type: File,   default: null },
  qrSizeMm: { type: Number, default: 25  },
})
const emit = defineEmits(['position-selected'])

const canvas   = ref(null)
const position = ref(null)
const pageDims = ref(null)
const loading  = ref(false)
const erreur   = ref(null)

// Taille du marqueur QR en px CSS — recalculée selon les dims réelles de la page
// qrSizeMm / page_width_mm * canvas_css_width
function getMarkerSizeCss() {
  if (!canvas.value || !pageDims.value) return 48
  const rect = canvas.value.getBoundingClientRect()
  return Math.round((props.qrSizeMm / pageDims.value.width_mm) * rect.width)
}

// ── Rendu du PDF ───────────────────────────────────────────────────────
async function renderPdf(file) {
  if (!file) return

  // Attendre que le canvas soit monté dans le DOM
  await nextTick()
  if (!canvas.value) return

  loading.value = true
  erreur.value  = null
  position.value = null

  try {
    const buffer    = await file.arrayBuffer()
    const pdf       = await pdfjsLib.getDocument({ data: buffer }).promise
    const page      = await pdf.getPage(1)

    // Viewport natif (scale=1) → dimensions réelles en points PDF
    const vpNatif   = page.getViewport({ scale: 1 })

    // 1 point PDF = 0.3528 mm
    pageDims.value = {
      width_mm:  parseFloat((vpNatif.width  * 0.3528).toFixed(2)),
      height_mm: parseFloat((vpNatif.height * 0.3528).toFixed(2)),
    }

    // Calcul du scale pour s'adapter à la largeur du conteneur (max 640px)
    const containerW = canvas.value.parentElement?.clientWidth || 640
    const scale      = Math.min(containerW / vpNatif.width, 2)
    const vpScaled   = page.getViewport({ scale })

    // Définir les dimensions internes du canvas (résolution de rendu)
    canvas.value.width  = vpScaled.width
    canvas.value.height = vpScaled.height

    const ctx = canvas.value.getContext('2d')
    await page.render({ canvasContext: ctx, viewport: vpScaled }).promise

  } catch (e) {
    erreur.value = 'Impossible de lire ce fichier PDF. Vérifiez qu\'il n\'est pas protégé par mot de passe.'
    console.error('[QrPositionPicker]', e)
  } finally {
    loading.value = false
  }
}

// ── Clic sur le canvas ─────────────────────────────────────────────────
function handleClick(event) {
  if (!canvas.value || !pageDims.value || loading.value) return

  // getBoundingClientRect → coordonnées CSS réelles affichées à l'écran
  const rect  = canvas.value.getBoundingClientRect()
  const cssX  = event.clientX - rect.left
  const cssY  = event.clientY - rect.top
  const cssW  = rect.width
  const cssH  = rect.height

  // Sécurité : dimensions invalides
  if (!cssW || !cssH) return

  const { width_mm, height_mm } = pageDims.value
  const qrMm = props.qrSizeMm ?? 25
  const markerSize = getMarkerSizeCss()

  // Conversion CSS → mm, borné pour que le QR reste entièrement dans la page
  const x_mm = parseFloat(Math.max(0, Math.min(
    (cssX / cssW) * width_mm,
    width_mm - qrMm
  )).toFixed(2))

  const y_mm = parseFloat(Math.max(0, Math.min(
    (cssY / cssH) * height_mm,
    height_mm - qrMm
  )).toFixed(2))

  // Centrer le marqueur sur le clic, borné dans le canvas CSS
  const markerX = Math.max(0, Math.min(cssX - markerSize / 2, cssW - markerSize))
  const markerY = Math.max(0, Math.min(cssY - markerSize / 2, cssH - markerSize))

  position.value = { x_css: markerX, y_css: markerY, x_mm, y_mm }
  emit('position-selected', { x_mm, y_mm })
}

// ── Observer les changements de fichier ───────────────────────────────
// immediate: true → se déclenche aussi à la première valeur
watch(
  () => props.file,
  (file) => {
    position.value = null
    erreur.value   = null
    if (file) renderPdf(file)
  },
  { immediate: true }
)
</script>

<template>
  <div class="space-y-3">

    <!-- Message d'erreur -->
    <div v-if="erreur"
         class="p-3 rounded-xl text-sm text-center"
         style="background:rgba(181,83,60,0.08); color:#8c3520;
                border:1px solid rgba(181,83,60,0.2);">
      {{ erreur }}
    </div>

    <!-- Instruction -->
    <p v-else class="text-sm text-taupe text-center">
      {{ loading ? 'Chargement du document…' : 'Cliquez sur la page pour positionner le QR Code' }}
    </p>

    <!-- Conteneur canvas -->
    <div class="relative w-full rounded-xl border border-sand overflow-hidden shadow-sm"
         :class="loading ? 'cursor-wait' : 'cursor-crosshair'"
         style="min-height: 120px; background:#F2E9DE;"
         @click="handleClick">

      <!-- Spinner de chargement -->
      <Transition name="fade">
        <div v-if="loading"
             class="absolute inset-0 flex flex-col items-center justify-center z-20 gap-3"
             style="background:rgba(242,233,222,0.85);">
          <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
          <p class="text-xs text-taupe">Rendu du PDF en cours…</p>
        </div>
      </Transition>

      <!-- Canvas PDF.js — occupe toute la largeur, hauteur automatique -->
      <canvas
        ref="canvas"
        class="block w-full h-auto"
        style="display: block;"
      ></canvas>

      <!-- Marqueur QR — taille proportionnelle à qrSizeMm -->
      <Transition name="pop">
        <div v-if="position && !loading"
             class="absolute pointer-events-none"
             :style="{
               left:   position.x_css + 'px',
               top:    position.y_css + 'px',
               width:  getMarkerSizeCss() + 'px',
               height: getMarkerSizeCss() + 'px',
             }">
          <!-- Cadre du QR -->
          <div class="w-full h-full rounded border-2 flex items-center justify-center"
               style="border-color:#4A372C; background:rgba(74,55,44,0.12);">
            <!-- Mini grille QR symbolique -->
            <svg viewBox="0 0 10 10" class="w-5 h-5" fill="#4A372C">
              <rect x="0" y="0" width="4" height="4" rx="0.3"/>
              <rect x="0.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
              <rect x="1.4" y="1.4" width="1.2" height="1.2"/>
              <rect x="6" y="0" width="4" height="4" rx="0.3"/>
              <rect x="6.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
              <rect x="7.4" y="1.4" width="1.2" height="1.2"/>
              <rect x="0" y="6" width="4" height="4" rx="0.3"/>
              <rect x="0.8" y="6.8" width="2.4" height="2.4" fill="#F2E9DE"/>
              <rect x="1.4" y="7.4" width="1.2" height="1.2"/>
              <rect x="5" y="4.5" width="1" height="1"/>
              <rect x="7" y="5.5" width="2" height="1"/>
              <rect x="6" y="7" width="1" height="3"/>
              <rect x="8.5" y="8.5" width="1.5" height="1.5" rx="0.2"/>
            </svg>
          </div>
          <!-- Petite étiquette -->
          <div class="absolute -top-5 left-0 text-xs font-medium px-1.5 py-0.5 rounded whitespace-nowrap"
               style="background:#4A372C; color:#FBF7F0; font-size:10px;">
            QR ici
          </div>
        </div>
      </Transition>
    </div>

    <!-- Coordonnées + bouton reset -->
    <div v-if="position" class="flex items-center justify-between text-xs text-taupe px-1">
      <span>
        Position choisie :
        <strong class="text-brown-dark">x = {{ position.x_mm }} mm</strong>,
        <strong class="text-brown-dark">y = {{ position.y_mm }} mm</strong>
      </span>
      <button type="button"
              @click.stop="position = null; emit('position-selected', { x_mm: null, y_mm: null })"
              class="text-taupe hover:text-terracotta transition-colors underline underline-offset-2 ml-2">
        Réinitialiser
      </button>
    </div>
    <p v-else-if="!loading && !erreur"
       class="text-xs text-center text-taupe italic px-1">
      Sans sélection, le QR sera placé en bas à droite par défaut.
    </p>

  </div>
</template>

<style scoped>
/* Fade pour le spinner */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

/* Pop pour le marqueur QR */
.pop-enter-active { transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1); }
.pop-enter-from   { opacity: 0; transform: scale(0.5); }
</style>
