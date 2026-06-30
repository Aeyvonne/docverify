<script setup>
import { ref, watch, nextTick, computed } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'

pdfjsLib.GlobalWorkerOptions.workerSrc =
  new URL('pdfjs-dist/build/pdf.worker.min.mjs', import.meta.url).toString()

const props = defineProps({
  file:     { type: File,   default: null },
  qrSizeMm: { type: Number, default: 25  },
})
const emit = defineEmits(['positions-updated'])

// ── État interne ───────────────────────────────────────────────────────
const canvas      = ref(null)
const loading     = ref(false)
const erreur      = ref(null)
const currentPage = ref(1)
const totalPages  = ref(0)
const pageDims    = ref([])   // [{ width_mm, height_mm }] indexé par page (0-based)
const pdfDoc      = ref(null)

// positions[pageIndex] = { x_mm, y_mm } | null
const positions   = ref([])

// Mode : 'auto' = page 1 propagée, 'manuel' = chaque page indépendante
const mode = ref('auto')

// ── Computed ──────────────────────────────────────────────────────────
const currentDims = computed(() => pageDims.value[currentPage.value - 1] ?? null)
const currentPos  = computed(() => positions.value[currentPage.value - 1] ?? null)

function getMarkerSizeCss() {
  if (!canvas.value || !currentDims.value) return 48
  const rect = canvas.value.getBoundingClientRect()
  return Math.round((props.qrSizeMm / currentDims.value.width_mm) * rect.width)
}

// Marqueur CSS calculé à partir de la position mm
const markerStyle = computed(() => {
  if (!canvas.value || !currentDims.value || !currentPos.value) return null
  const rect = canvas.value.getBoundingClientRect()
  const { width_mm, height_mm } = currentDims.value
  const ms = getMarkerSizeCss()
  const cssX = (currentPos.value.x_mm / width_mm) * rect.width
  const cssY = (currentPos.value.y_mm / height_mm) * rect.height
  return {
    left:   Math.max(0, cssX) + 'px',
    top:    Math.max(0, cssY) + 'px',
    width:  ms + 'px',
    height: ms + 'px',
  }
})

// ── Chargement du PDF ─────────────────────────────────────────────────
async function loadPdf(file) {
  if (!file) return
  loading.value  = true
  erreur.value   = null
  positions.value = []
  pageDims.value  = []
  currentPage.value = 1

  try {
    const buffer = await file.arrayBuffer()
    pdfDoc.value = await pdfjsLib.getDocument({ data: buffer }).promise
    totalPages.value = pdfDoc.value.numPages

    // Pré-calculer les dimensions de toutes les pages
    const dims = []
    for (let i = 1; i <= totalPages.value; i++) {
      const p  = await pdfDoc.value.getPage(i)
      const vp = p.getViewport({ scale: 1 })
      dims.push({
        width_mm:  parseFloat((vp.width  * 0.3528).toFixed(2)),
        height_mm: parseFloat((vp.height * 0.3528).toFixed(2)),
      })
    }
    pageDims.value  = dims
    positions.value = Array(totalPages.value).fill(null)

    await renderPage(1)
  } catch (e) {
    erreur.value = 'Impossible de lire ce PDF. Vérifiez qu\'il n\'est pas protégé.'
  } finally {
    loading.value = false
  }
}

async function renderPage(pageNum) {
  if (!pdfDoc.value || !canvas.value) return
  loading.value = true
  await nextTick()

  try {
    const page       = await pdfDoc.value.getPage(pageNum)
    const vpNatif    = page.getViewport({ scale: 1 })
    const containerW = canvas.value.parentElement?.clientWidth || 600
    const scale      = Math.min(containerW / vpNatif.width, 2)
    const vp         = page.getViewport({ scale })

    canvas.value.width  = vp.width
    canvas.value.height = vp.height

    const ctx = canvas.value.getContext('2d')
    ctx.clearRect(0, 0, vp.width, vp.height)
    await page.render({ canvasContext: ctx, viewport: vp }).promise
  } finally {
    loading.value = false
  }
}

// ── Navigation ────────────────────────────────────────────────────────
async function goTo(pageNum) {
  if (pageNum < 1 || pageNum > totalPages.value || loading.value) return
  currentPage.value = pageNum
  await renderPage(pageNum)
}

