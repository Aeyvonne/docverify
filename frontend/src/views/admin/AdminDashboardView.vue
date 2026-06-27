<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import ChartDonut from '@/components/ChartDonut.vue'
import ChartLine  from '@/components/ChartLine.vue'
import api from '@/api/axios'

const stats   = ref(null)
const loading = ref(true)
const erreur  = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/stats')
    stats.value = data
  } catch (e) {
    erreur.value = e.response?.data?.message ?? 'Impossible de charger les statistiques.'
  } finally {
    loading.value = false
  }
})

const pct = (part, total) => total > 0 ? Math.round((part / total) * 100) : 0

// Données camembert documents
const donutDocLabels = ['Actifs', 'Révoqués', 'Expirés']
const donutDocColors = ['#7C9070', '#B5533C', '#C99A3C']
const donutDocData   = computed(() => stats.value
  ? [stats.value.documents.actifs, stats.value.documents.revoques, stats.value.documents.expires]
  : [])

// Données camembert émetteurs
const donutEmetLabels = ['Certifiés', 'En attente']
const donutEmetColors = ['#7C9070', '#4A372C']
const donutEmetData   = computed(() => stats.value
  ? [stats.value.emetteurs.certifies, stats.value.emetteurs.total - stats.value.emetteurs.certifies]
  : [])

// Données courbe
const lineDatasets = computed(() => stats.value ? [
  { label: 'Documents',     data: stats.value.graphiques.documents_mois, color: '#D9C6A8' },
  { label: 'Vérifications', data: stats.value.graphiques.verifs_mois,    color: '#7C9070' },
] : [])
</script>

