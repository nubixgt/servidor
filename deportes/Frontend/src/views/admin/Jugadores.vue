<script setup>
import { computed, ref } from 'vue'
import { PencilIcon, Trash2Icon, UserMinusIcon, UserCheckIcon } from 'lucide-vue-next'
import { usePanel } from '../../composables/usePanel'
import { useAuth } from '../../composables/useAuth'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import { imgUrl } from '../../services/api'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import ConfirmDelete from '../../components/admin/ConfirmDelete.vue'
import PlayerForm from '../../components/admin/PlayerForm.vue'

// Lo usan el admin (todos los equipos) y el encargado (el servidor ya devuelve solo su equipo).
const { state } = usePanel()
const { sesion } = useAuth()
const admin = useAdminData()
const m = useCrudModal()
const esAdmin = computed(() => sesion.rol === 'admin')
const busqueda = ref('')
const equipo = ref('')
const estado = ref('activo')
const razon = ref('')

const POS = { POR: 'Portero', DEF: 'Defensa', MED: 'Mediocampo', DEL: 'Delantero' }

const filas = computed(() =>
  state.players.filter(
    (j) =>
      (!estado.value || j.status === estado.value) &&
      (!equipo.value || +j.team_id === +equipo.value) &&
      j.full_name.toLowerCase().includes(busqueda.value.toLowerCase())
  )
)

function abrirBaja(j) {
  razon.value = ''
  m.abrir('baja', j)
}
</script>

<template>
  <div class="page-enter">
    <AdminHeading :title="esAdmin ? 'Jugadores' : 'Mis jugadores'" add-label="Añadir jugador" @add="m.abrir('form')" />
    <p v-if="m.aviso.value" class="notice" role="status">{{ m.aviso.value }}</p>

    <div class="filter-bar">
      <label class="search-field">Buscar en jugadores<input v-model="busqueda" type="search" placeholder="Escribe un nombre…" /></label>
      <label v-if="esAdmin">Equipo
        <select v-model="equipo"><option value="">Todos</option><option v-for="t in state.teams" :key="t.id" :value="t.id">{{ t.name }}</option></select>
      </label>
      <label>Estado
        <select v-model="estado"><option value="activo">Activos</option><option value="inactivo">Dados de baja</option><option value="">Todos</option></select>
      </label>
      <span>{{ filas.length }} registros</span>
    </div>

    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>Jugador</th><th v-if="esAdmin" class="hide-mobile">Equipo</th><th>Posición</th><th>Dorsal</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="j in filas" :key="j.id">
            <td>
              <span class="table-person">
                <img v-if="j.photo_url" :src="imgUrl(j.photo_url)" alt="" loading="lazy" />
                <span>{{ j.full_name }}<small>{{ j.status === 'inactivo' ? 'Baja: ' + (j.razon_baja || '—') : j.telefono }}</small></span>
              </span>
            </td>
            <td v-if="esAdmin" class="hide-mobile muted">{{ j.short_name }}</td>
            <td class="muted">{{ POS[j.position] || '—' }}</td>
            <td>{{ j.jersey_number ? '#' + j.jersey_number : '—' }}</td>
            <td>
              <div class="admin-tools">
                <button class="icon-btn" :aria-label="'Editar ' + j.full_name" title="Editar" @click="m.abrir('form', j)"><PencilIcon :size="14" /></button>
                <button v-if="j.status === 'activo'" class="icon-btn" :aria-label="'Dar de baja a ' + j.full_name" title="Dar de baja" @click="abrirBaja(j)"><UserMinusIcon :size="14" /></button>
                <button v-else class="icon-btn" :aria-label="'Reactivar a ' + j.full_name" title="Reactivar" @click="m.ejecutar(() => admin.altaJugador(j.id), 'Jugador reactivado.')"><UserCheckIcon :size="14" /></button>
                <button v-if="esAdmin" class="icon-btn" :aria-label="'Eliminar ' + j.full_name" title="Eliminar" @click="m.abrir('delete', j)"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!filas.length"><td :colspan="esAdmin ? 5 : 4" class="empty">No hay registros que mostrar.</td></tr>
        </tbody>
      </table>
    </div>

    <ModalDialog v-if="m.modo.value === 'form'" :title="m.item.value ? 'Editar jugador' : 'Añadir jugador'" :busy="m.ocupado.value" @close="m.cerrar">
      <p v-if="m.error.value" class="error-text" role="alert">{{ m.error.value }}</p>
      <PlayerForm
        :player="m.item.value"
        :teams="state.teams"
        :elegir-equipo="esAdmin"
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d, f) => m.ejecutar(() => admin.guardarJugador(d, f), 'Cambios guardados correctamente.')"
      />
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'baja'" title="Dar de baja al jugador" :busy="m.ocupado.value" @close="m.cerrar">
      <p v-if="m.error.value" class="error-text" role="alert">{{ m.error.value }}</p>
      <form @submit.prevent="m.ejecutar(() => admin.bajaJugador(m.item.value.id, razon), 'Jugador dado de baja.')">
        <p class="confirm-copy">{{ m.item.value.full_name }} dejará de aparecer entre los jugadores activos. Puedes reactivarlo después.</p>
        <label>Motivo de la baja<input v-model.trim="razon" required minlength="3" maxlength="500" placeholder="Ej.: lesión, cambio de ciudad…" /></label>
        <div class="form-actions">
          <button type="button" class="btn btn-secondary" :disabled="m.ocupado.value" @click="m.cerrar">Cancelar</button>
          <button class="btn btn-danger" :disabled="m.ocupado.value">{{ m.ocupado.value ? 'Procesando…' : 'Dar de baja' }}</button>
        </div>
      </form>
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'delete'" title="Confirmar eliminación" :busy="m.ocupado.value" @close="m.cerrar">
      <ConfirmDelete
        :texto="`¿Eliminar ${m.item.value.full_name}? Esta acción no se puede deshacer.`"
        :busy="m.ocupado.value"
        :error="m.error.value"
        @cancelar="m.cerrar"
        @confirmar="m.ejecutar(() => admin.eliminarJugador(m.item.value.id), 'Jugador eliminado.')"
      />
    </ModalDialog>
  </div>
</template>
