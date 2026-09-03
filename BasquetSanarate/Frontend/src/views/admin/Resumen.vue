<template>
  <div class="flex flex-col w-full gap-space-xl">
    <!-- Hero / bienvenida -->
    <section class="relative overflow-hidden rounded-xl bg-surface-container-lowest p-space-lg md:p-space-xl shadow-sm">
      <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-primary-container/20 blur-3xl pointer-events-none"></div>
      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-space-md">
        <div class="flex flex-col gap-space-2xs">
          <span class="inline-flex items-center gap-1.5 px-space-xs py-0.5 rounded-full bg-inverse-surface text-primary-container font-label-meta text-label-meta uppercase tracking-wider font-bold w-fit">
            <span class="w-2 h-2 rounded-full bg-primary-container animate-ping"></span>
            Liga de Baloncesto Sanarateca
          </span>
          <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight">
            ¡Bienvenido, {{ auth.user?.nombre || 'Administrador' }}!
          </h1>
          <p class="font-body-md text-body-md text-on-surface-variant flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px] text-secondary">today</span>
            <span>{{ fechaHoy }}</span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-space-sm">
          <router-link
            to="/admin/partidos"
            class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase font-bold tracking-wide shadow-[0_8px_20px_-4px_rgba(204,255,0,0.45)] hover:bg-primary-fixed-dim transition-transform active:scale-95"
          >
            <span class="material-symbols-outlined text-[20px]">sports_score</span>
            <span>Mesa de control</span>
          </router-link>
        </div>
      </div>
    </section>

    <!-- Ribbon de métricas -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-space-md">
      <RibbonCard
        icon="shield" :value="resumen.equipos" label="Equipos inscritos"
        :hint="`${resumen.equipos} activos`" to="/admin/equipos" cta="Gestionar" />
      <RibbonCard
        icon="groups" :value="resumen.jugadores_activos" label="Atletas habilitados"
        :hint="`${resumen.jugadores_suspendidos} suspendidos`" to="/admin/jugadores" cta="Gestionar" />
      <RibbonCard
        dark icon="sports_basketball" :value="resumen.partidos.total" label="Partidos total"
        :hint="`${resumen.partidos.en_vivo} en vivo`" to="/admin/partidos" cta="Mesa control" />
      <RibbonCard
        icon="leaderboard" :value="resumen.partidos.finalizados" label="Partidos jugados"
        :hint="`${resumen.partidos.programados} programados`" to="/admin/estadisticas" cta="Ver líderes" />
      <RibbonCard
        icon="campaign" :value="resumen.novedades.publicadas" label="Boletines publicados"
        :hint="`${resumen.novedades.borradores} en borrador`" to="/admin/novedades" cta="Gestionar" />
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-lg items-start">
      <!-- Columna principal -->
      <div class="lg:col-span-8 flex flex-col gap-space-lg">
        <!-- Acciones rápidas -->
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
          <div class="flex items-center gap-space-xs mb-space-md">
            <span class="w-2.5 h-2.5 rounded-full bg-primary"></span>
            <h2 class="font-headline-md text-headline-md uppercase text-on-surface tracking-tight">Acciones rápidas</h2>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-space-sm">
            <QuickAction to="/admin/equipos" icon="add_moderator" title="Nuevo equipo" desc="Registrar club o franquicia" />
            <QuickAction to="/admin/jugadores" icon="person_add" title="Inscribir atleta" desc="Ficha y dorsal" />
            <QuickAction to="/admin/partidos" icon="calendar_add_on" title="Programar juego" desc="Cancha, hora y arbitraje" />
            <QuickAction to="/admin/novedades" icon="edit_note" title="Redactar boletín" desc="Anuncios y avisos" />
          </div>
        </section>

        <!-- Partidos que requieren atención -->
        <section class="rounded-xl bg-surface-container-lowest p-space-lg shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-xs mb-space-md">
            <div class="flex items-center gap-space-xs">
              <span class="material-symbols-outlined text-error text-[24px]">crisis_alert</span>
              <div>
                <h2 class="font-headline-md text-headline-md uppercase text-on-surface tracking-tight leading-tight">Partidos con atención pendiente</h2>
                <p class="font-body-sm text-body-sm text-secondary">Encuentros en vivo o con acta sin cerrar.</p>
              </div>
            </div>
            <span v-if="resumen.atencion.length" class="self-start sm:self-auto px-space-xs py-0.5 rounded-full bg-error-container text-on-error-container font-label-meta text-label-meta font-bold uppercase">
              {{ resumen.atencion.length }} pendientes
            </span>
          </div>

          <div v-if="loading" class="py-space-lg text-center text-secondary font-body-sm">Cargando…</div>
          <div v-else-if="!resumen.atencion.length" class="py-space-lg text-center text-secondary font-body-sm">
            No hay partidos pendientes de atención.
          </div>
          <div v-else class="flex flex-col gap-space-sm">
            <div
              v-for="p in resumen.atencion"
              :key="p.id"
              class="rounded-lg bg-surface p-space-md flex flex-col md:flex-row md:items-center justify-between gap-space-md"
            >
              <div class="flex items-center gap-space-md min-w-0">
                <div
                  class="flex flex-col items-center justify-center px-space-sm py-space-xs rounded-lg shrink-0 text-center min-w-[70px]"
                  :class="p.estado === 'En Vivo' ? 'bg-inverse-surface text-surface-bright' : 'bg-surface-container-highest text-secondary'"
                >
                  <span class="font-label-meta text-label-meta uppercase font-bold" :class="p.estado === 'En Vivo' ? 'text-primary-container' : 'text-error'">
                    {{ p.estado === 'En Vivo' ? 'EN VIVO' : 'ACTA' }}
                  </span>
                  <span class="font-label-meta text-label-meta">J{{ p.jornada || '—' }}</span>
                </div>
                <div class="min-w-0">
                  <div class="flex items-center gap-space-sm mt-0.5 flex-wrap">
                    <span class="font-headline-lg text-headline-lg uppercase text-on-surface truncate">{{ p.equipo_local }}</span>
                    <span class="font-headline-lg text-headline-lg font-bold text-primary">{{ p.marcador_local }}</span>
                    <span class="font-headline-md text-headline-md text-outline">-</span>
                    <span class="font-headline-lg text-headline-lg font-bold text-on-surface">{{ p.marcador_visitante }}</span>
                    <span class="font-headline-lg text-headline-lg uppercase text-on-surface truncate">{{ p.equipo_visitante }}</span>
                  </div>
                  <div class="font-body-sm text-body-sm text-secondary mt-0.5">
                    {{ p.fecha || 'Sin fecha' }} <template v-if="p.hora">• {{ p.hora.slice(0, 5) }}</template> • {{ p.fase }}
                  </div>
                </div>
              </div>
              <router-link
                to="/admin/partidos"
                class="inline-flex items-center gap-1 px-space-sm py-space-xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase font-bold hover:bg-primary-fixed-dim transition-colors shadow-sm self-end md:self-center"
              >
                <span class="material-symbols-outlined text-[16px]">scoreboard</span>
                <span>Abrir</span>
              </router-link>
            </div>
          </div>
        </section>
      </div>

      <!-- Columna lateral -->
      <div class="lg:col-span-4 flex flex-col gap-space-lg">
        <section class="rounded-xl bg-inverse-surface text-inverse-on-surface p-space-lg shadow-sm">
          <div class="flex items-center gap-space-xs mb-space-sm">
            <span class="material-symbols-outlined text-primary-container text-[20px]">admin_panel_settings</span>
            <h2 class="font-headline-md text-headline-md uppercase text-surface-bright tracking-tight">Sesión</h2>
          </div>
          <div class="flex flex-col gap-space-xs font-body-sm text-body-sm">
            <div class="flex items-center justify-between py-1 border-b border-surface-bright/10">
              <span class="text-surface-container-high font-semibold">Usuario</span>
              <span class="text-surface-bright font-mono text-xs">{{ auth.user?.usuario }}</span>
            </div>
            <div class="flex items-center justify-between py-1 border-b border-surface-bright/10">
              <span class="text-surface-container-high font-semibold">Rol</span>
              <span class="text-surface-bright font-mono text-xs">{{ auth.user?.rol || auth.user?.role }}</span>
            </div>
            <div class="flex items-center justify-between py-1">
              <span class="text-surface-container-high font-semibold">Estado</span>
              <span class="text-primary-container font-bold flex items-center gap-1 text-xs">
                <span class="material-symbols-outlined text-[14px]">check_circle</span> Activo
              </span>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useToast } from '../../composables/useToast';
import dashboardService from '../../services/dashboardService';
import RibbonCard from '../../components/admin/ResumenRibbonCard.vue';
import QuickAction from '../../components/admin/ResumenQuickAction.vue';

const auth = useAuthStore();
const toast = useToast();

const loading = ref(true);
const resumen = reactive({
  equipos: 0,
  jugadores_activos: 0,
  jugadores_suspendidos: 0,
  partidos: { total: 0, en_vivo: 0, finalizados: 0, programados: 0 },
  novedades: { publicadas: 0, borradores: 0 },
  atencion: [],
  actividad: []
});

const fechaHoy = new Date().toLocaleDateString('es-GT', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric'
}).replace(/^\w/, (c) => c.toUpperCase());

onMounted(async () => {
  try {
    const data = await dashboardService.getResumen();
    Object.assign(resumen, data);
  } catch {
    toast.error('No se pudo cargar el resumen');
  } finally {
    loading.value = false;
  }
});
</script>
