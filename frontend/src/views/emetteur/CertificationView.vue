<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/components/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const auth     = useAuthStore()
const loading  = ref(false)
const fetching = ref(true)
const success  = ref(false)
const error    = ref(null)

const demandeExistante = ref(null)

// Formulaire avec v-model directs sur chaque champ
const ninea         = ref('')
const rccm          = ref('')
const fichierPreuve = ref(null)
const message       = ref('')
const fichierNom    = ref('')

// Noms d'erreurs par champ
const erreurs = ref({})

onMounted(async () => {
  try {
    const { data } = await api.get('/demandes-certification/ma-demande')
    // data peut être null (pas de demande) ou un objet demande
    if (data && data.id) {
      demandeExistante.value = data
    }
  } catch (_) {
    // 404 ou erreur réseau → pas de demande, on affiche le formulaire
  } finally {
    fetching.value = false
  }
})

function handleFileChange(e) {
  const file = e.target.files[0] ?? null
  fichierPreuve.value = file
  fichierNom.value    = file?.name ?? ''
  if (file) erreurs.value.fichier_preuve = null
}

const statutConfig = {
  en_attente: { label: 'En attente de traitement', color: '#C99A3C', bg: 'rgba(201,154,60,0.08)', border: 'rgba(201,154,60,0.25)' },
  approuvee:  { label: 'Approuvée',                color: '#7C9070', bg: 'rgba(124,144,112,0.08)', border: 'rgba(124,144,112,0.25)' },
  refusee:    { label: 'Refusée',                  color: '#B5533C', bg: 'rgba(181,83,60,0.08)',   border: 'rgba(181,83,60,0.25)' },
}

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(new Date(d))
}

function valider() {
  erreurs.value = {}
  if (!ninea.value.trim())    erreurs.value.ninea          = 'Le NINEA est obligatoire.'
  if (!rccm.value.trim())     erreurs.value.rccm           = 'Le RCCM est obligatoire.'
  if (!fichierPreuve.value)   erreurs.value.fichier_preuve = 'Le fichier justificatif est obligatoire.'
  return Object.keys(erreurs.value).length === 0
}

async function submitDemande() {
  error.value = null
  if (!valider()) return

  loading.value = true

  const formData = new FormData()
  formData.append('ninea',          ninea.value.trim())
  formData.append('rccm',           rccm.value.trim())
  formData.append('fichier_preuve', fichierPreuve.value)
  if (message.value.trim()) formData.append('message', message.value.trim())

  try {
    const { data } = await api.post('/demandes-certification', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    demandeExistante.value = data.demande
    success.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      // Erreurs de validation Laravel → affichage par champ
      erreurs.value = Object.fromEntries(
        Object.entries(errors).map(([k, v]) => [k, v[0]])
      )
      error.value = 'Veuillez corriger les erreurs ci-dessous.'
    } else {
      error.value = e.response?.data?.message ?? 'Une erreur est survenue. Veuillez réessayer.'
    }
  } finally {
    loading.value = false
  }
}

function recommencer() {
  demandeExistante.value = null
  ninea.value         = ''
  rccm.value          = ''
  fichierPreuve.value = null
  fichierNom.value    = ''
  message.value       = ''
  erreurs.value       = {}
  error.value         = null
}
</script>