// ── Clic sur le canvas ─────────────────────────────────────────────────
function handleClick(event) {
  if (!canvas.value || !currentDims.value || loading.value) return

  const rect   = canvas.value.getBoundingClientRect()
  const cssX   = event.clientX - rect.left
  const cssY   = event.clientY - rect.top
  const { width_mm, height_mm } = currentDims.value
  const qrMm = props.qrSizeMm ?? 25

  const x_mm = parseFloat(Math.max(0, Math.min(
    (cssX / rect.width)  * width_mm, width_mm  - qrMm
  )).toFixed(2))

  const y_mm = parseFloat(Math.max(0, Math.min(
    (cssY / rect.height) * height_mm, height_mm - qrMm
  )).toFixed(2))

  const idx = currentPage.value - 1

  if (mode.value === 'auto') {
    // Propager la position de la page 1 à toutes les pages (même ratio)
    const ratioX = x_mm / width_mm
    const ratioY = y_mm / height_mm
    positions.value = pageDims.value.map(d => ({
      x_mm: parseFloat((ratioX * d.width_mm).toFixed(2)),
      y_mm: parseFloat((ratioY * d.height_mm).toFixed(2)),
    }))
  } else {
    // Mode manuel : uniquement la page courante
    const newPos = [...positions.value]
    newPos[idx] = { x_mm, y_mm }
    positions.value = newPos
  }

  emitPositions()
}

function resetPage() {
  const newPos = [...positions.value]
  newPos[currentPage.value - 1] = null
  positions.value = newPos
  emitPositions()
}

function resetAll() {
  positions.value = Array(totalPages.value).fill(null)
  emitPositions()
}

function emitPositions() {
  // Émettre un tableau indexé par page (1-based) → null si auto/bas-droite
  emit('positions-updated', positions.value.map((p, i) => ({
    page: i + 1,
    x_mm: p?.x_mm ?? null,
    y_mm: p?.y_mm ?? null,
  })))
}

// ── Watcher fichier ───────────────────────────────────────────────────
watch(() => props.file, (file) => {
  if (file) loadPdf(file)
  else { pdfDoc.value = null; totalPages.value = 0; positions.value = [] }
}, { immediate: true })

watch(mode, () => {
  // Quand on change de mode, on remet tout à zéro
  positions.value = Array(totalPages.value).fill(null)
  emitPositions()
})
</script>

