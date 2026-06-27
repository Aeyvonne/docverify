<script setup>
/**
 * Badge de statut d'un document.
 * Reçoit directement les valeurs retournées par l'API : statut, statut_label, statut_color
 */
const props = defineProps({
  statut:      { type: String, required: true },          // 'valide' | 'revoque' | 'expire'
  label:       { type: String, default: null },           // texte affiché (override optionnel)
  size:        { type: String, default: 'md' },           // 'sm' | 'md' | 'lg' | 'hero'
  animated:    { type: Boolean, default: false },         // effet flottant pour la page verify
})

const labels = {
  valide:  'Valide',
  revoque: 'Révoqué',
  expire:  'Expiré',
}

const icons = {
  valide:  '✓',
  revoque: '✕',
  expire:  '⏱',
}

const sizeClasses = {
  sm:   'text-xs px-2.5 py-1 gap-1',
  md:   'text-sm px-4 py-1.5 gap-1.5',
  lg:   'text-base px-6 py-2.5 gap-2',
  hero: 'text-xl px-8 py-4 gap-3',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full font-medium tracking-wide select-none',
      `badge-${statut}`,
      sizeClasses[size],
      animated ? 'badge-float' : '',
    ]"
  >
    <!-- Icône -->
    <span :class="size === 'hero' ? 'text-2xl' : ''">{{ icons[statut] ?? '?' }}</span>
    <!-- Texte -->
    <span>{{ label ?? labels[statut] ?? statut }}</span>
  </span>
</template>
