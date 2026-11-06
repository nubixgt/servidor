<template>
  <header
    id="top-header"
    class="fixed top-0 right-0 w-full md:w-[calc(100%-16rem)] z-40 glass-nav border-b border-white/15 flex justify-between items-center px-6 lg:px-10 py-3.5 h-[72px] transition-all animate-slide-down"
  >
    <!-- Left side: Mobile trigger & Navigation tabs -->
    <div class="flex items-center gap-6">
      <button
        id="btn-mobile-menu"
        @click="store.mobileMenuOpen = !store.mobileMenuOpen"
        class="md:hidden text-[#f1f0ee] p-1.5 rounded-lg hover:bg-white/10 active:scale-90 transition-all"
        aria-label="Abrir menú de navegación"
      >
        <span class="material-symbols-outlined text-2xl">menu</span>
      </button>

      <!-- Aquí iban INICIO · PROGRESO · INFORMES: repetían el menú de la izquierda con otros destinos (sin Plan ni
           Cuaderno) y en Cuaderno o Historial no se marcaba ninguno (revisión del 2026-10-22). Un solo menú. -->
    </div>

    <!-- Right side: Actions & Athlete card -->
    <div class="flex items-center gap-3 sm:gap-5">
      <!-- «Ayuda» en el teléfono: aquí, a un toque, en vez de un segundo botón flotante. -->
      <button
        type="button"
        @click="store.abrirAyuda()"
        class="sm:hidden p-2 text-[#c5c9ac] hover:text-[#caf300] transition-all rounded-xl hover:bg-white/10 active:scale-95"
        title="Ayuda" aria-label="Ayuda"
      >
        <span class="material-symbols-outlined text-[22px]">support_agent</span>
      </button>
      <button
        id="btn-top-notifications"
        @click="store.notificationsOpen = !store.notificationsOpen"
        class="relative p-2 text-[#c5c9ac] hover:text-[#caf300] transition-all rounded-xl hover:bg-white/10 hover:scale-110 active:scale-95"
        title="Notificaciones"
      >
        <span class="material-symbols-outlined text-[22px]">notifications</span>
        <!-- El punto SOLO cuando hay algo. El anterior estaba fijo en el
             codigo y no se apagaba nunca, asi que dejaba de significar nada. -->
        <span
          v-if="store.notificationCount"
          class="absolute top-1 right-1 min-w-[16px] h-4 px-1 rounded-full bg-[#caf300] text-[#121700] text-[10px] font-black leading-4 text-center shadow-[0_0_8px_rgba(202,243,0,0.6)]"
        >{{ store.notificationCount > 9 ? '9+' : store.notificationCount }}</span>
      </button>

      <button
        id="btn-top-settings"
        @click="store.abrirAjustes()"
        class="p-2 text-[#c5c9ac] hover:text-[#caf300] transition-all rounded-xl hover:bg-white/10 hover:scale-110 active:scale-95"
        title="Configuración"
      >
        <span class="material-symbols-outlined text-[22px]">settings</span>
      </button>

      <!-- Menú del atleta. La flecha era decorativa: la píldora entera era un
           enlace al perfil y no se desplegaba nada. Ahora guarda lo que no es
           de uso diario -- perfil e historial -- para que las pestañas
           principales sean sólo aquello a lo que se entra todos los días. -->
      <div class="relative">
        <button
          id="btn-user-menu"
          @click="alternarMenu"
          class="flex items-center gap-3 pl-2 sm:pl-3 cursor-pointer group hover:scale-[1.02] active:scale-95 transition-all"
          :aria-expanded="store.userMenuOpen"
          aria-haspopup="menu"
        >
          <div class="text-right hidden sm:block">
            <p class="text-xs font-bold text-white group-hover:text-[#caf300] transition-colors">
              Hola, {{ store.athlete.name }}
            </p>
            <p v-if="store.athlete.roleTitle" class="text-[10px] font-extrabold text-[#caf300] tracking-wider uppercase">
              {{ store.athlete.roleTitle }}
            </p>
          </div>
          <div class="w-9 h-9 rounded-full overflow-hidden border border-[#caf300]/60 shadow-[0_0_10px_rgba(202,243,0,0.25)] group-hover:border-[#caf300] transition-all shrink-0">
            <img
              v-if="store.athleteAvatarSrc"
              :src="store.athleteAvatarSrc"
              :alt="store.athlete.name"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
            />
            <span v-else class="w-full h-full flex items-center justify-center bg-[#caf300]/15 text-[#caf300] text-sm font-black">
              {{ (store.athlete.name || '?').charAt(0).toUpperCase() }}
            </span>
          </div>
          <span
            class="material-symbols-outlined text-[#8f9378] text-lg group-hover:text-[#caf300] transition-all hidden sm:inline-block"
            :class="store.userMenuOpen ? 'rotate-180 text-[#caf300]' : ''"
          >
            keyboard_arrow_down
          </span>
        </button>

        <!-- Capa que cierra al pulsar fuera. Va detrás del menú (z menor). -->
        <div v-if="store.userMenuOpen" class="fixed inset-0 z-40" @click="store.userMenuOpen = false"></div>

        <div
          v-if="store.userMenuOpen"
          class="absolute right-0 mt-3 w-60 z-50 glass-panel rounded-2xl border border-white/15 shadow-2xl overflow-hidden animate-scale-in"
          role="menu"
        >
          <div class="px-4 py-3 border-b border-white/10">
            <p class="text-xs font-black text-white truncate">{{ store.athlete.name }}</p>
            <p class="text-[10px] font-mono-data text-[#8f9378] truncate">{{ store.authUser?.email || '' }}</p>
          </div>

          <!-- Consumo de IA. Es costo de PROVEEDOR, no una factura: lo que la
               app le pagó a Anthropic, OpenAI y Google por esta cuenta.
               Borrar el plan no lo borra: el gasto ya se hizo.
               SÓLO ADMINISTRADOR, y no por privacidad sino porque enseñárselo
               al atleta le cambia lo que hace: a un beta al que se le dijo que
               pagará el costo exacto, ver el contador subir en cada pregunta
               le enseña a NO preguntar -- y entonces se pierde justo el dato
               de uso que la beta existe para recoger. El panel de métricas del
               administrador tiene el desglose completo, por herramienta y por
               atleta. -->
          <div v-if="store.isAdmin" class="px-4 py-3 border-b border-white/10 font-mono-data">
            <div class="flex justify-between items-baseline">
              <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Consumo de IA</span>
              <span v-if="store.usageLoading" class="text-[10px] text-[#8f9378]">…</span>
            </div>

            <template v-if="usage">
              <p class="text-lg font-black text-[#caf300] leading-tight mt-1">
                {{ dolares(usage.total_cost_usd) }}
              </p>
              <p class="text-[10px] text-[#c5c9ac] mt-0.5">
                {{ usage.runs || 0 }} generaciones
                <template v-if="gastoDelMes !== null"> · {{ dolares(gastoDelMes) }} este mes</template>
              </p>
              <p v-if="usage.stale" class="text-[10px] text-[#8f9378] mt-1">
                Último dato guardado; el servicio de IA no respondió.
              </p>
            </template>
            <p v-else-if="!store.usageLoading" class="text-[10px] text-[#8f9378] mt-1">
              Todavía no hay consumo registrado.
            </p>
          </div>

          <router-link
            v-for="item in menuItems"
            :key="item.route"
            :to="item.route"
            role="menuitem"
            @click="store.userMenuOpen = false"
            class="flex items-start gap-3 px-4 py-3 text-[#c5c9ac] hover:text-white hover:bg-white/10 transition-colors"
          >
            <span class="material-symbols-outlined text-[20px] text-[#caf300] shrink-0">{{ item.icon }}</span>
            <span class="min-w-0">
              <span class="block text-xs font-black uppercase tracking-wider">{{ item.label }}</span>
              <span class="block text-[10px] text-[#8f9378] leading-snug mt-0.5">{{ item.hint }}</span>
            </span>
          </router-link>

          <button
            role="menuitem"
            @click="handleLogout"
            class="w-full flex items-center gap-3 px-4 py-3 text-[#ff8f83] hover:bg-[#93000a]/25 transition-colors border-t border-white/10"
          >
            <span class="material-symbols-outlined text-[20px] shrink-0">power_settings_new</span>
            <span class="text-xs font-black uppercase tracking-wider">Cerrar sesión</span>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import swal from '../../utils/swal';

