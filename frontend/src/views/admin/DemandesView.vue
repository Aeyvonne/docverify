<script setup>
/**
 * Page admin — gestion des demandes de certification émetteurs.
 * Route : /admin/demandes
 */
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/api/axios'

const demandes    = ref([])
const loading     = ref(true)
const filterStatut = ref('tous')

// Modal refus
const showRefusModal  = ref(false)
const refusTarget     = ref(null)
const refusMotif      = ref('')
const refusLoading    = ref(false)
const refusError      = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/demandes')
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

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
  }).format(new Date(d))
}

const statutStyle = {
  en_attente: { label: 'En attente', color: '#8a6010', bg: 'rgba(201,154,60,0.1)',   border: 'rgba(201,154,60,0.3)' },
  approuvee:  { label: 'Approuvée',  color: '#4a6640', bg: 'rgba(124,144,112,0.1)',  border: 'rgba(124,144,112,0.3)' },
  refusee:    { label: 'Refusée',    color: '#8c3520', bg: 'rgba(181,83,60,0.08)',    border: 'rgba(181,83,60,0.3)' },
}

// Ouvre le justificatif dans un nouvel onglet avec le token Bearer
function voirJustificatif(demandeId) {
  const token = localStorage.getItem('auth_token')
  // On passe par fetch pour envoyer l'auth header, puis on crée un blob URL
  fetch(`/api/admin/demandes/${demandeId}/justificatif`, {
    headers: { Authorization: `Bearer ${token}` },
  })
    .then(r => r.blob())
    .then(blob => {
      const url = URL.createObjectURL(blob)
      window.open(url, '_blank')
    })
}
async function certifierDepuisDemande(demande) {
  try {
    await api.patch(`/admin/emetteurs/${demande.user_id}/certify`)
    // Met à jour la demande localement
    const d = demandes.value.find(x => x.id === demande.id)
    if (d) d.statut = 'approuvee'
  } catch (e) {
    alert(e.response?.data?.message ?? 'Erreur lors de la certification.')
  }
}

