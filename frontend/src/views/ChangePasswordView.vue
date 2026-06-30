<script setup>
import { ref, computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import AdminLayout from '@/components/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth    = useAuthStore()
const isAdmin = computed(() => auth.user?.role === 'admin')

const form    = ref({ current_password: '', password: '', password_confirmation: '' })
const loading = ref(false)
const success = ref(null)
const errMsg  = ref(null)

// Retour adapté selon le rôle
const retourRoute = computed(() => {
  if (auth.user?.role === 'admin')      return '/admin/parametres'
  if (auth.user?.role === 'validateur') return '/validateur'
  return '/dashboard'
})

async function submit() {
  success.value = null
  errMsg.value  = null
  loading.value = true
  try {
    const { data } = await api.put('/change-password', form.value)
    success.value = data.message
    form.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (e) {
    errMsg.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors du changement.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <!-- Layout adapté selon le rôle -->
  <AdminLayout v-if="isAdmin" max-width="max-w-md">
    <template #default>
      <RouterLink :to="retourRoute"
                  class="inline-flex items-center gap-2 text-xs mb-6 no-underline transition-colors"
                  style="color:#8C7A6B;">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
        </svg>
        Retour aux paramètres
      </RouterLink>
      <PasswordForm :form="form" :loading="loading" :success="success" :err-msg="errMsg" @submit="submit" />
    </template>
  </AdminLayout>

  <AppLayout v-else max-width="max-w-md">
    <RouterLink :to="retourRoute"
                class="inline-flex items-center gap-2 text-xs mb-6 no-underline transition-colors"
                style="color:#8C7A6B;">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
      </svg>
      Retour au tableau de bord
    </RouterLink>
    <PasswordForm :form="form" :loading="loading" :success="success" :err-msg="errMsg" @submit="submit" />
  </AppLayout>
</template>

<script>
// Composant interne réutilisable pour le formulaire
const PasswordForm = {
  props: {
    form:    Object,
    loading: Boolean,
    success: String,
    errMsg:  String,
  },
  emits: ['submit'],
  template: `
    <div>
      <div class="mb-6">
        <p style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.18em;color:#8C7A6B;margin-bottom:4px;">
          Mon compte
        </p>
        <h1 style="font-family:'Cormorant',Georgia,serif;font-size:2rem;font-weight:300;color:#4A372C;">
          Changer mon mot de passe
        </h1>
      </div>

      <div style="background:#FBF7F0;border:1px solid rgba(217,198,168,0.6);border-radius:16px;padding:1.75rem;box-shadow:0 2px 16px rgba(74,55,44,0.06);">
        <p style="font-size:0.8rem;color:#8C7A6B;margin-bottom:1.25rem;">
          Minimum 8 caractères, une majuscule et un caractère spécial.
        </p>

        <form @submit.prevent="$emit('submit')" class="space-y-4" novalidate>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:600;color:#4A372C;margin-bottom:5px;">
              Mot de passe actuel
            </label>
            <input :value="form.current_password"
                   @input="form.current_password = $event.target.value"
                   type="password" autocomplete="current-password"
                   style="width:100%;border:1px solid rgba(217,198,168,0.8);border-radius:8px;padding:0.55rem 0.85rem;font-size:0.875rem;color:#3A2E26;background:#fff;outline:none;" />
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:600;color:#4A372C;margin-bottom:5px;">
              Nouveau mot de passe
            </label>
            <input :value="form.password"
                   @input="form.password = $event.target.value"
                   type="password" autocomplete="new-password" placeholder="••••••••"
                   style="width:100%;border:1px solid rgba(217,198,168,0.8);border-radius:8px;padding:0.55rem 0.85rem;font-size:0.875rem;color:#3A2E26;background:#fff;outline:none;" />
          </div>
          <div>
            <label style="display:block;font-size:0.78rem;font-weight:600;color:#4A372C;margin-bottom:5px;">
              Confirmer le nouveau mot de passe
            </label>
            <input :value="form.password_confirmation"
                   @input="form.password_confirmation = $event.target.value"
                   type="password" autocomplete="new-password" placeholder="••••••••"
                   style="width:100%;border:1px solid rgba(217,198,168,0.8);border-radius:8px;padding:0.55rem 0.85rem;font-size:0.875rem;color:#3A2E26;background:#fff;outline:none;" />
          </div>

          <div v-if="success"
               style="padding:0.7rem 1rem;border-radius:8px;background:rgba(74,139,74,0.1);color:#2d6a2d;font-size:0.82rem;">
            ✓ {{ success }}
          </div>
          <div v-if="errMsg"
               style="padding:0.7rem 1rem;border-radius:8px;background:rgba(181,83,60,0.1);color:#B5533C;font-size:0.82rem;">
            {{ errMsg }}
          </div>

          <button type="submit" :disabled="loading"
                  style="width:100%;background:#4A372C;color:#FBF7F0;border:none;border-radius:8px;padding:0.65rem 1.5rem;font-size:0.875rem;font-weight:500;cursor:pointer;transition:background 0.18s;"
                  :style="loading ? 'opacity:0.55;cursor:not-allowed;' : ''">
            {{ loading ? 'Modification…' : 'Modifier le mot de passe' }}
          </button>
        </form>
      </div>
    </div>
  `
}
</script>
