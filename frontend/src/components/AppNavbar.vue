<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth     = useAuthStore()
const router   = useRouter()
const menuOpen = ref(false)
const notifOpen = ref(false)
const notifStore = useNotificationsStore()

onMounted(() => {
  if (auth.isAdmin) notifStore.startPolling()
})
onUnmounted(() => notifStore.stopPolling())

async function handleLogout() {
  notifStore.reset()
  await auth.logout()
  router.push({ name: 'login' })
}

function toggleNotif() {
  notifOpen.value = !notifOpen.value
  if (notifOpen.value && notifStore.nonLues > 0) notifStore.markRead()
}

function formatDate(d) {
  return new Intl.DateTimeFormat('fr-FR', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }).format(new Date(d))
}
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 z-50"
    style="
      background: rgba(242, 233, 222, 0.72);
      backdrop-filter: blur(24px) saturate(1.4);
      -webkit-backdrop-filter: blur(24px) saturate(1.4);
      border-bottom: 1px solid rgba(217, 198, 168, 0.45);
      box-shadow: 0 2px 20px rgba(74,55,44,0.08), 0 1px 0 rgba(255,255,255,0.6) inset;
    "
  >
    <nav class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">

      <!-- Logo -->
      <RouterLink
        :to="auth.isAuthenticated ? (auth.isAdmin ? '/admin' : '/dashboard') : '/'"
        style="text-decoration:none; display:flex; align-items:center;"
      >
        <span style="font-family:'Cormorant',Georgia,serif; font-size:1.85rem;
                     font-weight:300; letter-spacing:0.14em; color:#4A372C;">
          DocVerify
        </span>
      </RouterLink>

      <!-- Navigation desktop -->
      <div class="hidden md:flex items-center gap-7">

        <!-- Émetteur -->
        <template v-if="auth.isEmetteur">
          <RouterLink to="/dashboard"     class="nav-link">Tableau de bord</RouterLink>
          <RouterLink to="/documents"     class="nav-link">Mes documents</RouterLink>
          <!-- Certification : uniquement pour les institutions, pas les particuliers -->
          <RouterLink v-if="!auth.isParticulier" to="/certification" class="nav-link">Certification</RouterLink>
          <RouterLink to="/documents/new" class="nav-btn-primary">+ Certifier</RouterLink>
        </template>

        <!-- Admin -->
        <template v-else-if="auth.isAdmin">
          <RouterLink to="/admin"           class="nav-link">Statistiques</RouterLink>
          <RouterLink to="/admin/emetteurs" class="nav-link">Émetteurs</RouterLink>

          <!-- Demandes avec badge count -->
          <RouterLink to="/admin/demandes" class="nav-link" style="display:inline-flex; align-items:center; gap:6px;">
            Demandes
            <span v-if="notifStore.nonLues > 0" class="notif-badge">{{ notifStore.nonLues }}</span>
          </RouterLink>

          <RouterLink to="/admin/admins" class="nav-link">Admins</RouterLink>

          <!-- Cloche notifications -->
          <div class="relative">
            <!-- Overlay pour fermer en cliquant dehors -->
            <div v-if="notifOpen"
                 class="fixed inset-0 z-40"
                 @click="notifOpen = false"></div>

            <button @click="toggleNotif"
                    class="notif-bell"
                    :class="{ 'notif-bell-active': notifStore.nonLues > 0 }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
              </svg>
              <span v-if="notifStore.nonLues > 0" class="notif-dot">{{ notifStore.nonLues }}</span>
            </button>

            <!-- Dropdown notifications -->
            <Transition name="notif-drop">
              <div v-if="notifOpen"
                   class="notif-dropdown"
                   @click.stop>

                <div class="notif-dropdown-header">
                  <span>Notifications</span>
                  <span v-if="notifStore.notifications.length" class="notif-count-pill">
                    {{ notifStore.notifications.length }}
                  </span>
                </div>

                <div v-if="!notifStore.notifications.length"
                     class="notif-empty">Aucune notification</div>

                <div v-else class="notif-list">
                  <RouterLink
                    v-for="n in notifStore.notifications" :key="n.id"
                    to="/admin/demandes"
                    class="notif-item"
                    :class="{ 'notif-item-unread': !n.lu }"
                    @click="notifOpen = false">
                    <div class="notif-item-dot" :class="n.lu ? 'notif-dot-read' : 'notif-dot-unread'"></div>
                    <div class="flex-1 min-w-0">
                      <p class="notif-item-msg">{{ n.message }}</p>
                      <p class="notif-item-date">{{ formatDate(n.created_at) }}</p>
                    </div>
                  </RouterLink>
                </div>

              </div>
            </Transition>
          </div>

        </template>

        <!-- Non connecté -->
        <template v-else>
          <RouterLink to="/login"    class="nav-link">Connexion</RouterLink>
          <RouterLink to="/register" class="nav-btn-primary">S'inscrire</RouterLink>
        </template>

        <!-- Déconnexion -->
        <button v-if="auth.isAuthenticated" @click="handleLogout" class="nav-link"
                style="background:none; border:none; cursor:pointer;">
          Déconnexion
        </button>
      </div>

      <!-- Burger mobile -->
      <button @click="menuOpen = !menuOpen"
              class="md:hidden p-2 rounded-lg"
              style="color:#4A372C; background:none; border:none; cursor:pointer;"
              aria-label="Menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path v-if="!menuOpen" stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
          <path v-else stroke-linecap="round" stroke-linejoin="round"
                d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </nav>

    <!-- Menu mobile -->
    <Transition name="slide-down">
      <div v-if="menuOpen"
           style="border-top:1px solid rgba(217,198,168,0.35);
                  background:rgba(251,247,240,0.92);
                  backdrop-filter:blur(20px);
                  -webkit-backdrop-filter:blur(20px);">
        <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col gap-4">
          <template v-if="auth.isEmetteur">
            <RouterLink to="/dashboard"     class="nav-link" @click="menuOpen=false">Tableau de bord</RouterLink>
            <RouterLink to="/documents"     class="nav-link" @click="menuOpen=false">Mes documents</RouterLink>
            <RouterLink v-if="!auth.isParticulier" to="/certification" class="nav-link" @click="menuOpen=false">Certification</RouterLink>
            <RouterLink to="/documents/new" class="nav-link font-medium" @click="menuOpen=false">+ Certifier</RouterLink>
          </template>
          <template v-else-if="auth.isAdmin">
            <RouterLink to="/admin"           class="nav-link" @click="menuOpen=false">Statistiques</RouterLink>
            <RouterLink to="/admin/emetteurs" class="nav-link" @click="menuOpen=false">Émetteurs</RouterLink>
            <RouterLink to="/admin/demandes"  class="nav-link" @click="menuOpen=false"
                        style="display:inline-flex; align-items:center; gap:6px;">
              Demandes
              <span v-if="notifStore.nonLues > 0" class="notif-badge">{{ notifStore.nonLues }}</span>
            </RouterLink>
            <RouterLink to="/admin/admins"    class="nav-link" @click="menuOpen=false">Admins</RouterLink>
          </template>
          <template v-else>
            <RouterLink to="/login"    class="nav-link" @click="menuOpen=false">Connexion</RouterLink>
            <RouterLink to="/register" class="nav-link font-medium" @click="menuOpen=false">S'inscrire</RouterLink>
          </template>
          <button v-if="auth.isAuthenticated" @click="handleLogout"
                  class="nav-link text-left"
                  style="background:none; border:none; cursor:pointer;">
            Déconnexion
          </button>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
