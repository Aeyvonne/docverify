<script setup>
/**
 * Layout partagé pour toutes les pages authentifiées.
 * Gère la navbar + le padding-top automatique.
 * Utilisation : <AppLayout> ... contenu ... </AppLayout>
 */
import AppNavbar from '@/components/AppNavbar.vue'

defineProps({
  // Largeur max du contenu (classe Tailwind)
  maxWidth: { type: String, default: 'max-w-5xl' },
  // Fond de page
  bg: { type: String, default: 'bg-beige-light' },
})
</script>

<template>
  <div :class="['min-h-screen', bg]">
    <AppNavbar />
    <main :class="['mx-auto px-5 pt-28 pb-16', maxWidth]">
      <slot />
    </main>
  </div>
</template>
