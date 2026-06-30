<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import api from '@/api/axios'

const emetteurs = ref([])
const loading   = ref(true)
const search    = ref('')

// Modal création
const showCreateModal = ref(false)
const createForm = ref({
  nom:'', prenom:'', email:'', password:'',
  telephone:'', nom_institution:'', type_institution:'', is_certified: false,
})
const createLoading = ref(false)
const createError   = ref(null)

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/emetteurs')
    emetteurs.value = data
  } finally {
    loading.value = false
  }
})

const filtered = computed(() =>
  search.value.trim()
    ? emetteurs.value.filter(e =>
        [e.nom, e.prenom, e.email, e.nom_institution].join(' ')
          .toLowerCase().includes(search.value.toLowerCase())
      )
    : emetteurs.value
)

async function toggleActive(emetteur) {
  try {
    const { data } = await api.patch(`/admin/emetteurs/${emetteur.id}/toggle`)
    const idx = emetteurs.value.findIndex(e => e.id === emetteur.id)
    if (idx !== -1) {
      emetteurs.value[idx] = { ...emetteurs.value[idx], is_active: data.is_active }
    }
  } catch (_) {}
}

async function certify(emetteur) {
  try {
    await api.patch(`/admin/emetteurs/${emetteur.id}/certify`)
    // Forcer la réactivité Vue en remplaçant l'objet entier
    const idx = emetteurs.value.findIndex(e => e.id === emetteur.id)
    if (idx !== -1) {
      emetteurs.value[idx] = { ...emetteurs.value[idx], is_certified: true }
    }
  } catch (_) {}
}

async function submitCreate() {
  createLoading.value = true
  createError.value   = null
  try {
    const { data } = await api.post('/admin/emetteurs', createForm.value)
    emetteurs.value.unshift(data)
    showCreateModal.value = false
    Object.keys(createForm.value).forEach(k =>
      createForm.value[k] = k === 'is_certified' ? false : ''
    )
  } catch (e) {
    const errors = e.response?.data?.errors
    createError.value = errors
      ? Object.values(errors).flat().join(' ')
      : e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    createLoading.value = false
  }
}
</script>