<template>
  <AppLayout max-width="max-w-6xl">

    <!-- En-tête -->
    <div class="mb-8 fade-in-up">
      <p class="text-xs font-display font-medium tracking-[0.2em] uppercase mb-1"
         style="color:#8C7A6B;">Administration</p>
      <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">
        Tableau de bord
      </h1>
    </div>

    <!-- Chargement -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-24 gap-4">
      <div class="w-10 h-10 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
      <p class="text-sm" style="color:#8C7A6B;">Chargement…</p>
    </div>

    <!-- Erreur -->
    <div v-else-if="erreur" class="card-premium p-10 text-center fade-in-up">
      <p class="text-3xl mb-3">⚠</p>
      <p class="font-display font-semibold mb-2" style="color:#3A2E26;">Erreur</p>
      <p class="text-sm mb-5" style="color:#8C7A6B;">{{ erreur }}</p>
      <button @click="$router.go(0)" class="btn-secondary text-sm px-5">Réessayer</button>
    </div>

    <template v-else-if="stats">

      <!-- ══════════════════════════════════════════════
           KPI CARDS — ligne horizontale
      ══════════════════════════════════════════════ -->
      <div class="grid grid-cols-3 lg:grid-cols-6 gap-3 mb-6 fade-in-up delay-100">
        <div v-for="kpi in [
          { icon:'📄', val: stats.documents.total,      label:'Documents', accent:'#D9C6A8' },
          { icon:'✅', val: stats.documents.actifs,     label:'Actifs',    accent:'#7C9070' },
          { icon:'🚫', val: stats.documents.revoques,   label:'Révoqués',  accent:'#B5533C' },
          { icon:'⏱',  val: stats.documents.expires,    label:'Expirés',   accent:'#C99A3C' },
          { icon:'👁',  val: stats.verifications.total, label:'Scans',     accent:'#D9C6A8' },
          { icon:'🏛',  val: stats.emetteurs.total,     label:'Émetteurs', accent:'#D9C6A8' },
        ]" :key="kpi.label"
             class="rounded-xl p-4 text-center"
             style="background:#3A2E26; border:1px solid rgba(217,198,168,0.12);">
          <p class="text-lg mb-1">{{ kpi.icon }}</p>
          <p class="font-display font-black leading-none mb-1"
             :style="{ fontSize:'clamp(1.5rem,3.5vw,2rem)', color: kpi.accent }">
            {{ kpi.val }}
          </p>
          <p class="text-xs uppercase tracking-wide" style="color:rgba(217,198,168,0.5);">
            {{ kpi.label }}
          </p>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════
           COURBE ACTIVITÉ — fond sombre pleine largeur
      ══════════════════════════════════════════════ -->
      <div class="rounded-2xl p-6 mb-6 fade-in-up delay-200"
           style="background:#2D1F17; border:1px solid rgba(217,198,168,0.1);">
        <ChartLine
          title="Activité — 12 derniers mois"
          :labels="stats.graphiques.labels"
          :datasets="lineDatasets"
          :height="240"
          :dark-bg="true"
        />
      </div>

      <!-- ══════════════════════════════════════════════
           DEUX DOUGHNUTS — côte à côte sur fond sombre
      ══════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6 fade-in-up delay-300">

        <!-- Documents -->
        <div class="rounded-2xl p-6"
             style="background:#2D1F17; border:1px solid rgba(217,198,168,0.1);">
          <ChartDonut
            title="Répartition"
            subtitle="Documents"
            :labels="donutDocLabels"
            :data="donutDocData"
            :colors="donutDocColors"
            :size="150"
            :dark-bg="true"
          />
        </div>

        <!-- Émetteurs -->
        <div class="rounded-2xl p-6"
             style="background:#2D1F17; border:1px solid rgba(217,198,168,0.1);">
          <ChartDonut
            title="Certification"
            subtitle="Émetteurs"
            :labels="donutEmetLabels"
            :data="donutEmetData"
            :colors="donutEmetColors"
            :size="150"
            :dark-bg="true"
          />
        </div>
      </div>

      <!-- ══════════════════════════════════════════════
           BARRES DE PROGRESSION — fond clair
      ══════════════════════════════════════════════ -->
      <div class="card-premium p-6 mb-5 fade-in-up delay-300">
        <p class="text-xs font-display font-semibold uppercase tracking-[0.15em] mb-5"
           style="color:#8C7A6B;">
          Distribution des documents
        </p>
        <div class="space-y-4">
          <div v-for="row in [
            { label:'Actifs',   val: stats.documents.actifs,   color:'#7C9070', bg:'rgba(124,144,112,0.12)' },
            { label:'Révoqués', val: stats.documents.revoques, color:'#B5533C', bg:'rgba(181,83,60,0.1)'   },
            { label:'Expirés',  val: stats.documents.expires,  color:'#C99A3C', bg:'rgba(201,154,60,0.1)'  },
          ]" :key="row.label">
            <div class="flex items-center gap-4">
              <!-- Label + valeur -->
              <div class="w-24 flex-shrink-0 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full flex-shrink-0"
                      :style="{ background: row.color }"></span>
                <span class="text-xs" style="color:#8C7A6B;">{{ row.label }}</span>
              </div>
              <!-- Barre -->
              <div class="flex-1 h-2.5 rounded-full overflow-hidden" style="background:#E8DCCB;">
                <div class="h-full rounded-full transition-all duration-1000"
                     :style="{
                       background: row.color,
                       width: pct(row.val, stats.documents.total) + '%',
                       boxShadow: `0 0 8px ${row.color}60`,
                     }">
                </div>
              </div>
              <!-- Pourcentage -->
              <div class="w-14 text-right flex-shrink-0">
                <span class="text-xs font-bold" :style="{ color: row.color }">
                  {{ row.val }}
                </span>
                <span class="text-xs ml-1" style="color:#8C7A6B;">
                  ({{ pct(row.val, stats.documents.total) }}%)
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════
           RACCOURCIS
      ══════════════════════════════════════════════ -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 fade-in-up delay-300">
        <RouterLink v-for="link in [
          { to:'/admin/emetteurs', icon:'🏛', label:'Émetteurs',  sub:'Gérer les comptes',         accent:'#D9C6A8' },
          { to:'/admin/demandes',  icon:'📋', label:'Demandes',   sub:'Certifications en attente', accent:'#C99A3C' },
          { to:'/admin/admins',    icon:'👤', label:'Admins',     sub:'Comptes administrateurs',   accent:'#7C9070' },
        ]" :key="link.to" :to="link.to"
           class="rounded-xl p-4 flex items-center gap-3 group transition-all no-underline
                  hover:-translate-y-0.5 hover:shadow-lg"
           style="background:#3A2E26; border:1px solid rgba(217,198,168,0.1);">
          <span class="text-xl">{{ link.icon }}</span>
          <div class="flex-1">
            <p class="text-sm font-display font-semibold" :style="{ color: link.accent }">
              {{ link.label }}
            </p>
            <p class="text-xs" style="color:rgba(217,198,168,0.45);">{{ link.sub }}</p>
          </div>
          <svg class="w-4 h-4 opacity-30 group-hover:opacity-70 group-hover:translate-x-1 transition-all flex-shrink-0"
               style="color:#D9C6A8;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
          </svg>
        </RouterLink>
      </div>

    </template>
  </AppLayout>
</template>
