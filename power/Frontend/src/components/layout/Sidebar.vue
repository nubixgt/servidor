<template>
  <nav
    id="main-sidebar"
    class="hidden md:flex flex-col fixed left-0 top-0 h-full w-64 glass-nav border-r border-white/15 p-5 z-50 select-none shadow-2xl animate-slide-left"
  >
    <!-- Brand Header -->
    <div class="mb-6 px-1">
      <router-link
        to="/admin/dashboard"
        class="cursor-pointer block transition-transform hover:scale-[1.02] active:scale-95"
      >
        <h1 class="text-2xl font-black text-white tracking-tight leading-none">
          Power Coach <span class="text-[#caf300]">AI</span>
        </h1>
        <p class="text-[11px] text-[#8f9378] font-bold tracking-widest uppercase mt-1">
          ELITE PERFORMANCE LAB
        </p>
      </router-link>
    </div>

    <!-- User Mini Card (Glass Pill style with smooth hover elevation) -->
    <router-link
      to="/admin/perfil"
      class="flex items-center gap-3 mb-6 p-2.5 glass-card border border-white/15 hover:border-[#caf300]/50 rounded-2xl cursor-pointer transition-all duration-300 group shadow-md hover:scale-[1.02] active:scale-95"
    >
      <div class="relative w-10 h-10 rounded-full overflow-hidden border-2 border-[#caf300] shrink-0 shadow-[0_0_10px_rgba(202,243,0,0.3)]">
        <img
          v-if="store.athleteAvatarSrc"
          :src="store.athleteAvatarSrc"
          :alt="store.athlete.name"
          class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
        />
        <span v-else class="w-full h-full flex items-center justify-center bg-[#caf300]/15 text-[#caf300] text-sm font-black">
          {{ (store.athlete.name || '?').charAt(0).toUpperCase() }}
        </span>
        <div class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-[#caf300] rounded-full ring-2 ring-black"></div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-bold text-white truncate group-hover:text-[#caf300] transition-colors">
          {{ store.athlete.name }}
        </p>
        <p v-if="store.athlete.roleTitle" class="text-[10px] font-extrabold text-[#caf300] tracking-wider uppercase truncate">
          {{ store.athlete.roleTitle }}
        </p>
      </div>
      <span class="material-symbols-outlined text-[#8f9378] text-lg group-hover:translate-x-1 transition-transform">
        chevron_right
      </span>
    </router-link>

    <!-- Navigation Links -->
    <div class="flex-1 space-y-2 overflow-y-auto pr-1">
      <router-link
        v-for="item in navItems"
        :key="item.id"
        :to="item.route"
        :id="`nav-link-${item.id}`"
        v-slot="{ isActive }"
      >
        <div
          class="w-full flex items-center gap-3.5 px-4 py-3 rounded-xl text-left transition-all duration-300 cursor-pointer group"
          :class="isActive
            ? 'bg-[#caf300] text-[#121700] font-extrabold shadow-[0_0_20px_rgba(202,243,0,0.35)] scale-[1.02] translate-x-1'
            : 'text-[#c5c9ac] hover:text-white hover:bg-white/10 hover:translate-x-1 font-medium'"
        >
          <span
            class="material-symbols-outlined text-[22px] transition-transform duration-300 group-hover:scale-110"
            :class="isActive ? 'text-[#121700]' : 'text-[#c5c9ac]'"
            :style="isActive ? { fontVariationSettings: '\'FILL\' 1' } : undefined"
          >
            {{ item.icon }}
          </span>
          <span class="text-sm tracking-tight">
            {{ item.label }}
          </span>
        </div>
      </router-link>
    </div>

    <!-- Footer. Aqui habia un segundo boton de "Coach": sobra desde que el
         flotante (components/layout/FloatingActions.vue) esta en todas las
         pantallas y en la esquina donde cae el pulgar. Dos accesos a lo mismo,
         uno de ellos escondido abajo del todo, solo reparten la atencion. -->
    <div class="mt-auto pt-4 space-y-3 border-t border-white/15">
      <!-- PUERTA FALSA · invitar.
           ==================================================================
           La mas valiosa de las siete, porque prueba la hipotesis que NO
           tenemos: las nueve cuentas de la beta se repartieron a mano, asi que
           no hay motor de crecimiento -- ni viral, ni pagado. Se puede validar
           que el producto sirve y no tener por donde crecer.
           Ver components/layout/PuertaFalsa.vue. -->
      <!-- PUERTA FALSA · app_movil. La beta la pidio; hoy es una web. Mide si
           la echan de menos lo bastante como para pulsarlo. -->
      <PuertaFalsa
        puerta="app_movil"
        etiqueta="Instalar la app"
        icono="install_mobile"
        titulo="Todavia no existe"
        texto="Hoy Power Coach es una web. Estamos pensando en una app para el telefono, con avisos de tu sesion del dia. ¿La instalarias?"
        clase="w-full flex items-center gap-3 px-3 py-2 text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/5 rounded-xl transition-all text-xs font-semibold"
      />
      <!-- «Invitar a un atleta» fue una puerta falsa hasta el 2026-10-11; ahora es de verdad
           (Beta 3.0, invitaciones: components/modals/InvitarModal.vue). -->
      <button
        type="button"
        @click="store.invitarAbierto = true"
        class="w-full flex items-center gap-3 px-3 py-2 text-[#c5c9ac] hover:text-[#caf300] hover:bg-white/5 rounded-xl transition-all text-xs font-semibold"
      >
        <span class="material-symbols-outlined text-[18px]">person_add</span>
        <span>Invitar a un atleta</span>
      </button>
      <button
        id="btn-logout"
        @click="handleLogout"
        class="w-full flex items-center gap-3 px-3 py-2 text-[#ff8f83] hover:text-[#ffb4ab] hover:bg-[#93000a]/20 rounded-xl transition-all text-xs font-semibold hover:scale-[1.02] active:scale-95"
      >
        <span class="material-symbols-outlined text-[18px]">power_settings_new</span>
        <span>Cerrar Sesión</span>
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import PuertaFalsa from './PuertaFalsa.vue';
import swal from '../../utils/swal';

const router = useRouter();
const store = useAppStore();

const navItems = computed(() => {
  const items = [
    // Perfil e Historial NO estan aqui: viven en el menu del atleta (arriba a
    // la derecha). El perfil se rellena una vez y se retoca de vez en cuando, y
    // el historial es una vista avanzada -- ninguno de los dos es una pestaña a
    // la que se entre a diario, y ocupando sitio aqui competian con las que si.
    { id: 'inicio', label: 'Inicio', icon: 'home', route: '/admin/dashboard' },
    { id: 'plan', label: 'Plan', icon: 'calendar_today', route: '/admin/plan' },
    { id: 'cuaderno', label: 'Cuaderno', icon: 'edit_note', route: '/admin/cuaderno' },
    // Progreso e Informes sólo estaban en la barra de arriba, que no se ve en el teléfono: desde un móvil
    // no había forma de llegar (revisión del 2026-10-21).
    { id: 'progreso', label: 'Progreso', icon: 'trending_up', route: '/admin/analytics' },
    { id: 'informes', label: 'Informes', icon: 'description', route: '/admin/reports' },
    // Historial, también en PC (2026-10-22): sólo estaba en el menú del teléfono, y quien abría una semana
    // pasada no veía dónde estaba ni cómo volver.
    { id: 'historial', label: 'Historial', icon: 'history', route: '/admin/historial' },
  ];

  if (store.isAdmin) {
    items.push({ id: 'usuarios', label: 'Usuarios (Beta)', icon: 'admin_panel_settings', route: '/admin/usuarios' });
    items.push({ id: 'notificaciones', label: 'Avisos', icon: 'campaign', route: '/admin/notificaciones' });
    items.push({ id: 'metricas', label: 'Métricas', icon: 'monitoring', route: '/admin/metricas' });
  }

  return items;
});

const handleLogout = async () => {
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
