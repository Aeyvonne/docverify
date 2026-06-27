<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import QrPositionPicker from '@/components/QrPositionPicker.vue'
import StampAnimation from '@/components/StampAnimation.vue'
import { useAuthStore } from '@/stores/auth'
import { useDocumentsStore } from '@/stores/documents'
import { useDownload } from '@/composables/useDownload'
import api from '@/api/axios'

const auth     = useAuthStore()
const docStore = useDocumentsStore()
const router   = useRouter()
const { download, downloading } = useDownload()

// ── Formulaire ────────────────────────────────────────────────────────
const form = ref({
  titre:           '',
  type:            '',
  date_emission:   '',
  date_expiration: '',
  qr_position_x:   null,
  qr_position_y:   null,
  qr_size_mm:      25,   // taille par défaut 25mm
})
const selectedFile = ref(null)
const loading      = ref(false)
const errorMsg     = ref(null)
const successDoc   = ref(null)  // document créé → pour l'étape de confirmation

// Types accessibles à tous les émetteurs (particuliers et institutions)
const typesParticulier = [
  { value: 'diplome',     label: 'Diplôme' },
  { value: 'attestation', label: 'Attestation' },
  { value: 'certificat',  label: 'Certificat' },
  { value: 'contrat',     label: 'Contrat' },
  { value: 'autre',       label: 'Autre' },
]

// Types supplémentaires réservés aux institutions certifiées
const typesInstitution = [
  { value: 'offre_emploi',   label: 'Offre d\'emploi' },
  { value: 'appel_offres',   label: 'Appel d\'offres' },
  { value: 'communique',     label: 'Communiqué officiel' },
  { value: 'decision',       label: 'Décision / Arrêté' },
  { value: 'convention',     label: 'Convention / Accord' },
  { value: 'rapport',        label: 'Rapport officiel' },
]

const typesDisponibles = computed(() => {
  // Particulier → uniquement les types personnels, jamais les types institutionnels
  if (auth.isParticulier) return typesParticulier

  // Institution non certifiée → types de base seulement
  if (!auth.isCertified) return typesParticulier

  // Institution certifiée → accès complet
  return [...typesParticulier, ...typesInstitution]
})

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file && file.type === 'application/pdf') {
    selectedFile.value = file
    form.value.qr_position_x = null
    form.value.qr_position_y = null
  }
}

function onPositionSelected({ x_mm, y_mm }) {
  form.value.qr_position_x = x_mm   // peut être null si reset
  form.value.qr_position_y = y_mm
}

