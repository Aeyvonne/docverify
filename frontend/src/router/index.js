import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // ── Public ────────────────────────────────────────────────────────────
  {
    path: '/',
    name: 'landing',
    component: () => import('@/views/AccueilView.vue'),
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/views/RegisterView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/views/ForgotPasswordView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/views/ResetPasswordView.vue'),
    meta: { guestOnly: true },
  },
  {
    path: '/change-password',
    name: 'change-password',
    component: () => import('@/views/ChangePasswordView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/verify/:token',
    name: 'verify',
    component: () => import('@/views/verify/VerifyView.vue'),
  },

  // ── Émetteur ──────────────────────────────────────────────────────────
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/views/emetteur/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'emetteur' },
  },
  {
    path: '/documents',
    name: 'documents',
    component: () => import('@/views/emetteur/DocumentsView.vue'),
    meta: { requiresAuth: true, role: 'emetteur' },
  },
  {
    path: '/documents/new',
    name: 'documents.new',
    component: () => import('@/views/emetteur/UploadDocumentView.vue'),
    meta: { requiresAuth: true, role: 'emetteur' },
  },
  {
    path: '/certification',
    name: 'certification',
    component: () => import('@/views/emetteur/CertificationView.vue'),
    meta: { requiresAuth: true, role: 'emetteur' },
  },

  // ── Admin ─────────────────────────────────────────────────────────────
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/admin/AdminDashboardView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/emetteurs',
    name: 'admin.emetteurs',
    component: () => import('@/views/admin/EmetteursView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/demandes',
    name: 'admin.demandes',
    component: () => import('@/views/admin/DemandesView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/admins',
    name: 'admin.admins',
    component: () => import('@/views/admin/AdminsView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/admin/parametres',
    name: 'admin.parametres',
    component: () => import('@/views/admin/ParametresView.vue'),
    meta: { requiresAuth: true, role: 'admin' },
  },

  // ── Validateur ────────────────────────────────────────────────────────
  {
    path: '/validateur',
    name: 'validateur',
    component: () => import('@/views/validateur/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'validateur' },
  },
  {
    path: '/validateur/demandes',
    name: 'validateur.demandes',
    component: () => import('@/views/validateur/DemandesView.vue'),
    meta: { requiresAuth: true, role: 'validateur' },
  },
  {
    path: '/validateur/profil',
    name: 'validateur.profil',
    component: () => import('@/views/validateur/ProfilView.vue'),
    meta: { requiresAuth: true, role: 'validateur' },
  },

  // ── 404 ───────────────────────────────────────────────────────────────
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// Retourne la route d'accueil selon le rôle
function homeRoute(role) {
  if (role === 'admin')      return { name: 'admin' }
  if (role === 'validateur') return { name: 'validateur' }
  return { name: 'dashboard' }
}

// ── Guards ─────────────────────────────────────────────────────────────
router.beforeEach((to) => {
  const auth = useAuthStore()
  const role = auth.user?.role

  // Page réservée aux non-connectés
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return homeRoute(role)
  }

  // Page nécessitant une connexion
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Vérification du rôle — redirige vers sa propre home si mauvais rôle
  if (to.meta.role && role !== to.meta.role) {
    // /change-password n'a pas de meta.role → accessible à tous
    return homeRoute(role)
  }
})

export default router