/* ── Notif badge sur "Demandes" ── */
.notif-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 9px;
  font-size: 0.65rem;
  font-weight: 700;
  background: #B5533C;
  color: #fff;
  line-height: 1;
}

/* ── Cloche ── */
.notif-bell {
  position: relative;
  width: 34px; height: 34px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  background: transparent;
  border: 1px solid rgba(217,198,168,0.5);
  color: #4A372C;
  cursor: pointer;
  transition: background 0.18s;
}
.notif-bell:hover        { background: #E8DCCB; }
.notif-bell-active       { background: rgba(181,83,60,0.08); border-color: rgba(181,83,60,0.3); }
.notif-bell-active:hover { background: rgba(181,83,60,0.14); }

.notif-dot {
  position: absolute;
  top: -5px; right: -5px;
  min-width: 16px; height: 16px;
  padding: 0 4px;
  border-radius: 8px;
  background: #B5533C;
  color: #fff;
  font-size: 0.6rem;
  font-weight: 700;
  display: flex; align-items: center; justify-content: center;
  border: 2px solid #F2E9DE;
}

/* ── Dropdown ── */
.notif-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  width: 320px;
  background: #FBF7F0;
  border: 1px solid rgba(217,198,168,0.6);
  border-radius: 14px;
  box-shadow: 0 8px 32px rgba(74,55,44,0.14);
  z-index: 100;
  overflow: hidden;
}
.notif-dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #8C7A6B;
  border-bottom: 1px solid #E8DCCB;
}
.notif-count-pill {
  background: #E8DCCB;
  color: #4A372C;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 10px;
}
.notif-empty {
  padding: 24px 16px;
  text-align: center;
  font-size: 0.8rem;
  color: #8C7A6B;
}
.notif-list { max-height: 320px; overflow-y: auto; }

