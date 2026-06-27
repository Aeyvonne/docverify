<script setup>
import { ref, onMounted, computed } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import BadgeStatut from '@/components/BadgeStatut.vue'
import { useDocumentsStore } from '@/stores/documents'
import { useDownload } from '@/composables/useDownload'

const store        = useDocumentsStore()
const { download, downloading, downloadError } = useDownload()
const downloadingId = ref(null) // id du document en cours de téléchargement
const filterStatut = ref('tous')

// Modal révocation
const showRevokeModal = ref(false)
const revokeTarget    = ref(null)
const revokeMotif     = ref('')
const revokeLoading   = ref(false)
const revokeError     = ref(null)

// Notification de succès (copie lien)
const copySuccess = ref(false)

onMounted(() => store.fetchAll())

const filtered = computed(() => {
  if (filterStatut.value === 'tous') return store.list
  return store.list.filter(d => d.statut === filterStatut.value)
})

const counts = computed(() => ({
  tous:    store.list.length,
  actif:   store.list.filter(d => d.statut === 'actif').length,
  revoque: store.list.filter(d => d.statut === 'revoque').length,
}))

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
  }).format(new Date(d))
}

function verifyUrl(token) {
  return `${window.location.origin}/verify/${token}`
}

async function copyLink(token) {
  await navigator.clipboard.writeText(verifyUrl(token))
  copySuccess.value = true
  setTimeout(() => { copySuccess.value = false }, 2000)
}

function openRevoke(doc) {
  revokeTarget.value    = doc
  revokeMotif.value     = ''
  revokeError.value     = null
  showRevokeModal.value = true
}

async function submitRevoke() {
  if (revokeMotif.value.trim().length < 5) {
    revokeError.value = 'Le motif doit contenir au moins 5 caractères.'
    return
  }
  revokeLoading.value = true
  revokeError.value   = null
  try {
    await store.revoke(revokeTarget.value.id, revokeMotif.value)
    showRevokeModal.value = false
  } catch (e) {
    revokeError.value = e.response?.data?.message ?? 'Une erreur est survenue.'
  } finally {
    revokeLoading.value = false
  }
}

async function handleDownload(doc) {
  downloadingId.value = doc.id
  await download(
    `/documents/${doc.id}/download`,
    `DocVerify_${doc.titre.replace(/\s+/g, '_')}.pdf`
  )
  downloadingId.value = null
}
</script>

