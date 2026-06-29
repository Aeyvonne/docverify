<script setup>
import { ref, onMounted } from 'vue'
import AppNavbar from '@/components/AppNavbar.vue'
import StampAnimation from '@/components/StampAnimation.vue'
import LaptopMockup from '@/components/LaptopMockup.vue'

// Compteurs animés pour la section stats
const counts = ref({ documents: 0, institutions: 0, verifications: 0 })
const targets = { documents: 1240, institutions: 87, verifications: 9400 }

onMounted(() => {
  // Animation des compteurs au scroll
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        animateCounters()
        observer.disconnect()
      }
    })
  }, { threshold: 0.3 })

  const statsEl = document.getElementById('stats-section')
  if (statsEl) observer.observe(statsEl)
})

function animateCounters() {
  const duration = 1800
  const start = Date.now()
  const tick = () => {
    const elapsed = Date.now() - start
    const progress = Math.min(elapsed / duration, 1)
    const ease = 1 - Math.pow(1 - progress, 3) // ease-out cubic
    counts.value.documents     = Math.floor(ease * targets.documents)
    counts.value.institutions  = Math.floor(ease * targets.institutions)
    counts.value.verifications = Math.floor(ease * targets.verifications)
    if (progress < 1) requestAnimationFrame(tick)
    else {
      counts.value.documents     = targets.documents
      counts.value.institutions  = targets.institutions
      counts.value.verifications = targets.verifications
    }
  }
  requestAnimationFrame(tick)
}

const features = [
 
  // {
  //   title: 'QR Code positionnable',
  //   desc: 'L\'émetteur choisit visuellement où placer le QR Code sur son document.',
  //   svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 3.75 9.375v-4.5ZM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 0 1-1.125-1.125v-4.5ZM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0 1 13.5 9.375v-4.5Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75h.75v.75h-.75v-.75ZM6.75 16.5h.75v.75h-.75v-.75ZM16.5 6.75h.75v.75h-.75v-.75ZM13.5 13.5h.75v.75h-.75v-.75ZM13.5 19.5h.75v.75h-.75v-.75ZM19.5 13.5h.75v.75h-.75v-.75ZM19.5 19.5h.75v.75h-.75v-.75ZM16.5 16.5h.75v.75h-.75v-.75Z"/>`,
  // },
  {
    title: 'Vérification mondiale',
    desc: 'Accessible sans compte. N\'importe qui, n\'importe où, scanne et voit le statut en temps réel.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>`,
  },
  // {
  //   title: 'Historique des scans',
  //   desc: 'Chaque scan est enregistré avec l\'heure, la ville et le pays. Consultez qui a vérifié votre document.',
  //   svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>`,
  // },
  {
    title: 'Révocation instantanée',
    desc: 'Révoquez un document en deux clics. Tous les vérificateurs voient le nouveau statut immédiatement.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>`,
  },
  {
    title: 'Rapport PDF officiel',
    desc: 'Générez un rapport de vérification téléchargeable avec le statut, les métadonnées et le QR Code.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>`,
  },
]

// Cas d'usage avec SVG icons
const useCases = [
  {
    title: 'Universités',
    desc: 'Diplômes, attestations, relevés de notes — certifiés et vérifiables par les recruteurs.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/>`,
  },
  {
    title: 'Administrations',
    desc: 'Actes officiels, décisions, certificats — authentifiés et traçables pour les citoyens.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z"/>`,
  },
  {
    title: 'Entreprises',
    desc: 'Contrats, offres d\'emploi, appels d\'offres — protégés contre la falsification.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>`,
  },
  {
    title: 'Santé',
    desc: 'Ordonnances, résultats, certificats médicaux — sécurisés et traçables.',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>`,
  },
]

const laptopFeatures = [
  {
    title: 'Statut en temps réel',
    desc: 'Valide, révoqué ou expiré — le statut est toujours à jour dès le scan.',
    color: '#7C9070',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>`,
  },
  {
    title: 'Informations complètes',
    desc: "Titre, type, date d'émission, émetteur certifié — tout visible sans compte.",
    color: '#C99A3C',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>`,
  },
  {
    title: 'Rapport téléchargeable',
    desc: 'Générez un rapport PDF officiel de vérification en un clic, à conserver comme preuve.',
    color: '#B5533C',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>`,
  },
]