function openRefus(demande) {
  refusTarget.value  = demande
  refusMotif.value   = ''
  refusError.value   = null
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
    const { data } = await api.patch(`/admin/demandes/${refusTarget.value.id}/refuse`, {
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
</script>

<template>
  <AdminLayout>

    <!-- En-tête -->
    <div class="mb-8 fade-in-up">
      <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">
        Administration
      </p>
      <h1 class="font-display font-semibold text-3xl text-brown-dark">Demandes de certification</h1>
    </div>

    <!-- Filtres -->
    <div class="flex gap-2 flex-wrap mb-6 fade-in-up delay-100">
      <button v-for="f in [
        { key:'tous',       label:'Toutes' },
        { key:'en_attente', label:'En attente' },
        { key:'approuvee',  label:'Approuvées' },
        { key:'refusee',    label:'Refusées' },
      ]" :key="f.key"
              @click="filterStatut = f.key"
              :class="[
                'text-xs px-4 py-2 rounded-full border transition-all',
                filterStatut === f.key
                  ? 'bg-brown-dark text-cream border-brown-dark shadow-sm'
                  : 'bg-cream text-taupe border-sand hover:border-brown hover:text-brown-dark',
              ]">
        {{ f.label }}
        <span class="ml-1.5 opacity-60">({{ counts[f.key] }})</span>
      </button>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <!-- Liste -->
    <div v-else class="space-y-4 fade-in-up delay-200">
      <article v-for="d in filtered" :key="d.id" class="card-premium p-5 md:p-6">

        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
          <div class="flex-1 min-w-0">

            <!-- Statut + nom émetteur -->
            <div class="flex items-center flex-wrap gap-2 mb-2">
              <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                    :style="{
                      color:  statutStyle[d.statut]?.color,
                      background: statutStyle[d.statut]?.bg,
                      border: '1px solid ' + statutStyle[d.statut]?.border,
                    }">
                {{ statutStyle[d.statut]?.label }}
              </span>
            </div>

            <p class="font-display font-semibold text-brown-dark">
              {{ d.user?.prenom }} {{ d.user?.nom }}
            </p>
            <p class="text-xs text-taupe">{{ d.user?.email }}</p>
            <p v-if="d.user?.nom_institution" class="text-xs text-taupe">
              {{ d.user.nom_institution }}
            </p>

            <!-- NINEA + RCCM -->
            <div class="flex gap-4 mt-3 text-xs">
              <div>
                <span class="text-taupe">NINEA : </span>
                <span class="font-medium text-brown-dark font-mono">{{ d.ninea }}</span>
              </div>
              <div>
                <span class="text-taupe">RCCM : </span>
                <span class="font-medium text-brown-dark font-mono">{{ d.rccm }}</span>
              </div>
            </div>

            <p class="text-xs text-taupe mt-2">Soumise le {{ formatDate(d.created_at) }}</p>

            <!-- Fichier justificatif -->
            <button v-if="d.fichier_preuve"
                    @click="voirJustificatif(d.id)"
                    class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-colors"
                    style="background:rgba(74,55,44,0.08); color:#4A372C; border:1px solid rgba(74,55,44,0.2);">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
              </svg>
              Voir le justificatif
            </button>

            <!-- Motif de refus -->
            <div v-if="d.motif_refus"
                 class="mt-3 p-3 rounded-lg text-xs leading-relaxed"
                 style="background:rgba(181,83,60,0.06); color:#8c3520; border:1px solid rgba(181,83,60,0.2);">
              <strong>Motif du refus :</strong> {{ d.motif_refus }}
            </div>
          </div>

          <!-- Actions (uniquement si en attente) -->
          <div v-if="d.statut === 'en_attente'"
               class="flex flex-col gap-2 flex-shrink-0">
            <button @click="certifierDepuisDemande(d)"
                    class="text-xs px-4 py-2 rounded-lg font-medium transition-colors"
                    style="background:rgba(124,144,112,0.12); color:#4a6640;
                           border:1px solid rgba(124,144,112,0.3);">
              ✓ Approuver
            </button>
            <button @click="openRefus(d)"
                    class="text-xs px-4 py-2 rounded-lg font-medium transition-colors"
                    style="background:rgba(181,83,60,0.08); color:#8c3520;
                           border:1px solid rgba(181,83,60,0.25);">
              ✕ Refuser
            </button>
          </div>
        </div>

      </article>

      <!-- Vide -->
      <div v-if="!filtered.length" class="card-premium p-12 text-center">
        <p class="text-taupe text-sm">Aucune demande{{ filterStatut !== 'tous' ? ' ' + filterStatut.replace('_', ' ') : '' }}.</p>
      </div>
    </div>

  </AdminLayout>

  <!-- Modal refus -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showRefusModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-5"
           @click.self="showRefusModal = false">
        <div class="absolute inset-0 backdrop-blur-sm"
             style="background:rgba(74,55,44,0.2);"></div>

        <div class="relative w-full max-w-md card-premium p-7 z-10">
          <h2 class="font-display font-semibold text-xl text-brown-dark mb-1">Refuser la demande</h2>
          <p class="text-sm text-taupe mb-5">
            {{ refusTarget?.user?.prenom }} {{ refusTarget?.user?.nom }} —
            indiquez le motif du refus (visible par l'émetteur).
          </p>

          <div v-if="refusError"
               class="mb-4 p-3 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.07); color:#8c3520; border:1px solid rgba(181,83,60,0.2);">
            {{ refusError }}
          </div>

          <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
            Motif du refus *
          </label>
          <textarea v-model="refusMotif" rows="4"
                    placeholder="Expliquez pourquoi la demande est refusée…"
                    class="input-field resize-none mb-5"></textarea>

          <div class="flex gap-3">
            <button @click="submitRefus" :disabled="refusLoading"
                    class="flex-1 btn-primary"
                    style="background:#B5533C;">
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
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to       { opacity: 0; }
</style>
