<script setup>
import { ref } from 'vue'
import ValidateurLayout from '@/components/ValidateurLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth = useAuthStore()

const mdp     = ref({ current_password: '', password: '', password_confirmation: '' })
const mdpMsg  = ref(null)
const mdpErr  = ref(null)
const saving  = ref(false)

async function saveMdp() {
  mdpMsg.value = null
  mdpErr.value = null
  saving.value = true
  try {
    const { data } = await api.put('/change-password', mdp.value)
    mdpMsg.value = data.message
    mdp.value = { current_password: '', password: '', password_confirmation: '' }
  } catch (e) {
    mdpErr.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors du changement.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <ValidateurLayout max-width="max-w-2xl">

    <div class="mb-8">
      <p class="text-xs font-medium tracking-[0.2em] uppercase mb-1" style="color:#8C7A6B;">Validation</p>
      <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">Mon profil</h1>
    </div>

    <!-- Infos compte -->
    <div class="card-premium p-7 mb-6">
      <div class="flex items-center gap-4 pb-5 mb-5" style="border-bottom:1px solid #E8DCCB;">
        <div class="w-14 h-14 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0"
             style="background:#1A2E20;color:#86C670;">
          {{ (auth.user?.prenom?.[0] ?? '') + (auth.user?.nom?.[0] ?? '') }}
        </div>
        <div>
          <p class="font-display font-semibold text-lg" style="color:#3A2E26;">
            {{ auth.user?.prenom }} {{ auth.user?.nom }}
          </p>
          <p class="text-xs mt-0.5" style="color:#8C7A6B;">{{ auth.user?.email }}</p>
          <span class="inline-block mt-1 text-xs px-2.5 py-1 rounded-full font-medium"
                style="background:rgba(74,102,64,0.1);color:#4a6640;border:1px solid rgba(74,102,64,0.2);">
            Responsable de validation
          </span>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <p class="text-xs uppercase tracking-wide mb-1" style="color:#8C7A6B;">Prénom</p>
          <p class="font-medium" style="color:#3A2E26;">{{ auth.user?.prenom ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs uppercase tracking-wide mb-1" style="color:#8C7A6B;">Nom</p>
          <p class="font-medium" style="color:#3A2E26;">{{ auth.user?.nom ?? '—' }}</p>
        </div>
        <div class="col-span-2">
          <p class="text-xs uppercase tracking-wide mb-1" style="color:#8C7A6B;">Email</p>
          <p class="font-medium" style="color:#3A2E26;">{{ auth.user?.email ?? '—' }}</p>
        </div>
        <div v-if="auth.user?.telephone" class="col-span-2">
          <p class="text-xs uppercase tracking-wide mb-1" style="color:#8C7A6B;">Téléphone</p>
          <p class="font-medium" style="color:#3A2E26;">{{ auth.user.telephone }}</p>
        </div>
      </div>
    </div>

    <!-- Changer mot de passe -->
    <div class="card-premium p-7">
      <h2 class="font-semibold text-base mb-1" style="color:#3A2E26;">Changer le mot de passe</h2>
      <p class="text-xs mb-5" style="color:#8C7A6B;">
        Minimum 8 caractères, une majuscule et un caractère spécial.
      </p>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Mot de passe actuel</label>
          <input v-model="mdp.current_password" type="password" autocomplete="current-password" class="input-field" />
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Nouveau mot de passe</label>
          <input v-model="mdp.password" type="password" autocomplete="new-password" class="input-field" />
        </div>
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Confirmer</label>
          <input v-model="mdp.password_confirmation" type="password" autocomplete="new-password" class="input-field" />
        </div>
      </div>

      <div v-if="mdpMsg" class="mt-4 p-3 rounded-xl text-sm"
           style="background:rgba(74,139,74,0.08);color:#2d6a2d;border:1px solid rgba(74,139,74,0.2);">
        ✓ {{ mdpMsg }}
      </div>
      <div v-if="mdpErr" class="mt-4 p-3 rounded-xl text-sm"
           style="background:rgba(181,83,60,0.08);color:#B5533C;border:1px solid rgba(181,83,60,0.2);">
        {{ mdpErr }}
      </div>

      <button @click="saveMdp" :disabled="saving" class="btn-primary mt-5">
        {{ saving ? 'Modification…' : 'Modifier le mot de passe' }}
      </button>
    </div>

  </ValidateurLayout>
</template>
