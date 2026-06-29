<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth   = useAuthStore()
const router = useRouter()

const loading  = ref(false)
const errorMsg = ref(null)

const form = ref({
  prenom:                '',
  nom:                   '',
  email:                 '',
  telephone:             '',
  nom_institution:       '',
  type_institution:      '',
  adresse:               '',
  password:              '',
  password_confirmation: '',
})

const typesInstitution = [
  'Université', 'Institut supérieur', 'Lycée / Collège',
  'École primaire', 'Entreprise privée', 'Administration publique',
  'ONG / Association', 'Cabinet professionnel', 'Autre',
]

const pwdRules = computed(() => {
  const pwd = form.value.password
  return [
    { label: '8 caractères minimum', ok: pwd.length >= 8 },
    { label: 'Une majuscule',         ok: /[A-Z]/.test(pwd) },
    { label: 'Un caractère spécial',  ok: /[^a-zA-Z0-9]/.test(pwd) },
  ]
})
const pwdValid = computed(() => pwdRules.value.every(r => r.ok))

async function handleSubmit() {
  if (form.value.password !== form.value.password_confirmation) {
    errorMsg.value = 'Les mots de passe ne correspondent pas.'
    return
  }
  if (!pwdValid.value) {
    errorMsg.value = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un caractère spécial.'
    return
  }
  loading.value  = true
  errorMsg.value = null

  try {
    const { data } = await api.post('/register', form.value)
    auth.user  = data.user
    auth.token = data.token
    sessionStorage.setItem('auth_token', data.token)
    sessionStorage.setItem('auth_user',  JSON.stringify(data.user))
    router.push({ name: 'dashboard' })
  } catch (e) {
    const errors = e.response?.data?.errors
    errorMsg.value = errors
      ? Object.values(errors).flat().join(' — ')
      : e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex" style="background:#F2E9DE;">

    <!-- ── Colonne gauche branding ── -->
    <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 relative flex-col justify-between p-12 overflow-hidden"
         style="background:#4A372C;">
      <span class="absolute font-black select-none pointer-events-none"
            style="font-family:'Poppins',sans-serif; font-size:clamp(80px,12vw,160px);
                   font-weight:900; color:rgba(251,247,240,0.05);
                   top:50%; left:50%; transform:translate(-50%,-50%); white-space:nowrap;">
        DOCVERIFY
      </span>
      <RouterLink to="/" class="relative z-10">
        <span style="font-family:'Cormorant',Georgia,serif; font-size:1.875rem;
                     font-weight:300; color:#FBF7F0; letter-spacing:0.12em;">
          DocVerify
        </span>
      </RouterLink>
      <div class="relative z-10 max-w-xs">
        <p style="font-family:'Cormorant',Georgia,serif; font-size:1.5rem;
                  font-weight:300; color:rgba(251,247,240,0.9); line-height:1.4;"
           class="mb-4">
          « La confiance commence par la certification. »
        </p>
        <p style="color:rgba(251,247,240,0.5); font-size:0.875rem; line-height:1.6;">
          Rejoignez les institutions qui protègent leurs documents officiels avec DocVerify.
        </p>
      </div>
      <div class="relative z-10 space-y-3">
        <div v-for="t in ['QR Code cryptographique unique','Vérification publique en un scan','Historique complet des consultations']"
             :key="t" class="flex items-center gap-3">
          <span class="w-5 h-5 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold"
                style="background:rgba(251,247,240,0.15); color:#FBF7F0;">✓</span>
          <span style="color:rgba(251,247,240,0.7); font-size:0.875rem;">{{ t }}</span>
        </div>
      </div>
    </div>

    <!-- ── Colonne droite ── -->
    <div class="flex-1 flex flex-col overflow-y-auto">

      <!-- Header mobile -->
      <div class="lg:hidden flex justify-center pt-10 pb-4">
        <RouterLink to="/"
                    style="font-family:'Cormorant',Georgia,serif; font-size:1.5rem;
                           font-weight:300; color:#4A372C; letter-spacing:0.12em; text-decoration:none;">
          DocVerify
        </RouterLink>
      </div>

      <div class="flex-1 flex items-start justify-center px-6 py-10 lg:py-12">
        <div class="w-full max-w-lg">

          <!-- Lien connexion -->
          <div class="text-right mb-6">
            <span style="font-size:0.875rem; color:#8C7A6B;">Déjà un compte ? </span>
            <RouterLink to="/login"
                        style="font-size:0.875rem; color:#6B4F3F; font-weight:500;
                               text-decoration:underline; text-underline-offset:2px;">
              Se connecter
            </RouterLink>
          </div>

          <!-- En-tête -->
          <div class="mb-7">
            <p class="text-xs font-display font-medium tracking-[0.2em] uppercase mb-2"
               style="color:#8C7A6B;">🏢 Institution / Entreprise</p>
            <h1 class="font-display font-bold text-3xl" style="color:#3A2E26;">Créer un compte</h1>
            <p class="text-sm mt-1" style="color:#8C7A6B;">
              Réservé aux institutions et entreprises officielles.
            </p>
          </div>

          <!-- Erreur -->
          <div v-if="errorMsg" class="mb-5 p-4 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.08); color:#8c3520; border:1px solid rgba(181,83,60,0.25);">
            {{ errorMsg }}
          </div>

          <form @submit.prevent="handleSubmit" class="space-y-4" novalidate>

            <!-- Section institution -->
            <div class="rounded-xl p-4 space-y-4" style="background:#E8DCCB;">
              <p class="text-xs font-medium uppercase tracking-wide" style="color:#6B4F3F;">
                Informations de l'organisation
              </p>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Nom de l'organisation *
                </label>
                <input v-model="form.nom_institution" type="text" class="input-field" required
                       placeholder="Ex: Université de Dakar" />
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Type *
                </label>
                <select v-model="form.type_institution" class="input-field" required>
                  <option value="">Choisir le type…</option>
                  <option v-for="t in typesInstitution" :key="t" :value="t.toLowerCase()">{{ t }}</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Adresse
                </label>
                <input v-model="form.adresse" type="text" class="input-field"
                       placeholder="Adresse complète de l'organisation" />
              </div>
            </div>

            <!-- Section responsable -->
            <div class="rounded-xl p-4 space-y-4" style="background:#F2E9DE; border:1px solid #D9C6A8;">
              <p class="text-xs font-medium uppercase tracking-wide" style="color:#6B4F3F;">
                Responsable du compte
              </p>

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
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Email professionnel *
                </label>
                <input v-model="form.email" type="email" class="input-field" required
                       placeholder="responsable@institution.com" />
              </div>

              <div>
                <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                  Téléphone
                </label>
                <input v-model="form.telephone" type="tel" class="input-field"
                       placeholder="+221 77 777 77 77" />
              </div>
            </div>

            <!-- Sécurité -->
            <div class="flex items-center gap-3 py-1">
              <div class="flex-1 h-px" style="background:#D9C6A8;"></div>
              <span class="text-xs" style="color:#8C7A6B;">Sécurité</span>
              <div class="flex-1 h-px" style="background:#D9C6A8;"></div>
            </div>

            <div>
              <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                Mot de passe * <span class="normal-case opacity-70">(min. 8 caractères)</span>
              </label>
              <input v-model="form.password" type="password" class="input-field" required
                     placeholder="••••••••" autocomplete="new-password" />
              <div v-if="form.password.length > 0" class="mt-2 flex flex-col gap-1">
                <div v-for="rule in pwdRules" :key="rule.label" class="flex items-center gap-2">
                  <span class="w-3.5 h-3.5 rounded-full flex-shrink-0 flex items-center justify-center text-xs"
                        :style="rule.ok
                          ? 'background:rgba(124,144,112,0.2); color:#4a6640;'
                          : 'background:rgba(181,83,60,0.1); color:#B5533C;'">
                    {{ rule.ok ? '✓' : '×' }}
                  </span>
                  <span class="text-xs" :style="rule.ok ? 'color:#4a6640;' : 'color:#8C7A6B;'">
                    {{ rule.label }}
                  </span>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-xs font-medium uppercase tracking-wide mb-1.5" style="color:#8C7A6B;">
                Confirmer le mot de passe *
              </label>
              <input v-model="form.password_confirmation" type="password" class="input-field" required
                     placeholder="••••••••" autocomplete="new-password" />
            </div>

            <button type="submit" :disabled="loading" class="btn-primary w-full" style="margin-top:1.5rem;">
              <span v-if="!loading">Créer mon compte</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                Création…
              </span>
            </button>

          </form>
        </div>
      </div>
    </div>

  </div>
</template>