<template>
  <div class="space-y-3">

    <!-- Erreur -->
    <div v-if="erreur" class="p-3 rounded-xl text-sm text-center"
         style="background:rgba(181,83,60,0.08);color:#8c3520;border:1px solid rgba(181,83,60,0.2);">
      {{ erreur }}
    </div>

    <template v-if="totalPages > 0">

      <!-- Sélecteur de mode -->
      <div class="flex gap-2 p-1 rounded-xl" style="background:#E8DCCB;">
        <button type="button"
                @click="mode = 'auto'"
                class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-all"
                :style="mode === 'auto'
                  ? 'background:#4A372C;color:#FBF7F0;'
                  : 'background:transparent;color:#8C7A6B;'">
          📌 Position unique (toutes les pages)
        </button>
        <button type="button"
                @click="mode = 'manuel'"
                class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-all"
                :style="mode === 'manuel'
                  ? 'background:#4A372C;color:#FBF7F0;'
                  : 'background:transparent;color:#8C7A6B;'">
          🎯 Position par page
        </button>
      </div>

      <!-- Info mode -->
      <p class="text-xs text-center" style="color:#8C7A6B;">
        <template v-if="mode === 'auto'">
          Cliquez sur la page 1 — le QR sera placé proportionnellement sur toutes les pages.
        </template>
        <template v-else>
          Naviguez entre les pages et cliquez pour placer le QR sur chaque page indépendamment.
        </template>
      </p>

      <!-- Navigation pages -->
      <div v-if="totalPages > 1" class="flex items-center justify-between gap-2">
        <button type="button"
                @click="goTo(currentPage - 1)"
                :disabled="currentPage <= 1 || loading"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all disabled:opacity-40"
                style="background:#E8DCCB;color:#4A372C;">
          ← Précédente
        </button>

        <!-- Pastilles pages -->
        <div class="flex gap-1.5 flex-wrap justify-center">
          <button v-for="p in totalPages" :key="p" type="button"
                  @click="goTo(p)"
                  class="w-7 h-7 rounded-full text-xs font-bold transition-all"
                  :style="p === currentPage
                    ? 'background:#4A372C;color:#FBF7F0;'
                    : positions[p-1]
                      ? 'background:rgba(124,144,112,0.25);color:#4A5E3A;border:1px solid rgba(124,144,112,0.5);'
                      : 'background:#E8DCCB;color:#8C7A6B;'">
            {{ p }}
          </button>
        </div>

        <button type="button"
                @click="goTo(currentPage + 1)"
                :disabled="currentPage >= totalPages || loading"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all disabled:opacity-40"
                style="background:#E8DCCB;color:#4A372C;">
          Suivante →
        </button>
      </div>

      <!-- Indicateur page courante -->
      <div class="flex items-center justify-between text-xs" style="color:#8C7A6B;">
        <span>Page <strong style="color:#3A2E26;">{{ currentPage }}</strong> / {{ totalPages }}</span>
        <div class="flex gap-2">
          <span v-if="currentPos" class="text-xs" style="color:#7C9070;">
            ✓ QR positionné ({{ currentPos.x_mm }}mm, {{ currentPos.y_mm }}mm)
          </span>
          <span v-else class="text-xs italic">
            Pas de position → bas-droit par défaut
          </span>
        </div>
      </div>

      <!-- Canvas -->
      <div class="relative w-full rounded-xl overflow-hidden shadow-sm"
           :class="loading ? 'cursor-wait' : 'cursor-crosshair'"
           style="border:1px solid #D9C6A8;min-height:120px;background:#F2E9DE;"
           @click="handleClick">

        <!-- Spinner -->
        <Transition name="fade">
          <div v-if="loading"
               class="absolute inset-0 flex flex-col items-center justify-center z-20 gap-3"
               style="background:rgba(242,233,222,0.85);">
            <div class="w-8 h-8 border-2 border-t-brown rounded-full animate-spin"
                 style="border-color:#D9C6A8;border-top-color:#4A372C;"></div>
            <p class="text-xs" style="color:#8C7A6B;">Rendu en cours…</p>
          </div>
        </Transition>

        <canvas ref="canvas" class="block w-full h-auto"></canvas>

        <!-- Marqueur QR -->
        <Transition name="pop">
          <div v-if="currentPos && !loading"
               class="absolute pointer-events-none"
               :style="markerStyle">
            <div class="w-full h-full rounded flex items-center justify-center"
                 style="border:2px dashed #4A372C;background:rgba(74,55,44,0.15);">
              <svg viewBox="0 0 10 10" class="w-4 h-4" fill="#4A372C">
                <rect x="0" y="0" width="4" height="4" rx="0.3"/>
                <rect x="0.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                <rect x="1.4" y="1.4" width="1.2" height="1.2"/>
                <rect x="6" y="0" width="4" height="4" rx="0.3"/>
                <rect x="6.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                <rect x="7.4" y="1.4" width="1.2" height="1.2"/>
                <rect x="0" y="6" width="4" height="4" rx="0.3"/>
                <rect x="0.8" y="6.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                <rect x="1.4" y="7.4" width="1.2" height="1.2"/>
              </svg>
            </div>
            <div class="absolute -top-5 left-0 text-xs font-medium px-1.5 py-0.5 rounded whitespace-nowrap"
                 style="background:#4A372C;color:#FBF7F0;font-size:10px;">
              QR p.{{ currentPage }}
            </div>
          </div>
        </Transition>
      </div>

      <!-- Résumé positions + actions -->
      <div class="flex items-center justify-between text-xs" style="color:#8C7A6B;">
        <span>
          {{ positions.filter(p => p !== null).length }}/{{ totalPages }} page(s) avec position définie
        </span>
        <div class="flex gap-3">
          <button v-if="currentPos" type="button"
                  @click="resetPage"
                  class="underline underline-offset-2 hover:text-terracotta transition-colors"
                  style="color:#8C7A6B;">
            Réinitialiser cette page
          </button>
          <button v-if="positions.some(p => p !== null)" type="button"
                  @click="resetAll"
                  class="underline underline-offset-2 transition-colors"
                  style="color:#B5533C;">
            Tout réinitialiser
          </button>
        </div>
      </div>

    </template>

    <!-- État vide (pas encore de fichier) -->
    <div v-else-if="!erreur"
         class="w-full h-36 rounded-xl flex flex-col items-center justify-center gap-2"
         style="background:#F2E9DE;border:2px dashed #D9C6A8;">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24"
           style="color:#D9C6A8;">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
      </svg>
      <p class="text-xs" style="color:#8C7A6B;">Sélectionnez un PDF ci-dessus</p>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity 0.2s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
.pop-enter-active{transition:all 0.2s cubic-bezier(0.34,1.56,0.64,1)}
.pop-enter-from{opacity:0;transform:scale(0.5)}
</style>