async function handleSubmit() {
  if (!selectedFile.value) { errorMsg.value = 'Veuillez sélectionner un fichier PDF.'; return }
  if (!form.value.titre)   { errorMsg.value = 'Le titre est requis.'; return }
  if (!form.value.type)    { errorMsg.value = 'Le type de document est requis.'; return }

  loading.value = true
  errorMsg.value = null

  const formData = new FormData()
  formData.append('fichier_original', selectedFile.value)
  formData.append('titre',            form.value.titre)
  formData.append('type',             form.value.type)
  formData.append('date_emission',    form.value.date_emission)
  if (form.value.date_expiration) formData.append('date_expiration', form.value.date_expiration)
  if (form.value.qr_position_x !== null) formData.append('qr_position_x', form.value.qr_position_x)
  if (form.value.qr_position_y !== null) formData.append('qr_position_y', form.value.qr_position_y)
  formData.append('qr_size_mm', form.value.qr_size_mm)

  try {
    const { data } = await api.post('/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    successDoc.value = data
    docStore.prepend(data) // met à jour le cache du store
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      errorMsg.value = Object.values(errors).flat().join(' ')
    } else {
      errorMsg.value = e.response?.data?.message ?? 'Une erreur est survenue.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppLayout max-width="max-w-2xl">

      <!-- Succès -->
      <div v-if="successDoc" class="fade-in-up">
        <div class="card-premium p-8 text-center">

          <!-- Animation tamponnage (joue une seule fois) -->
          <div class="flex justify-center mb-6">
            <StampAnimation :auto-play="true" :loop="false" size="md" />
          </div>

          <h1 class="font-display font-semibold text-2xl text-brown-dark mb-2">Document certifié</h1>
          <p class="text-sm text-taupe mb-6">Le QR Code a été généré et intégré dans le PDF.</p>

          <div class="bg-beige-medium rounded-xl p-4 text-left mb-6">
            <p class="text-xs text-taupe mb-1">Token QR</p>
            <p class="font-mono text-sm text-brown-dark break-all">{{ successDoc.qr_token }}</p>
          </div>

          <div class="flex flex-col gap-3">
            <button
              @click="download(`/documents/${successDoc.id}/download`, `DocVerify_${successDoc.titre}.pdf`)"
              :disabled="downloading"
              class="btn-primary flex items-center justify-center gap-2">
              <svg v-if="!downloading" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
              </svg>
              <span v-if="!downloading" class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin" v-show="false"></span>
              {{ downloading ? 'Téléchargement…' : 'Télécharger le PDF certifié' }}
            </button>
            <RouterLink to="/documents" class="btn-secondary">
              Voir tous mes documents
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Formulaire -->
      <template v-else>
        <div class="mb-8 fade-in-up">
          <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">Nouveau document</p>
          <h1 class="font-display font-semibold text-3xl text-brown-dark">Certifier un document</h1>
        </div>

        <!-- Erreur globale -->
        <div v-if="errorMsg"
             class="mb-5 p-4 rounded-xl bg-terracotta/8 border border-terracotta/25 fade-in-up">
          <p class="text-sm text-terracotta">{{ errorMsg }}</p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-6 fade-in-up delay-100">

          <!-- Fichier PDF -->
          <div class="card-premium p-6">
            <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-3">
              Fichier PDF *
            </label>
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-sand rounded-xl cursor-pointer hover:border-brown transition-colors bg-beige-light hover:bg-cream">
              <svg class="w-8 h-8 text-taupe mb-2" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
              </svg>
              <span v-if="selectedFile" class="text-sm font-medium text-brown">{{ selectedFile.name }}</span>
              <span v-else class="text-sm text-taupe">Cliquez ou glissez votre PDF ici</span>
              <input type="file" accept="application/pdf" class="hidden" @change="handleFileChange" />
            </label>
          </div>

          <!-- Informations du document -->
          <div class="card-premium p-6 space-y-5">
            <p class="text-xs font-medium text-taupe uppercase tracking-wide">Informations</p>

            <!-- Titre -->
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Titre *</label>
              <input v-model="form.titre" type="text" placeholder="Ex: Diplôme de Licence en Informatique"
                     class="input-field" required />
            </div>

            <!-- Type -->
            <div>
              <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Type *</label>
              <select v-model="form.type" class="input-field" required>
                <option value="" disabled>Choisir le type…</option>
                <option v-for="t in typesDisponibles" :key="t.value" :value="t.value">
                  {{ t.label }}
                </option>
              </select>
              <!-- Message informatif pour les institutions non certifiées -->
              <p v-if="!auth.isParticulier && !auth.isCertified"
                 class="text-xs mt-1.5" style="color:#8C7A6B;">
                <RouterLink to="/certification"
                            class="underline underline-offset-2 hover:text-brown transition-colors">
                  Certifiez votre institution
                </RouterLink>
                pour accéder aux types officiels (offres d'emploi, appels d'offres…)
              </p>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Date d'émission *</label>
                <input v-model="form.date_emission" type="date" class="input-field" required />
              </div>
              <div>
                <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Date d'expiration</label>
                <input v-model="form.date_expiration" type="date" class="input-field" />
              </div>
            </div>
          </div>

          <!-- Placement QR — toujours monté, gère l'état vide en interne -->
          <div class="card-premium p-6">
            <p class="text-xs font-medium text-taupe uppercase tracking-wide mb-1">Placement du QR Code</p>
            <p class="text-xs text-taupe mb-4">
              {{ selectedFile ? 'Cliquez sur la page pour choisir la position.' : 'Sélectionnez d\'abord un PDF ci-dessus.' }}
            </p>

            <!-- Taille du QR — slider -->
            <div class="mb-5">
              <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-medium text-taupe uppercase tracking-wide">
                  Taille du QR Code
                </label>
                <span class="text-xs font-bold text-brown-dark bg-beige-medium px-2 py-0.5 rounded-full">
                  {{ form.qr_size_mm }} mm
                </span>
              </div>
              <!-- Slider de 15 à 60mm -->
              <input
                v-model.number="form.qr_size_mm"
                type="range" min="15" max="60" step="5"
                class="w-full h-1.5 rounded-full appearance-none cursor-pointer"
                style="accent-color: #4A372C; background: #D9C6A8;"
              />
              <div class="flex justify-between text-xs text-taupe mt-1">
                <span>15mm — Discret</span>
                <span>60mm — Très visible</span>
              </div>
              <!-- Aperçu visuel de la taille relative -->
              <div class="flex items-center gap-3 mt-3">
                <span class="text-xs text-taupe">Aperçu :</span>
                <div class="rounded border-2 border-brown/40 bg-brown/8 flex items-center justify-center"
                     :style="{
                       width:  Math.round(form.qr_size_mm * 1.5) + 'px',
                       height: Math.round(form.qr_size_mm * 1.5) + 'px',
                       minWidth: '22px',
                       minHeight: '22px',
                     }">
                  <svg viewBox="0 0 10 10" fill="#4A372C"
                       :style="{ width: Math.round(form.qr_size_mm * 0.9) + 'px', height: Math.round(form.qr_size_mm * 0.9) + 'px' }">
                    <rect x="0" y="0" width="4" height="4" rx="0.3"/>
                    <rect x="0.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                    <rect x="1.4" y="1.4" width="1.2" height="1.2"/>
                    <rect x="6" y="0" width="4" height="4" rx="0.3"/>
                    <rect x="6.8" y="0.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                    <rect x="7.4" y="1.4" width="1.2" height="1.2"/>
                    <rect x="0" y="6" width="4" height="4" rx="0.3"/>
                    <rect x="0.8" y="6.8" width="2.4" height="2.4" fill="#F2E9DE"/>
                    <rect x="1.4" y="7.4" width="1.2" height="1.2"/>
                    <rect x="5" y="4.5" width="1" height="1"/>
                    <rect x="7" y="5.5" width="2" height="1"/>
                    <rect x="8.5" y="8.5" width="1.5" height="1.5" rx="0.2"/>
                  </svg>
                </div>
                <span class="text-xs text-taupe">sur votre document</span>
              </div>
            </div>

            <!-- Séparateur -->
            <div class="h-px bg-sand/50 mb-4"></div>

            <!-- Zone de prévisualisation vide avant sélection -->
            <div v-if="!selectedFile"
                 class="w-full h-36 rounded-xl border-2 border-dashed border-sand flex flex-col items-center justify-center gap-2"
                 style="background:#F2E9DE;">
              <svg class="w-8 h-8 text-sand" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
              </svg>
              <p class="text-xs text-taupe">Le PDF apparaîtra ici</p>
            </div>

            <!-- Picker actif dès qu'un fichier est sélectionné -->
            <QrPositionPicker
              v-else
              :file="selectedFile"
              :qr-size-mm="form.qr_size_mm"
              @position-selected="onPositionSelected"
            />
          </div>

          <!-- Bouton soumettre -->
          <button type="submit" :disabled="loading" class="btn-primary w-full">
            <span v-if="!loading">Certifier le document</span>
            <span v-else class="flex items-center justify-center gap-2">
              <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
              Certification en cours…
            </span>
          </button>

        </form>
      </template>
  </AppLayout>
</template>
