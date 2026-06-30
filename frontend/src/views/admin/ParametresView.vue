<script setup>
import { ref, onMounted } from 'vue'
import AdminLayout from '@/components/AdminLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth = useAuthStore()
const onglet = ref('profil') // 'profil' | 'securite' | 'smtp'

// ── Profil ────────────────────────────────────────────────────────────
const profil = ref({
  nom:       auth.user?.nom       ?? '',
  prenom:    auth.user?.prenom    ?? '',
  email:     auth.user?.email     ?? '',
  telephone: auth.user?.telephone ?? '',
})
const profilMsg    = ref(null)
const profilErr    = ref(null)
const savingProfil = ref(false)

async function saveProfil() {
  profilMsg.value = null
  profilErr.value = null
  savingProfil.value = true
  try {
    const { data } = await api.put('/admin/profil', profil.value)
    auth.user = data
    sessionStorage.setItem('auth_user', JSON.stringify(data))
    profilMsg.value = 'Profil mis à jour.'
  } catch (e) {
    profilErr.value = e.response?.data?.message ?? 'Erreur lors de la mise à jour.'
  } finally {
    savingProfil.value = false
  }
}

// ── Sécurité ──────────────────────────────────────────────────────────
const mdp = ref({ current: '', password: '', password_confirmation: '' })
const mdpMsg    = ref(null)
const mdpErr    = ref(null)
const savingMdp = ref(false)

async function saveMdp() {
  mdpMsg.value = null
  mdpErr.value = null
  savingMdp.value = true
  try {
    await api.put('/admin/password', mdp.value)
    mdpMsg.value = 'Mot de passe modifié avec succès.'
    mdp.value = { current: '', password: '', password_confirmation: '' }
  } catch (e) {
    mdpErr.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors du changement.'
  } finally {
    savingMdp.value = false
  }
}

// ── SMTP ──────────────────────────────────────────────────────────────
const smtp = ref({
  mail_mailer:       'smtp',
  mail_host:         '',
  mail_port:         587,
  mail_username:     '',
  mail_password:     '',
  mail_encryption:   'tls',
  mail_from_address: '',
  mail_from_name:    'DocVerify',
})
const smtpMsg     = ref(null)
const smtpErr     = ref(null)
const savingSmtp  = ref(false)
const testingSmtp = ref(false)
const testEmail   = ref('')
const loadingSmtp = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/smtp')
    // Fusionner avec les valeurs par défaut
    Object.keys(smtp.value).forEach(k => {
      if (data[k] !== undefined && data[k] !== null) smtp.value[k] = data[k]
    })
  } catch (_) {
    // Pas encore configuré
  } finally {
    loadingSmtp.value = false
  }
})

async function saveSmtp() {
  smtpMsg.value = null
  smtpErr.value = null
  savingSmtp.value = true
  try {
    await api.put('/admin/smtp', smtp.value)
    smtpMsg.value = 'Configuration SMTP enregistrée.'
    // Ne pas écraser le champ mot de passe avec la valeur vide retournée
    smtp.value.mail_password = ''
  } catch (e) {
    smtpErr.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Erreur lors de la sauvegarde.'
  } finally {
    savingSmtp.value = false
  }
}

async function testSmtp() {
  if (!testEmail.value) { smtpErr.value = 'Saisissez une adresse email de test.'; return }
  smtpMsg.value = null
  smtpErr.value = null
  testingSmtp.value = true
  try {
    await api.post('/admin/smtp/test', { email: testEmail.value })
    smtpMsg.value = `Email de test envoyé à ${testEmail.value}.`
  } catch (e) {
    smtpErr.value = e.response?.data?.message ?? 'Échec de l\'envoi. Vérifiez vos paramètres.'
  } finally {
    testingSmtp.value = false
  }
}
</script>

