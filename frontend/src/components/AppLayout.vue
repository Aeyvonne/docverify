<script setup>
import AppNavbar from '@/components/AppNavbar.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

defineProps({
  maxWidth: { type: String, default: 'max-w-5xl' },
  bg:       { type: String, default: 'bg-beige-light' },
})
</script>

<template>
  <div :class="['min-h-screen', bg]">
    <AppNavbar v-if="!auth.isAdmin" />
    <main :class="['mx-auto px-5 pb-16', maxWidth, auth.isAdmin ? 'pt-8' : 'pt-28']">
      <slot />
    </main>
  </div>
</template>
