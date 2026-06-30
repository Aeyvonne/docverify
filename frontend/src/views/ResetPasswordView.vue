<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink, useRouter, useRoute } from 'vue-router'
import api from '@/api/axios'

const route  = useRoute()
const router = useRouter()

const form = ref({
  token:                 '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})
const loading = ref(false)
const success  = ref(null)
const errMsg   = ref(null)

onMounted(() => {
  form.value.token = route.query.token ?? ''
  form.value.email = route.query.email ?? ''
})

async function submit() {
  loading.value = true
  success.value = null
  errMsg.value  = null
  try {
    const { data } = await api.post('/reset-password', form.value)
    success.value = data.message
    setTimeout(() => router.push({ name: 'login' }), 2500)
  } catch (e) {
    errMsg.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Lien invalide ou expiré.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4" style="background:#F2E9DE;">
    <div class="w-full max-w-sm">

      <RouterLink to="/" class="block mb-8 text-center">
        <span style="font-family:'Cormorant',Georgia,serif; font-size:2rem; font-weight:300; color:#4A372C; letter-spacing:0.12em;">
          DocVerify
        </span>
      </RouterLink>

      <div class="card">
        <h1 class="form-title">Nouveau mot de passe</h1>
        <p class="form-sub">Choisissez un mot de passe sécurisé (min. 8 caractères, 1 majuscule, 1 caractère spécial).</p>

        <div v-if="success" class="msg-success mt-4">
          ✓ {{ success }}<br>
          <span class="text-xs mt-1 block" style="color:#2d6a2d;">Redirection vers la connexion…</span>
        </div>

        <form v-else @submit.prevent="submit" class="mt-5 space-y-4" novalidate>
          <div>
            <label class="field-label">Email</label>
            <input v-model="form.email" type="email" class="field-input"
                   autocomplete="email" placeholder="votre@email.com" />
          </div>
          <div>
            <label class="field-label">Nouveau mot de passe</label>
            <input v-model="form.password" type="password" class="field-input"
                   autocomplete="new-password" placeholder="••••••••" />
          </div>
          <div>
            <label class="field-label">Confirmer le mot de passe</label>
            <input v-model="form.password_confirmation" type="password" class="field-input"
                   autocomplete="new-password" placeholder="••••••••" />
          </div>
          <div v-if="errMsg" class="msg-error">{{ errMsg }}</div>
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            {{ loading ? 'Réinitialisation…' : 'Réinitialiser le mot de passe' }}
          </button>
        </form>
      </div>

      <p class="text-center text-sm mt-5">
        <RouterLink to="/login" class="underline underline-offset-2" style="color:#4A372C;">
          ← Retour à la connexion
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<style scoped>
.card { background:#FBF7F0; border:1px solid rgba(217,198,168,0.6); border-radius:16px; padding:2rem; box-shadow:0 4px 24px rgba(74,55,44,0.08); }
.form-title { font-family:'Cormorant',Georgia,serif; font-size:1.6rem; font-weight:400; color:#3A2E26; }
.form-sub   { font-size:0.82rem; color:#8C7A6B; margin-top:4px; line-height:1.5; }
.field-label { display:block; font-size:0.78rem; font-weight:600; color:#4A372C; margin-bottom:5px; }
.field-input { width:100%; border:1px solid rgba(217,198,168,0.8); border-radius:8px; padding:0.55rem 0.85rem; font-size:0.875rem; color:#3A2E26; background:#fff; outline:none; transition:border-color 0.18s; }
.field-input:focus { border-color:#8C7A6B; }
.btn-primary { background:#4A372C; color:#FBF7F0; border:none; border-radius:8px; padding:0.65rem 1.5rem; font-size:0.875rem; font-weight:500; cursor:pointer; transition:background 0.18s; display:block; text-align:center; }
.btn-primary:hover    { background:#6B4F3F; }
.btn-primary:disabled { opacity:0.55; cursor:not-allowed; }
.msg-success { padding:0.7rem 1rem; border-radius:8px; background:rgba(74,139,74,0.1); color:#2d6a2d; font-size:0.82rem; }
.msg-error   { padding:0.7rem 1rem; border-radius:8px; background:rgba(181,83,60,0.1); color:#B5533C; font-size:0.82rem; }
</style>
