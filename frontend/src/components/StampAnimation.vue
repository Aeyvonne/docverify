<script setup>
/**
 * Animation de tamponnage QR Code sur un document.
 *
 * Séquence :
 *   1. Le document PDF apparaît (slide-up)
 *   2. Le tampon QR descend depuis le haut (stamp arm)
 *   3. Impact : compression + rebond élastique + éclaboussure d'encre
 *   4. Le QR reste imprimé sur le document avec un léger halo
 *   5. Badge "CERTIFIÉ" apparaît en fondu avec rotation légère
 *
 * Props :
 *   autoPlay   — démarre automatiquement au montage (défaut: true)
 *   loop       — rejoue en boucle (défaut: false)
 *   size       — 'sm' | 'md' | 'lg'  (défaut: 'md')
 */
import { ref, onMounted, watch } from 'vue'

const props = defineProps({
  autoPlay: { type: Boolean, default: true },
  loop:     { type: Boolean, default: false },
  size:     { type: String,  default: 'md' },
})

const emit = defineEmits(['complete'])

// ── État de la séquence ───────────────────────────────────────────────
const phase = ref('idle')
// phases : idle → doc-in → arm-ready → stamping → impact → stamped → certified

const sizes = {
  sm: { doc: 'w-36 h-48', qr: 'w-10 h-10', arm: 'w-10', badge: 'text-xs' },
  md: { doc: 'w-52 h-68', qr: 'w-14 h-14', arm: 'w-14', badge: 'text-sm' },
  lg: { doc: 'w-64 h-84', qr: 'w-16 h-16', arm: 'w-16', badge: 'text-base' },
}
const sz = sizes[props.size] ?? sizes.md

async function sleep(ms) {
  return new Promise(r => setTimeout(r, ms))
}

async function play() {
  phase.value = 'doc-in'       ; await sleep(600)
  phase.value = 'arm-ready'    ; await sleep(400)
  phase.value = 'stamping'     ; await sleep(300)
  phase.value = 'impact'       ; await sleep(180)
  phase.value = 'stamped'      ; await sleep(500)
  phase.value = 'certified'
  emit('complete')

  if (props.loop) {
    await sleep(2200)
    phase.value = 'idle'
    await sleep(200)
    play()
  }
}

onMounted(() => {
  if (props.autoPlay) play()
})

// Permet de déclencher depuis l'extérieur : ref.value.replay()
defineExpose({ replay: play })
</script>

