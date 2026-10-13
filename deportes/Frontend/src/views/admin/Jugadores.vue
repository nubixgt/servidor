<script setup>
import { computed, ref } from 'vue'
import { PencilIcon, Trash2Icon } from 'lucide-vue-next'
import { usePulse } from '../../composables/usePulse'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import ConfirmDelete from '../../components/admin/ConfirmDelete.vue'
import PlayerForm from '../../components/admin/PlayerForm.vue'

const { state } = usePulse()
const admin = useAdminData()
const m = useCrudModal()
const busqueda = ref('')

const filas = computed(() => state.players.filter((j) => j.full_name.toLowerCase().includes(busqueda.value.toLowerCase())))
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Jugadores" add-label="Añadir jugador" @add="m.abrir('form')" />
    <p v-if="m.aviso.value" class="notice" role="status">{{ m.aviso.value }}</p>

    <div class="filter-bar">
      <label class="search-field">Buscar en jugadores<input v-model="busqueda" type="search" placeholder="Escribe un nombre…" /></label>
      <span>{{ filas.length }} registros</span>
    </div>

    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>Nombre</th><th>Equipo / posición</th><th>Dorsal</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="j in filas" :key="j.id">
            <td><RouterLink :to="`/jugadores/${j.id}`">{{ j.full_name }}</RouterLink></td>
            <td class="muted">{{ j.short_name }} · {{ j.position }}</td>
            <td>#{{ j.jersey_number }}</td>
            <td>
              <div class="admin-tools">
                <button class="icon-btn" :aria-label="'Editar ' + j.full_name" title="Editar" @click="m.abrir('form', j)"><PencilIcon :size="14" /></button>
                <button class="icon-btn" :aria-label="'Eliminar ' + j.full_name" title="Eliminar" @click="m.abrir('delete', j)"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!filas.length"><td colspan="4" class="empty">No hay registros que mostrar.</td></tr>
        </tbody>
      </table>
    </div>

    <ModalDialog v-if="m.modo.value === 'form'" :title="m.item.value ? 'Editar jugador' : 'Añadir jugador'" :busy="m.ocupado.value" @close="m.cerrar">
      <p v-if="m.error.value" class="error-text" role="alert">{{ m.error.value }}</p>
      <PlayerForm
        :player="m.item.value"
        :teams="state.teams"
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d) => m.ejecutar(() => admin.guardarJugador(d), 'Cambios guardados correctamente.')"
      />
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
