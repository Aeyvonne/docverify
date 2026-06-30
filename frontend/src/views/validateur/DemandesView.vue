<script setup>
import { ref, computed, onMounted } from 'vue'
import ValidateurLayout from '@/components/ValidateurLayout.vue'
import api from '@/api/axios'

const demandes     = ref([])
const loading      = ref(true)
const filterStatut = ref('en_attente')

const showRefusModal = ref(false)
const refusTarget    = ref(null)
const refusMotif     = ref('')
const refusLoading   = ref(false)
const refusError     = ref(null)

const actionLoading = ref(null) // id de la demande en cours

onMounted(async () => {
  try {
    const { data } = await api.get('/validateur/demandes')
    demandes.value = data
  } finally {
    loading.value = false
  }
})

const counts = computed(() => ({
  tous:       demandes.value.length,
  en_attente: demandes.value.filter(d => d.statut === 'en_attente').length,
  approuvee:  demandes.value.filter(d => d.statut === 'approuvee').length,
  refusee:    demandes.value.filter(d => d.statut === 'refusee').length,
}))

const filtered = computed(() =>
  filterStatut.value === 'tous'
    ? demandes.value
    : demandes.value.filter(d => d.statut === filterStatut.value)
)

const statutStyle = {
  en_attente: { label:'En attente', color:'#8a6010', bg:'rgba(201,154,60,0.1)',   border:'rgba(201,154,60,0.3)' },
  approuvee:  { label:'Approuvée',  color:'#4a6640', bg:'rgba(74,102,64,0.1)',    border:'rgba(74,102,64,0.25)' },
  refusee:    { label:'Refusée',    color:'#8c3520', bg:'rgba(181,83,60,0.08)',   border:'rgba(181,83,60,0.25)' },
}

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day:'2-digit', month:'short', year:'numeric' }).format(new Date(d))
}

async function approuver(demande) {
  actionLoading.value = demande.id
  try {
    const { data } = await api.patch(`/validateur/demandes/${demande.id}/approuver`)
    const idx = demandes.value.findIndex(d => d.id === demande.id)
    if (idx !== -1) demandes.value[idx] = data.demande
  } catch (e) {
    alert(e.response?.data?.message ?? 'Erreur lors de l\'approbation.')
  } finally {
    actionLoading.value = null
  }
}

function openRefus(demande) {
  refusTarget.value    = demande
  refusMotif.value     = ''
  refusError.value     = null
  showRefusModal.value = true
}

async function submitRefus() {
  if (refusMotif.value.trim().length < 5) {
    refusError.value = 'Le motif doit contenir au moins 5 caractères.'
    return
  }
  refusLoading.value = true
  refusError.value   = null
  try {
    const { data } = await api.patch(`/validateur/demandes/${refusTarget.value.id}/refuse`, {
      motif_refus: refusMotif.value,
    })
    const idx = demandes.value.findIndex(d => d.id === refusTarget.value.id)
    if (idx !== -1) demandes.value[idx] = data.demande
    showRefusModal.value = false
  } catch (e) {
    refusError.value = e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    refusLoading.value = false
  }
}

function voirJustificatif(id) {
  const token = sessionStorage.getItem('auth_token')
  fetch(`/api/validateur/demandes/${id}/justificatif`, {
    headers: { Authorization: `Bearer ${token}` },
  }).then(r => r.blob()).then(blob => {
    window.open(URL.createObjectURL(blob), '_blank')
  })
}
</script>