<template>
  <!--
    Conteneur principal : taille fixe pour que les éléments absolus
    aient un point de référence cohérent.
  -->
  <div class="stamp-scene relative flex items-end justify-center select-none"
       :style="{ width: '220px', height: '320px' }">

    <!-- ══ 1. LE DOCUMENT ══ -->
    <div class="stamp-doc absolute bottom-0 left-1/2 -translate-x-1/2"
         :class="[
           sz.doc,
           phase === 'idle' ? 'opacity-0 translate-y-4' : '',
           phase === 'doc-in' || phase === 'arm-ready' || phase === 'stamping'
             ? 'doc-enter' : '',
           phase === 'impact'    ? 'doc-squish' : '',
           phase === 'stamped'   ? 'doc-settle' : '',
           phase === 'certified' ? 'doc-settle' : '',
         ]">

      <!-- Corps du document (lignes de texte simulées) -->
      <div class="w-full h-full rounded-xl shadow-lg flex flex-col p-4 gap-2 relative overflow-hidden"
           style="background:#FBF7F0; border:1px solid #E8DCCB;">

        <!-- En-tête document -->
        <div class="w-3/4 h-2.5 rounded-full" style="background:#E8DCCB;"></div>
        <div class="w-1/2 h-2 rounded-full" style="background:#EDE3D6;"></div>

        <!-- Séparateur -->
        <div class="w-full h-px my-1" style="background:#E8DCCB;"></div>

        <!-- Lignes de texte -->
        <div v-for="i in 6" :key="i"
             class="h-1.5 rounded-full"
             :style="{
               background:'#EDE3D6',
               width: [90,75,85,60,80,45][i-1] + '%',
             }">
        </div>

        <div class="flex-1"></div>

        <!-- Zone QR en bas à droite -->
        <div class="absolute bottom-3 right-3">
          <!-- QR imprimé — n'apparaît qu'à partir de 'stamped' -->
          <Transition name="qr-print">
            <div v-if="phase === 'stamped' || phase === 'certified'"
                 :class="['rounded-sm flex items-center justify-center', sz.qr]"
                 style="background:#4A372C;">
              <!-- Grille QR Code dessinée en SVG inline -->
              <svg viewBox="0 0 21 21" class="w-full h-full p-1" fill="#FBF7F0">
                <!-- Coins de positionnement (3 carrés) -->
                <rect x="0" y="0" width="7" height="7" rx="0.5"/>
                <rect x="1.5" y="1.5" width="4" height="4" rx="0.3" fill="#4A372C"/>
                <rect x="2.5" y="2.5" width="2" height="2"/>
                <rect x="14" y="0" width="7" height="7" rx="0.5"/>
                <rect x="15.5" y="1.5" width="4" height="4" rx="0.3" fill="#4A372C"/>
                <rect x="16.5" y="2.5" width="2" height="2"/>
                <rect x="0" y="14" width="7" height="7" rx="0.5"/>
                <rect x="1.5" y="15.5" width="4" height="4" rx="0.3" fill="#4A372C"/>
                <rect x="2.5" y="16.5" width="2" height="2"/>
                <!-- Données QR simulées (petits carrés aléatoires) -->
                <rect x="9" y="1" width="1" height="1"/>
                <rect x="11" y="1" width="2" height="1"/>
                <rect x="9" y="3" width="2" height="1"/>
                <rect x="12" y="3" width="1" height="1"/>
                <rect x="9" y="5" width="1" height="2"/>
                <rect x="11" y="5" width="2" height="1"/>
                <rect x="1" y="9" width="2" height="1"/>
                <rect x="5" y="9" width="1" height="2"/>
                <rect x="8" y="8" width="5" height="5" rx="0.3"/>
                <rect x="9.5" y="9.5" width="2" height="2" fill="#4A372C"/>
                <rect x="15" y="9" width="1" height="2"/>
                <rect x="17" y="9" width="2" height="1"/>
                <rect x="19" y="11" width="1" height="1"/>
                <rect x="15" y="12" width="3" height="1"/>
                <rect x="1" y="15" width="1" height="2"/>
                <rect x="3" y="16" width="2" height="1"/>
                <rect x="9" y="15" width="3" height="2"/>
                <rect x="13" y="15" width="1" height="1"/>
                <rect x="15" y="15" width="2" height="2"/>
                <rect x="18" y="16" width="2" height="1"/>
                <rect x="9" y="18" width="1" height="2"/>
                <rect x="12" y="19" width="2" height="1"/>
                <rect x="16" y="18" width="1" height="2"/>
                <rect x="19" y="19" width="1" height="1"/>
              </svg>
            </div>
          </Transition>

          <!-- Placeholder vide avant tamponnage -->
          <div v-if="phase !== 'stamped' && phase !== 'certified'"
               :class="['rounded border-2 border-dashed', sz.qr]"
               style="border-color:#D9C6A8;">
          </div>
        </div>

        <!-- Halo d'encre au moment de l'impact -->
        <Transition name="ink-splash">
          <div v-if="phase === 'impact'"
               class="absolute bottom-2 right-2 rounded-full pointer-events-none"
               :class="sz.qr"
               style="background:radial-gradient(circle, rgba(74,55,44,0.35) 0%, transparent 70%);
                      transform: scale(2.5);">
          </div>
        </Transition>
      </div>
    </div>

    <!-- ══ 2. LE BRAS + TAMPON ══ -->
    <div class="stamp-arm absolute left-1/2 -translate-x-1/2"
         :class="[
           sz.arm,
           'flex flex-col items-center',
           phase === 'idle'       ? 'arm-hidden'  : '',
           phase === 'doc-in'     ? 'arm-hidden'  : '',
           phase === 'arm-ready'  ? 'arm-up'      : '',
           phase === 'stamping'   ? 'arm-down'    : '',
           phase === 'impact'     ? 'arm-impact'  : '',
           phase === 'stamped'    ? 'arm-retract' : '',
           phase === 'certified'  ? 'arm-retract' : '',
         ]"
         style="bottom: 52px; right: 20px; position: absolute; width: 56px;">

      <!-- Tige du bras -->
      <div class="w-1.5 rounded-full mx-auto"
           style="height:60px; background:linear-gradient(to bottom, #8C7A6B, #6B4F3F);">
      </div>

      <!-- Tête du tampon -->
      <div class="w-full rounded-lg flex items-center justify-center shadow-md"
           style="height:40px;
                  background:linear-gradient(135deg, #4A372C 0%, #6B4F3F 100%);
                  border:2px solid #3A2E26;">
        <!-- Mini grille QR sur le tampon -->
        <svg viewBox="0 0 10 10" class="w-5 h-5" fill="rgba(251,247,240,0.9)">
          <rect x="0" y="0" width="4" height="4" rx="0.3"/>
          <rect x="0.8" y="0.8" width="2.4" height="2.4" fill="#4A372C"/>
          <rect x="1.3" y="1.3" width="1.4" height="1.4"/>
          <rect x="6" y="0" width="4" height="4" rx="0.3"/>
          <rect x="6.8" y="0.8" width="2.4" height="2.4" fill="#4A372C"/>
          <rect x="7.3" y="1.3" width="1.4" height="1.4"/>
          <rect x="0" y="6" width="4" height="4" rx="0.3"/>
          <rect x="0.8" y="6.8" width="2.4" height="2.4" fill="#4A372C"/>
          <rect x="1.3" y="7.3" width="1.4" height="1.4"/>
          <rect x="5" y="4.5" width="1" height="1"/>
          <rect x="7" y="5" width="2" height="1"/>
          <rect x="6" y="7" width="1" height="3"/>
          <rect x="8" y="8" width="2" height="2" rx="0.2"/>
        </svg>
      </div>

      <!-- Encre qui dégouline légèrement sur la tête du tampon -->
      <div class="w-full h-0.5 rounded-full mt-0.5 opacity-40"
           style="background:#3A2E26;"></div>
    </div>

    <!-- ══ 3. BADGE CERTIFIÉ ══ -->
    <Transition name="badge-stamp">
      <div v-if="phase === 'certified'"
           class="absolute top-6 left-1/2 certified-badge"
           style="transform: translateX(-50%) rotate(-12deg);">
        <div class="px-4 py-1.5 rounded border-2 font-display font-black uppercase tracking-widest"
             :class="sz.badge"
             style="color:#7C9070;
                    border-color:#7C9070;
                    background:rgba(124,144,112,0.08);">
          ✓ Certifié
        </div>
      </div>
    </Transition>

    <!-- ══ 4. PARTICULES D'ENCRE (impact) ══ -->
    <Transition name="particles">
      <div v-if="phase === 'impact'"
           class="absolute pointer-events-none"
           style="bottom: 68px; right: 10px;">
        <span v-for="i in 6" :key="i"
              class="ink-particle absolute block rounded-full"
              :style="{
                width: (2 + i % 3) + 'px',
                height: (2 + i % 3) + 'px',
                background: '#4A372C',
                '--angle': (i * 60) + 'deg',
                '--dist': (12 + i * 4) + 'px',
              }">
        </span>
      </div>
    </Transition>

  </div>
