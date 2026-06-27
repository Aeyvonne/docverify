<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth     = useAuthStore()
const router   = useRouter()
const menuOpen = ref(false)

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
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
          <RouterLink to="/admin/demandes"  class="nav-link">Demandes</RouterLink>
          <RouterLink to="/admin/admins"    class="nav-link">Admins</RouterLink>
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
            <RouterLink to="/admin/demandes"  class="nav-link" @click="menuOpen=false">Demandes</RouterLink>
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
