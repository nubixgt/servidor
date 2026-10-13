<script setup>
import { reactive, ref } from 'vue'

const props = defineProps({
  match: Object,
  teams: { type: Array, default: () => [] },
  busy: Boolean,
  errors: { type: Object, default: () => ({}) },
})
const emit = defineEmits(['save', 'cancel'])

const form = reactive({
  home_team_id: props.teams[0]?.id || '',
  away_team_id: props.teams[1]?.id || '',
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
      <label>Jornada<input v-model.number="form.round_number" type="number" min="1" max="99" required /></label>
      <label class="full">Estado
        <select v-model="form.status">
          <option value="scheduled">Programado</option><option value="live">En juego</option><option value="finished">Finalizado</option>
        </select>
      </label>
    </div>
    <p class="form-help">Finalizar el partido incorpora sus resultados y eventos a los rankings. Un 0–0 no necesita eventos de gol.</p>
    <p v-if="error" class="error-text" role="alert">{{ error }}</p>
    <div class="form-actions">
      <button type="button" class="btn btn-secondary" :disabled="busy" @click="emit('cancel')">Cancelar</button>
      <button class="btn btn-primary" :disabled="busy || teams.length < 2">{{ busy ? 'Guardando…' : 'Guardar partido' }}</button>
    </div>
  </form>
</template>
