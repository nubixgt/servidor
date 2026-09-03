<template>
  <div class="flex flex-col w-full">
    <!-- HERO -->
    <section class="relative w-full overflow-hidden bg-inverse-surface text-surface py-space-3xl px-gutter-mobile md:px-gutter-desktop">
      <div class="absolute inset-0 pointer-events-none select-none opacity-5 flex items-center justify-center overflow-hidden">
        <span class="font-headline-xl text-[160px] md:text-[280px] leading-none uppercase font-bold text-primary-container tracking-tighter transform -rotate-6">SANARATE</span>
      </div>

      <div class="max-w-[1280px] mx-auto w-full relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
        <div class="lg:col-span-7 flex flex-col items-start space-y-space-md">
          <div class="inline-flex items-center gap-space-xs bg-surface-variant/10 text-primary-container px-space-md py-space-2xs rounded-full">
            <span class="w-2.5 h-2.5 rounded-full bg-primary-container animate-ping"></span>
            <span class="font-label-pill text-label-pill uppercase tracking-wider">Torneo Apertura 2025 • Temporada Oficial</span>
          </div>

          <div class="relative">
            <h1 class="font-display-hero text-display-hero-mobile md:text-display-hero uppercase tracking-tight text-surface font-extrabold leading-none">
              JUEGA EL <span class="text-primary-container inline-block drop-shadow-[0_0_25px_rgba(204,255,0,0.35)]">PARTIDO,</span><br />
              VIVE EL <span class="text-primary-container inline-block">MOMENTO</span>
            </h1>
            <div class="mt-space-2xs font-headline-md text-headline-md uppercase tracking-wider text-surface-dim opacity-90">
              Liga de Baloncesto Sanarateca
            </div>
          </div>

          <p class="font-body-lg text-body-lg text-surface-dim max-w-xl leading-relaxed">
            La máxima pasión del deporte de las canastas en Sanarate. Destreza, ritmo frenético y comunidad unida en cada posesión sobre la duela.
          </p>

          <div class="pt-space-sm flex flex-wrap items-center gap-space-md w-full sm:w-auto">
            <router-link to="/partidos" class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase px-space-xl py-space-md shadow-[0_8px_24px_-4px_rgba(204,255,0,0.45)] hover:scale-105 transition-all duration-200">
              <span class="material-symbols-outlined text-[20px] mr-space-xs">sports_basketball</span>
              Explorar Torneo
            </router-link>
            <router-link to="/partidos" class="w-full sm:w-auto inline-flex items-center justify-center rounded-full bg-surface-variant/10 text-surface font-label-pill text-label-pill uppercase px-space-xl py-space-md hover:bg-surface-variant/20 transition-all duration-200">
              <span class="material-symbols-outlined text-[20px] mr-space-xs">calendar_month</span>
              Ver Calendario
            </router-link>
          </div>
        </div>

        <div class="lg:col-span-5 relative flex items-center justify-center mt-space-lg lg:mt-0">
          <div class="absolute w-72 h-72 md:w-96 md:h-96 rounded-full bg-primary-container/15 blur-3xl -z-0"></div>
          <div class="relative z-10 w-full max-w-[420px] bg-surface-container-low/10 rounded-xl p-space-md backdrop-blur-md shadow-2xl">
            <div class="relative overflow-hidden rounded-lg aspect-[4/5] bg-surface-container-high flex items-center justify-center">
              <img v-if="figura?.foto_ruta" class="w-full h-full object-cover object-top" :alt="figura.nombre_completo" :src="assetUrl(figura.foto_ruta)" />
              <span v-else class="material-symbols-outlined text-[96px] text-outline-variant">sports_basketball</span>
              <div class="absolute inset-0 bg-gradient-to-t from-inverse-surface via-inverse-surface/30 to-transparent"></div>
              <div class="absolute top-space-md left-space-md bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase shadow-md flex items-center gap-space-2xs">
                <span class="w-2 h-2 rounded-full bg-inverse-surface"></span>
                Máximo Anotador
              </div>
              <div v-if="figura" class="absolute bottom-space-md left-space-md right-space-md p-space-md rounded bg-inverse-surface/85 backdrop-blur-md">
                <span class="font-label-meta text-label-meta text-primary-container uppercase tracking-widest">{{ figura.posicion || 'Jugador' }} • {{ figura.equipo_nombre || 'Sin equipo' }}</span>
                <div class="flex items-baseline justify-between mt-space-2xs">
                  <h3 class="font-headline-md text-headline-md text-surface uppercase truncate">{{ figura.nombre_completo }}</h3>
                  <span v-if="figura.dorsal != null" class="font-stat-display text-headline-md text-primary-container shrink-0">#{{ figura.dorsal }}</span>
                </div>
                <div class="grid grid-cols-2 gap-space-xs mt-space-sm pt-space-xs bg-surface-variant/10 rounded p-space-xs text-center">
                  <div>
                    <div class="font-headline-md text-headline-md text-surface leading-none">{{ figura.stats.ppg }}</div>
                    <div class="font-label-meta text-label-meta text-surface-dim uppercase mt-space-2xs">PPG</div>
                  </div>
                  <div>
                    <div class="font-headline-md text-headline-md text-primary-container leading-none">{{ figura.stats.tres_pct }}%</div>
                    <div class="font-label-meta text-label-meta text-surface-dim uppercase mt-space-2xs">Triples</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- METRICS -->
    <section class="w-full bg-surface-container-lowest py-space-lg shadow-sm">
      <div class="max-w-[1280px] mx-auto px-gutter-mobile md:px-gutter-desktop">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
          <div v-for="metric in metrics" :key="metric.label" class="bg-surface-container-low p-space-md rounded-lg flex items-center gap-space-md transition-transform hover:-translate-y-1 duration-200">
            <div class="w-12 h-12 rounded-full bg-primary-container text-on-primary-fixed flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-[24px]">{{ metric.icon }}</span>
            </div>
            <div class="flex flex-col min-w-0">
              <span class="font-stat-display text-headline-xl text-on-surface leading-none">{{ metric.value }}</span>
              <span class="font-label-meta text-label-meta text-secondary uppercase truncate">{{ metric.label }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FIXTURE -->
    <section class="w-full py-space-2xl px-gutter-mobile md:px-gutter-desktop">
      <div class="max-w-[1280px] mx-auto flex flex-col gap-space-lg">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-space-sm">
          <div>
            <div class="inline-flex items-center gap-space-xs text-primary font-label-meta text-label-meta uppercase tracking-wider mb-space-2xs">
              <span class="w-2 h-2 rounded-full bg-primary"></span>
              Fixture
            </div>
            <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-tight tracking-tight">Próximos Encuentros</h2>
          </div>
          <router-link to="/partidos" class="inline-flex items-center gap-space-2xs font-label-pill text-label-pill uppercase text-on-surface hover:text-primary transition-colors">
            <span>Ver calendario completo</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </router-link>
        </div>

        <div v-if="proximos.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <div v-for="m in proximos" :key="m.id" class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col justify-between">
            <div class="flex items-center justify-between gap-space-xs mb-space-md">
              <span class="bg-surface-container text-on-surface-variant font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase truncate max-w-[60%]">
                {{ m.sede || 'Sede por confirmar' }}
              </span>
              <span
                class="font-label-meta text-label-meta px-space-sm py-space-2xs rounded-full uppercase font-bold"
                :class="m.estado === 'En Vivo' ? 'bg-primary-container text-on-primary-fixed animate-pulse' : 'bg-surface-container-high text-secondary'"
              >{{ m.estado === 'En Vivo' ? 'En Vivo' : (m.fecha || 'Programado') }}</span>
            </div>
            <div class="bg-surface-container-low rounded-lg p-space-md my-space-xs">
              <div class="flex items-center justify-between">
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-2 mb-space-2xs">
                    <img v-if="m.local.logo_ruta" :src="assetUrl(m.local.logo_ruta)" alt="" class="w-full h-full object-contain" />
                    <span v-else class="material-symbols-outlined text-primary-container text-[20px]">shield</span>
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight truncate w-full">{{ m.local.nombre }}</span>
                </div>
                <div class="flex flex-col items-center justify-center w-2/12">
                  <div class="bg-primary-container text-on-primary-fixed px-space-sm py-space-2xs rounded-full font-label-pill text-label-pill uppercase font-bold">
                    {{ m.estado === 'Programado' ? 'VS' : `${m.marcador_local}-${m.marcador_visitante}` }}
                  </div>
                  <span v-if="m.hora" class="font-label-meta text-label-meta text-secondary mt-space-2xs">{{ m.hora }}</span>
                </div>
                <div class="flex flex-col items-center text-center w-5/12">
                  <div class="w-14 h-14 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-2 mb-space-2xs">
                    <img v-if="m.visitante.logo_ruta" :src="assetUrl(m.visitante.logo_ruta)" alt="" class="w-full h-full object-contain" />
                    <span v-else class="material-symbols-outlined text-primary-container text-[20px]">shield</span>
                  </div>
                  <span class="font-headline-md text-headline-md uppercase text-on-surface leading-tight truncate w-full">{{ m.visitante.nombre }}</span>
                </div>
              </div>
            </div>
            <div class="mt-space-md pt-space-sm flex items-center justify-between">
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span class="material-symbols-outlined text-[16px]">sports</span>
                <span>Jornada {{ m.jornada || '—' }}</span>
              </div>
              <router-link to="/partidos" class="rounded-full bg-surface-container text-on-surface px-space-md py-space-xs font-label-pill text-label-pill uppercase hover:bg-primary-container hover:text-on-primary-fixed transition-colors">
                Detalle
              </router-link>
            </div>
          </div>
        </div>
        <p v-else class="font-body-md text-body-md text-secondary py-space-lg text-center">No hay encuentros programados por ahora.</p>
      </div>
    </section>

    <!-- DESTACADOS -->
    <section v-if="topJugadores.length || topEquipo" class="w-full bg-surface-container py-space-2xl px-gutter-mobile md:px-gutter-desktop">
      <div class="max-w-[1280px] mx-auto flex flex-col gap-space-xl">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-sm">
          <div>
            <span class="font-label-meta text-label-meta uppercase text-primary tracking-widest font-bold">Talento Departamental</span>
            <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-tight">Figuras & Equipos Destacados</h2>
          </div>
          <router-link to="/jugadores" class="inline-flex items-center gap-space-2xs font-label-pill text-label-pill uppercase text-on-surface hover:text-primary transition-colors">
            <span>Ver todos los atletas</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </router-link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <div v-for="j in topJugadores" :key="'j' + j.id" class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="relative overflow-hidden rounded-lg aspect-square bg-surface-container-high mb-space-md flex items-center justify-center">
              <img v-if="j.foto_ruta" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" :alt="j.nombre_completo" :src="assetUrl(j.foto_ruta)" />
              <span v-else class="material-symbols-outlined text-[72px] text-outline-variant">person</span>
              <div class="absolute top-space-sm left-space-sm bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase font-bold">Figura</div>
              <div class="absolute bottom-space-sm right-space-sm bg-inverse-surface/80 text-surface rounded-full px-space-sm py-space-2xs font-label-meta text-label-meta">#{{ j.dorsal ?? '—' }} • {{ j.posicion || 'S/P' }}</div>
            </div>
            <div>
              <span class="font-label-meta text-label-meta text-secondary uppercase">{{ j.equipo_nombre || 'Sin equipo' }}</span>
              <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface mt-space-2xs truncate">{{ j.nombre_completo }}</h3>
            </div>
            <div class="grid grid-cols-2 gap-space-xs mt-space-md pt-space-md bg-surface-container-low p-space-sm rounded">
              <div>
                <div class="font-headline-md text-headline-md text-on-surface leading-none">{{ j.stats.ppg }}</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">PPG</div>
              </div>
              <div>
                <div class="font-headline-md text-headline-md text-primary leading-none">{{ j.stats.rpg }}</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">RPG</div>
              </div>
            </div>
          </div>

          <div v-if="topEquipo" class="bg-surface-container-lowest rounded-xl p-space-lg shadow-sm flex flex-col justify-between group hover:shadow-md transition-all">
            <div class="relative overflow-hidden rounded-lg aspect-square bg-surface-container-high mb-space-md flex items-center justify-center">
              <img v-if="topEquipo.logo_ruta" class="w-full h-full object-contain p-space-lg" :alt="topEquipo.nombre" :src="assetUrl(topEquipo.logo_ruta)" />
              <span v-else class="material-symbols-outlined text-[72px] text-outline-variant">shield</span>
              <div class="absolute top-space-sm left-space-sm bg-primary-container text-on-primary-fixed rounded-full px-space-sm py-space-2xs font-label-pill text-label-pill uppercase font-bold">Líder del Torneo</div>
            </div>
            <div>
              <span class="font-label-meta text-label-meta text-secondary uppercase">Plantel Destacado</span>
              <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface mt-space-2xs truncate">{{ topEquipo.nombre }}</h3>
            </div>
            <div class="grid grid-cols-2 gap-space-xs mt-space-md pt-space-md bg-surface-container-low p-space-sm rounded">
              <div>
                <div class="font-headline-md text-headline-md text-on-surface leading-none">{{ topEquipo.clasificacion.record }}</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Récord V-D</div>
              </div>
              <div>
                <div class="font-headline-md text-headline-md text-primary leading-none">{{ topEquipo.clasificacion.dif > 0 ? '+' : '' }}{{ topEquipo.clasificacion.dif }}</div>
                <div class="font-label-meta text-label-meta text-secondary uppercase">Diferencial</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import equiposService from '../../services/equiposService';
import jugadoresService from '../../services/jugadoresService';
import partidosService from '../../services/partidosService';

const equipos = ref([]);
const jugadores = ref([]);
const partidos = ref([]);

const metrics = computed(() => [
  { icon: 'groups', label: 'Equipos registrados', value: equipos.value.length },
  { icon: 'person', label: 'Atletas activos', value: jugadores.value.length },
  { icon: 'scoreboard', label: 'Partidos jugados', value: partidos.value.filter((p) => p.estado === 'Finalizado').length },
  { icon: 'stadium', label: 'Sedes', value: new Set(equipos.value.map((e) => e.sede).filter(Boolean)).size }
]);

const proximos = computed(() =>
  partidos.value.filter((p) => p.estado === 'En Vivo' || p.estado === 'Programado').slice(0, 3)
);

const figura = computed(() => {
  if (!jugadores.value.length) return null;
  return [...jugadores.value].sort((a, b) => b.stats.ppg - a.stats.ppg)[0];
});

const topJugadores = computed(() =>
  [...jugadores.value].sort((a, b) => b.stats.ppg - a.stats.ppg).slice(0, 2)
);

const topEquipo = computed(() => equipos.value[0] || null);

onMounted(async () => {
  try {
    const [e, j, p] = await Promise.all([
      equiposService.list(),
      jugadoresService.list(),
      partidosService.list()
    ]);
    equipos.value = e;
    jugadores.value = j.items;
    partidos.value = p;
  } catch {
    /* la landing degrada a estados vacíos */
  }
});
</script>
