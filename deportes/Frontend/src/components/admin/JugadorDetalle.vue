<script setup>
import { computed } from 'vue'
import { PencilIcon } from 'lucide-vue-next'
import { imgUrl } from '../../services/api'
import { useVisorImagen } from '../../composables/useVisorImagen'

const props = defineProps({ jugador: { type: Object, required: true } })
defineEmits(['editar'])
const { verFoto } = useVisorImagen()

const POS = { POR: 'Portero', DEF: 'Defensa', MED: 'Mediocampista', DEL: 'Delantero' }

const edad = computed(() => {
  const f = props.jugador.birth_date
  if (!f) return null
  const [a, m, d] = f.split('-').map(Number)
  const hoy = new Date()
  return hoy.getFullYear() - a - (hoy.getMonth() + 1 < m || (hoy.getMonth() + 1 === m && hoy.getDate() < d) ? 1 : 0)
})
const fecha = (f) => (f ? f.slice(0, 10).split('-').reverse().join('/') : null)

const datos = computed(() => {
  const j = props.jugador
  const filas = [
    ['Equipo', j.team_name],
    ['Posición', POS[j.position]],
    ['Dorsal', j.jersey_number ? '#' + j.jersey_number : null],
    ['DPI', j.dpi],
    ['Teléfono', j.telefono],
    ['Fecha de nacimiento', fecha(j.birth_date)],
    ['Edad', edad.value === null ? null : edad.value + ' años'],
    ['Nacionalidad', j.nationality],
    ['Altura', j.height_cm ? j.height_cm + ' cm' : null],
    ['Peso', j.weight_kg ? j.weight_kg + ' kg' : null],
    ['Pie hábil', j.preferred_foot],
    ['Estado', j.status === 'activo' ? 'Activo' : 'Dado de baja'],
  ]
  if (j.status === 'inactivo') filas.push(['Motivo de la baja', j.razon_baja], ['Fecha de baja', fecha(j.fecha_baja)])
  return filas
})
</script>

<template>
  <div class="detalle">
    <section class="detalle-cabecera">
      <button v-if="jugador.photo_url" type="button" class="foto-zoom foto-retrato" :aria-label="'Ampliar foto de ' + jugador.full_name" @click="verFoto(jugador.photo_url, jugador.full_name)">
        <img :src="imgUrl(jugador.photo_url)" :alt="'Foto de ' + jugador.full_name" />
      </button>
      <span v-else class="foto-retrato foto-vacia">{{ jugador.full_name.split(' ').map((n) => n[0]).slice(0, 2).join('') }}</span>
      <div>
        <h3>{{ jugador.full_name }}</h3>
        <p class="muted small">{{ jugador.team_name }}</p>
      </div>
    </section>

    <dl class="bio-grid">
      <div v-for="[nombre, valor] in datos" :key="nombre"><dt>{{ nombre }}</dt><dd>{{ valor || '—' }}</dd></div>
    </dl>

    <div class="form-actions">
      <button class="btn btn-primary" @click="$emit('editar')"><PencilIcon :size="16" />Editar jugador</button>
    </div>
  </div>
</template>