const store = useAppStore();
const route = useRoute();
const router = useRouter();

// Lo que sale de las pestañas principales tiene que quedar EN UN SITIO, no en
// ninguno: el perfil se rellena una vez y se retoca de vez en cuando, y el
// historial es una vista avanzada. La pista de debajo importa -- un menú de
// palabras sueltas obliga a entrar para averiguar qué hay dentro.
const menuItems = [
  { route: '/admin/perfil', icon: 'person', label: 'Mi perfil', hint: 'Tus datos, objetivos, horarios y salud' },
  { route: '/admin/historial', icon: 'history', label: 'Historial', hint: 'Planes anteriores y lo que ya entrenaste' },
];

// Un menú abierto sobre una pantalla que ya cambió confunde: se cierra solo al
// navegar, venga de donde venga la navegación.
watch(() => route.fullPath, () => { store.userMenuOpen = false; });

// El consumo se pide al ABRIR, no al cargar la app: la consulta cruza el puente
// hasta el libro de costos y no tiene por qué pagarla cada carga de página.
// `forzar` al abrir para que el número no se quede viejo tras generar un plan.
const alternarMenu = () => {
  store.userMenuOpen = !store.userMenuOpen;
  if (store.userMenuOpen) store.loadUsage(true);
};

const usage = computed(() => store.usage);

// Un gasto de $0.0004 no puede enseñarse como "$0.00": el atleta lo lee como
// gratis. Por debajo de un centavo se dan los decimales que hagan falta.
const dolares = (n) => {
  const v = Number(n || 0);
  if (v > 0 && v < 0.01) return `$${v.toFixed(4)}`;
  return `$${v.toFixed(2)}`;
};

const gastoDelMes = computed(() => {
  const meses = store.usage?.by_month;
  if (!meses) return null;
  const ahora = new Date();
  const clave = `${ahora.getFullYear()}-${String(ahora.getMonth() + 1).padStart(2, '0')}`;
  return meses[clave]?.cost_usd ?? null;
});

const handleLogout = async () => {
  store.userMenuOpen = false;
  const result = await swal.fire({
    icon: 'question',
    title: '¿Cerrar sesión?',
    text: 'Tendrás que iniciar sesión con Google de nuevo para volver a entrar.',
    showCancelButton: true,
    confirmButtonText: 'Sí, cerrar sesión',
    cancelButtonText: 'Cancelar',
  });
  if (!result.isConfirmed) return;

  store.logout();
  store.showToast('Sesión cerrada. Accede nuevamente.');
  router.push('/login');
};
</script>
