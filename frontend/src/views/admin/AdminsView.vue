<script setup>
/**
 * Page admin — gestion des comptes administrateurs.
 * Un admin peut créer d'autres admins.
 * Route : /admin/admins
 */
import { ref, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth    = useAuthStore()
const admins  = ref([])
const loading = ref(true)

// Modal création
const showModal    = ref(false)
const createLoading = ref(false)
const createError   = ref(null)
const createSuccess = ref(false)

const form = ref({
  prenom: '', nom: '', email: '', password: '', telephone: '',
})

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/admins')
    admins.value = data
  } finally {
    loading.value = false
  }
})

function openModal() {
  Object.keys(form.value).forEach(k => form.value[k] = '')
  createError.value   = null
  createSuccess.value = false
  showModal.value     = true
}

async function submitCreate() {
  createLoading.value = true
  createError.value   = null
  try {
    const { data } = await api.post('/admin/admins', form.value)
    admins.value.unshift(data)
    createSuccess.value = true
    setTimeout(() => { showModal.value = false }, 1400)
  } catch (e) {
    const errors = e.response?.data?.errors
    createError.value = errors
      ? Object.values(errors).flat().join(' ')
      : e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    createLoading.value = false
  }
}

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
  }).format(new Date(d))
}
</script>

<template>
  <AppLayout>

    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 fade-in-up">
      <div>
        <p class="text-xs font-display font-medium tracking-[0.2em] uppercase mb-1"
           style="color:#8C7A6B;">
          Administration
        </p>
        <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">
          Administrateurs
        </h1>
        <p class="text-sm mt-1" style="color:#8C7A6B;">
          {{ admins.length }} administrateur{{ admins.length > 1 ? 's' : '' }} au total
        </p>
      </div>
      <button @click="openModal" class="btn-primary text-sm px-5 py-2.5 flex items-center gap-2 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nouvel administrateur
      </button>
    </div>

    <!-- Avertissement sécurité -->
    <div class="mb-6 p-4 rounded-xl flex items-start gap-3 fade-in-up"
         style="background:rgba(201,154,60,0.08); border:1px solid rgba(201,154,60,0.25);">
      <span style="color:#C99A3C;" class="flex-shrink-0 mt-0.5">⚠</span>
      <p class="text-sm" style="color:#3A2E26; line-height:1.6;">
        <strong>Accès restreint.</strong> Les comptes administrateurs ont un accès complet à la plateforme.
        Ne créez des comptes admin que pour des personnes de confiance.
      </p>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <!-- Liste des admins -->
    <div v-else class="space-y-3 fade-in-up delay-100">
      <div v-for="admin in admins" :key="admin.id"
           class="card-premium p-5 flex items-center gap-4">

        <!-- Avatar initiales -->
        <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center
                    font-display font-semibold text-sm"
             style="background:#4A372C; color:#FBF7F0;">
          {{ (admin.prenom?.[0] ?? '') + (admin.nom?.[0] ?? '') }}
        </div>

        <!-- Infos -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-display font-semibold" style="color:#3A2E26;">
              {{ admin.prenom }} {{ admin.nom }}
            </p>
            <!-- Badge "Vous" pour l'admin connecté -->
            <span v-if="admin.id === auth.user?.id"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  style="background:rgba(74,55,44,0.1); color:#4A372C; border:1px solid rgba(74,55,44,0.2);">
              Vous
            </span>
          </div>
          <p class="text-xs mt-0.5" style="color:#8C7A6B;">{{ admin.email }}</p>
          <p v-if="admin.telephone" class="text-xs" style="color:#8C7A6B;">{{ admin.telephone }}</p>
        </div>

        <!-- Date de création -->
        <div class="text-right flex-shrink-0 hidden sm:block">
          <p class="text-xs" style="color:#8C7A6B;">Créé le</p>
          <p class="text-xs font-medium" style="color:#3A2E26;">{{ formatDate(admin.created_at) }}</p>
        </div>

      </div>

      <!-- Vide -->
      <div v-if="!admins.length" class="card-premium p-12 text-center">
        <p class="text-sm" style="color:#8C7A6B;">Aucun administrateur trouvé.</p>
      </div>
    </div>

  </AppLayout>

  <!-- Modal création admin -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-5"
           @click.self="showModal = false">

        <div class="absolute inset-0 backdrop-blur-sm"
             style="background:rgba(74,55,44,0.2);"></div>

        <div class="relative w-full max-w-md card-premium p-7 z-10">

          <!-- Succès -->
          <Transition name="reveal">
            <div v-if="createSuccess" class="text-center py-4">
              <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center"
                   style="background:rgba(124,144,112,0.15);">
                <span style="color:#7C9070; font-size:1.75rem;">✓</span>
              </div>
              <p class="font-display font-semibold text-lg" style="color:#3A2E26;">Administrateur créé</p>
              <p class="text-sm mt-1" style="color:#8C7A6B;">Le compte a été créé avec succès.</p>
            </div>
          </Transition>

          <!-- Formulaire -->
          <div v-if="!createSuccess">
            <h2 class="font-display font-semibold text-xl mb-1" style="color:#3A2E26;">
              Nouvel administrateur
            </h2>
            <p class="text-sm mb-5" style="color:#8C7A6B;">
              Ce compte aura un accès complet à l'administration de DocVerify.
            </p>

            <div v-if="createError"
                 class="mb-4 p-3 rounded-xl text-sm"
                 style="background:rgba(181,83,60,0.07); color:#8c3520;
                        border:1px solid rgba(181,83,60,0.2);">
              {{ createError }}
            </div>

            <form @submit.prevent="submitCreate" class="space-y-4">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium uppercase tracking-wide mb-1.5"
                         style="color:#8C7A6B;">Prénom *</label>
                  <input v-model="form.prenom" type="text" class="input-field" required />
                </div>
                <div>
                  <label class="block text-xs font-medium uppercase tracking-wide mb-1.5"
                         style="color:#8C7A6B;">Nom *</label>
                  <input v-model="form.nom" type="text" class="input-field" required />
                </div>
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5"
                       style="color:#8C7A6B;">Email *</label>
                <input v-model="form.email" type="email" class="input-field" required />
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5"
                       style="color:#8C7A6B;">Téléphone</label>
                <input v-model="form.telephone" type="tel" class="input-field"
                       placeholder="+228 90 00 00 00" />
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5"
                       style="color:#8C7A6B;">Mot de passe * <span class="normal-case opacity-70">(min. 8 car.)</span></label>
                <input v-model="form.password" type="password" class="input-field" required />
              </div>

              <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="createLoading" class="flex-1 btn-primary">
                  <span v-if="!createLoading">Créer le compte admin</span>
                  <span v-else class="flex items-center justify-center gap-2">
                    <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                    Création…
                  </span>
                </button>
                <button type="button" @click="showModal = false" class="btn-secondary px-5">
                  Annuler
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to       { opacity: 0; }
.reveal-enter-active { transition: all 0.3s ease; }
.reveal-enter-from   { opacity: 0; transform: scale(0.95); }
</style>
