<template>
  <div class="min-h-screen bg-[#07080a] text-[#f1f0ee] flex relative overflow-x-hidden">
    <!-- La puerta legal, encima de todo y antes que nada. La app guarda datos
         de salud y los manda a modelos de terceros: eso se dice antes de
         entrar, no en una pestaña que nadie abre. -->
    <LegalGate />
    <!-- Cinematic Backdrop Image -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden select-none">
      <img
        :src="store.backgroundSrc"
        alt="Fondo Power Coach AI"
        class="absolute inset-0 w-full h-full object-cover opacity-95 scale-100"
      />

      <!-- Soft transparent overlay -->
      <div class="absolute inset-0 bg-black/20" />
      <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(202,243,0,0.12),transparent_60%)]" />
      <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(202,243,0,0.07),transparent_50%)]" />
    </div>

    <!-- Toast Notification -->
    <div
      v-if="store.toastMessage"
      class="fixed top-20 right-6 z-50 p-4 bg-[#caf300] text-[#121700] font-black rounded-2xl shadow-[0_0_30px_rgba(202,243,0,0.45)] flex items-center gap-3 animate-slideIn select-none"
    >
      <span class="material-symbols-outlined text-xl">info</span>
      <span class="text-sm">{{ store.toastMessage }}</span>
    </div>

    <!-- Main Sidebar for Desktop -->
    <Sidebar />

    <!-- Mobile Menu Drawer -->
    <div
      v-if="store.mobileMenuOpen"
      class="fixed inset-0 z-50 bg-black/85 backdrop-blur-2xl md:hidden flex flex-col p-6 animate-fadeIn select-none"
    >
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-2xl font-black text-white">
          Power Coach <span class="text-[#caf300]">AI</span>
        </h1>
        <button
          @click="store.mobileMenuOpen = false"
          class="p-2 text-white hover:text-[#caf300]"
        >
          <span class="material-symbols-outlined text-2xl">close</span>
        </button>
      </div>

      <div class="space-y-3 flex-1">
        <router-link
          v-for="item in mobileNavItems"
          :key="item.id"
          :to="item.route"
          @click="store.mobileMenuOpen = false"
          v-slot="{ isActive }"
        >
          <div
            class="w-full py-3 px-4 rounded-xl text-left font-bold capitalize transition-colors cursor-pointer"
            :class="isActive
              ? 'bg-[#caf300] text-[#121700] font-black shadow-[0_0_20px_rgba(202,243,0,0.3)]'
              : 'text-white hover:bg-white/10'"
          >
            {{ item.label }}
          </div>
        </router-link>
      </div>

      <div class="space-y-2 pt-4 border-t border-white/10">
        <button
          @click="openAiCoachFromMobile"
          class="w-full py-3.5 bg-[#caf300] text-[#121700] rounded-xl font-black uppercase tracking-wider flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(202,243,0,0.35)]"
        >
          <img
            v-if="store.coachAvatarSrc"
            :src="store.coachAvatarSrc"
            alt="Coach"
            class="w-6 h-6 rounded-full object-cover object-top"
          />
          <span v-else class="material-symbols-outlined text-xl">smart_toy</span>
          <span>Coach</span>
        </button>

        <button
          @click="handleLogoutFromMobile"
          class="w-full py-2.5 glass-pill text-[#ff8f83] rounded-xl font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2"
        >
          <span class="material-symbols-outlined text-base">power_settings_new</span>
          <span>Cerrar Sesión</span>
        </button>
      </div>
    </div>

    <!-- Main Layout Area -->
    <div class="flex-1 md:ml-64 flex flex-col min-w-0 relative z-10">
      <!-- Top Header -->
      <TopHeader />

      <!-- Notifications Popover -->
      <!-- Capa que cierra al pulsar fuera. Va detras del popover (z menor).
           Sin esto el popover se quedaba abierto tapando la pagina, y ahora
           que lleva botones dentro es peor: no hay forma de salir sin acertar
           otra vez en la campana. -->
      <div
        v-if="store.notificationsOpen"
        class="fixed inset-0 z-40"
        @click="store.notificationsOpen = false"
      ></div>

      <!-- Aqui hubo dos avisos INVENTADOS fijos en el codigo ("Ciclismo Z2
           exportado a Garmin Edge 1040") que se le mostraban igual a todo el
           mundo. Se vaciaron a proposito y quedo el hueco esperando una
           fuente real; esta es esa fuente: los avisos que manda el
           administrador (Database/011_create_notifications.sql). Lo que no
           venga de ahi no se muestra. -->
      <div
        v-if="store.notificationsOpen"
        class="fixed top-20 right-4 sm:right-10 z-50 glass-panel rounded-2xl p-4 w-[calc(100vw-2rem)] sm:w-96 max-h-[70vh] overflow-y-auto hide-scrollbar shadow-2xl space-y-3 animate-scale-in border border-white/15"
      >
        <div class="flex justify-between items-center border-b border-white/10 pb-2">
          <span class="font-black text-white uppercase text-xs font-mono-data">Notificaciones</span>
          <router-link
            v-if="store.isAdmin"
            to="/admin/notificaciones"
            @click="store.notificationsOpen = false"
            class="text-[10px] font-black uppercase tracking-wider text-[#caf300] hover:underline"
          >Enviar</router-link>
        </div>

        <p v-if="!store.notifications.length" class="text-[#8f9378] leading-relaxed text-xs font-mono-data">
          No hay avisos por ahora. Tu plan y tu check-in te esperan en Inicio.
        </p>

        <article
          v-for="aviso in store.notifications"
          :key="aviso.id"
          class="glass-card border border-white/10 rounded-xl p-3 flex gap-2.5 items-start animate-fade-in-up"
        >
          <span class="material-symbols-outlined text-[#caf300] text-[18px] mt-0.5 shrink-0">campaign</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-bold text-white leading-snug">{{ aviso.title }}</p>
            <p class="text-xs text-[#c5c9ac] leading-relaxed mt-1 whitespace-pre-wrap break-words">{{ aviso.body }}</p>
            <p class="text-[10px] font-mono-data text-[#8f9378] mt-1.5">
              {{ fechaAviso(aviso.createdAt) }}<span v-if="aviso.author"> · {{ aviso.author }}</span>
            </p>
          </div>
          <button
            @click="store.dismissNotification(aviso.id)"
            class="p-1 text-[#8f9378] hover:text-[#ffb4ab] rounded-lg hover:bg-white/10 active:scale-95 transition-all shrink-0"
            title="Quitar de mi bandeja"
          >
            <span class="material-symbols-outlined text-[18px]">close</span>
          </button>
        </article>
      </div>

      <!-- Dynamic Page Views Container -->
      <main class="flex-1 px-4 sm:px-8 lg:px-12 pt-28 pb-36 sm:pb-12 max-w-7xl w-full mx-auto">
        <router-view></router-view>
      </main>
    </div>

    <!-- Modals -->
    <AiCoachModal />
    <HealthModal />
    <CheckinModal />
    <SettingsModal />
    <AyudaModal />
    <InvitarModal />
    <FloatingActions v-if="!enElAlta" />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import Sidebar from './Sidebar.vue';
import LegalGate from './LegalGate.vue';
import TopHeader from './TopHeader.vue';
import AiCoachModal from '../modals/AiCoachModal.vue';
import HealthModal from '../modals/HealthModal.vue';
import CheckinModal from '../modals/CheckinModal.vue';
import SettingsModal from '../modals/SettingsModal.vue';
import AyudaModal from '../modals/AyudaModal.vue';
import InvitarModal from '../modals/InvitarModal.vue';
import FloatingActions from './FloatingActions.vue';
import swal from '../../utils/swal';

const store = useAppStore();
const router = useRouter();
const route = useRoute();

// La primera vez no se aterriza en un panel vacio. Alguien que acaba de entrar
// con Google no tiene perfil, y sin perfil no hay plan, ni graficas, ni nada
// que mirar -- un panel en blanco no dice que hacer. Se le lleva al paso a
// paso, que empieza por conectar Garmin.
//
// El disparador es un perfil SIN EMPEZAR (0%), no uno a medias: quien ya lo
// dejo a la mitad decide por su cuenta cuando volver, y arrastrarle al
// asistente cada vez que entra seria peor que no tenerlo.
// En el alta (el paso a paso del perfil y la bienvenida) no hay chat ni opinión
// flotando encima: cada toque tiene que ser del paso en el que está.
// El último paso de la bienvenida SÍ los enseña: le explica al atleta dónde están.
const enElAlta = computed(() => (route.path.startsWith('/admin/bienvenida') && store.altaEnCurso)
  || (route.path.startsWith('/admin/perfil') && route.query.onboarding === '1'));

const llevarAlPasoAPasoSiEsLaPrimeraVez = () => {
  if (!store.token) return;
  // El administrador no entra a la app para entrenar: con su perfil al 0 % cada
  // recarga de Métricas o de Usuarios le llevaba al asistente de atleta
  // (auditoría del 2026-09-23). Si quiere su plan, su perfil sigue en el menú.
  if (store.isAdmin) return;
  const hecho = store.profile?.completionRate ?? store.athlete?.completionRate ?? 0;
  if (hecho > 0) return;
  if (route.path.startsWith('/admin/perfil')) return;
  // La bienvenida es el FINAL de este mismo paso a paso, y se llega a ella con
  // el perfil todavia al 0% si el atleta se salto todos los apartados -- que
  // esta permitido. Sin esta linea, recargar ahi le devuelve al principio del
  // asistente que acaba de terminar: un bucle del que solo se sale rellenando
  // algo.
  if (route.path.startsWith('/admin/bienvenida')) return;
  router.replace({ path: '/admin/perfil', query: { onboarding: '1' } });
};

onMounted(() => {
  // Runs synchronously so a refresh keeps the user's name/photo instead of
  // flashing the mock athlete until the profile loads.
  store.applyAuthUserToAthlete();
  // El usuario, del SERVIDOR y no del navegador. Sin esto, `termsVersionRequired`
  // -- y cualquier cosa que el backend anada al usuario -- no llega nunca a
  // quien ya tiene la sesion abierta.
  store.refreshAuthUser();
  if (store.token && !store.profileLoaded) {
    store.loadProfile().then(llevarAlPasoAPasoSiEsLaPrimeraVez);
  } else {
    llevarAlPasoAPasoSiEsLaPrimeraVez();
  }
  if (store.token && !store.checkinLoaded) {
    store.loadCheckin();
  }
  if (store.token && !store.planLoaded) {
    store.loadPlan();
  }
  if (store.token && !store.notificationsLoaded) {
    store.loadNotifications();
  }
  document.addEventListener('visibilitychange', avisosAlVolver);
});

// La respuesta a una consulta de ayuda llega como aviso (Beta 3.0, F3): se vuelven a pedir
// al cambiar de pantalla o al volver a la pestaña, como mucho cada 60 s.
const refrescarAvisos = () => {
  if (store.token && Date.now() - (store.notificationsLoadedAt || 0) > 60000) store.loadNotifications();
};
const avisosAlVolver = () => {
  if (document.visibilityState === 'visible') refrescarAvisos();
};
watch(() => route.path, refrescarAvisos);
onUnmounted(() => document.removeEventListener('visibilitychange', avisosAlVolver));

const mobileNavItems = computed(() => {
  const items = [
    { id: 'inicio', label: 'Inicio', route: '/admin/dashboard' },
    { id: 'plan', label: 'Plan', route: '/admin/plan' },
    { id: 'cuaderno', label: 'Cuaderno', route: '/admin/cuaderno' },
    // Perfil e Historial viven en el menu del atleta; en movil se llega por el
    // mismo sitio que en escritorio, para que no haya dos mapas distintos.
    { id: 'perfil', label: 'Perfil', route: '/admin/perfil' },
    { id: 'historial', label: 'Historial', route: '/admin/historial' },
    { id: 'analytics', label: 'Progreso', route: '/admin/analytics' },
    { id: 'reports', label: 'Informes', route: '/admin/reports' },
  ];

  if (store.isAdmin) {
    items.push({ id: 'usuarios', label: 'Usuarios (Beta)', route: '/admin/usuarios' });
    items.push({ id: 'notificaciones', label: 'Avisos', route: '/admin/notificaciones' });
  }

  return items;
});

// Se muestra la fecha del aviso, no "hace 3 horas": un aviso de hace dos dias
// que dice "manana no habra servicio" se lee mal si solo pone lo viejo que es.
const fechaAviso = (iso) => {
  if (!iso) return '';
  const d = new Date(String(iso).replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return '';
  return d.toLocaleString([], { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
};

const openAiCoachFromMobile = () => {
  store.mobileMenuOpen = false;
  store.isAiCoachOpen = true;
};

const handleLogoutFromMobile = async () => {
  const result = await swal.fire({
    icon: 'question',
    title: '¿Cerrar sesión?',
    text: 'Tendrás que iniciar sesión con Google de nuevo para volver a entrar.',
    showCancelButton: true,
    confirmButtonText: 'Sí, cerrar sesión',
    cancelButtonText: 'Cancelar',
  });

  if (!result.isConfirmed) return;

  store.mobileMenuOpen = false;
  store.logout();
  store.showToast('Sesión cerrada. Accede nuevamente.');
  router.push('/login');
};
</script>