<template>
  <AdminLayout>

    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 fade-in-up">
      <div>
        <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">
          Administration
        </p>
        <h1 class="font-display font-semibold text-3xl text-brown-dark">Émetteurs</h1>
      </div>
      <button @click="showCreateModal = true"
              class="btn-primary text-sm px-5 py-2.5 flex items-center gap-2 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nouvel émetteur
      </button>
    </div>

    <!-- Recherche -->
    <div class="mb-5 fade-in-up delay-100">
      <input v-model="search" type="text"
             placeholder="Rechercher un émetteur…"
             class="input-field max-w-sm" />
    </div>

    <!-- Résumé rapide -->
    <div class="flex gap-4 mb-4 fade-in-up delay-100" v-if="!loading">
      <div class="flex items-center gap-2 text-sm" style="color:#8C7A6B;">
        <span class="w-2.5 h-2.5 rounded-full" style="background:#4a6640;"></span>
        <span><strong style="color:#3A2E26;">{{ emetteurs.filter(e => e.is_certified).length }}</strong> certifié{{ emetteurs.filter(e => e.is_certified).length > 1 ? 's' : '' }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm" style="color:#8C7A6B;">
        <span class="w-2.5 h-2.5 rounded-full" style="background:#D9C6A8;"></span>
        <span><strong style="color:#3A2E26;">{{ emetteurs.filter(e => !e.is_certified).length }}</strong> en attente</span>
      </div>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <!-- Liste -->
    <div v-else class="space-y-3 fade-in-up delay-200">
      <div v-for="e in filtered" :key="e.id"
           class="card-premium p-5 flex flex-col sm:flex-row sm:items-center gap-4">

        <!-- Avatar initiales — vert si certifié, beige sinon -->
        <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center
                    font-display font-semibold text-sm"
             :style="e.is_certified
               ? 'background:#4a6640; color:#FBF7F0;'
               : 'background:#E8DCCB; color:#6B4F3F;'">
          {{ (e.prenom?.[0] ?? '') + (e.nom?.[0] ?? '') }}
        </div>

        <!-- Infos -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center flex-wrap gap-2 mb-1">
            <p class="font-display font-semibold text-brown-dark">{{ e.prenom }} {{ e.nom }}</p>

            <!-- Badge certifié — plus visible avec checkmark -->
            <span v-if="e.is_certified"
                  class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-semibold"
                  style="background:#4a6640; color:#FBF7F0;">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
              </svg>
              Certifié
            </span>

            <!-- Badge actif/inactif -->
            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :style="e.is_active
                    ? 'background:rgba(124,144,112,0.1);color:#4a6640;border:1px solid rgba(124,144,112,0.25);'
                    : 'background:rgba(181,83,60,0.08);color:#8c3520;border:1px solid rgba(181,83,60,0.25);'">
              {{ e.is_active ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          <p class="text-xs text-taupe">{{ e.email }}</p>
          <p class="text-xs text-taupe">
            {{ e.nom_institution ?? '—' }}
            <span v-if="e.type_institution"> · {{ e.type_institution }}</span>
          </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2 flex-shrink-0">
          <button @click="toggleActive(e)"
                  class="text-xs px-3 py-1.5 rounded-lg border transition-colors"
                  :style="e.is_active
                    ? 'border-color:rgba(181,83,60,0.3);color:#B5533C;'
                    : 'border-color:rgba(124,144,112,0.3);color:#7C9070;'">
            {{ e.is_active ? 'Désactiver' : 'Activer' }}
          </button>
          <button v-if="!e.is_certified" @click="certify(e)"
                  class="text-xs px-3 py-1.5 rounded-lg border border-sand text-taupe hover:bg-beige-medium transition-colors">
            Certifier
          </button>
        </div>
      </div>

      <!-- Vide -->
      <div v-if="!filtered.length" class="card-premium p-12 text-center">
        <p class="text-taupe text-sm">Aucun émetteur trouvé.</p>
      </div>
    </div>

  </AdminLayout>

  <!-- Modal créer un émetteur -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showCreateModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-5 overflow-y-auto"
           @click.self="showCreateModal = false">
        <div class="absolute inset-0 backdrop-blur-sm"
             style="background:rgba(74,55,44,0.2);"></div>

        <div class="relative w-full max-w-lg card-premium p-7 z-10 my-5">
          <h2 class="font-display font-semibold text-xl text-brown-dark mb-5">Créer un émetteur</h2>

          <div v-if="createError"
               class="mb-4 p-3 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.07);color:#8c3520;border:1px solid rgba(181,83,60,0.2);">
            {{ createError }}
          </div>

          <form @submit.prevent="submitCreate" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Prénom *</label>
                <input v-model="createForm.prenom" type="text" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Nom *</label>
                <input v-model="createForm.nom" type="text" class="input-field" required />
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Email *</label>
              <input v-model="createForm.email" type="email" class="input-field" required />
            </div>
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Mot de passe *</label>
              <input v-model="createForm.password" type="password" class="input-field" required />
            </div>
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Institution</label>
              <input v-model="createForm.nom_institution" type="text" class="input-field" />
            </div>
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Type</label>
              <input v-model="createForm.type_institution" type="text"
                     placeholder="université, lycée, entreprise…" class="input-field" />
            </div>
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-1.5">Téléphone</label>
              <input v-model="createForm.telephone" type="tel" class="input-field" />
            </div>
            <div class="flex items-center gap-3">
              <input v-model="createForm.is_certified" type="checkbox" id="cert_check"
                     class="w-4 h-4 rounded" />
              <label for="cert_check" class="text-sm text-brown-dark cursor-pointer">
                Certifier immédiatement
              </label>
            </div>
            <div class="flex gap-3 pt-2">
              <button type="submit" :disabled="createLoading" class="flex-1 btn-primary">
                <span v-if="!createLoading">Créer le compte</span>
                <span v-else class="flex items-center justify-center gap-2">
                  <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                  Création…
                </span>
              </button>
              <button type="button" @click="showCreateModal = false" class="btn-secondary px-5">
                Annuler
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to       { opacity: 0; }
</style>
