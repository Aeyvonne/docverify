<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import AppLayout from '@/components/AppLayout.vue'
import BadgeStatut from '@/components/BadgeStatut.vue'
import { useAuthStore } from '@/stores/auth'
import { useDocumentsStore } from '@/stores/documents'

const auth  = useAuthStore()
const store = useDocumentsStore()

onMounted(() => store.fetchAll())

const stats = computed(() => ({
  total:    store.list.length,
  actifs:   store.list.filter(d => d.statut === 'actif').length,
  revoques: store.list.filter(d => d.statut === 'revoque').length,
  recent:   store.list.slice(0, 5),
}))

function formatDate(d) {
  if (!d) return '—'
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(d))
}

const typeIcon = {
  diplome: '🎓', contrat: '📋', certificat: '🏅',
  attestation: '📄', facture: '🧾', rapport: '📊',
}
function getIcon(type) { return typeIcon[type?.toLowerCase()] ?? '📄' }
</script>

<template>
  <AppLayout>

    <!-- ── En-tête avec gradient ── -->
    <div class="dashboard-hero mb-8 fade-in-up">
      <div class="dashboard-hero-inner">
        <p class="eyebrow">Tableau de bord</p>
        <div class="flex items-center flex-wrap gap-3">
          <h1 class="hero-title">
            Bonjour, {{ auth.user?.prenom ?? 'Émetteur' }} 👋
          </h1>
          <span v-if="auth.isCertified && !auth.isParticulier" class="certified-hero-badge">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round"
                    d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.745 3.745 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.745 3.745 0 0 1 3.296-1.043A3.745 3.745 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.745 3.745 0 0 1 3.296 1.043 3.745 3.745 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"/>
            </svg>
            Institution certifiée
          </span>
        </div>
        <p v-if="auth.user?.nom_institution" class="hero-sub">{{ auth.user.nom_institution }}</p>
      </div>
    </div>

    <!-- ── Alerte non certifié ── -->
    <div v-if="!auth.isCertified && !auth.isParticulier"
         class="alert-warn mb-8 fade-in-up">
      <span class="alert-icon">⚠</span>
      <div>
        <p class="alert-title">Compte non certifié</p>
        <p class="alert-sub">
          Certaines fonctionnalités sont limitées.
          <RouterLink to="/certification" class="alert-link">Faire une demande →</RouterLink>
        </p>
      </div>
    </div>

    <!-- ── Stats cards colorées ── -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-10 fade-in-up delay-100">

      <!-- Total -->
      <div class="stat-card stat-brown">
        <div class="stat-icon-wrap stat-icon-brown">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
          </svg>
        </div>
        <div class="stat-num stat-num-brown">{{ stats.total }}</div>
        <div class="stat-label">Documents total</div>
        <div class="stat-bar stat-bar-brown" :style="{ width: '100%' }"></div>
      </div>

      <!-- Actifs -->
      <div class="stat-card stat-sage">
        <div class="stat-icon-wrap stat-icon-sage">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
          </svg>
        </div>
        <div class="stat-num stat-num-sage">{{ stats.actifs }}</div>
        <div class="stat-label">Actifs &amp; valides</div>
        <div class="stat-bar stat-bar-sage"
             :style="{ width: stats.total ? `${(stats.actifs / stats.total) * 100}%` : '0%' }"></div>
      </div>

      <!-- Révoqués -->
      <div class="stat-card stat-terra">
        <div class="stat-icon-wrap stat-icon-terra">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
        </div>
        <div class="stat-num stat-num-terra">{{ stats.revoques }}</div>
        <div class="stat-label">Révoqués</div>
        <div class="stat-bar stat-bar-terra"
             :style="{ width: stats.total ? `${(stats.revoques / stats.total) * 100}%` : '0%' }"></div>
      </div>
    </div>

    <!-- ── Actions rapides ── -->
    <div class="flex flex-col sm:flex-row gap-3 mb-10 fade-in-up delay-200">
      <RouterLink to="/documents/new"
                  class="btn-primary flex items-center gap-2 justify-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Certifier un document
      </RouterLink>
      <RouterLink to="/documents" class="btn-secondary flex items-center justify-center">
        Voir tous les documents
      </RouterLink>
    </div>

    <!-- ── Documents récents ── -->
    <section class="fade-in-up delay-300">
      <div class="flex items-center justify-between mb-4">
        <h2 class="section-title">Derniers documents</h2>
        <RouterLink to="/documents" class="see-all-link">Voir tout →</RouterLink>
      </div>

      <div v-if="store.loading" class="flex justify-center py-10">
        <div class="w-7 h-7 border-2 border-sand border-t-brown rounded-full animate-spin"></div>
      </div>

      <div v-else-if="stats.recent.length" class="space-y-2">
        <RouterLink v-for="doc in stats.recent" :key="doc.id" to="/documents"
                    class="doc-row group">
          <!-- Icône -->
          <div class="doc-row-icon group-hover:scale-110 transition-transform">
            {{ getIcon(doc.type) }}
          </div>

          <!-- Infos -->
          <div class="flex-1 min-w-0">
            <p class="doc-row-title">{{ doc.titre }}</p>
            <p class="doc-row-sub">{{ doc.type }} · {{ formatDate(doc.date_emission) }}</p>
          </div>

          <!-- Badge -->
          <BadgeStatut :statut="doc.statut" size="sm" />

          <!-- Flèche -->
          <svg class="w-4 h-4 text-taupe opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
               fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
          </svg>
        </RouterLink>
      </div>

      <div v-else class="card-premium p-14 text-center">
        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4"
             style="background:#E8DCCB;">
          <span style="font-size:1.75rem;">📄</span>
        </div>
        <p class="text-taupe text-sm mb-5">Aucun document certifié pour le moment.</p>
        <RouterLink to="/documents/new" class="btn-primary inline-flex text-sm px-6">
          Certifier mon premier document
        </RouterLink>
      </div>
    </section>

  </AppLayout>