</template>

<style scoped>
/* ── Document ── */
.stamp-doc {
  transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1),
              opacity   0.35s ease;
}

.doc-enter {
  animation: docSlideIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes docSlideIn {
  from { opacity: 0; transform: translateX(-50%) translateY(20px); }
  to   { opacity: 1; transform: translateX(-50%) translateY(0); }
}

/* Légère compression verticale à l'impact */
.doc-squish {
  animation: docSquish 0.18s ease forwards;
}
@keyframes docSquish {
  0%   { transform: translateX(-50%) scaleY(1);    }
  50%  { transform: translateX(-50%) scaleY(0.96); }
  100% { transform: translateX(-50%) scaleY(1);    }
}

.doc-settle {
  transform: translateX(-50%) scaleY(1);
}

/* ── Bras du tampon ── */
.stamp-arm {
  transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1);
}

.arm-hidden  { transform: translateY(-120px) translateX(0); opacity: 0; }
.arm-up      { transform: translateY(-80px)  translateX(0); opacity: 1; transition: opacity 0.2s; }
.arm-down    { transform: translateY(-10px)  translateX(0); transition: transform 0.2s cubic-bezier(0.55, 0, 1, 0.45); }
.arm-impact  { transform: translateY(0px)    translateX(0); transition: transform 0.08s ease; }
.arm-retract { transform: translateY(-100px) translateX(0); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }

/* ── QR imprimé : apparaît avec un effet encre (scale + blur → net) ── */
.qr-print-enter-active {
  animation: qrInk 0.35s ease forwards;
}
@keyframes qrInk {
  0%   { opacity: 0; transform: scale(1.3); filter: blur(4px); }
  60%  { opacity: 1; transform: scale(0.95); filter: blur(0); }
  100% { opacity: 1; transform: scale(1);   filter: blur(0); }
}

/* ── Badge certifié ── */
.badge-stamp-enter-active {
  animation: badgeStamp 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes badgeStamp {
  0%   { opacity: 0; transform: translateX(-50%) rotate(-20deg) scale(0.5); }
  100% { opacity: 1; transform: translateX(-50%) rotate(-12deg) scale(1);   }
}

/* ── Halo d'encre ── */
.ink-splash-enter-active {
  animation: inkSplash 0.25s ease forwards;
}
@keyframes inkSplash {
  0%   { opacity: 0.8; transform: scale(1.5); }
  100% { opacity: 0;   transform: scale(3); }
}

/* ── Particules d'encre ── */
.particles-enter-active .ink-particle {
  animation: particleShoot 0.4s ease forwards;
}
@keyframes particleShoot {
  0%   { opacity: 1; transform: translate(0, 0) scale(1); }
  100% {
    opacity: 0;
    transform:
      translate(
        calc(cos(var(--angle)) * var(--dist)),
        calc(sin(var(--angle)) * var(--dist))
      ) scale(0);
  }
}
</style>
