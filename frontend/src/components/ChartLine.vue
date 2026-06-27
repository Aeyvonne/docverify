<script setup>
/**
 * Graphique courbe premium.
 * - Dégradé vertical sous chaque courbe (fill gradient)
 * - Points plus grands avec halo au hover
 * - Grille ultra-légère
 * - Légende custom en dehors du canvas
 */
import { ref, onMounted, onUnmounted, watch } from 'vue'
import {
  Chart, LineController, LineElement, PointElement,
  CategoryScale, LinearScale, Filler, Tooltip, Legend,
} from 'chart.js'

Chart.register(LineController, LineElement, PointElement,
  CategoryScale, LinearScale, Filler, Tooltip, Legend)

const props = defineProps({
  labels:   { type: Array,  required: true },
  datasets: { type: Array,  required: true }, // [{ label, data, color }]
  title:    { type: String, default: '' },
  height:   { type: Number, default: 240 },
  darkBg:   { type: Boolean, default: true },
})

const canvas  = ref(null)
let chartInst = null

function makeGradient(ctx, color, h) {
  const grad = ctx.createLinearGradient(0, 0, 0, h)
  grad.addColorStop(0,   color + '55')
  grad.addColorStop(0.6, color + '18')
  grad.addColorStop(1,   color + '00')
  return grad
}

function buildChart() {
  if (!canvas.value) return
  if (chartInst) { chartInst.destroy(); chartInst = null }

  const ctx = canvas.value.getContext('2d')
  const h   = props.height

  const textColor  = props.darkBg ? 'rgba(251,247,240,0.45)' : '#8C7A6B'
  const gridColor  = props.darkBg ? 'rgba(255,255,255,0.06)'  : 'rgba(217,198,168,0.4)'
  const tooltipBg  = props.darkBg ? '#1A0F09'                 : '#4A372C'

  chartInst = new Chart(canvas.value, {
    type: 'line',
    data: {
      labels:   props.labels,
      datasets: props.datasets.map(ds => ({
        label:            ds.label,
        data:             ds.data,
        borderColor:      ds.color ?? '#6B4F3F',
        backgroundColor:  makeGradient(ctx, ds.color ?? '#6B4F3F', h),
        borderWidth:      2.5,
        pointRadius:      4,
        pointHoverRadius: 7,
        pointBackgroundColor: ds.color ?? '#6B4F3F',
        pointBorderColor:     props.darkBg ? '#2D1F17' : '#FBF7F0',
        pointBorderWidth:     2,
        pointHoverBorderWidth: 0,
        fill:             true,
        tension:          0.45,
        spanGaps:         true,
      })),
    },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      interaction: {
        mode:       'index',
        intersect:  false,
      },
      animation: {
        duration: 900,
        easing:   'easeInOutCubic',
      },
      scales: {
        x: {
          border:    { display: false },
          grid:      { color: gridColor, drawTicks: false },
          ticks:     {
            color:   textColor,
            font:    { size: 10, family: 'Inter' },
            maxRotation: 0,
            padding: 8,
          },
        },
        y: {
          beginAtZero: true,
          border:    { display: false, dash: [4, 4] },
          grid:      { color: gridColor, drawTicks: false },
          ticks:     {
            color:     textColor,
            font:      { size: 10, family: 'Inter' },
            precision: 0,
            padding:   10,
            maxTicksLimit: 5,
          },
        },
      },
      plugins: {
        legend: { display: false }, // légende custom en dehors
        tooltip: {
          backgroundColor:  tooltipBg,
          titleColor:       '#FBF7F0',
          bodyColor:        '#D9C6A8',
          padding:          { x: 14, y: 10 },
          cornerRadius:     10,
          displayColors:    true,
          boxWidth:         8,
          boxHeight:        8,
          boxPadding:       4,
          usePointStyle:    true,
          callbacks: {
            title: (items) => items[0].label,
          },
        },
      },
    },
  })
}

onMounted(buildChart)
watch(() => [props.datasets, props.labels], buildChart, { deep: true })
onUnmounted(() => chartInst?.destroy())
</script>

<template>
  <div>
    <!-- En-tête avec titre + légende custom -->
    <div class="flex items-start justify-between mb-4 gap-3">
      <p v-if="title" class="text-xs font-display font-semibold uppercase tracking-[0.15em]"
         :style="{ color: darkBg ? 'rgba(251,247,240,0.5)' : '#8C7A6B' }">
        {{ title }}
      </p>
      <!-- Légende inline -->
      <div class="flex items-center gap-4 flex-shrink-0">
        <div v-for="ds in datasets" :key="ds.label"
             class="flex items-center gap-1.5">
          <span class="w-5 h-0.5 rounded-full inline-block"
                :style="{ background: ds.color }"></span>
          <span class="text-xs"
                :style="{ color: darkBg ? 'rgba(251,247,240,0.6)' : '#8C7A6B' }">
            {{ ds.label }}
          </span>
        </div>
      </div>
    </div>

    <!-- Canvas -->
    <div :style="{ height: height + 'px' }">
      <canvas ref="canvas"></canvas>
    </div>
  </div>
</template>
