<script setup>
import { ref, computed } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth       = useAuthStore()
const notifStore = useNotificationsStore()
const route      = useRoute()
const router     = useRouter()
const sidebarOpen = ref(false)

const initiales = computed(() => {
  const u = auth.user
  return u ? (u.prenom?.[0] ?? '') + (u.nom?.[0] ?? '') : 'V'
})

const navItems = [
  { to: '/validateur',          icon: 'home',   label: 'Tableau de bord' },
  { to: '/validateur/demandes', icon: 'inbox',  label: 'Demandes',       badge: true },
  { to: '/validateur/profil',   icon: 'cog',    label: 'Mon profil' },
]

function isActive(to) {
  if (to === '/validateur') return route.path === '/validateur'
  return route.path.startsWith(to)
}

async function handleLogout() {
  notifStore.reset()
  await auth.logout()
  router.push({ name: 'login' })
}

defineProps({ maxWidth: { type: String, default: 'max-w-5xl' } })
</script>

<template>
  <div class="min-h-screen flex" style="background:#F5EFE6;">

    <!-- Overlay mobile -->
    <Transition name="overlay">
      <div v-if="sidebarOpen" class="fixed inset-0 z-30 lg:hidden"
           style="background:rgba(42,30,22,0.45);backdrop-filter:blur(3px);"
           @click="sidebarOpen = false" />
    </Transition>

    <!-- Sidebar -->
    <aside class="fixed top-0 left-0 h-full z-40 flex flex-col"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           style="width:240px;background:#1A2E20;transition:transform 0.28s cubic-bezier(.4,0,.2,1);">

      <!-- Logo -->
      <div class="flex items-center gap-3 px-5 py-5 border-b" style="border-color:rgba(134,198,112,0.1);">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(134,198,112,0.12);">
          <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="#86C670" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
          </svg>
        </div>
        <div>
          <p class="font-display font-light text-lg leading-none" style="color:#A8D89A;letter-spacing:0.1em;">DocVerify</p>
          <p class="text-xs mt-0.5" style="color:rgba(168,216,154,0.4);">Validation</p>
        </div>
        <button @click="sidebarOpen = false" class="ml-auto lg:hidden p-1 rounded"
                style="color:rgba(168,216,154,0.5);background:none;border:none;cursor:pointer;">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <RouterLink v-for="item in navItems" :key="item.to" :to="item.to"
                    @click="sidebarOpen = false"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm transition-all no-underline relative"
                    :style="isActive(item.to)
                      ? 'background:rgba(134,198,112,0.15);color:#A8D89A;'
                      : 'color:rgba(168,216,154,0.45);'">

          <div v-if="isActive(item.to)"
               class="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-5 rounded-r"
               style="background:#86C670;"></div>

          <!-- Icônes -->
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"
               :style="isActive(item.to) ? 'color:#86C670;' : 'color:rgba(134,198,112,0.35);'">
            <template v-if="item.icon === 'home'">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </template>
            <template v-else-if="item.icon === 'inbox'">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z"/>
            </template>
            <template v-else-if="item.icon === 'cog'">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </template>
          </svg>

          <span class="flex-1 font-medium">{{ item.label }}</span>

          <span v-if="item.badge && notifStore.nonLues > 0"
                class="text-xs font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center"
                style="background:#4a6640;color:#fff;font-size:0.6rem;">
            {{ notifStore.nonLues }}
          </span>
        </RouterLink>
      </nav>

      <!-- Profil + déconnexion -->
      <div class="px-3 pb-4 pt-3 border-t" style="border-color:rgba(134,198,112,0.08);">
        <div class="flex items-center gap-3 px-3 py-2.5 rounded-xl mb-1"
             style="background:rgba(134,198,112,0.06);">
          <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
               style="background:rgba(134,198,112,0.15);color:#86C670;">
            {{ initiales }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate" style="color:#A8D89A;">
              {{ auth.user?.prenom }} {{ auth.user?.nom }}
            </p>
            <p class="text-xs truncate" style="color:rgba(168,216,154,0.4);">Validateur</p>
          </div>
        </div>
        <button @click="handleLogout"
                class="flex items-center gap-3 px-3 py-2 rounded-xl text-sm w-full transition-all"
                style="color:rgba(168,216,154,0.35);background:none;border:none;cursor:pointer;">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
          </svg>
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0 lg:pl-60">

      <!-- Topbar mobile -->
      <header class="lg:hidden flex items-center justify-between px-4 h-14 border-b flex-shrink-0"
              style="background:#1A2E20;border-color:rgba(134,198,112,0.08);">
        <button @click="sidebarOpen = true" class="p-2 rounded-lg"
                style="color:rgba(168,216,154,0.7);background:none;border:none;cursor:pointer;">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
          </svg>
        </button>
        <span class="font-display font-light text-lg" style="color:#A8D89A;letter-spacing:0.1em;">DocVerify</span>
        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
             style="background:rgba(134,198,112,0.15);color:#86C670;">{{ initiales }}</div>
      </header>

      <main :class="['mx-auto w-full px-5 py-8 flex-1', maxWidth]">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.overlay-enter-active,.overlay-leave-active{transition:opacity 0.25s ease}
.overlay-enter-from,.overlay-leave-to{opacity:0}
</style>
