<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  match: Object,
  teams: { type: Array, default: () => [] },
  equipoPropio: Number, // encargado: su equipo queda como local por defecto
  busy: Boolean,
  errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  home_team_id: props.equipoPropio || props.teams[0]?.id || '',
  away_team_id: props.teams.find((t) => t.id !== (props.equipoPropio || props.teams[0]?.id))?.id || '',
  round_number: 1,
  status: 'scheduled',
  ...props.match,
})

// kickoff_at se guarda en UTC; el formulario trabaja en hora de Guatemala (UTC−6).
const aLocal = (utc) => new Date(new Date(utc.replace(' ', 'T') + 'Z').getTime() - 6 * 3600000).toISOString().slice(0, 16)
const aUtc = (local) => new Date(local + ':00-06:00').toISOString().slice(0, 19).replace('T', ' ')

const fecha = ref(props.match?.kickoff_at ? aLocal(props.match.kickoff_at) : '')
const error = ref('')

function guardar() {
  error.value = ''
  if (+form.home_team_id === +form.away_team_id) {
    error.value = 'Selecciona dos equipos distintos.'
    return
  }
  emit('save', { ...form, kickoff_at: aUtc(fecha.value) })
}
</script>

<template>
  <form @submit.prevent="guardar">
    <div v-if="match" class="score-preview">
      Marcador actual <b>{{ match.home_score }} : {{ match.away_score }}</b>
      <p class="form-help">El marcador se modifica registrando goles o autogoles en «Eventos».</p>
    </div>
    <div class="form-grid">
      <label>Equipo local
        <select v-model.number="form.home_team_id" required><option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option></select>
      </label>
      <label>Equipo visitante
        <select v-model.number="form.away_team_id" required><option v-for="t in teams" :key="t.id" :value="t.id">{{ t.name }}</option></select>
      </label>
      <label>Fecha y hora en Guatemala<input v-model="fecha" type="datetime-local" required /><small class="field-error">{{ errors.kickoff_at }}</small></label>
      <label class="full">Jornada<input v-model.number="form.round_number" type="number" min="1" max="99" required /></label>
    </div>
    <p class="form-help">El partido se inicia y se finaliza desde «Eventos», donde también se registran los goles.</p>
    <p v-if="error" class="error-text" role="alert">{{ error }}</p>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy || teams.length < 2">{{ busy ? 'Guardando…' : 'Guardar partido' }}</button>
    </div>
  </form>
</template>