<template>
  <ValidateurLayout>

    <!-- En-tête -->
    <div class="mb-8 fade-in-up">
      <p class="text-xs font-medium tracking-[0.2em] uppercase mb-1" style="color:#8C7A6B;">Validation</p>
      <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">Demandes de certification</h1>
    </div>

    <!-- Filtres -->
    <div class="flex gap-2 flex-wrap mb-6 fade-in-up delay-100">
      <button v-for="f in [
        { key:'en_attente', label:'En attente' },
        { key:'approuvee',  label:'Approuvées' },
        { key:'refusee',    label:'Refusées' },
        { key:'tous',       label:'Toutes' },
      ]" :key="f.key"
              @click="filterStatut = f.key"
              :class="['text-xs px-4 py-2 rounded-full border transition-all',
                filterStatut === f.key
                  ? 'bg-brown-dark text-cream border-brown-dark shadow-sm'
                  : 'bg-cream text-taupe border-sand hover:border-brown']">
        {{ f.label }}
        <span class="ml-1.5 opacity-60">({{ counts[f.key] }})</span>
      </button>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 rounded-full animate-spin" style="border-color:#E8DCCB;border-top-color:#4a6640;"></div>
    </div>

    <!-- Liste -->
    <div v-else class="space-y-4 fade-in-up delay-200">
      <article v-for="d in filtered" :key="d.id" class="card-premium p-5 md:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
          <div class="flex-1 min-w-0">

            <!-- Statut -->
            <div class="flex items-center gap-2 flex-wrap mb-2">
              <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                    :style="`color:${statutStyle[d.statut]?.color};background:${statutStyle[d.statut]?.bg};border:1px solid ${statutStyle[d.statut]?.border};`">
                {{ statutStyle[d.statut]?.label }}
              </span>
            </div>

            <p class="font-display font-semibold text-brown-dark">{{ d.user?.prenom }} {{ d.user?.nom }}</p>
            <p class="text-xs text-taupe">{{ d.user?.email }}</p>
            <p v-if="d.user?.nom_institution" class="text-xs text-taupe">{{ d.user.nom_institution }}</p>

            <div class="flex gap-4 mt-3 text-xs">
              <div><span class="text-taupe">NINEA : </span><span class="font-medium text-brown-dark font-mono">{{ d.ninea }}</span></div>
              <div><span class="text-taupe">RCCM : </span><span class="font-medium text-brown-dark font-mono">{{ d.rccm }}</span></div>
            </div>
            <p class="text-xs text-taupe mt-2">Soumise le {{ formatDate(d.created_at) }}</p>

            <!-- Justificatif -->
            <button v-if="d.fichier_preuve" @click="voirJustificatif(d.id)"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                    style="background:rgba(74,55,44,0.08);color:#4A372C;border:1px solid rgba(74,55,44,0.2);">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
              </svg>
              Voir le justificatif
            </button>

            <!-- Motif refus -->
            <div v-if="d.motif_refus" class="mt-3 p-3 rounded-lg text-xs leading-relaxed"
                 style="background:rgba(181,83,60,0.06);color:#8c3520;border:1px solid rgba(181,83,60,0.2);">
              <strong>Motif du refus :</strong> {{ d.motif_refus }}
            </div>
          </div>

          <!-- Actions -->
          <div v-if="d.statut === 'en_attente'" class="flex flex-col gap-2 flex-shrink-0">
            <button @click="approuver(d)" :disabled="actionLoading === d.id"
                    class="text-xs px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
                    style="background:rgba(74,102,64,0.12);color:#4a6640;border:1px solid rgba(74,102,64,0.3);">
              <span v-if="actionLoading === d.id"
                    class="w-3 h-3 border border-current border-t-transparent rounded-full animate-spin"></span>
              ✓ Approuver
            </button>
            <button @click="openRefus(d)"
                    class="text-xs px-4 py-2 rounded-lg font-medium transition-colors"
                    style="background:rgba(181,83,60,0.08);color:#8c3520;border:1px solid rgba(181,83,60,0.25);">
              ✕ Refuser
            </button>
          </div>
        </div>
      </article>

      <div v-if="!filtered.length" class="card-premium p-12 text-center">
        <p class="text-taupe text-sm">Aucune demande {{ filterStatut !== 'tous' ? filterStatut.replace('_', ' ') : '' }}.</p>
      </div>
    </div>

  </ValidateurLayout>

  <!-- Modal refus -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showRefusModal" class="fixed inset-0 z-50 flex items-center justify-center p-5"
           @click.self="showRefusModal = false">
        <div class="absolute inset-0 backdrop-blur-sm" style="background:rgba(74,55,44,0.2);"></div>
        <div class="relative w-full max-w-md card-premium p-7 z-10">
          <h2 class="font-display font-semibold text-xl text-brown-dark mb-1">Refuser la demande</h2>
          <p class="text-sm text-taupe mb-5">
            {{ refusTarget?.user?.prenom }} {{ refusTarget?.user?.nom }} — indiquez le motif du refus.
          </p>
          <div v-if="refusError" class="mb-4 p-3 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.07);color:#8c3520;border:1px solid rgba(181,83,60,0.2);">
            {{ refusError }}
          </div>
          <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Motif *</label>
          <textarea v-model="refusMotif" rows="4"
                    placeholder="Expliquez pourquoi la demande est refusée…"
                    class="input-field resize-none mb-5"></textarea>
          <div class="flex gap-3">
            <button @click="submitRefus" :disabled="refusLoading"
                    class="flex-1 btn-primary" style="background:#B5533C;">
              <span v-if="!refusLoading">Confirmer le refus</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
              </span>
            </button>
            <button @click="showRefusModal = false" class="btn-secondary px-5">Annuler</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,.modal-fade-leave-active{transition:all 0.25s ease}
.modal-fade-enter-from,.modal-fade-leave-to{opacity:0}
</style>