</template>

<style scoped>
/* ── Hero ── */
.dashboard-hero {
  background: linear-gradient(135deg, #4A372C 0%, #6B4F3F 50%, #7C6050 100%);
  border-radius: 20px;
  padding: 2px;
  box-shadow: 0 8px 32px rgba(74,55,44,0.2);
}
.dashboard-hero-inner {
  background: linear-gradient(135deg, rgba(251,247,240,0.96), rgba(242,233,222,0.96));
  border-radius: 18px;
  padding: 1.75rem 2rem;
}
.eyebrow {
  font-family: 'Poppins', sans-serif;
  font-size: 0.65rem; font-weight: 600;
  letter-spacing: 0.22em; text-transform: uppercase;
  color: #8C7A6B; margin-bottom: 0.5rem;
}
.hero-title {
  font-family: 'Poppins', sans-serif;
  font-size: clamp(1.5rem,4vw,2rem);
  font-weight: 700; color: #3A2E26;
}
.hero-sub { font-size: 0.875rem; color: #6B4F3F; margin-top: 0.25rem; }

.certified-hero-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 5px 12px; border-radius: 20px;
  font-size: 0.75rem; font-weight: 600;
  background: linear-gradient(135deg, #5a7a4e, #7C9070);
  color: #fff;
  box-shadow: 0 4px 12px rgba(124,144,112,0.3);
}

/* ── Alerte ── */
.alert-warn {
  display: flex; align-items: flex-start; gap: 12px;
  padding: 14px 16px; border-radius: 14px;
  background: rgba(201,154,60,0.07);
  border: 1px solid rgba(201,154,60,0.25);
}
.alert-icon { font-size: 1.25rem; flex-shrink: 0; color: #C99A3C; }
.alert-title { font-size: 0.875rem; font-weight: 600; color: #3A2E26; }
.alert-sub { font-size: 0.75rem; color: #8C7A6B; margin-top: 2px; }
.alert-link { color: #6B4F3F; text-decoration: underline; margin-left: 4px; }
.alert-link:hover { color: #4A372C; }

/* ── Stat cards ── */
.stat-card {
  border-radius: 16px;
  padding: 1.25rem 1.5rem;
  position: relative;
  overflow: hidden;
}
.stat-brown { background: linear-gradient(135deg,rgba(74,55,44,0.08),rgba(107,79,63,0.05)); border:1px solid rgba(74,55,44,0.15); }
.stat-sage  { background: linear-gradient(135deg,rgba(124,144,112,0.1),rgba(90,122,78,0.06)); border:1px solid rgba(124,144,112,0.2); }
.stat-terra { background: linear-gradient(135deg,rgba(181,83,60,0.08),rgba(140,53,32,0.05)); border:1px solid rgba(181,83,60,0.18); }

.stat-icon-wrap {
  width: 38px; height: 38px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 0.75rem;
}
.stat-icon-brown { background:rgba(74,55,44,0.12); color:#4A372C; }
.stat-icon-sage  { background:rgba(124,144,112,0.15); color:#4a6640; }
.stat-icon-terra { background:rgba(181,83,60,0.12); color:#B5533C; }

.stat-num {
  font-family: 'Poppins', sans-serif;
  font-size: clamp(2rem,5vw,3rem);
  font-weight: 800; line-height: 1;
  margin-bottom: 0.25rem;
}
.stat-num-brown { color:#4A372C; }
.stat-num-sage  { color:#4a6640; }
.stat-num-terra { color:#B5533C; }

.stat-label {
  font-size: 0.75rem; color: #8C7A6B;
  text-transform: uppercase; letter-spacing: 0.1em;
  margin-bottom: 0.75rem;
}

.stat-bar {
  height: 3px; border-radius: 2px;
  transition: width 1s cubic-bezier(0.22,1,0.36,1);
}
.stat-bar-brown { background:linear-gradient(to right,#4A372C,#6B4F3F); }
.stat-bar-sage  { background:linear-gradient(to right,#5a7a4e,#7C9070); }
.stat-bar-terra { background:linear-gradient(to right,#8c3520,#B5533C); }

/* ── Section title ── */
.section-title {
  font-family: 'Poppins', sans-serif;
  font-size: 0.8rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.12em;
  color: #4A372C;
}
.see-all-link {
  font-size: 0.75rem; color: #6B4F3F;
  text-decoration: none; font-weight: 500;
  transition: color 0.15s;
}
.see-all-link:hover { color: #4A372C; }

/* ── Doc row ── */
.doc-row {
  display: flex; align-items: center; gap: 14px;
  background: rgba(251,247,240,0.9);
  border: 1px solid rgba(217,198,168,0.45);
  border-radius: 14px;
  padding: 14px 16px;
  text-decoration: none;
  transition: all 0.2s ease;
  box-shadow: 0 1px 4px rgba(74,55,44,0.04);
}
.doc-row:hover {
  box-shadow: 0 6px 24px rgba(74,55,44,0.1);
  border-color: rgba(140,122,107,0.35);
  transform: translateY(-1px);
}
.doc-row-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: #E8DCCB;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; flex-shrink: 0;
}
.doc-row-title {
  font-family: 'Poppins', sans-serif;
  font-weight: 600; font-size: 0.875rem;
  color: #3A2E26; truncate: true;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.doc-row-sub { font-size: 0.7rem; color: #8C7A6B; margin-top: 2px; text-transform: capitalize; }
</style>
