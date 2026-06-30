<script setup>
import { ref, onMounted, computed } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth    = useAuthStore()
const onglet  = ref('admins') // 'admins' | 'validateurs'
const loading = ref(true)

const admins      = ref([])
const validateurs = ref([])

const showModal     = ref(false)
const modalRole     = ref('admin') // 'admin' | 'validateur'
const createLoading = ref(false)
const createError   = ref(null)
const createSuccess = ref(false)

const form = ref({ prenom: '', nom: '', email: '', password: '', telephone: '' })

onMounted(async () => {
  try {
    const [a, v] = await Promise.all([
      api.get('/admin/admins'),
      api.get('/admin/validateurs'),
    ])
    admins.value      = a.data
    validateurs.value = v.data
  } finally {
    loading.value = false
  }
})

const listeActive = computed(() => onglet.value === 'admins' ? admins.value : validateurs.value)

function openModal(role) {
  modalRole.value     = role
  Object.keys(form.value).forEach(k => form.value[k] = '')
  createError.value   = null
  createSuccess.value = false
  showModal.value     = true
}

async function submitCreate() {
  createLoading.value = true
  createError.value   = null
  try {
    const url = modalRole.value === 'admin' ? '/admin/admins' : '/admin/validateurs'
    const { data } = await api.post(url, form.value)
    if (modalRole.value === 'admin') admins.value.unshift(data)
    else validateurs.value.unshift(data)
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

async function toggleValidateur(v) {
  try {
    const { data } = await api.patch(`/admin/validateurs/${v.id}/toggle`)
    const idx = validateurs.value.findIndex(x => x.id === v.id)
    if (idx !== -1) validateurs.value[idx] = { ...validateurs.value[idx], is_active: data.is_active }
  } catch (_) {}
}

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d))
}
</script>