<template>
  <AdminLayout max-width="max-w-3xl">

    <!-- En-tête -->
    <div class="mb-8">
      <p class="text-xs font-display font-medium tracking-[0.2em] uppercase mb-1" style="color:#8C7A6B;">
        Administration
      </p>
      <h1 class="font-display font-semibold text-3xl" style="color:#3A2E26;">Paramètres</h1>
    </div>

    <!-- Onglets -->
    <div class="flex gap-1 p-1 rounded-xl mb-8" style="background:#E8DCCB;">
      <button v-for="tab in [
        { key:'profil',   icon:'👤', label:'Mon profil' },
        { key:'securite', icon:'🔒', label:'Sécurité' },
        { key:'smtp',     icon:'✉️', label:'Email SMTP' },
      ]" :key="tab.key"
              @click="onglet = tab.key"
              class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-medium transition-all"
              :style="onglet === tab.key
                ? 'background:#4A372C;color:#FBF7F0;box-shadow:0 2px 8px rgba(74,55,44,0.25);'
                : 'background:transparent;color:#8C7A6B;'">
        <span>{{ tab.icon }}</span>
        <span>{{ tab.label }}</span>
      </button>
    </div>

    <!-- ── Onglet Profil ── -->
    <div v-if="onglet === 'profil'" class="fade-in-up">
      <div class="card-premium p-7">
        <div class="flex items-center gap-4 mb-7 pb-6" style="border-bottom:1px solid #E8DCCB;">
          <div class="w-14 h-14 rounded-full flex items-center justify-center font-display font-semibold text-xl flex-shrink-0"
               style="background:#4A372C;color:#FBF7F0;">
            {{ (auth.user?.prenom?.[0] ?? '') + (auth.user?.nom?.[0] ?? '') }}
          </div>
          <div>
            <p class="font-display font-semibold text-lg" style="color:#3A2E26;">
              {{ auth.user?.prenom }} {{ auth.user?.nom }}
            </p>
            <p class="text-xs mt-0.5" style="color:#8C7A6B;">{{ auth.user?.email }}</p>
            <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full font-medium"
                  style="background:rgba(74,55,44,0.1);color:#4A372C;">Administrateur</span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Prénom</label>
            <input v-model="profil.prenom" type="text" class="input-field" />
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Nom</label>
            <input v-model="profil.nom" type="text" class="input-field" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Adresse email</label>
            <input v-model="profil.email" type="email" class="input-field" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">
              Téléphone <span style="color:#8C7A6B;font-weight:400;">(optionnel)</span>
            </label>
            <input v-model="profil.telephone" type="tel" placeholder="+221 77 777 77 77" class="input-field" />
          </div>
        </div>

        <div v-if="profilMsg" class="mt-5 p-3 rounded-xl text-sm"
             style="background:rgba(74,139,74,0.08);color:#2d6a2d;border:1px solid rgba(74,139,74,0.2);">
          ✓ {{ profilMsg }}
        </div>
        <div v-if="profilErr" class="mt-5 p-3 rounded-xl text-sm"
             style="background:rgba(181,83,60,0.08);color:#B5533C;border:1px solid rgba(181,83,60,0.2);">
          {{ profilErr }}
        </div>

        <button @click="saveProfil" :disabled="savingProfil" class="btn-primary mt-6">
          {{ savingProfil ? 'Enregistrement…' : 'Enregistrer le profil' }}
        </button>
      </div>
    </div>

    <!-- ── Onglet Sécurité ── -->
    <div v-else-if="onglet === 'securite'" class="fade-in-up">
      <div class="card-premium p-7">
        <div class="mb-6 pb-5" style="border-bottom:1px solid #E8DCCB;">
          <h2 class="font-semibold text-base" style="color:#3A2E26;">Changer le mot de passe</h2>
          <p class="text-xs mt-1" style="color:#8C7A6B;">
            Minimum 8 caractères, une majuscule et un caractère spécial.
          </p>
        </div>

        <div class="space-y-5">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Mot de passe actuel</label>
            <input v-model="mdp.current" type="password" autocomplete="current-password" class="input-field" />
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Nouveau mot de passe</label>
            <input v-model="mdp.password" type="password" autocomplete="new-password" class="input-field" />
          </div>
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Confirmer le nouveau mot de passe</label>
            <input v-model="mdp.password_confirmation" type="password" autocomplete="new-password" class="input-field" />
          </div>
        </div>

        <div v-if="mdpMsg" class="mt-5 p-3 rounded-xl text-sm"
             style="background:rgba(74,139,74,0.08);color:#2d6a2d;border:1px solid rgba(74,139,74,0.2);">
          ✓ {{ mdpMsg }}
        </div>
        <div v-if="mdpErr" class="mt-5 p-3 rounded-xl text-sm"
             style="background:rgba(181,83,60,0.08);color:#B5533C;border:1px solid rgba(181,83,60,0.2);">
          {{ mdpErr }}
        </div>

        <button @click="saveMdp" :disabled="savingMdp" class="btn-primary mt-6">
          {{ savingMdp ? 'Modification…' : 'Modifier le mot de passe' }}
        </button>
      </div>
    </div>

    <!-- ── Onglet SMTP ── -->
    <div v-else-if="onglet === 'smtp'" class="fade-in-up">
      <div class="card-premium p-7">
        <div class="mb-6 pb-5" style="border-bottom:1px solid #E8DCCB;">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="font-semibold text-base" style="color:#3A2E26;">Configuration Email (SMTP)</h2>
              <p class="text-xs mt-1" style="color:#8C7A6B;">
                Paramètres utilisés pour envoyer les notifications, confirmations et alertes.
              </p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full flex-shrink-0"
                  style="background:rgba(74,55,44,0.08);color:#4A372C;">Email</span>
          </div>
        </div>

        <div v-if="loadingSmtp" class="py-10 text-center text-sm" style="color:#8C7A6B;">
          Chargement de la configuration…
        </div>

        <template v-else>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

            <div class="sm:col-span-2">
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Hôte SMTP</label>
              <input v-model="smtp.mail_host" type="text" placeholder="smtp.gmail.com" class="input-field" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Port</label>
              <select v-model.number="smtp.mail_port" class="input-field">
                <option :value="25">25 — Non chiffré</option>
                <option :value="465">465 — SSL</option>
                <option :value="587">587 — TLS (recommandé)</option>
                <option :value="2525">2525 — Alternatif</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Chiffrement</label>
              <select v-model="smtp.mail_encryption" class="input-field">
                <option value="tls">TLS (STARTTLS)</option>
                <option value="ssl">SSL</option>
                <option value="none">Aucun</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Identifiant</label>
              <input v-model="smtp.mail_username" type="text" placeholder="user@exemple.com"
                     autocomplete="off" class="input-field" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">
                Mot de passe SMTP
                <span style="color:#8C7A6B;font-weight:400;">(laisser vide pour ne pas changer)</span>
              </label>
              <input v-model="smtp.mail_password" type="password" autocomplete="new-password"
                     placeholder="Nouveau mot de passe uniquement" class="input-field" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Adresse expéditeur</label>
              <input v-model="smtp.mail_from_address" type="email" placeholder="noreply@docverify.com" class="input-field" />
            </div>

            <div>
              <label class="block text-xs font-semibold uppercase tracking-wide mb-2" style="color:#4A372C;">Nom expéditeur</label>
              <input v-model="smtp.mail_from_name" type="text" placeholder="DocVerify" class="input-field" />
            </div>
          </div>

          <!-- Test d'envoi -->
          <div class="mt-6 p-4 rounded-xl" style="background:#F5EFE6;border:1px solid #E8DCCB;">
            <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color:#4A372C;">Tester la configuration</p>
            <div class="flex gap-2">
              <input v-model="testEmail" type="email" placeholder="Email de test…"
                     class="input-field flex-1" />
              <button @click="testSmtp" :disabled="testingSmtp"
                      class="btn-secondary whitespace-nowrap flex-shrink-0"
                      style="padding:0.5rem 1.2rem;">
                {{ testingSmtp ? 'Envoi…' : 'Envoyer un test' }}
              </button>
            </div>
          </div>

          <div v-if="smtpMsg" class="mt-4 p-3 rounded-xl text-sm"
               style="background:rgba(74,139,74,0.08);color:#2d6a2d;border:1px solid rgba(74,139,74,0.2);">
            ✓ {{ smtpMsg }}
          </div>
          <div v-if="smtpErr" class="mt-4 p-3 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.08);color:#B5533C;border:1px solid rgba(181,83,60,0.2);">
            {{ smtpErr }}
          </div>

          <button @click="saveSmtp" :disabled="savingSmtp" class="btn-primary mt-6">
            {{ savingSmtp ? 'Enregistrement…' : 'Enregistrer la configuration SMTP' }}
          </button>
        </template>
      </div>
    </div>

  </AdminLayout>
</template>
