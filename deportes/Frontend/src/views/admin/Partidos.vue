<script setup>
import { computed, ref } from 'vue'
import { ListVideoIcon, PencilIcon, Trash2Icon } from 'lucide-vue-next'
import { usePanel } from '../../composables/usePanel'
import { useAuth } from '../../composables/useAuth'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import { STATUS_LABEL, formatFecha } from '../../utils/pulseFormat'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import ConfirmDelete from '../../components/admin/ConfirmDelete.vue'
import MatchForm from '../../components/admin/MatchForm.vue'
import EventEditor from '../../components/admin/EventEditor.vue'

const { state } = usePanel()
const { sesion } = useAuth()
const esAdmin = computed(() => sesion.rol === 'admin')
const admin = useAdminData()
const m = useCrudModal()
const busqueda = ref('')

const filas = computed(() =>
  state.matches.filter((p) => `${p.home_name} ${p.away_name}`.toLowerCase().includes(busqueda.value.toLowerCase()))
)
const marcador = (p) => (p.status === 'scheduled' ? '—' : `${p.home_score} : ${p.away_score}`)
</script>

<template>
  <div class="page-enter">
    <AdminHeading :title="esAdmin ? 'Partidos' : 'Mis partidos'" add-label="Añadir partido" @add="m.abrir('form')" />
    <p v-if="m.aviso.value" class="notice" role="status">{{ m.aviso.value }}</p>

    <div class="filter-bar">
      <label class="search-field">Buscar en partidos<input v-model="busqueda" type="search" placeholder="Escribe un equipo…" /></label>
      <span>{{ filas.length }} registros</span>
    </div>

    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>Encuentro</th><th>Fecha / estado</th><th>Marcador</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="p in filas" :key="p.id">
            <td>
              <b>{{ p.home_short }} vs {{ p.away_short }}</b><small class="block muted mt-1">Jornada {{ p.round_number }}</small>
            </td>
            <td class="muted">{{ formatFecha(p.kickoff_at, true) }}<small class="block mt-1">{{ STATUS_LABEL[p.status] }}</small></td>
            <td>{{ marcador(p) }}</td>
            <td>
              <div class="admin-tools">
                <button class="icon-btn" title="Eventos del partido" aria-label="Gestionar eventos del partido" @click="m.abrir('events', p)"><ListVideoIcon :size="15" /></button>
                <button class="icon-btn" :aria-label="`Editar ${p.home_short} vs ${p.away_short}`" title="Editar" @click="m.abrir('form', p)"><PencilIcon :size="14" /></button>
                <button v-if="esAdmin" class="icon-btn" :aria-label="`Eliminar ${p.home_short} vs ${p.away_short}`" title="Eliminar" @click="m.abrir('delete', p)"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!filas.length"><td colspan="4" class="empty">No hay registros que mostrar.</td></tr>
        </tbody>
      </table>
    </div>

    <ModalDialog v-if="m.modo.value === 'form'" :title="m.item.value ? 'Editar partido' : 'Añadir partido'" :busy="m.ocupado.value" @close="m.cerrar">
      <p v-if="m.error.value" class="error-text" role="alert">{{ m.error.value }}</p>
      <MatchForm
        :match="m.item.value"
        :teams="state.teams"
        :equipo-propio="esAdmin ? undefined : sesion.equipo_id"
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d) => m.ejecutar(() => admin.guardarPartido(d), 'Cambios guardados correctamente.')"
      />
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'events'" title="Eventos del partido" :busy="m.ocupado.value" @close="m.cerrar">
      <EventEditor :match="m.item.value" @busy="m.ocupado.value = $event" />
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'delete'" title="Confirmar eliminación" :busy="m.ocupado.value" @close="m.cerrar">
      <ConfirmDelete
        :texto="`¿Eliminar ${m.item.value.home_short} vs ${m.item.value.away_short}? Esta acción no se puede deshacer.`"
        extra="Se eliminarán también sus eventos y se recalcularán las estadísticas."
        :busy="m.ocupado.value"
        :error="m.error.value"
        @cancelar="m.cerrar"
        @confirmar="m.ejecutar(() => admin.eliminarPartido(m.item.value.id), 'Partido eliminado.')"
      />
    </ModalDialog>
  </div>
</template>
