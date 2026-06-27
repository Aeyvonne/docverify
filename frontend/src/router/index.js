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
    meta: { requiresAuth: true, role: 'emetteur', institutionOnly: true },
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

// ── Guards ─────────────────────────────────────────────────────────────
router.beforeEach((to) => {
  const auth = useAuthStore()

  // Page réservée aux non-connectés (login)
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return auth.isAdmin ? { name: 'admin' } : { name: 'dashboard' }
  }

  // Page nécessitant une connexion
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Vérification du rôle
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return auth.isAdmin ? { name: 'admin' } : { name: 'dashboard' }
  }

  // Page réservée aux institutions — bloquer les particuliers
  if (to.meta.institutionOnly && auth.user?.type_institution === 'particulier') {
    return { name: 'dashboard' }
  }
})

export default router
