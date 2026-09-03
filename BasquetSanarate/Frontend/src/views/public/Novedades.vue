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

        <div v-if="categorias.length" class="flex items-center gap-space-xs overflow-x-auto pb-space-2xs">
          <button
            v-for="cat in categorias"
            :key="cat"
            @click="activeCat = cat"
            :class="[
              'px-space-md py-space-xs rounded-full font-label-pill text-label-pill uppercase transition-all duration-200 whitespace-nowrap',
              activeCat === cat ? 'bg-primary-container text-on-primary-fixed shadow-sm font-semibold' : 'bg-surface-container-high text-secondary hover:text-on-surface'
            ]"
          >{{ cat }}</button>
        </div>
      </div>
    </section>

    <div v-if="loading" class="max-w-[1280px] mx-auto px-gutter-desktop py-space-3xl text-center text-secondary font-body-md">Cargando…</div>
    <div v-else-if="!noticias.length" class="max-w-[1280px] mx-auto px-gutter-desktop py-space-3xl text-center text-secondary font-body-md">
      Aún no hay publicaciones.
    </div>

    <template v-else>
      <!-- Hero -->
      <section v-if="destacada" class="w-full max-w-[1280px] mx-auto px-gutter-desktop pb-space-2xl">
        <div class="relative bg-surface-container-lowest rounded-xl shadow-md overflow-hidden">
          <div class="grid grid-cols-1 lg:grid-cols-12 items-stretch min-h-[400px]">
            <div class="relative lg:col-span-7 overflow-hidden bg-surface-container min-h-[280px] lg:min-h-full flex items-center justify-center">
              <img v-if="destacada.portada_ruta" class="w-full h-full object-cover" :alt="destacada.titulo" :src="assetUrl(destacada.portada_ruta)" />
              <span v-else class="material-symbols-outlined text-[64px] text-outline-variant">newspaper</span>
              <span class="absolute top-space-md left-space-md px-space-md py-space-xs rounded-full bg-primary-container text-on-primary-fixed font-label-pill text-label-pill uppercase tracking-wider shadow-md">
                {{ destacada.categoria }}
              </span>
            </div>
            <div class="lg:col-span-5 p-space-lg lg:p-space-2xl flex flex-col justify-between gap-space-md">
              <div class="space-y-space-md">
                <div class="flex items-center gap-space-xs text-secondary font-label-meta text-label-meta uppercase">
                  <span class="material-symbols-outlined text-[16px] text-primary">calendar_month</span>
                  <span>{{ destacada.fecha_emision || '—' }}</span>
                  <span v-if="destacada.autor_nombre">• {{ destacada.autor_nombre }}</span>
                </div>
                <h2 class="font-headline-xl text-headline-xl uppercase text-on-surface leading-[1.08] tracking-tight">{{ destacada.titulo }}</h2>
                <p class="text-secondary font-body-md text-body-md leading-relaxed line-clamp-6">{{ destacada.cuerpo }}</p>
              </div>
              <a
                v-if="destacada.pdf_ruta"
                :href="assetUrl(destacada.pdf_ruta)"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-space-xs px-space-xl py-space-sm rounded-full bg-inverse-surface text-surface hover:bg-primary-container hover:text-on-primary-fixed font-label-pill text-label-pill uppercase transition-all duration-300 shadow-md w-fit"
              >
                <span>Leer circular completa</span>
                <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
              </a>
            </div>
          </div>
        </div>
      </section>

      <!-- Grid -->
      <section class="w-full max-w-[1280px] mx-auto px-gutter-desktop pb-space-3xl">
        <div class="mb-space-lg">
          <span class="font-label-meta text-label-meta uppercase tracking-wider text-secondary">Actualidad Deportiva</span>
          <h3 class="font-headline-lg text-headline-lg uppercase text-on-surface">Artículos & Resoluciones Recientes</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-lg">
          <article
            v-for="item in resto"
            :key="item.id"
            class="flex flex-col bg-surface-container-lowest rounded-xl p-space-md shadow-sm hover:shadow-lg transition-all duration-300 group"
          >
            <div class="relative w-full h-56 rounded-lg overflow-hidden bg-surface-container mb-space-md flex items-center justify-center">
              <img v-if="item.portada_ruta" :src="assetUrl(item.portada_ruta)" :alt="item.titulo" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
              <span v-else class="material-symbols-outlined text-[48px] text-outline-variant">newspaper</span>
              <span class="absolute top-space-sm left-space-sm px-space-sm py-space-2xs rounded-full bg-inverse-surface/90 text-surface font-label-meta text-label-meta uppercase">{{ item.categoria }}</span>
            </div>
            <div class="flex-1 flex flex-col justify-between">
              <div class="space-y-space-xs">
                <div class="flex items-center gap-space-2xs text-secondary font-label-meta text-label-meta">
                  <span>{{ item.fecha_emision || '—' }}</span>
                </div>
                <h4 class="font-headline-md text-headline-md uppercase text-on-surface group-hover:text-primary transition-colors leading-tight line-clamp-2">{{ item.titulo }}</h4>
                <p class="font-body-sm text-body-sm text-secondary line-clamp-3">{{ item.cuerpo }}</p>
              </div>
              <div class="pt-space-md mt-space-md flex items-center justify-between border-t border-surface-container">
                <span class="font-label-meta text-label-meta text-secondary">Por: {{ item.autor_nombre || 'Liga Sanarateca' }}</span>
                <a
                  v-if="item.pdf_ruta"
                  :href="assetUrl(item.pdf_ruta)"
                  target="_blank"
                  rel="noopener"
                  class="w-9 h-9 rounded-full bg-surface-container-high flex items-center justify-center text-on-surface group-hover:bg-primary-container group-hover:text-on-primary-fixed transition-colors"
                >
                  <span class="material-symbols-outlined text-[18px]">picture_as_pdf</span>
                </a>
              </div>
            </div>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { assetUrl } from '../../services/assets';
import novedadesService from '../../services/novedadesService';

const loading = ref(true);
const activeCat = ref('Todos');
const todas = ref([]);

const categorias = computed(() => ['Todos', ...new Set(todas.value.map((n) => n.categoria).filter(Boolean))]);

const noticias = computed(() =>
  activeCat.value === 'Todos' ? todas.value : todas.value.filter((n) => n.categoria === activeCat.value)
);

const destacada = computed(() => noticias.value.find((n) => n.fijado) || noticias.value[0] || null);
const resto = computed(() => noticias.value.filter((n) => n.id !== destacada.value?.id));

onMounted(async () => {
  try {
    todas.value = await novedadesService.list();
  } catch {
    todas.value = [];
  } finally {
    loading.value = false;
  }
});
</script>