const steps = [
  {
    num: '01',
    title: 'Soumettez votre document',
    desc: 'Téléversez votre PDF, renseignez les informations et positionnez le QR Code où vous voulez sur la page.',
    color: '#4A372C',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>`,
  },
  {
    num: '02',
    title: 'Le document est certifié',
    desc: 'DocVerify calcule l\'empreinte cryptographique SHA-256 et tamponne le QR dans le PDF. Instantané.',
    color: '#6B4F3F',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>`,
  },
  {
    num: '03',
    title: 'Vérification en un scan',
    desc: 'Le destinataire scanne le QR avec son téléphone. Il voit le statut, l\'émetteur, la date — en temps réel.',
    color: '#7C9070',
    svg: `<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>`,
  },
]
</script>

<template>
  <div class="bg-beige-light overflow-x-hidden">
    <AppNavbar />

    <!-- ══════════════════════════════════════════════════════════════════
         HERO — Grande et impactante
    ══════════════════════════════════════════════════════════════════ -->
    <section class="relative min-h-screen flex flex-col justify-center overflow-hidden">

      <!-- Filigrane géant -->
      <span class="watermark-text select-none" aria-hidden="true">DOCVERIFY</span>

      <!-- Contenu hero -->
      <div class="relative z-10 max-w-6xl mx-auto px-6 pt-28 pb-16 lg:pt-36">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

          <!-- Texte -->
          <div>
            <!-- Eyebrow pill -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6 fade-in-up"
                 style="background:rgba(107,79,63,0.1); border:1px solid rgba(107,79,63,0.2);">
              <span class="w-2 h-2 rounded-full animate-pulse" style="background:#7C9070;"></span>
              <span class="text-xs font-display font-medium text-brown uppercase tracking-widest">
                Plateforme de certification officielle
              </span>
            </div>

            <!-- Titre principal — GRAND -->
            <h1 class="fade-in-up delay-100"
                style="font-family:'Cormorant',Georgia,serif;
                       font-size: clamp(3rem, 7vw, 5.5rem);
                       font-weight: 300;
                       line-height: 1.05;
                       color: #3A2E26;
                       margin-bottom: 1.25rem;">
              Certifiez.<br>
              <em style="font-style:italic; color:#6B4F3F;">Protégez.</em><br>
              Vérifiez.
            </h1>

            <!-- Description — taille lisible -->
            <p class="text-lg md:text-xl text-taupe leading-relaxed mb-8 max-w-lg fade-in-up delay-200"
               style="font-size: clamp(1rem, 2vw, 1.2rem);">
              DocVerify certifie vos documents PDF avec un QR Code.
              N'importe qui peut vérifier l'authenticité d'un document en un seul scan sans compte, sans installation.
            </p>

            <!-- Boutons CTA — plus grands et plus visibles -->
            <div class="flex flex-col sm:flex-row gap-3 fade-in-up delay-300">
              <RouterLink to="/register"
                          class="btn-primary text-base px-8 py-4 flex items-center justify-center gap-2">
                Créer un compte 
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                </svg>
              </RouterLink>
              <RouterLink to="/login"
                          class="btn-secondary text-base px-8 py-4 flex items-center justify-center gap-2">
                Se connecter
              </RouterLink>
            </div>

            <!-- Proof points -->
            <div class="flex flex-wrap gap-5 mt-8 fade-in-up delay-500">
              <div v-for="p in [
                { icon:'✓', text:'Gratuit pour commencer' },
                { icon:'✓', text:'Aucune installation requise' },
                { icon:'✓', text:'Vérification publique' },
              ]" :key="p.text" class="flex items-center gap-2">
                <span class="text-xs font-bold" style="color:#7C9070;">{{ p.icon }}</span>
                <span class="text-sm text-taupe">{{ p.text }}</span>
              </div>
            </div>
          </div>

          <!-- Animation + card flottante -->
          <div class="flex flex-col items-center gap-6 fade-in-up delay-200">
            <StampAnimation :loop="true" size="lg" />

            <!-- Mini card de vérification simulée -->
            <div class="card-premium px-6 py-4 w-full max-w-xs">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center"
                     style="background:rgba(124,144,112,0.15);">
                  <svg class="w-5 h-5" fill="none" stroke="#4a6640" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                  </svg>
                </div>
                <div>
                  <p class="text-xs text-taupe">Statut du document</p>
                  <p class="font-display font-semibold text-sm" style="color:#4a6640;">Document valide</p>
                </div>
              </div>
              <div class="h-px my-3" style="background:#E8DCCB;"></div>
              <p class="text-xs text-taupe">
                Émis par <strong class="text-brown-dark">ISFAD</strong><br>
                Vérifié aujourd'hui · Dakar, Sénégal
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Scroll indicator -->
      <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 opacity-50">
        <div class="w-px h-12 bg-brown/30 animate-pulse"></div>
        <span class="text-xs text-taupe tracking-widest uppercase">Découvrir</span>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         STATS — Chiffres animés
    ══════════════════════════════════════════════════════════════════ -->
    <section id="stats-section" style="background:#4A372C;" class="py-16 px-6">
      <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 text-center">
          <div>
            <p class="font-display font-black text-cream mb-1"
               style="font-size:clamp(2.5rem,6vw,4rem); line-height:1;">
              {{ counts.documents.toLocaleString('fr-FR') }}+
            </p>
            <p class="text-cream/60 text-sm uppercase tracking-widest">Documents certifiés</p>
          </div>
          <div>
            <p class="font-display font-black text-cream mb-1"
               style="font-size:clamp(2.5rem,6vw,4rem); line-height:1;">
              {{ counts.institutions }}+
            </p>
            <p class="text-cream/60 text-sm uppercase tracking-widest">Institutions</p>
          </div>
          <div>
            <p class="font-display font-black text-cream mb-1"
               style="font-size:clamp(2.5rem,6vw,4rem); line-height:1;">
              {{ counts.verifications.toLocaleString('fr-FR') }}+
            </p>
            <p class="text-cream/60 text-sm uppercase tracking-widest">Vérifications effectuées</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         LAPTOP MOCKUP — Aperçu produit
    ══════════════════════════════════════════════════════════════════ -->
    <section class="laptop-section py-24 px-6 relative overflow-hidden">

      <!-- Filigrane section -->
      <span class="laptop-watermark" aria-hidden="true">PLATEFORME</span>

      <!-- Orbs décoratifs -->
      <div class="laptop-orb laptop-orb-1" aria-hidden="true"></div>
      <div class="laptop-orb laptop-orb-2" aria-hidden="true"></div>

      <div class="relative z-10 max-w-5xl mx-auto">

        <!-- En-tête section -->
        <div class="text-center mb-16">
          <p class="text-xs font-display font-medium tracking-[0.25em] text-taupe uppercase mb-3">
            Aperçu de la plateforme
          </p>
          <h2 style="font-family:'Cormorant',Georgia,serif;
                     font-size:clamp(2rem,5vw,3.5rem);
                     font-weight:300; color:#3A2E26; line-height:1.1;">
            Tout se passe<br>
            <em style="font-style:italic; color:#6B4F3F;">en un coup d'œil.</em>
          </h2>
          <p class="text-sm text-taupe mt-4 max-w-md mx-auto leading-relaxed">
            Le vérificateur scanne le QR Code et voit instantanément le statut du document,
            l'émetteur certifié et toutes les métadonnées.
          </p>
        </div>

        <!-- Layout : texte gauche + laptop droit -->
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">

          <!-- Puces features -->          
          <div class="flex flex-col gap-5">
            <div v-for="feat in laptopFeatures" :key="feat.title"
                 class="flex items-start gap-4 group">
              <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center transition-colors"
                   :style="`background:${feat.color}18; border:1px solid ${feat.color}30;`">
                <svg class="w-5 h-5" fill="none" :stroke="feat.color" stroke-width="1.5" viewBox="0 0 24 24"
                     v-html="feat.svg"></svg>
              </div>
              <div>
                <p class="font-display font-semibold text-brown-dark text-sm mb-1">{{ feat.title }}</p>
                <p class="text-xs text-taupe leading-relaxed">{{ feat.desc }}</p>
              </div>
            </div>

            <!-- CTA discret -->
            <RouterLink to="/register"
                        class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-brown-dark
                               hover:text-brown transition-colors group w-fit">
              Essayer gratuitement
              <svg class="w-4 h-4 transition-transform group-hover:translate-x-1"
                   fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
              </svg>
            </RouterLink>
          </div>

          <!-- Laptop mockup -->
          <div class="flex items-center justify-center">
            <LaptopMockup />
          </div>

        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         COMMENT ÇA MARCHE — 3 étapes visuelles
    ══════════════════════════════════════════════════════════════════ -->
    <section id="comment-ca-marche" class="py-20 px-6" style="background:#FBF7F0;">
      <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
          <p class="text-xs font-display font-medium tracking-[0.25em] text-taupe uppercase mb-3">
            Le processus
          </p>
          <h2 style="font-family:'Cormorant',Georgia,serif;
                     font-size:clamp(2rem,5vw,3.5rem);
                     font-weight:300;
                     color:#3A2E26;
                     line-height:1.1;">
            Trois étapes.<br>Une confiance totale.
          </h2>
        </div>

        <!-- Steps -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
          <!-- Ligne de connexion (desktop) -->
          <div class="hidden md:block absolute top-12 left-1/4 right-1/4 h-px"
               style="background:linear-gradient(to right, #D9C6A8, #8C7A6B, #D9C6A8);"></div>

          <div v-for="(step, i) in steps" :key="i"
               class="card-premium p-7 relative z-10">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-5"
                 :style="`background:${step.color}18;`">
              <svg class="w-6 h-6" fill="none" :stroke="step.color" stroke-width="1.5" viewBox="0 0 24 24"
                   v-html="step.svg">
              </svg>
            </div>
            <span class="text-xs font-display font-black text-sand tracking-widest">{{ step.num }}</span>
            <h3 class="font-display font-semibold text-brown-dark text-lg mt-1 mb-3 leading-snug">
              {{ step.title }}
            </h3>
            <p class="text-sm text-taupe leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         FONCTIONNALITÉS — Grid 2×3
    ══════════════════════════════════════════════════════════════════ -->
    <section class="py-20 px-6" style="background:#F2E9DE;">
      <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
          <p class="text-xs font-display font-medium tracking-[0.25em] text-taupe uppercase mb-3">
            Fonctionnalités
          </p>
          <h2 style="font-family:'Cormorant',Georgia,serif;
                     font-size:clamp(2rem,5vw,3.5rem);
                     font-weight:300;
                     color:#3A2E26;">
            Tout ce qu'il vous faut.
          </h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
          <div v-for="f in features" :key="f.title"
               class="card-premium p-6 hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 group">
            <!-- Icône SVG dans un cercle -->
            <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-5 transition-colors"
                 style="background:#E8DCCB;">
              <svg class="w-5 h-5" fill="none" stroke="#4A372C" stroke-width="1.5" viewBox="0 0 24 24"
                   v-html="f.svg">
              </svg>
            </div>
            <h3 class="font-display font-semibold text-brown-dark text-base mb-2">{{ f.title }}</h3>
            <p class="text-sm text-taupe leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         CAS D'USAGE — Secteurs
    ══════════════════════════════════════════════════════════════════ -->
    <section class="py-20 px-6" style="background:#FBF7F0;">
      <div class="max-w-5xl mx-auto">

        <div class="text-center mb-14">
          <p class="text-xs font-display font-medium tracking-[0.25em] text-taupe uppercase mb-3">
            Pour qui ?
          </p>
          <h2 style="font-family:'Cormorant',Georgia,serif;
                     font-size:clamp(2rem,5vw,3.5rem);
                     font-weight:300;
                     color:#3A2E26;">
            Tous les secteurs officiels.
          </h2>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
          <div v-for="u in useCases" :key="u.title"
               class="card-premium p-6 text-center hover:shadow-md transition-all hover:-translate-y-0.5">
            <!-- Icône SVG dans cercle sombre -->
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                 style="background:#4A372C;">
              <svg class="w-6 h-6" fill="none" stroke="#FBF7F0" stroke-width="1.5" viewBox="0 0 24 24"
                   v-html="u.svg">
              </svg>
            </div>
            <h3 class="font-display font-semibold text-brown-dark mb-2">{{ u.title }}</h3>
            <p class="text-xs text-taupe leading-relaxed">{{ u.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         CTA FINAL — Bande sombre d'appel à l'action
    ══════════════════════════════════════════════════════════════════ -->
    <section class="py-20 px-6 text-center relative overflow-hidden"
             style="background:#4A372C;">

      <!-- Filigrane -->
      <span class="absolute font-black text-cream/5 select-none pointer-events-none"
            style="font-family:'Poppins',sans-serif; font-size:clamp(80px,16vw,200px);
                   font-weight:900; top:50%; left:50%; transform:translate(-50%,-50%);
                   white-space:nowrap;">
        COMMENCER
      </span>

      <div class="relative z-10 max-w-xl mx-auto">
        <h2 class="text-cream mb-4"
            style="font-family:'Cormorant',Georgia,serif;
                   font-size:clamp(2rem,5vw,3.5rem);
                   font-weight:300; line-height:1.1;">
          Prêt à certifier vos documents ?
        </h2>
        <p class="text-cream/60 text-base mb-8 leading-relaxed">
          Créez votre compte en 2 minutes. Soumettez votre première demande de certification
          et commencez à protéger vos documents officiels.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
          <RouterLink to="/register"
                      class="text-base px-8 py-4 rounded-lg font-medium flex items-center justify-center gap-2 transition-all"
                      style="background:#FBF7F0; color:#4A372C;">
            Créer un compte  →
          </RouterLink>
          <RouterLink to="/login"
                      class="text-base px-8 py-4 rounded-lg font-medium flex items-center justify-center gap-2 transition-all"
                      style="background:rgba(251,247,240,0.12); color:#FBF7F0;
                             border:1px solid rgba(251,247,240,0.25);">
            Se connecter
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════════════
         FOOTER
    ══════════════════════════════════════════════════════════════════ -->
    <footer class="py-8 px-6" style="background:#3A2E26;">
      <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <span class="font-serif text-lg font-light tracking-widest text-cream/60"
              style="font-family:'Cormorant',Georgia,serif;">
          DocVerify
        </span>
        <p class="text-xs text-cream/30 text-center">
          © {{ new Date().getFullYear() }} DocVerify — Plateforme de certification de documents officiels
        </p>
        <div class="flex gap-4 text-xs text-cream/40">
          <RouterLink to="/login" class="hover:text-cream/70 transition-colors">Connexion</RouterLink>
          <RouterLink to="/register" class="hover:text-cream/70 transition-colors">Inscription</RouterLink>
        </div>
      </div>
    </footer>

  </div>
</template>

<style scoped>
.laptop-section {
  background: #FBF7F0;
}

/* Filigrane section */
.laptop-watermark {
  position: absolute;
  font-family: 'Poppins', sans-serif;
  font-weight: 900;
  font-size: clamp(80px, 18vw, 200px);
  color: #8C7A6B;
  opacity: 0.05;
  white-space: nowrap;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  z-index: 0;
  pointer-events: none;
  user-select: none;
  letter-spacing: -2px;
}

/* Orbs */
.laptop-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(90px);
  pointer-events: none;
  z-index: 0;
}
.laptop-orb-1 {
  width: 500px; height: 500px;
  top: -100px; right: -150px;
  background: radial-gradient(circle, rgba(124,144,112,0.14) 0%, transparent 70%);
}
.laptop-orb-2 {
  width: 400px; height: 400px;
  bottom: -80px; left: -100px;
  background: radial-gradient(circle, rgba(201,154,60,0.1) 0%, transparent 70%);
}
</style>
