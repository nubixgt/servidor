<script setup>
import { computed, reactive, ref } from 'vue'
import { PencilIcon, Trash2Icon } from 'lucide-vue-next'
import { usePulse } from '../../composables/usePulse'
import { useAdminData } from '../../composables/useAdminData'
import { EVENT_LABEL } from '../../utils/pulseFormat'

const props = defineProps({ match: { type: Object, required: true } })
const emit = defineEmits(['busy'])

const { state, eventosDePartido } = usePulse()
const admin = useAdminData()

const eventos = ref(eventosDePartido(props.match.id))
const error = ref('')
const aviso = ref('')
const ocupado = ref(false)
const confirmando = ref(null)
const editando = ref(null)
const form = reactive({ player_id: '', assist_player_id: '', type: 'goal', minute: 1 })

const actual = computed(() => state.matches.find((p) => +p.id === +props.match.id) || props.match)
const candidatos = computed(() => state.players.filter((j) => [+props.match.home_team_id, +props.match.away_team_id].includes(+j.team_id)))
const asistentes = computed(() => {
  const j = candidatos.value.find((x) => +x.id === +form.player_id)
  return candidatos.value.filter((x) => +x.team_id === +j?.team_id && +x.id !== +form.player_id)
})

function limpiar() {
  editando.value = null
  Object.assign(form, { player_id: '', assist_player_id: '', type: 'goal', minute: 1 })
}

function editar(e) {
  confirmando.value = null
  editando.value = e.id
  Object.assign(form, { player_id: +e.player_id, assist_player_id: e.assist_player_id ? +e.assist_player_id : '', type: e.type, minute: +e.minute })
}

async function ejecutar(accion, mensaje) {
  ocupado.value = true
  emit('busy', true)
  error.value = ''
  aviso.value = ''
  try {
    await accion()
    eventos.value = eventosDePartido(props.match.id)
    limpiar()
    confirmando.value = null
    aviso.value = mensaje
  } catch (e) {
    error.value = e.message
  } finally {
    ocupado.value = false
    emit('busy', false)
  }
}

const guardar = () =>
  ejecutar(
    () =>
      admin.guardarEvento(props.match.id, {
        ...form,
        id: editando.value || undefined,
        assist_player_id: form.type === 'goal' && form.assist_player_id ? +form.assist_player_id : null,
      }),
    'Evento guardado. Marcador y estadísticas actualizados.'
  )

const eliminar = (id) => ejecutar(() => admin.eliminarEvento(id), 'Evento eliminado. Estadísticas actualizadas.')
</script>

<template>
  <div class="event-editor">
    <div class="score-preview">
      {{ actual.home_short }} <b>{{ actual.home_score }} : {{ actual.away_score }}</b> {{ actual.away_short }}
      <p class="form-help">{{ actual.status === 'finished' ? 'Este resultado ya cuenta para los rankings.' : 'Los rankings se actualizarán al finalizar el partido.' }}</p>
    </div>

    <p v-if="actual.status === 'scheduled'" class="notice info">Edita el partido y cambia su estado a «En juego» para registrar eventos.</p>
    <p v-if="error" class="error-text" role="alert">{{ error }}</p>
    <p v-if="aviso" class="notice" role="status">{{ aviso }}</p>

    <form v-if="actual.status !== 'scheduled'" @submit.prevent="guardar">
      <h3>{{ editando ? 'Corregir evento' : 'Registrar evento' }}</h3>
      <div class="form-grid">
        <label>Tipo
          <select v-model="form.type" @change="form.assist_player_id = ''">
            <option v-for="(nombre, clave) in EVENT_LABEL" :key="clave" :value="clave">{{ nombre }}</option>
          </select>
        </label>
        <label>Minuto<input v-model.number="form.minute" type="number" min="0" max="130" required /></label>
        <label class="full">Jugador
          <select v-model.number="form.player_id" required @change="form.assist_player_id = ''">
            <option value="" disabled>Seleccionar jugador</option>
            <option v-for="j in candidatos" :key="j.id" :value="j.id">{{ j.full_name }} · {{ j.short_name }} #{{ j.jersey_number }}</option>
          </select>
        </label>
        <label v-if="form.type === 'goal'" class="full">Asistencia (opcional)
          <select v-model.number="form.assist_player_id">
            <option value="">Sin asistencia</option>
            <option v-for="j in asistentes" :key="j.id" :value="j.id">{{ j.full_name }}</option>
          </select>
        </label>
      </div>
      <div class="form-actions">
        <button v-if="editando" type="button" class="btn btn-secondary" :disabled="ocupado" @click="limpiar">Cancelar edición</button>
        <button class="btn btn-primary" :disabled="ocupado">{{ ocupado ? 'Guardando…' : editando ? 'Guardar corrección' : 'Añadir evento' }}</button>
      </div>
    </form>

    <div class="table-scroll">
      <table class="data-table">
        <thead><tr><th>Min.</th><th>Evento / jugador</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="e in eventos" :key="e.id">
            <td>{{ e.minute }}′</td>
            <td>
              <b>{{ EVENT_LABEL[e.type] }} · {{ e.player_name }}</b>
              <p v-if="e.assist_name" class="muted small">Asistencia: {{ e.assist_name }}</p>
            </td>
            <td>
              <div v-if="confirmando === e.id" class="admin-tools">
                <button class="text-link" :disabled="ocupado" @click="eliminar(e.id)">Confirmar eliminación</button>
                <button class="text-link" :disabled="ocupado" @click="confirmando = null">Cancelar</button>
              </div>
              <div v-else class="admin-tools">
                <button class="icon-btn" :disabled="ocupado" :aria-label="'Editar evento de ' + e.player_name" @click="editar(e)"><PencilIcon :size="14" /></button>
                <button class="icon-btn" :disabled="ocupado" :aria-label="'Eliminar evento de ' + e.player_name" @click="confirmando = e.id"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!eventos.length"><td colspan="3" class="empty">Todavía no hay eventos.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