.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  padding: 11px 16px;
  border-bottom: 1px solid rgba(217,198,168,0.3);
  text-decoration: none;
  transition: background 0.15s;
  cursor: pointer;
}
.notif-item:last-child    { border-bottom: none; }
.notif-item:hover         { background: #F2E9DE; }
.notif-item-unread        { background: rgba(181,83,60,0.04); }
.notif-item-unread:hover  { background: rgba(181,83,60,0.08); }

.notif-item-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 5px;
}
.notif-dot-unread { background: #B5533C; }
.notif-dot-read   { background: #D9C6A8; }

.notif-item-msg {
  font-size: 0.8rem;
  color: #3A2E26;
  line-height: 1.4;
}
.notif-item-date {
  font-size: 0.68rem;
  color: #8C7A6B;
  margin-top: 3px;
}

/* ── Transition dropdown ── */
.notif-drop-enter-active, .notif-drop-leave-active { transition: all 0.2s ease; }
.notif-drop-enter-from, .notif-drop-leave-to       { opacity: 0; transform: translateY(-8px); }

.nav-link {
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  color: #4A372C;
  text-decoration: none;
  letter-spacing: 0.025em;
  transition: color 0.18s ease;
  background: none;
  border: none;
  padding: 0;
  position: relative;
}
.nav-link:hover { color: #6B4F3F; }
.router-link-active.nav-link {
  color: #6B4F3F;
  font-weight: 600;
}
.router-link-active.nav-link::after {
  content: '';
  position: absolute;
  bottom: -4px;
  left: 0; right: 0;
  height: 2px;
  background: #6B4F3F;
  border-radius: 1px;
}

.nav-btn-primary {
  font-family: 'Inter', sans-serif;
  font-size: 0.8rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  color: #FBF7F0;
  background: #4A372C;
  border: none;
  border-radius: 8px;
  padding: 0.5rem 1.25rem;
  text-decoration: none;
  cursor: pointer;
  transition: background 0.18s ease;
  display: inline-flex;
  align-items: center;
}
.nav-btn-primary:hover { background: #6B4F3F; }

.slide-down-enter-active,
.slide-down-leave-active { transition: all 0.22s ease; }
.slide-down-enter-from,
.slide-down-leave-to     { opacity: 0; transform: translateY(-6px); }
</style>