<template>
  <AdminLayout>

    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 fade-in-up">
      <div>
        <p class="text-xs font-display font-medium tracking-[0.2em] uppercase mb-1" style="color:#8C7A6B;">
          Administration
        </p>
        <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">Équipe</h1>
        <p class="text-sm mt-1" style="color:#8C7A6B;">
          Gérez les administrateurs et les responsables de validation
        </p>
      </div>
      <button @click="openModal(onglet === 'admins' ? 'admin' : 'validateur')"
              class="btn-primary text-sm px-5 py-2.5 flex items-center gap-2 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        {{ onglet === 'admins' ? 'Nouvel administrateur' : 'Nouveau validateur' }}
      </button>
    </div>

    <!-- Onglets -->
    <div class="flex gap-1 p-1 rounded-xl mb-6" style="background:#E8DCCB;">
      <button v-for="tab in [
        { key:'admins',      label:'Administrateurs', count: admins.length },
        { key:'validateurs', label:'Validateurs',     count: validateurs.length },
      ]" :key="tab.key"
              @click="onglet = tab.key"
              class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-all"
              :style="onglet === tab.key
                ? 'background:#4A372C;color:#FBF7F0;box-shadow:0 2px 8px rgba(74,55,44,0.2);'
                : 'background:transparent;color:#8C7A6B;'">
        {{ tab.label }}
        <span class="text-xs px-1.5 py-0.5 rounded-full font-bold"
              :style="onglet === tab.key
                ? 'background:rgba(255,255,255,0.15);color:#FBF7F0;'
                : 'background:rgba(74,55,44,0.1);color:#8C7A6B;'">
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- Alerte admins -->
    <div v-if="onglet === 'admins'" class="mb-5 p-4 rounded-xl flex items-start gap-3"
         style="background:rgba(201,154,60,0.08);border:1px solid rgba(201,154,60,0.25);">
      <span style="color:#C99A3C;" class="flex-shrink-0 mt-0.5">⚠</span>
      <p class="text-sm" style="color:#3A2E26;line-height:1.6;">
        <strong>Accès complet.</strong> Les administrateurs gèrent la plateforme, les émetteurs et les validateurs.
      </p>
    </div>

    <!-- Info validateurs -->
    <div v-if="onglet === 'validateurs'" class="mb-5 p-4 rounded-xl flex items-start gap-3"
         style="background:rgba(124,144,112,0.08);border:1px solid rgba(124,144,112,0.25);">
      <span style="color:#7C9070;" class="flex-shrink-0 mt-0.5">ℹ</span>
      <p class="text-sm" style="color:#3A2E26;line-height:1.6;">
        Les validateurs reçoivent les demandes de certification et peuvent les approuver ou refuser.
        Ils n'ont pas accès à l'administration générale.
      </p>
    </div>

    <!-- Loader -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <!-- Liste -->
    <div v-else class="space-y-3 fade-in-up delay-100">
      <div v-for="u in listeActive" :key="u.id"
           class="card-premium p-5 flex items-center gap-4">

        <!-- Avatar -->
        <div class="w-11 h-11 rounded-full flex-shrink-0 flex items-center justify-center font-display font-semibold text-sm"
             :style="onglet === 'admins'
               ? 'background:#4A372C;color:#FBF7F0;'
               : (u.is_active ? 'background:#4a6640;color:#FBF7F0;' : 'background:#E8DCCB;color:#8C7A6B;')">
          {{ (u.prenom?.[0] ?? '') + (u.nom?.[0] ?? '') }}
        </div>

        <!-- Infos -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="font-display font-semibold" style="color:#3A2E26;">{{ u.prenom }} {{ u.nom }}</p>
            <span v-if="u.id === auth.user?.id"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  style="background:rgba(74,55,44,0.1);color:#4A372C;border:1px solid rgba(74,55,44,0.2);">
              Vous
            </span>
            <!-- Badge actif/inactif pour validateurs -->
            <span v-if="onglet === 'validateurs'"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :style="u.is_active
                    ? 'background:rgba(124,144,112,0.1);color:#4a6640;border:1px solid rgba(124,144,112,0.25);'
                    : 'background:rgba(181,83,60,0.08);color:#8c3520;border:1px solid rgba(181,83,60,0.25);'">
              {{ u.is_active ? 'Actif' : 'Inactif' }}
            </span>
          </div>
          <p class="text-xs mt-0.5" style="color:#8C7A6B;">{{ u.email }}</p>
          <p v-if="u.telephone" class="text-xs" style="color:#8C7A6B;">{{ u.telephone }}</p>
        </div>

        <!-- Date + action validateur -->
        <div class="flex items-center gap-3 flex-shrink-0">
          <div class="text-right hidden sm:block">
            <p class="text-xs" style="color:#8C7A6B;">Créé le</p>
            <p class="text-xs font-medium" style="color:#3A2E26;">{{ formatDate(u.created_at) }}</p>
          </div>
          <button v-if="onglet === 'validateurs' && u.id !== auth.user?.id"
                  @click="toggleValidateur(u)"
                  class="text-xs px-3 py-1.5 rounded-lg border transition-colors"
                  :style="u.is_active
                    ? 'border-color:rgba(181,83,60,0.3);color:#B5533C;'
                    : 'border-color:rgba(124,144,112,0.3);color:#7C9070;'">
            {{ u.is_active ? 'Désactiver' : 'Activer' }}
          </button>
        </div>
      </div>

      <div v-if="!listeActive.length" class="card-premium p-12 text-center">
        <p class="text-sm" style="color:#8C7A6B;">
          {{ onglet === 'admins' ? 'Aucun administrateur.' : 'Aucun validateur. Créez-en un pour gérer les demandes de certification.' }}
        </p>
      </div>
    </div>

  </AdminLayout>

  <!-- Modal création -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-5"
           @click.self="showModal = false">
        <div class="absolute inset-0 backdrop-blur-sm" style="background:rgba(74,55,44,0.2);"></div>

        <div class="relative w-full max-w-md card-premium p-7 z-10">

          <Transition name="reveal">
            <div v-if="createSuccess" class="text-center py-4">
              <div class="w-14 h-14 rounded-full mx-auto mb-4 flex items-center justify-center"
                   style="background:rgba(124,144,112,0.15);">
                <span style="color:#7C9070;font-size:1.75rem;">✓</span>
              </div>
              <p class="font-display font-semibold text-lg" style="color:#3A2E26;">
                {{ modalRole === 'admin' ? 'Administrateur' : 'Validateur' }} créé
              </p>
              <p class="text-sm mt-1" style="color:#8C7A6B;">Le compte a été créé avec succès.</p>
            </div>
          </Transition>

          <div v-if="!createSuccess">
            <h2 class="font-display font-semibold text-xl mb-1" style="color:#3A2E26;">
              {{ modalRole === 'admin' ? 'Nouvel administrateur' : 'Nouveau validateur' }}
            </h2>
            <p class="text-sm mb-5" style="color:#8C7A6B;">
              {{ modalRole === 'admin'
                ? 'Ce compte aura un accès complet à l\'administration.'
                : 'Ce compte pourra approuver ou refuser les demandes de certification.' }}
            </p>

            <div v-if="createError" class="mb-4 p-3 rounded-xl text-sm"
                 style="background:rgba(181,83,60,0.07);color:#8c3520;border:1px solid rgba(181,83,60,0.2);">
              {{ createError }}
            </div>

            <form @submit.prevent="submitCreate" class="space-y-4">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">Prénom *</label>
                  <input v-model="form.prenom" type="text" class="input-field" required />
                </div>
                <div>
                  <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">Nom *</label>
                  <input v-model="form.nom" type="text" class="input-field" required />
                </div>
              </div>
              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">Email *</label>
                <input v-model="form.email" type="email" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">Téléphone</label>
                <input v-model="form.telephone" type="tel" class="input-field" placeholder="+228 90 00 00 00" />
              </div>
              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Mot de passe * <span class="normal-case opacity-70">(min. 8 car., 1 majuscule, 1 spécial)</span>
                </label>
                <input v-model="form.password" type="password" class="input-field" required />
              </div>
              <div class="flex gap-3 pt-2">
                <button type="submit" :disabled="createLoading" class="flex-1 btn-primary">
                  <span v-if="!createLoading">Créer le compte</span>
                  <span v-else class="flex items-center justify-center gap-2">
                    <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                    Création…
                  </span>
                </button>
                <button type="button" @click="showModal = false" class="btn-secondary px-5">Annuler</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-fade-enter-active,.modal-fade-leave-active{transition:all 0.25s ease}
.modal-fade-enter-from,.modal-fade-leave-to{opacity:0}
.reveal-enter-active{transition:all 0.3s ease}
.reveal-enter-from{opacity:0;transform:scale(0.95)}
</style>
