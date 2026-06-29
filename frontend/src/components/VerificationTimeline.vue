<script setup>
/**
 * Timeline des scans d'un document.
 * Reçoit le tableau `timeline` retourné par GET /api/verify/{token}
 */
const props = defineProps({
  timeline: { type: Array, required: true },
  total:    { type: Number, default: 0 },
})

function formatDate(iso) {
  if (!iso) return '—'
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(iso))
}

function locationLabel(entry) {
  if (entry.ville && entry.pays) return `${entry.ville}, ${entry.pays}`
  if (entry.pays)  return entry.pays
  if (entry.ville) return entry.ville
  return 'Localisation inconnue'
}
</script>

<template>
  <div class="space-y-0">

    <!-- En-tête -->
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-display font-semibold text-brown-dark text-sm tracking-wide uppercase">
        Historique des scans
      </h3>
      <span class="text-xs text-taupe bg-beige-medium px-2.5 py-1 rounded-full">
        {{ total }} scan{{ total > 1 ? 's' : '' }} au total
      </span>
    </div>

    <!-- Liste -->
    <div v-if="timeline.length" class="relative">
      <!-- Ligne verticale -->
      <div class="absolute left-[18px] top-2 bottom-2 w-px bg-sand"></div>

      <div v-for="(entry, idx) in timeline" :key="entry.id"
           class="relative flex gap-4 pb-6 last:pb-0">

        <!-- Point de timeline -->
        <div class="relative z-10 flex-shrink-0">
          <div :class="[
            'w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold',
            entry.est_courant
              ? 'bg-brown-dark text-cream shadow-md'
              : 'bg-beige-medium text-taupe border border-sand',
          ]">
            {{ entry.est_courant ? '●' : idx }}
          </div>
        </div>

        <!-- Contenu -->
        <div :class="['flex-1 pt-1.5', entry.est_courant ? 'opacity-100' : 'opacity-70']">
          <!-- Badge statut + label "Ce scan" -->
          <div class="flex items-center gap-2 mb-1">
            <span :class="[
              'text-xs font-medium px-2 py-0.5 rounded-full',
              `badge-${entry.statut}`,
            ]">
              {{ entry.statut }}
            </span>
            <span v-if="entry.est_courant"
                  class="text-xs text-taupe italic">— ce scan</span>
          </div>

          <!-- Localisation + date -->
          <p class="text-sm text-brown font-medium">{{ locationLabel(entry) }}</p>
          <p class="text-xs text-taupe mt-0.5">{{ formatDate(entry.verified_at) }}</p>
        </div>
      </div>
    </div>

    <!-- État vide -->
    <p v-else class="text-sm text-taupe italic text-center py-4">
      Aucun scan enregistré.
    </p>
  </div>
</template>
