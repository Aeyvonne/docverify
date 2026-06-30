<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
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

// ── Blocage si non certifié ────────────────────────────────────────────
const peutCertifier = computed(() => auth.user?.is_certified === true)

// ── Formulaire ────────────────────────────────────────────────────────
const form = ref({
  titre:           '',
  type:            '',
  date_emission:   '',
  date_expiration: '',
  qr_size_mm:      25,
})
const selectedFile  = ref(null)
const qrPositions   = ref([])   // [{ page, x_mm, y_mm }]
const loading       = ref(false)
const errorMsg      = ref(null)
const successDoc    = ref(null)

const typesParticulier = [
  { value: 'diplome',     label: 'Diplôme' },
  { value: 'attestation', label: 'Attestation' },
  { value: 'certificat',  label: 'Certificat' },
  { value: 'contrat',     label: 'Contrat' },
  { value: 'autre',       label: 'Autre' },
]
const typesInstitution = [
  { value: 'offre_emploi', label: 'Offre d\'emploi' },
  { value: 'appel_offres', label: 'Appel d\'offres' },
  { value: 'communique',   label: 'Communiqué officiel' },
  { value: 'decision',     label: 'Décision / Arrêté' },
  { value: 'convention',   label: 'Convention / Accord' },
  { value: 'rapport',      label: 'Rapport officiel' },
]
const typesDisponibles = computed(() =>
  auth.isCertified ? [...typesParticulier, ...typesInstitution] : typesParticulier
)

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file && file.type === 'application/pdf') {
    selectedFile.value = file
    qrPositions.value  = []
  }
}

function onPositionsUpdated(positions) {
  qrPositions.value = positions
}

async function handleSubmit() {
  if (!selectedFile.value) { errorMsg.value = 'Veuillez sélectionner un fichier PDF.'; return }
  if (!form.value.titre)   { errorMsg.value = 'Le titre est requis.'; return }
  if (!form.value.type)    { errorMsg.value = 'Le type de document est requis.'; return }
  if (!form.value.date_emission) { errorMsg.value = 'La date d\'émission est requise.'; return }

  loading.value  = true
  errorMsg.value = null

  const formData = new FormData()
  formData.append('fichier_original', selectedFile.value)
  formData.append('titre',            form.value.titre)
  formData.append('type',             form.value.type)
  formData.append('date_emission',    form.value.date_emission)
  formData.append('qr_size_mm',       form.value.qr_size_mm)
  if (form.value.date_expiration)
    formData.append('date_expiration', form.value.date_expiration)

  // Envoyer les positions par page en JSON
  if (qrPositions.value.length > 0)
    formData.append('qr_positions', JSON.stringify(qrPositions.value))

  try {
    const { data } = await api.post('/documents', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    successDoc.value = data
    docStore.prepend(data)
  } catch (e) {
    const errors = e.response?.data?.errors
    errorMsg.value = errors
      ? Object.values(errors).flat().join(' ')
      : e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AppLayout max-width="max-w-2xl">

    <!-- ── Blocage si non certifié ── -->
    <div v-if="!peutCertifier" class="card-premium p-10 text-center fade-in-up">
      <div class="w-16 h-16 rounded-full mx-auto mb-5 flex items-center justify-center"
           style="background:rgba(201,154,60,0.1);">
        <span class="text-3xl">🔒</span>
      </div>
      <h1 class="font-display font-semibold text-2xl text-brown-dark mb-3">
        Certification requise
      </h1>
      <p class="text-sm text-taupe leading-relaxed mb-6 max-w-sm mx-auto">
        Votre institution doit être certifiée par un administrateur avant de pouvoir
        émettre des documents. Soumettez votre dossier de certification.
      </p>
      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <RouterLink to="/certification" class="btn-primary text-sm px-6">
          Faire une demande de certification
        </RouterLink>
        <RouterLink to="/dashboard" class="btn-secondary text-sm px-6">
          Retour au tableau de bord
        </RouterLink>
      </div>
    </div>

    <!-- ── Succès ── -->
    <div v-else-if="successDoc" class="fade-in-up">
      <div class="card-premium p-8 text-center">
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
            {{ downloading ? 'Téléchargement…' : 'Télécharger le PDF certifié' }}
          </button>
          <RouterLink to="/documents" class="btn-secondary">Voir tous mes documents</RouterLink>
        </div>
      </div>
    </div>

    <!-- ── Formulaire ── -->
    <template v-else>
      <div class="mb-8 fade-in-up">
        <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">Nouveau document</p>
        <h1 class="font-display font-semibold text-3xl text-brown-dark">Certifier un document</h1>
      </div>

      <div v-if="errorMsg"
           class="mb-5 p-4 rounded-xl fade-in-up"
           style="background:rgba(181,83,60,0.08);border:1px solid rgba(181,83,60,0.25);">
        <p class="text-sm" style="color:#B5533C;">{{ errorMsg }}</p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6 fade-in-up delay-100">

        <!-- Fichier PDF -->
        <div class="card-premium p-6">
          <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-3">Fichier PDF *</label>
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

        <!-- Informations -->
        <div class="card-premium p-6 space-y-5">
          <p class="text-xs font-medium text-taupe uppercase tracking-wide">Informations</p>

          <div>
            <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Titre *</label>
            <input v-model="form.titre" type="text" placeholder="Ex: Diplôme de Licence en Informatique"
                   class="input-field" required />
          </div>

          <div>
            <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">Type *</label>
            <select v-model="form.type" class="input-field" required>
              <option value="" disabled>Choisir le type…</option>
              <option v-for="t in typesDisponibles" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>

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

        <!-- QR Code -->
        <div class="card-premium p-6">
          <p class="text-xs font-medium text-taupe uppercase tracking-wide mb-1">Placement du QR Code</p>
          <p class="text-xs text-taupe mb-4">
            Choisissez comment positionner le QR sur vos pages.
          </p>

          <!-- Taille slider -->
          <div class="mb-5">
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-medium text-taupe uppercase tracking-wide">Taille du QR</label>
              <span class="text-xs font-bold text-brown-dark bg-beige-medium px-2 py-0.5 rounded-full">
                {{ form.qr_size_mm }} mm
              </span>
            </div>
            <input v-model.number="form.qr_size_mm" type="range" min="15" max="60" step="5"
                   class="w-full h-1.5 rounded-full appearance-none cursor-pointer"
                   style="accent-color:#4A372C;background:#D9C6A8;" />
            <div class="flex justify-between text-xs text-taupe mt-1">
              <span>15mm</span><span>60mm</span>
            </div>
          </div>

          <div class="h-px mb-4" style="background:#E8DCCB;"></div>

          <!-- Picker multi-pages -->
          <QrPositionPicker
            v-if="selectedFile"
            :file="selectedFile"
            :qr-size-mm="form.qr_size_mm"
            @positions-updated="onPositionsUpdated"
          />
          <div v-else
               class="w-full h-36 rounded-xl flex flex-col items-center justify-center gap-2"
               style="background:#F2E9DE;border:2px dashed #D9C6A8;">
            <p class="text-xs text-taupe">Sélectionnez d'abord un PDF ci-dessus</p>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" :disabled="loading" class="btn-primary w-full">
          <span v-if="!loading">Certifier le document</span>
          <span v-else class="flex items-center justify-center gap-2">
            <span class="w-4 h-4 border-2 border-t-cream rounded-full animate-spin"
                  style="border-color:rgba(251,247,240,0.3);border-top-color:#FBF7F0;"></span>
            Certification en cours…
          </span>
        </button>

      </form>
    </template>

  </AppLayout>
</template>
