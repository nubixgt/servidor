<script setup>
import { computed } from 'vue'
import { PencilIcon } from 'lucide-vue-next'
import { imgUrl } from '../../services/api'
import { useVisorImagen } from '../../composables/useVisorImagen'
import TeamBadge from '../pulse/TeamBadge.vue'

const props = defineProps({ equipo: { type: Object, required: true }, puedeEditar: { type: Boolean, default: true } })
defineEmits(['editar'])
const { verFoto } = useVisorImagen()

const datos = computed(() => {
  const e = props.equipo
  return [
    ['Representante', e.representante],
    ['Teléfono', e.telefono],
    ['DPI del representante', e.dpi],
    ['Sub‑representante', e.sub_representante_nombre],
    ['DPI del sub‑representante', e.sub_representante_dpi],
    ['Teléfono del sub‑representante', e.sub_representante_telefono],
    ['Abreviatura', e.short_name],
    ['Ciudad', e.city],
    ['Estadio', e.stadium],
    ['Entrenador', e.coach],
    ['Año de fundación', e.founded_year],
    ['Jugadores activos', e.players_count],
    ['Usuario de acceso', e.usuario],
  ]
})
</script>

<template>
  <div class="detalle">
    <section class="detalle-cabecera" :style="{ '--team-color': equipo.primary_color }">
      <button v-if="equipo.logo_url" type="button" class="foto-zoom" :aria-label="'Ampliar escudo de ' + equipo.name" @click="verFoto(equipo.logo_url, 'Escudo · ' + equipo.name)">
        <TeamBadge :team="equipo" :size="96" />
      </button>
      <TeamBadge v-else :team="equipo" :size="96" />
      <div>
        <h3>{{ equipo.name }}</h3>
        <p class="muted small">{{ [equipo.city, equipo.stadium].filter(Boolean).join(' · ') || 'Sin ciudad ni estadio registrados' }}</p>
        <span class="detalle-colores">
          <i :style="{ background: equipo.primary_color }" title="Color principal"></i>
          <i :style="{ background: equipo.secondary_color }" title="Color secundario"></i>
        </span>
      </div>
    </section>

    <dl class="bio-grid">
      <div v-for="[nombre, valor] in datos" :key="nombre"><dt>{{ nombre }}</dt><dd>{{ valor === null || valor === undefined || valor === '' ? '—' : valor }}</dd></div>
    </dl>

    <div v-if="equipo.foto_representante_url || equipo.sub_representante_foto_url" class="detalle-fotos">
      <figure v-if="equipo.foto_representante_url">
        <button type="button" class="foto-zoom" @click="verFoto(equipo.foto_representante_url, 'Representante · ' + equipo.representante)">
          <img :src="imgUrl(equipo.foto_representante_url)" :alt="'Foto de ' + equipo.representante" loading="lazy" />
        </button>
        <figcaption>Representante</figcaption>
      </figure>
      <figure v-if="equipo.sub_representante_foto_url">
        <button type="button" class="foto-zoom" @click="verFoto(equipo.sub_representante_foto_url, 'Sub‑representante · ' + (equipo.sub_representante_nombre || ''))">
          <img :src="imgUrl(equipo.sub_representante_foto_url)" alt="Foto del sub‑representante" loading="lazy" />
        </button>
        <figcaption>Sub‑representante</figcaption>
      </figure>
    </div>

    <div v-if="puedeEditar" class="form-actions">
      <button class="btn btn-primary" @click="$emit('editar')"><PencilIcon :size="16" />Editar equipo</button>
    </div>
  </div>
</template>
