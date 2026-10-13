<script setup>
import { reactive, ref } from 'vue'
import ImageUpload from './ImageUpload.vue'

const props = defineProps({
  player: Object,
  teams: { type: Array, default: () => [] },
  busy: Boolean,
  errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  full_name: '', team_id: props.teams[0]?.id || '', jersey_number: 1, position: 'DEL', birth_date: '',
  nationality: 'Guatemala', height_cm: 175, weight_kg: 70, preferred_foot: 'Derecho', photo_url: null,
  ...props.player,
})
const subiendo = ref(false)
const hoy = new Date().toISOString().slice(0, 10)
</script>

<template>
  <form @submit.prevent="emit('save', { ...form })">
    <div class="form-grid">
      <div class="full"><ImageUpload v-model="form.photo_url" label="Foto del jugador" @busy="subiendo = $event" /></div>
      <label class="full">Nombre completo<input v-model.trim="form.full_name" required maxlength="120" autocomplete="name" /><small v-if="errors.full_name" class="field-error">{{ errors.full_name }}</small></label>
      <label>Equipo
        <select v-model.number="form.team_id" required>
          <option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option>
        </select>
        <small class="field-error">{{ errors.team_id }}</small>
      </label>
      <label>Dorsal<input v-model.number="form.jersey_number" type="number" min="1" max="99" required /><small class="field-error">{{ errors.jersey_number }}</small></label>
      <label>Posición
        <select v-model="form.position">
          <option value="POR">Portero</option><option value="DEF">Defensa</option><option value="MED">Mediocampista</option><option value="DEL">Delantero</option>
        </select>
      </label>
      <label>Fecha de nacimiento<input v-model="form.birth_date" type="date" min="1900-01-01" :max="hoy" required /></label>
      <label>Nacionalidad<input v-model.trim="form.nationality" maxlength="60" required /></label>
      <label>Pie hábil
        <select v-model="form.preferred_foot"><option>Derecho</option><option>Izquierdo</option><option>Ambidiestro</option></select>
      </label>
      <label>Altura (cm)<input v-model.number="form.height_cm" type="number" min="100" max="230" required /></label>
      <label>Peso (kg)<input v-model.number="form.weight_kg" type="number" min="30" max="200" step="0.01" required /></label>
    </div>
    <p class="form-help">El dorsal debe ser único en su equipo. Una vez que tenga eventos, no podrá cambiar de equipo en esta temporada.</p>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy || subiendo" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy || subiendo || !teams.length">{{ busy ? 'Guardando…' : 'Guardar jugador' }}</button>
    </div>
  </form>
</template>
