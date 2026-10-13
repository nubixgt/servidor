<script setup>
import { reactive } from 'vue'
import ImageUpload from './ImageUpload.vue'

// `elegirEquipo`: solo el admin escoge equipo; el servidor fuerza el del encargado.
const props = defineProps({
  player: Object,
  teams: { type: Array, default: () => [] },
  elegirEquipo: { type: Boolean, default: true },
  busy: Boolean,
  errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  full_name: '', team_id: props.teams[0]?.id || '', dpi: '', telefono: '', position: 'DEL', jersey_number: null,
  birth_date: '', nationality: 'Guatemala', height_cm: null, weight_kg: null, preferred_foot: '',
  ...props.player,
})
const fotos = reactive({ foto: { file: null, remove: false } })
const hoy = new Date().toISOString().slice(0, 10)
</script>

<template>
  <form @submit.prevent="emit('save', { ...form }, { ...fotos })">
    <div class="form-grid">
      <div class="full"><ImageUpload v-model="fotos.foto" :current="player?.photo_url" label="Foto del jugador" /></div>
      <label class="full">Nombre completo<input v-model.trim="form.full_name" required maxlength="100" autocomplete="name" /></label>
      <label>DPI<input v-model.trim="form.dpi" required maxlength="20" /><small class="field-error">{{ errors.dpi }}</small></label>
      <label>Teléfono<input v-model.trim="form.telefono" required maxlength="20" /></label>
      <label v-if="elegirEquipo">Equipo
        <select v-model.number="form.team_id" required><option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option></select>
        <small class="field-error">{{ errors.equipo_id }}</small>
      </label>
      <label>Posición
        <select v-model="form.position" required>
          <option value="POR">Portero</option><option value="DEF">Defensa</option><option value="MED">Mediocampista</option><option value="DEL">Delantero</option>
        </select>
      </label>
      <label>Dorsal (opcional)<input v-model.number="form.jersey_number" type="number" min="1" max="99" /><small class="field-error">{{ errors.dorsal }}</small></label>
      <label>Fecha de nacimiento (opcional)<input v-model="form.birth_date" type="date" min="1900-01-01" :max="hoy" /></label>
      <label>Nacionalidad<input v-model.trim="form.nationality" maxlength="60" /></label>
      <label>Pie hábil
        <select v-model="form.preferred_foot"><option value="">—</option><option>Derecho</option><option>Izquierdo</option><option>Ambidiestro</option></select>
      </label>
      <label>Altura en cm (opcional)<input v-model.number="form.height_cm" type="number" min="100" max="230" /></label>
      <label>Peso en kg (opcional)<input v-model.number="form.weight_kg" type="number" min="30" max="200" step="0.01" /></label>
    </div>
    <p class="form-help">El dorsal debe ser único en su equipo. Un jugador con eventos registrados no puede cambiar de equipo.</p>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy || (elegirEquipo && !teams.length)">{{ busy ? 'Guardando…' : 'Guardar jugador' }}</button>
    </div>
  </form>
</template>