<template>
  <AppLayout>

    <!-- En-tête -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8 fade-in-up">
      <div>
        <p class="text-xs font-display font-medium tracking-[0.2em] text-taupe uppercase mb-1">
          Mes documents
        </p>
        <h1 class="font-display font-semibold text-3xl text-brown-dark">Documents certifiés</h1>
      </div>
      <RouterLink to="/documents/new"
                  class="btn-primary text-sm px-5 py-2.5 flex items-center gap-2 flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Nouveau
      </RouterLink>
    </div>

    <!-- Filtres pills -->
    <div class="flex gap-2 flex-wrap mb-6 fade-in-up delay-100">
      <button v-for="f in [
        { key:'tous',    label:'Tous' },
        { key:'actif',   label:'Actifs' },
        { key:'revoque', label:'Révoqués' },
      ]" :key="f.key"
              @click="filterStatut = f.key"
              :class="[
                'text-xs px-4 py-2 rounded-full border transition-all',
                filterStatut === f.key
                  ? 'bg-brown-dark text-cream border-brown-dark shadow-sm'
                  : 'bg-cream text-taupe border-sand hover:border-brown hover:text-brown-dark',
              ]">
        {{ f.label }}
        <span class="ml-1.5 opacity-60">({{ counts[f.key] }})</span>
      </button>
    </div>

    <!-- Notification copie -->
    <Transition name="reveal">
      <div v-if="copySuccess"
           class="mb-4 p-3 rounded-xl text-sm text-center fade-in-up"
           style="background:rgba(124,144,112,0.1); color:#4a6640; border:1px solid rgba(124,144,112,0.25);">
        ✓ Lien de vérification copié dans le presse-papier
      </div>
    </Transition>

    <!-- Loader -->
    <div v-if="store.loading" class="flex justify-center py-16">
      <div class="w-8 h-8 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
    </div>

    <!-- Liste documents -->
    <div v-else-if="filtered.length" class="space-y-3 fade-in-up delay-200">
      <article v-for="doc in filtered" :key="doc.id"
               class="card-premium p-5 md:p-6">

        <div class="flex flex-col sm:flex-row sm:items-start gap-4">

          <!-- Infos principales -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2.5 mb-2">
              <BadgeStatut :statut="doc.statut" size="sm" />
              <span class="text-xs text-taupe capitalize">{{ doc.type }}</span>
            </div>

            <h3 class="font-display font-semibold text-brown-dark text-lg leading-tight mb-1 truncate">
              {{ doc.titre }}
            </h3>

            <p class="text-xs text-taupe">
              Émis le {{ formatDate(doc.date_emission) }}
              <template v-if="doc.date_expiration">
                · Expire le {{ formatDate(doc.date_expiration) }}
              </template>
            </p>

            <!-- Motif révocation -->
            <p v-if="doc.motif_revocation"
               class="text-xs mt-1 italic"
               style="color:#8c3520;">
              Motif : {{ doc.motif_revocation }}
            </p>
          </div>

          <!-- Actions -->
          <div class="flex flex-row sm:flex-col gap-2 flex-shrink-0">

              <!-- Télécharger le PDF certifié -->
              <button
                @click="handleDownload(doc)"
                :disabled="downloadingId === doc.id"
                class="text-xs px-3 py-1.5 rounded-lg border border-sand text-taupe
                       hover:bg-beige-medium hover:text-brown-dark transition-colors
                       flex items-center gap-1.5 disabled:opacity-50">
                <svg v-if="downloadingId !== doc.id"
                     class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                <span v-if="downloadingId === doc.id"
                      class="w-3 h-3 border border-taupe border-t-brown rounded-full animate-spin">
                </span>
                {{ downloadingId === doc.id ? '…' : 'PDF' }}
              </button>

              <!-- Voir la page de vérification — RouterLink pour navigation SPA -->
            <RouterLink
               :to="{ name: 'verify', params: { token: doc.qr_token } }"
               target="_blank"
               class="text-xs px-3 py-1.5 rounded-lg border border-sand text-taupe
                      hover:bg-beige-medium hover:text-brown-dark transition-colors
                      flex items-center gap-1.5 no-underline">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
              </svg>
              Vérifier
            </RouterLink>

            <!-- Copier le lien -->
            <button @click="copyLink(doc.qr_token)"
                    class="text-xs px-3 py-1.5 rounded-lg border border-sand text-taupe
                           hover:bg-beige-medium hover:text-brown-dark transition-colors
                           flex items-center gap-1.5">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
              </svg>
              Copier
            </button>

            <!-- Révoquer -->
            <button v-if="doc.statut !== 'revoque'"
                    @click="openRevoke(doc)"
                    class="text-xs px-3 py-1.5 rounded-lg transition-colors
                           flex items-center gap-1.5"
                    style="border:1px solid rgba(181,83,60,0.3);
                           color:#B5533C;
                           background:transparent;"
                    @mouseenter="$event.target.style.background='rgba(181,83,60,0.06)'"
                    @mouseleave="$event.target.style.background='transparent'">
              Révoquer
            </button>

          </div>
        </div>
      </article>
    </div>

    <!-- État vide -->
    <div v-else class="card-premium p-14 text-center fade-in-up delay-200">
      <p class="text-taupe text-sm mb-5">
        {{ filterStatut === 'tous' ? 'Aucun document certifié.' : `Aucun document ${filterStatut}.` }}
      </p>
      <RouterLink v-if="filterStatut === 'tous'" to="/documents/new"
                  class="btn-primary inline-flex text-sm px-6">
        Certifier un document
      </RouterLink>
    </div>

  </AppLayout>

  <!-- ── Modal de révocation ── -->
  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showRevokeModal"
           class="fixed inset-0 z-50 flex items-center justify-center p-5"
           @click.self="showRevokeModal = false">

        <!-- Overlay -->
        <div class="absolute inset-0 backdrop-blur-sm"
             style="background:rgba(74,55,44,0.2);"></div>

        <!-- Contenu -->
        <div class="relative w-full max-w-md card-premium p-7 z-10">

          <h2 class="font-display font-semibold text-xl text-brown-dark mb-1">
            Révoquer le document
          </h2>
          <p class="text-sm text-taupe mb-5 leading-relaxed">
            « {{ revokeTarget?.titre }} » — Cette action est définitive et visible par tous les vérificateurs.
          </p>

          <div v-if="revokeError"
               class="mb-4 p-3 rounded-xl text-sm"
               style="background:rgba(181,83,60,0.07); color:#8c3520;
                      border:1px solid rgba(181,83,60,0.2);">
            {{ revokeError }}
          </div>

          <label class="block text-xs font-medium text-taupe uppercase tracking-wide mb-2">
            Motif de révocation *
          </label>
          <textarea v-model="revokeMotif" rows="4"
                    placeholder="Expliquez la raison de cette révocation…"
                    class="input-field resize-none mb-5"
                    @keydown.esc="showRevokeModal = false">
          </textarea>

          <div class="flex gap-3">
            <button @click="submitRevoke" :disabled="revokeLoading"
                    class="flex-1 btn-primary"
                    style="background:#B5533C;">
              <span v-if="!revokeLoading">Confirmer</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="w-4 h-4 border-2 border-cream/40 border-t-cream rounded-full animate-spin"></span>
                Révocation…
              </span>
            </button>
            <button @click="showRevokeModal = false" class="btn-secondary px-5">
              Annuler
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.reveal-enter-active { transition: opacity 0.3s ease; }
.reveal-enter-from   { opacity: 0; }

.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to       { opacity: 0; }
</style>