<template>
  <AppLayout max-width="max-w-lg">

    <!-- Chargement -->
    <div v-if="fetching" class="flex justify-center py-20">
      <div class="w-7 h-7 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <template v-else>

      <!-- ── Déjà certifié ── -->
      <div v-if="auth.isCertified" class="card-premium p-10 text-center fade-in-up">
        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
             style="background:rgba(124,144,112,0.12);">
          <span class="text-3xl" style="color:#7C9070;">★</span>
        </div>
        <h1 class="font-display font-semibold text-2xl text-brown-dark mb-2">Institution certifiée</h1>
        <p class="text-sm text-taupe">Votre compte a accès à tous les types de documents officiels.</p>
        <RouterLink to="/dashboard" class="btn-secondary inline-flex mt-6 text-sm px-5">
          ← Tableau de bord
        </RouterLink>
      </div>

      <!-- ── Succès ── -->
      <div v-else-if="success" class="card-premium p-10 text-center fade-in-up">
        <div class="w-16 h-16 rounded-full mx-auto mb-4 flex items-center justify-center"
             style="background:rgba(124,144,112,0.12);">
          <span class="text-3xl" style="color:#7C9070;">✓</span>
        </div>
        <h1 class="font-display font-semibold text-2xl text-brown-dark mb-2">Demande envoyée</h1>
        <p class="text-sm text-taupe leading-relaxed">
          Votre dossier a été transmis à l'administrateur.<br>
          Vous serez informé dès qu'il sera traité.
        </p>
        <RouterLink to="/dashboard" class="btn-secondary inline-flex mt-6 text-sm px-5">
          ← Tableau de bord
        </RouterLink>
      </div>

      <!-- ── Demande existante ── -->
      <div v-else-if="demandeExistante" class="fade-in-up">
        <div class="mb-8">
          <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">Mon compte</p>
          <h1 class="font-display font-semibold text-3xl text-brown-dark">Demande de certification</h1>
        </div>

        <div class="card-premium p-6 space-y-5">
          <!-- Statut -->
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium"
               :style="{
                 background: statutConfig[demandeExistante.statut]?.bg,
                 color:      statutConfig[demandeExistante.statut]?.color,
                 border:     '1px solid ' + statutConfig[demandeExistante.statut]?.border,
               }">
            {{ statutConfig[demandeExistante.statut]?.label ?? demandeExistante.statut }}
          </div>

          <!-- Infos -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs text-taupe mb-0.5">NINEA</p>
              <p class="font-mono font-medium text-brown-dark">{{ demandeExistante.ninea }}</p>
            </div>
            <div>
              <p class="text-xs text-taupe mb-0.5">RCCM</p>
              <p class="font-mono font-medium text-brown-dark">{{ demandeExistante.rccm }}</p>
            </div>
            <div>
              <p class="text-xs text-taupe mb-0.5">Soumise le</p>
              <p class="font-medium text-brown-dark">{{ formatDate(demandeExistante.created_at) }}</p>
            </div>
            <div v-if="demandeExistante.traite_le">
              <p class="text-xs text-taupe mb-0.5">Traitée le</p>
              <p class="font-medium text-brown-dark">{{ formatDate(demandeExistante.traite_le) }}</p>
            </div>
          </div>

          <!-- Motif refus -->
          <div v-if="demandeExistante.statut === 'refusee' && demandeExistante.motif_refus"
               class="p-4 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.06); border:1px solid rgba(181,83,60,0.2);">
            <p class="text-xs font-medium uppercase tracking-wide mb-1" style="color:#8c3520;">Motif du refus</p>
            <p class="leading-relaxed" style="color:#3A2E26;">{{ demandeExistante.motif_refus }}</p>
          </div>

          <!-- Re-soumettre si refusée -->
          <div v-if="demandeExistante.statut === 'refusee'">
            <p class="text-xs text-taupe mb-3">Vous pouvez corriger votre dossier et soumettre une nouvelle demande.</p>
            <button @click="recommencer" class="btn-primary text-sm px-5">
              Soumettre une nouvelle demande
            </button>
          </div>
        </div>
      </div>

      <!-- ── Formulaire principal ── -->
      <template v-else>

        <div class="mb-8 fade-in-up">
          <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">Mon compte</p>
          <h1 class="font-display font-semibold text-3xl text-brown-dark">Demande de certification</h1>
          <p class="text-sm text-taupe mt-2 leading-relaxed">
            Renseignez vos informations fiscales et joignez un justificatif officiel.
            L'administrateur traitera votre dossier sous 48h.
          </p>
        </div>

        <!-- Erreur globale -->
        <div v-if="error"
             class="mb-5 p-4 rounded-xl text-sm fade-in-up"
             style="background:rgba(181,83,60,0.07); color:#8c3520; border:1px solid rgba(181,83,60,0.2);">
          {{ error }}
        </div>

        <form @submit.prevent="submitDemande" class="card-premium p-7 space-y-5 fade-in-up delay-100"
              enctype="multipart/form-data" novalidate>

          <!-- Infos institution pré-remplies -->
          <div class="rounded-xl p-4 space-y-1.5" style="background:#E8DCCB;">
            <p class="text-xs text-taupe uppercase tracking-wide mb-2">Votre institution</p>
            <p class="text-sm text-brown-dark font-medium">
              {{ auth.user?.nom_institution ?? 'Non renseignée' }}
            </p>
            <p class="text-xs text-taupe">{{ auth.user?.prenom }} {{ auth.user?.nom }} · {{ auth.user?.email }}</p>
          </div>

          <!-- NINEA -->
          <div>
            <label for="ninea"
                   class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
              NINEA *
              <span class="normal-case ml-1 opacity-70">(Numéro d'Identification Nationale des Entreprises et Associations)</span>
            </label>
            <input
              id="ninea"
              v-model="ninea"
              type="text"
              placeholder="Ex : 0001122 2G3"
              class="input-field"
              :class="{ 'border-terracotta': erreurs.ninea }"
              autocomplete="off"
            />
            <p v-if="erreurs.ninea" class="text-xs mt-1" style="color:#B5533C;">{{ erreurs.ninea }}</p>
          </div>

          <!-- RCCM -->
          <div>
            <label for="rccm"
                   class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
              RCCM *
              <span class="normal-case ml-1 opacity-70">(Registre du Commerce et du Crédit Mobilier)</span>
            </label>
            <input
              id="rccm"
              v-model="rccm"
              type="text"
              placeholder="Ex : SN・DKR・2026・B・12345"
              class="input-field"
              :class="{ 'border-terracotta': erreurs.rccm }"
              autocomplete="off"
            />
            <p v-if="erreurs.rccm" class="text-xs mt-1" style="color:#B5533C;">{{ erreurs.rccm }}</p>
          </div>

          <!-- Fichier justificatif -->
          <div>
            <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
              Fichier justificatif *
              <span class="normal-case ml-1 opacity-70">PDF, JPG ou PNG — max 5 Mo</span>
            </label>

            <!-- Zone de drop stylisée — label cliquable -->
            <label
              for="fichier_preuve"
              class="flex items-center gap-4 w-full px-4 py-4 border-2 border-dashed rounded-xl
                     cursor-pointer transition-all"
              :class="erreurs.fichier_preuve
                ? 'border-terracotta/60 bg-terracotta/5'
                : fichierPreuve
                  ? 'border-brown bg-beige-medium'
                  : 'border-sand hover:border-brown hover:bg-cream bg-beige-light'"
            >
              <!-- Icône -->
              <div class="w-10 h-10 rounded-lg flex-shrink-0 flex items-center justify-center"
                   :style="fichierPreuve ? 'background:#4A372C;' : 'background:#E8DCCB;'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
                     :style="fichierPreuve ? 'color:#FBF7F0;' : 'color:#8C7A6B;'">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                </svg>
              </div>

              <!-- Texte -->
              <div class="flex-1 min-w-0">
                <p v-if="fichierPreuve" class="text-sm font-medium text-brown-dark truncate">
                  {{ fichierNom }}
                </p>
                <p v-else class="text-sm text-taupe">
                  Cliquez pour sélectionner votre justificatif
                </p>
                <p class="text-xs text-taupe mt-0.5">
                  {{ fichierPreuve ? 'Fichier sélectionné ✓' : 'Registre de commerce, extrait RCCM, statuts...' }}
                </p>
              </div>

              <!-- Bouton changer si fichier déjà choisi -->
              <span v-if="fichierPreuve"
                    class="text-xs text-taupe underline underline-offset-2 flex-shrink-0">
                Changer
              </span>

              <input
                id="fichier_preuve"
                type="file"
                accept="application/pdf,image/jpeg,image/png"
                class="sr-only"
                @change="handleFileChange"
              />
            </label>

            <p v-if="erreurs.fichier_preuve" class="text-xs mt-1" style="color:#B5533C;">
              {{ erreurs.fichier_preuve }}
            </p>
          </div>

          <!-- Message optionnel -->
          <div>
            <label for="message"
                   class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
              Message
              <span class="normal-case ml-1 opacity-70">(optionnel)</span>
            </label>
            <textarea
              id="message"
              v-model="message"
              rows="3"
              placeholder="Informations complémentaires pour l'administrateur…"
              class="input-field resize-none"
            ></textarea>
          </div>

          <!-- Bouton soumettre -->
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="!loading" class="flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
              </svg>
              Envoyer la demande
            </span>
            <span v-else class="flex items-center justify-center gap-2">
              <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
              Envoi en cours…
            </span>
          </button>

        </form>
      </template>

    </template>
  </AppLayout>
</template>
