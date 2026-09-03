<template>
  <div class="flex flex-col w-full">
    <!-- Header -->
    <section class="relative w-full max-w-[1280px] mx-auto px-gutter-desktop pt-space-xl pb-space-lg">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-space-md">
        <div class="space-y-space-2xs">
          <div class="flex items-center gap-space-xs">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-primary-container animate-pulse"></span>
            <span class="font-label-meta text-label-meta uppercase tracking-widest text-primary">Boletín Oficial & Sala de Prensa</span>
          </div>
          <h1 class="font-headline-xl text-headline-xl uppercase text-on-surface tracking-tight leading-none">
            Novedades <span class="text-secondary">& Comunicados</span>
          </h1>
        </div>

        <div class="flex items-center gap-space-xs overflow-x-auto pb-space-2xs">
          <button 
            v-for="cat in categorias"
            :key="cat.id"
            @click="activeCat = cat.id"
            :class="[
              'px-space-md py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200',
              activeCat === cat.id ? 'bg-primary-container text-on-primary-fixed shadow-sm font-semibold' : 'bg-surface-container-high text-secondary hover:text-on-surface'
            ]"
          >
            {{ cat.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- Main Hero Article -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pb-space-2xl">
      <div class="relative bg-surface-container-lowest rounded-xl shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 items-stretch min-h-[460px]">
          <div class="relative lg:col-span-7 overflow-hidden bg-surface-container min-h-[320px] lg:min-h-full">
            <img class="w-full h-full object-cover object-center transform hover:scale-105 transition-transform duration-700" alt="Noticia destacada" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA5c_WtXbngmZth0OuuzU9Qisi2EumOqSRJa-sbqhR-wMpwR4VaHCaKrYsDtGLJyjGiXry3a8FZ394Y2TqlHh4j3ThnxBEefC3bJRoY6qi7OaOPKWwLLH8z2Q93VaYmn4b_TS_6LrzTokFXaZBx15KYhakV-Rn3pzWXoIPtKKqIU1ZPQxl3e41l-u844idHASmZLXpM71JyHe9R-uslSapBc3Ig7HytmZUjyjtCe0IkRUMlcp_jcc-QFQ"/>
            <div class="absolute top-space-md left-space-md flex items-center gap-space-xs">
              <span class="px-space-md py-space-xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase tracking-wider shadow-md">
                Destacado • Torneo 2025
              </span>
              <span class="px-space-sm py-space-xs rounded-full bg-inverse-surface/90 text-surface font-label-meta text-label-meta uppercase tracking-wider backdrop-blur-sm">
                Comunicado #42
              </span>
            </div>
            <div class="absolute bottom-space-md left-space-md flex items-center gap-space-sm bg-inverse-surface/85 backdrop-blur-md px-space-md py-space-xs rounded-full text-surface text-body-sm font-body-sm shadow-md">
              <span class="material-symbols-outlined text-primary-container text-[18px]">calendar_month</span>
              <span>22 Febrero 2025</span>
              <span class="w-1 h-1 rounded-full bg-primary-container"></span>
              <span class="material-symbols-outlined text-primary-container text-[18px]">schedule</span>
              <span>4 min de lectura</span>
            </div>
          </div>

          <div class="lg:col-span-5 p-space-lg lg:p-space-2xl flex flex-col justify-between">
            <div class="space-y-space-md">
              <div class="flex items-center gap-space-xs text-secondary font-label-meta text-label-meta uppercase">
                <span class="material-symbols-outlined text-[16px] text-primary">verified</span>
                <span>Comisión Técnica Federada Sanarate</span>
              </div>
              <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-[1.08] tracking-tight">
                Definidas las llaves para los Playoffs 2025: Horarios y sedes oficiales confirmadas
              </h2>
              <p class="text-secondary font-body-md text-body-md leading-relaxed">
                Tras 18 jornadas de intensa competencia de temporada regular, la Liga Mayor Sanarateca oficializa los cruces definitivos al título. Toros del Norte y Halcones lideran como cabezas de serie para la ronda de eliminación directa en el Gimnasio Municipal.
              </p>
              <div class="grid grid-cols-2 gap-space-sm pt-space-xs">
                <div class="p-space-md rounded-lg bg-surface-container-low flex flex-col justify-center">
                  <span class="font-stat-display text-stat-display text-on-surface leading-none">08</span>
                  <span class="font-label-meta text-label-meta uppercase text-secondary mt-space-2xs">Equipos Clasificados</span>
                </div>
                <div class="p-space-md rounded-lg bg-surface-container-low flex flex-col justify-center">
                  <span class="font-stat-display text-stat-display text-primary leading-none">03</span>
                  <span class="font-label-meta text-label-meta uppercase text-secondary mt-space-2xs">Rondas de Playoff</span>
                </div>
              </div>
            </div>

            <div class="pt-space-lg flex items-center justify-between">
              <button class="inline-flex items-center gap-space-xs px-space-xl py-space-sm rounded-full bg-inverse-surface text-surface hover:bg-primary-container hover:text-on-primary-fixed font-label-pill text-label-pill uppercase transition-all duration-300 shadow-md">
                <span>Leer Circular Completa</span>
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- News Grid -->
    <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pb-space-3xl">
      <div class="flex items-center justify-between mb-space-lg">
        <div class="space-y-space-2xs">
          <span class="font-label-meta text-label-meta uppercase tracking-wider text-secondary">Actualidad Deportiva</span>
          <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface">Artículos & Resoluciones Recientes</h3>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
        <article 
          v-for="item in noticias" 
          :key="item.id"
          class="flex flex-col bg-surface-container-lowest rounded-xl p-space-md shadow-sm hover:shadow-lg transition-all duration-300 group"
        >
          <div class="relative w-full h-56 rounded-lg overflow-hidden bg-surface-container mb-space-md">
            <img :src="item.foto" :alt="item.titulo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"/>
            <span class="absolute top-space-sm left-space-sm px-space-sm py-space-2xs rounded-full bg-inverse-surface/90 text-surface font-label-meta text-label-meta uppercase">
              {{ item.categoria }}
            </span>
          </div>
          <div class="flex-1 flex flex-col justify-between">
            <div class="space-y-space-xs">
              <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                <span>{{ item.fecha }}</span>
                <span>•</span>
                <span>{{ item.lectura }}</span>
              </div>
              <h4 class="font-headline-md text-headline-md uppercase text-on-surface group-hover:text-primary transition-colors leading-tight">
                {{ item.titulo }}
              </h4>
              <p class="font-body-sm text-body-sm text-secondary line-clamp-3">
                {{ item.resumen }}
              </p>
            </div>
            <div class="pt-space-md mt-space-md flex items-center justify-between border-t border-surface-container">
              <span class="font-label-meta text-label-meta text-secondary">Por: {{ item.autor }}</span>
              <button class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface group-hover:bg-primary-container group-hover:text-on-primary-fixed transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
              </button>
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const activeCat = ref('all');

const categorias = [
  { id: 'all', label: 'Todos' },
  { id: 'playoffs', label: 'Playoffs' },
  { id: 'tecnica', label: 'Comisión Técnica' },
  { id: 'comunidad', label: 'Comunidad & Clínicas' }
];

const noticias = ref([
  {
    id: 1,
    titulo: 'Acta de Sanciones Jornada 18: Fallos y Amonestaciones Técnicas',
    categoria: 'Comisión Disciplinaria',
    fecha: '20 Feb 2025',
    lectura: '3 min',
    autor: 'Lic. R. Méndez',
    resumen: 'Revisión de videos de incidentes en el encuentro Jaguares vs Leones. Se determinan sanciones por faltas antideportivas.',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuB3yR2Ye9skQF7WKyB5rsxwdSZ0MuOk80bwhdnWAv--gWMGQ3BeIqK34a46O-0iX4ldfLgoYe_ZKZKtcCgZLUjdES3K69w5ApWDHLXVxfgWZbr4RZBGRaCbBWOj1yC3o04MvhlYystejPtXeqqUnr3W-8SwbRsLyff-AK4Gk_64_7AbzwqSqiXqsGSPqpEbZLPAfGkef5BCdMpfW5Vr4OucmEQkNfP9VsIQjnd_KjxjyBgQ4RHHUXXkWg'
  },
  {
    id: 2,
    titulo: 'Campamento Semillero Basket 2025: Abiertas las Inscripciones Sub-13',
    categoria: 'Clínica Infantil',
    fecha: '18 Feb 2025',
    lectura: '5 min',
    autor: 'Dir. Juventud y Deporte',
    resumen: 'La Liga Sanarateca lanza programa gratuito de formación táctica para niños y niñas del municipio con apoyo de exjugadores.',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuAgq37zbFj_xIoJknYmN01KSCLrazQuy2jSKLU-zvbbdBVGkQOk8gWm_Cc1DQ_0a8xaRAF5eD2_zBjfGHPdtj7P7erg_qaD53qqaNCDgO0E4jywU_JZtuXltwygTtY63wvel2hVYSmQdX51XLb--Ck1SQtQuGk_vLk167TvLD0khWEYWjDSCWrAgolIQP0RF_hgmdMvptkpnOJp0v7R6Wp1gEebjJ93BhTj_NH_PuBIkTnL2__C4N2vPQ'
  },
  {
    id: 3,
    titulo: 'Convocatoria Oficial: Roster Preliminar para el Torneo Intermunicipal',
    categoria: 'Selección Municipal',
    fecha: '16 Feb 2025',
    lectura: '2 min',
    autor: 'Coach Estuardo Morales',
    resumen: 'Quince jugadores destacados de la Liga Local han sido preseleccionados para representar a Sanarate en el certamen regional.',
    foto: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCBAYy39ZTVQ20We1WpoUCFAJWC1NAX8ICz7Bts_Pnk0jMowSUPTa5xy6AV-3ts9LWyBi1bAhcS-hOgRBjD8dBI1znLzoLq9T4jr01tFG8oz4FtzGyD-HZ1YgoKz9C7-FTyUi9KyVdtJhV0z1w7KkXk7w89V8U1GIeUEUMLJUS4TqqxiRTjaGJbNz6iDxsITvRQSClgN5YtP4GwPOGgEP-xjrRfMqQyzPGNKYDeS7Ny2Z3VyF8NxDINXw'
  }
]);
</script>
