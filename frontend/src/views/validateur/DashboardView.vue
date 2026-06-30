<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import ValidateurLayout from '@/components/ValidateurLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import api from '@/api/axios'

const auth       = useAuthStore()
const notifStore = useNotificationsStore()

const demandes = ref([])
const loading  = ref(true)

onMounted(async () => {
  notifStore.startPolling()
  try {
    const { data } = await api.get('/validateur/demandes')
    demandes.value = data
  } finally {
    loading.value = false
  }
})

const stats = computed(() => ({
  total:      demandes.value.length,
  en_attente: demandes.value.filter(d => d.statut === 'en_attente').length,
  approuvees: demandes.value.filter(d => d.statut === 'approuvee').length,
  refusees:   demandes.value.filter(d => d.statut === 'refusee').length,
}))

const dernieres = computed(() =>
  demandes.value.filter(d => d.statut === 'en_attente').slice(0, 5)
)

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d))
}
</script>

<template>
  <ValidateurLayout max-width="max-w-5xl">

    <!-- En-tête -->
    <div class="mb-8 fade-in-up">
      <p class="text-xs font-medium tracking-[0.2em] uppercase mb-1" style="color:#8C7A6B;">Validation</p>
      <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">
        Bonjour, {{ auth.user?.prenom }} 👋
      </h1>
      <p class="text-sm mt-1" style="color:#8C7A6B;">Responsable de validation des certifications</p>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-2 border-t-green-600 rounded-full animate-spin" style="border-color:#E8DCCB;border-top-color:#4a6640;"></div>
    </div>

    <template v-else>

      <!-- KPI cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8 fade-in-up delay-100">
        <div v-for="kpi in [
          { label:'Total',      val: stats.total,      color:'#4A372C', bg:'rgba(74,55,44,0.08)',      border:'rgba(74,55,44,0.15)' },
          { label:'En attente', val: stats.en_attente, color:'#8a6010', bg:'rgba(201,154,60,0.08)',    border:'rgba(201,154,60,0.25)' },
          { label:'Approuvées', val: stats.approuvees, color:'#4a6640', bg:'rgba(74,102,64,0.08)',     border:'rgba(74,102,64,0.2)' },
          { label:'Refusées',   val: stats.refusees,   color:'#B5533C', bg:'rgba(181,83,60,0.08)',     border:'rgba(181,83,60,0.2)' },
        ]" :key="kpi.label"
             class="rounded-2xl p-5 text-center"
             :style="`background:${kpi.bg};border:1px solid ${kpi.border};`">
          <p class="text-3xl font-black font-display mb-1" :style="`color:${kpi.color};`">{{ kpi.val }}</p>
          <p class="text-xs uppercase tracking-wide" style="color:#8C7A6B;">{{ kpi.label }}</p>
        </div>
      </div>

      <!-- Alerte si demandes en attente -->
      <div v-if="stats.en_attente > 0"
           class="mb-6 p-4 rounded-xl flex items-center gap-3 fade-in-up"
           style="background:rgba(201,154,60,0.08);border:1px solid rgba(201,154,60,0.3);">
        <span class="text-xl flex-shrink-0">⏳</span>
        <div class="flex-1">
          <p class="text-sm font-semibold" style="color:#3A2E26;">
            {{ stats.en_attente }} demande{{ stats.en_attente > 1 ? 's' : '' }} en attente de traitement
          </p>
          <p class="text-xs mt-0.5" style="color:#8C7A6B;">Traitez-les dès que possible pour débloquer les émetteurs.</p>
        </div>
        <RouterLink to="/validateur/demandes"
                    class="btn-primary text-xs px-4 py-2 flex-shrink-0 no-underline"
                    style="background:#4a6640;">
          Traiter →
        </RouterLink>
      </div>

      <!-- Dernières demandes en attente -->
      <div class="fade-in-up delay-200">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xs font-bold uppercase tracking-[0.12em]" style="color:#4A372C;">
            Demandes en attente
          </h2>
          <RouterLink to="/validateur/demandes" class="text-xs no-underline" style="color:#6B4F3F;">
            Tout voir →
          </RouterLink>
        </div>

        <div v-if="dernieres.length" class="space-y-3">
          <div v-for="d in dernieres" :key="d.id"
               class="card-premium p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-sm"
                 style="background:#E8DCCB;color:#4A372C;">
              {{ (d.user?.prenom?.[0] ?? '') + (d.user?.nom?.[0] ?? '') }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold truncate" style="color:#3A2E26;">
                {{ d.user?.prenom }} {{ d.user?.nom }}
              </p>
              <p class="text-xs truncate" style="color:#8C7A6B;">
                {{ d.user?.nom_institution }} · {{ formatDate(d.created_at) }}
              </p>
            </div>
            <RouterLink to="/validateur/demandes"
                        class="text-xs px-3 py-1.5 rounded-lg no-underline transition-colors flex-shrink-0"
                        style="background:rgba(74,102,64,0.1);color:#4a6640;border:1px solid rgba(74,102,64,0.25);">
              Traiter
            </RouterLink>
          </div>
        </div>

        <div v-else class="card-premium p-10 text-center">
          <p class="text-2xl mb-2">✅</p>
          <p class="text-sm" style="color:#8C7A6B;">Aucune demande en attente. Tout est à jour !</p>
        </div>
      </div>

    </template>
  </ValidateurLayout>
</template>
