<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth   = useAuthStore()
const router = useRouter()
const route  = useRoute()

const email    = ref('')
const password = ref('')
const loading  = ref(false)
const errorMsg = ref(null)
const showPwd  = ref(false)

async function handleSubmit() {
  if (!email.value || !password.value) return
  loading.value = true
  errorMsg.value = null

  try {
    await auth.login(email.value, password.value)
    const redirect = route.query.redirect
    router.push(redirect ?? (auth.isAdmin ? { name: 'admin' } : { name: 'dashboard' }))
  } catch (e) {
    errorMsg.value = e.response?.data?.message
      ?? e.response?.data?.errors?.email?.[0]
      ?? 'Identifiants incorrects.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex" style="background:#F2E9DE;">

    <!-- ── Colonne gauche : branding (masquée sur mobile) ── -->
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 relative flex-col justify-between p-12 overflow-hidden"
         style="background:#4A372C;">

      <span class="absolute font-black text-cream/5 select-none pointer-events-none"
            style="font-family:'Poppins',sans-serif; font-size:clamp(80px,12vw,160px);
                   font-weight:900; top:50%; left:50%; transform:translate(-50%,-50%);
                   white-space:nowrap;">
        DOCVERIFY
      </span>

      <RouterLink to="/" class="relative z-10">
        <span class="font-serif text-3xl font-light text-cream tracking-widest"
              style="font-family:'Cormorant',Georgia,serif; font-weight:300;">
          DocVerify
        </span>
      </RouterLink>

      <div class="relative z-10 max-w-xs">
        <p class="text-cream/90 text-2xl font-serif leading-relaxed mb-4"
           style="font-family:'Cormorant',Georgia,serif; font-weight:300;">
          « Chaque document mérite d'être protégé. »
        </p>
        <p class="text-cream/50 text-sm leading-relaxed">
          Certifiez, partagez et laissez vos destinataires vérifier l'authenticité en un scan.
        </p>
      </div>

      <!-- Indicateurs visuels -->
      <div class="relative z-10 flex gap-3">
        <div v-for="i in 3" :key="i"
             class="h-1 rounded-full flex-1"
             :style="i === 1 ? 'background:#FBF7F0;' : 'background:rgba(251,247,240,0.2);'">
        </div>
      </div>
    </div>

    <!-- ── Colonne droite : formulaire ── -->
    <div class="flex-1 flex flex-col">

      <!-- Header mobile -->
      <div class="lg:hidden flex justify-between items-center px-6 pt-8 pb-4">
        <RouterLink to="/"
                    class="font-serif text-xl font-light text-brown-dark tracking-widest"
                    style="font-family:'Cormorant',Georgia,serif;">
          DocVerify
        </RouterLink>
        <RouterLink to="/register"
                    class="text-xs font-medium text-brown border border-sand rounded-lg px-3 py-1.5 hover:bg-beige-medium transition-colors">
          S'inscrire
        </RouterLink>
      </div>

      <div class="flex-1 flex items-center justify-center px-6 py-10">
        <div class="w-full max-w-sm">

          <!-- Titre -->
          <div class="mb-8">
            <h1 class="font-display font-bold text-3xl text-brown-dark mb-1">
              Bon retour 👋
            </h1>
            <p class="text-sm text-taupe">
              Pas encore de compte ?
              <RouterLink to="/register"
                          class="text-brown font-medium underline underline-offset-2 hover:text-brown-dark ml-1">
                Créer un compte
              </RouterLink>
            </p>
          </div>

          <!-- Erreur -->
          <div v-if="errorMsg"
               class="mb-6 p-4 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.08); color:#8c3520;
                      border:1px solid rgba(181,83,60,0.25);">
            {{ errorMsg }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-5" novalidate>

            <!-- Email -->
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
                Adresse email
              </label>
              <input v-model="email" type="email" autocomplete="email"
                     placeholder="votre@email.com"
                     class="input-field text-base" required />
            </div>

            <!-- Mot de passe avec toggle -->
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
                Mot de passe
              </label>
              <div class="relative">
                <input v-model="password"
                       :type="showPwd ? 'text' : 'password'"
                       autocomplete="current-password"
                       placeholder="••••••••"
                       class="input-field text-base pr-12" required />
                <button type="button"
                        @click="showPwd = !showPwd"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-taupe hover:text-brown transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path v-if="!showPwd" stroke-linecap="round" stroke-linejoin="round"
                          d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                    <path v-if="!showPwd" stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path v-if="showPwd" stroke-linecap="round" stroke-linejoin="round"
                          d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Bouton connexion -->
            <button type="submit" :disabled="loading"
                    class="btn-primary w-full text-base py-3.5 !mt-6">
              <span v-if="!loading">Se connecter</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                Connexion…
              </span>
            </button>

          </form>

          <!-- Séparateur -->
          <div class="flex items-center gap-3 my-5">
            <div class="flex-1 h-px" style="background:#D9C6A8;"></div>
            <span class="text-xs text-taupe">ou</span>
            <div class="flex-1 h-px" style="background:#D9C6A8;"></div>
          </div>

          <!-- Bouton inscription -->
          <RouterLink to="/register"
                      class="btn-secondary w-full text-base py-3.5 flex items-center justify-center">
            Créer un nouveau compte
          </RouterLink>

          <!-- Retour accueil -->
          <p class="text-center text-xs text-taupe mt-5">
            <RouterLink to="/" class="underline underline-offset-2 hover:text-brown transition-colors">
              ← Retour à l'accueil
            </RouterLink>
          </p>

        </div>
      </div>
    </div>

  </div>
</template>
