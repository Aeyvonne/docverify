<script setup>
/**
 * Graphique Doughnut premium.
 * - Fond sombre pour faire ressortir les segments
 * - Valeur totale affichée au centre
 * - Légende verticale avec barres de pourcentage
 * - Animation d'entrée avec délai
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip)

const props = defineProps({
  labels:     { type: Array,  required: true },
  data:       { type: Array,  required: true },
  colors:     { type: Array,  default: () => ['#7C9070', '#B5533C', '#C99A3C', '#8C7A6B'] },
  title:      { type: String, default: '' },
  subtitle:   { type: String, default: '' },
  size:       { type: Number, default: 160 },
  darkBg:     { type: Boolean, default: true },
})

const canvas  = ref(null)
let chartInst = null

const total = computed(() => props.data.reduce((a, b) => a + (b || 0), 0))

const pct = (val) => total.value > 0 ? Math.round((val / total.value) * 100) : 0

// Couleurs avec opacité légère pour le hover
const hoverColors = computed(() =>
  props.colors.map(c => c + 'CC')
)

function buildChart() {
  if (!canvas.value) return
  if (chartInst) { chartInst.destroy(); chartInst = null }

  chartInst = new Chart(canvas.value, {
    type: 'doughnut',
    data: {
      labels: props.labels,
      datasets: [{
        data:                 props.data,
        backgroundColor:      props.colors.map(c => c + 'DD'),
        hoverBackgroundColor: props.colors,
        borderColor:          props.darkBg ? '#2D1F17' : '#FBF7F0',
        borderWidth:          3,
        hoverBorderWidth:     0,
        hoverOffset:          10,
        borderRadius:         4,
      }],
    },
    options: {
      responsive:       false,
      cutout:           '72%',
      rotation:         -90,
      circumference:    360,
      animation: {
        animateRotate:  true,
        animateScale:   false,
        duration:       1000,
        easing:         'easeInOutCubic',
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          enabled:         true,
          backgroundColor: '#1A0F09',
          titleColor:      '#FBF7F0',
          bodyColor:       '#D9C6A8',
          padding:         { x: 14, y: 10 },
          cornerRadius:    10,
          displayColors:   true,
          boxWidth:        10,
          boxHeight:       10,
          callbacks: {
            title: (items) => items[0].label,
            label: (ctx) => `  ${ctx.parsed} — ${pct(ctx.parsed)}%`,
          },
        },
      },
    },
  })
}

onMounted(buildChart)
watch(() => [props.data, props.labels], buildChart, { deep: true })
onUnmounted(() => chartInst?.destroy())
</script>

<template>
  <div class="flex flex-col h-full">

    <!-- Titre -->
    <div v-if="title" class="mb-4">
      <p class="text-xs font-display font-semibold uppercase tracking-[0.15em]"
         :style="{ color: darkBg ? 'rgba(251,247,240,0.5)' : '#8C7A6B' }">
        {{ title }}
      </p>
      <p v-if="subtitle" class="text-2xl font-display font-bold mt-0.5"
         :style="{ color: darkBg ? '#FBF7F0' : '#3A2E26' }">
        {{ subtitle }}
      </p>
    </div>

    <!-- Doughnut + valeur centrale -->
    <div class="flex items-center gap-6 flex-1">

      <!-- Canvas avec valeur au centre -->
      <div class="relative flex-shrink-0" :style="{ width: size+'px', height: size+'px' }">
        <canvas ref="canvas" :width="size" :height="size"></canvas>

        <!-- Valeur totale au centre -->
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
          <span class="font-display font-black leading-none"
                :style="{
                  fontSize: Math.round(size * 0.22) + 'px',
                  color: darkBg ? '#FBF7F0' : '#3A2E26',
                }">
            {{ total }}
          </span>
          <span class="text-xs mt-0.5"
                :style="{ color: darkBg ? 'rgba(251,247,240,0.45)' : '#8C7A6B' }">
            total
          </span>
        </div>
      </div>

      <!-- Légende verticale avec barres -->
      <div class="flex-1 space-y-3 min-w-0">
        <div v-for="(label, i) in labels" :key="label">
          <div class="flex items-center justify-between mb-1">
            <div class="flex items-center gap-2 min-w-0">
              <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                    :style="{ background: colors[i] ?? '#D9C6A8' }"></span>
              <span class="text-xs truncate"
                    :style="{ color: darkBg ? 'rgba(251,247,240,0.7)' : '#8C7A6B' }">
                {{ label }}
              </span>
            </div>
            <span class="text-xs font-bold ml-2 flex-shrink-0"
                  :style="{ color: darkBg ? '#FBF7F0' : '#3A2E26' }">
              {{ data[i] ?? 0 }}
            </span>
          </div>
          <!-- Mini barre de pourcentage -->
          <div class="h-1 rounded-full overflow-hidden"
               :style="{ background: darkBg ? 'rgba(255,255,255,0.08)' : '#E8DCCB' }">
            <div class="h-full rounded-full transition-all duration-1000"
                 :style="{
                   background: colors[i] ?? '#D9C6A8',
                   width: pct(data[i] ?? 0) + '%',
                 }">
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
