<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth       = useAuthStore()
const router     = useRouter()
const route      = useRoute()
const menuOpen   = ref(false)
const profilOpen = ref(false)
const notifStore = useNotificationsStore()

onMounted(() => {
  if (auth.isAdmin) notifStore.startPolling()
})
onUnmounted(() => notifStore.stopPolling())

const initiales = computed(() => {
  const u = auth.user
  return u ? (u.prenom?.[0] ?? '') + (u.nom?.[0] ?? '') : '?'
})

const nomComplet = computed(() => {
  const u = auth.user
  return u ? `${u.prenom ?? ''} ${u.nom ?? ''}`.trim() : ''
})

async function handleLogout() {
  profilOpen.value = false
  menuOpen.value   = false
  notifStore.reset()
  await auth.logout()
  router.push({ name: 'login' })
}

// Fermer le dropdown profil en cliquant dehors
function onClickOutside(e) {
  if (!e.target.closest('.profil-dropdown-wrap')) profilOpen.value = false
}
onMounted(() => document.addEventListener('click', onClickOutside))
onUnmounted(() => document.removeEventListener('click', onClickOutside))
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
      <div class="hidden md:flex items-center gap-6">

        <!-- Émetteur — liens de navigation -->
        <template v-if="auth.isEmetteur">
          <RouterLink to="/dashboard"     class="nav-link">Tableau de bord</RouterLink>
          <RouterLink to="/documents"     class="nav-link">Mes documents</RouterLink>
          <RouterLink to="/certification" class="nav-link">Certification</RouterLink>
          <RouterLink to="/documents/new" class="nav-btn-primary">+ Certifier</RouterLink>
        </template>

        <!-- Admin — géré par la sidebar -->
        <template v-else-if="auth.isAdmin" />

        <!-- Non connecté -->
        <template v-else>
          <RouterLink to="/login"    class="nav-link">Connexion</RouterLink>
          <RouterLink to="/register" class="nav-btn-primary">S'inscrire</RouterLink>
        </template>

        <!-- ── Dropdown profil (émetteurs connectés) ── -->
        <div v-if="auth.isEmetteur" class="profil-dropdown-wrap relative">
          <button
            @click.stop="profilOpen = !profilOpen"
            class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all"
            :style="profilOpen
              ? 'background:rgba(74,55,44,0.1);'
              : 'background:transparent;'"
            style="border:1px solid rgba(217,198,168,0.6);cursor:pointer;"
          >
            <!-- Avatar initiales -->
            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                 :style="auth.isCertified
                   ? 'background:#4a6640;color:#FBF7F0;'
                   : 'background:#4A372C;color:#FBF7F0;'">
              {{ initiales }}
            </div>
            <!-- Nom (masqué sur petits écrans) -->
            <span class="text-sm font-medium hidden lg:block" style="color:#3A2E26;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              {{ nomComplet }}
            </span>
            <!-- Badge certifié -->
            <span v-if="auth.isCertified"
                  class="hidden lg:flex items-center gap-1 text-xs px-1.5 py-0.5 rounded-full"
                  style="background:rgba(74,166,64,0.1);color:#4a6640;">
              <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
              </svg>
            </span>
            <!-- Chevron -->
            <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform"
                 :style="profilOpen ? 'transform:rotate(180deg);' : ''"
                 style="color:#8C7A6B;"
                 fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
            </svg>
          </button>

          <!-- Dropdown menu -->
          <Transition name="dropdown">
            <div v-if="profilOpen"
                 class="absolute right-0 mt-2 w-64 rounded-2xl overflow-hidden"
                 style="background:#FBF7F0;border:1px solid rgba(217,198,168,0.6);box-shadow:0 8px 32px rgba(74,55,44,0.14);z-index:100;">

              <!-- Header profil -->
              <div class="px-4 py-4" style="border-bottom:1px solid #E8DCCB;">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                       :style="auth.isCertified ? 'background:#4a6640;color:#FBF7F0;' : 'background:#4A372C;color:#FBF7F0;'">
                    {{ initiales }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold truncate" style="color:#3A2E26;">{{ nomComplet }}</p>
                    <p class="text-xs truncate" style="color:#8C7A6B;">{{ auth.user?.email }}</p>
                    <p v-if="auth.user?.nom_institution" class="text-xs truncate mt-0.5" style="color:#8C7A6B;">
                      {{ auth.user.nom_institution }}
                    </p>
                  </div>
                </div>
                <!-- Badge statut -->
                <div class="mt-3">
                  <span v-if="auth.isCertified"
                        class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium"
                        style="background:rgba(74,166,64,0.1);color:#4a6640;border:1px solid rgba(74,166,64,0.2);">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
                    </svg>
                    Institution certifiée
                  </span>
                  <span v-else
                        class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium"
                        style="background:rgba(201,154,60,0.1);color:#8a6010;border:1px solid rgba(201,154,60,0.25);">
                    ⏳ En attente de certification
                  </span>
                </div>
              </div>

              <!-- Menu items -->
              <div class="py-1.5">
                <RouterLink to="/change-password"
                            @click="profilOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm no-underline transition-colors"
                            style="color:#3A2E26;">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                       style="background:rgba(74,55,44,0.08);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:#4A372C;">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                    </svg>
                  </div>
                  Changer le mot de passe
                </RouterLink>

                <RouterLink to="/certification"
                            @click="profilOpen = false"
                            class="flex items-center gap-3 px-4 py-2.5 text-sm no-underline transition-colors"
                            style="color:#3A2E26;">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                       style="background:rgba(74,55,44,0.08);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:#4A372C;">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                    </svg>
                  </div>
                  Certification institution
                </RouterLink>
              </div>

              <!-- Séparateur + Déconnexion -->
              <div style="border-top:1px solid #E8DCCB;" class="py-1.5">
                <button @click="handleLogout"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm transition-colors"
                        style="background:none;border:none;cursor:pointer;color:#B5533C;">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                       style="background:rgba(181,83,60,0.08);">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" style="color:#B5533C;">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                    </svg>
                  </div>
                  Déconnexion
                </button>
              </div>
            </div>
          </Transition>
        </div>

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
                  background:rgba(251,247,240,0.97);
                  backdrop-filter:blur(20px);
                  -webkit-backdrop-filter:blur(20px);">
        <div class="max-w-6xl mx-auto px-6 py-5 flex flex-col gap-1">

          <template v-if="auth.isEmetteur">
            <!-- Infos profil mobile -->
            <div class="flex items-center gap-3 px-2 py-3 mb-2"
                 style="border-bottom:1px solid #E8DCCB;">
              <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm"
                   :style="auth.isCertified ? 'background:#4a6640;color:#FBF7F0;' : 'background:#4A372C;color:#FBF7F0;'">
                {{ initiales }}
              </div>
              <div>
                <p class="text-sm font-semibold" style="color:#3A2E26;">{{ nomComplet }}</p>
                <p class="text-xs" style="color:#8C7A6B;">{{ auth.user?.nom_institution }}</p>
              </div>
            </div>

            <RouterLink to="/dashboard"       class="mobile-link" @click="menuOpen=false">Tableau de bord</RouterLink>
            <RouterLink to="/documents"       class="mobile-link" @click="menuOpen=false">Mes documents</RouterLink>
            <RouterLink to="/certification"   class="mobile-link" @click="menuOpen=false">Certification</RouterLink>
            <RouterLink to="/documents/new"   class="mobile-link font-medium" @click="menuOpen=false">+ Certifier un document</RouterLink>

            <div style="border-top:1px solid #E8DCCB;margin:6px 0;"></div>

            <RouterLink to="/change-password" class="mobile-link" @click="menuOpen=false">Changer le mot de passe</RouterLink>

            <div style="border-top:1px solid #E8DCCB;margin:6px 0;"></div>
            <button @click="handleLogout" class="mobile-link text-left w-full"
                    style="background:none;border:none;cursor:pointer;color:#B5533C;">
              Déconnexion
            </button>
          </template>

          <template v-else>
            <RouterLink to="/login"    class="mobile-link" @click="menuOpen=false">Connexion</RouterLink>
            <RouterLink to="/register" class="mobile-link font-medium" @click="menuOpen=false">S'inscrire</RouterLink>
          </template>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
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
.router-link-active.nav-link { color: #6B4F3F; font-weight: 600; }
.router-link-active.nav-link::after {
  content: '';
  position: absolute;
  bottom: -4px; left: 0; right: 0;
  height: 2px;
  background: #6B4F3F;
  border-radius: 1px;
}

.nav-btn-primary {
  font-family: 'Inter', sans-serif;
  font-size: 0.8rem; font-weight: 500;
  letter-spacing: 0.04em;
  color: #FBF7F0;
  background: #4A372C;
  border: none; border-radius: 8px;
  padding: 0.5rem 1.25rem;
  text-decoration: none; cursor: pointer;
  transition: background 0.18s ease;
  display: inline-flex; align-items: center;
}
.nav-btn-primary:hover { background: #6B4F3F; }

.mobile-link {
  display: block;
  font-size: 0.9rem;
  color: #4A372C;
  text-decoration: none;
  padding: 10px 8px;
  border-radius: 10px;
  transition: background 0.15s;
}
.mobile-link:hover { background: rgba(217,198,168,0.3); }
.router-link-active.mobile-link { font-weight: 600; color: #6B4F3F; }

/* Dropdown animation */
.dropdown-enter-active { transition: all 0.18s cubic-bezier(0.16,1,0.3,1); }
.dropdown-leave-active { transition: all 0.14s ease; }
.dropdown-enter-from  { opacity: 0; transform: translateY(-6px) scale(0.98); }
.dropdown-leave-to    { opacity: 0; transform: translateY(-4px); }

/* Hover items dropdown */
.profil-dropdown-wrap a:hover,
.profil-dropdown-wrap button:hover { background: rgba(217,198,168,0.25); }

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.22s ease; }
.slide-down-enter-from,   .slide-down-leave-to     { opacity: 0; transform: translateY(-6px); }
</style>
