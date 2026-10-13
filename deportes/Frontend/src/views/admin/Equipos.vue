<script setup>
import { computed, ref } from 'vue'
import { PencilIcon, Trash2Icon } from 'lucide-vue-next'
import { usePanel } from '../../composables/usePanel'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import ConfirmDelete from '../../components/admin/ConfirmDelete.vue'
import TeamForm from '../../components/admin/TeamForm.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'

const { state } = usePanel()
const admin = useAdminData()
const m = useCrudModal()
const busqueda = ref('')

const filas = computed(() => {
  const q = busqueda.value.toLowerCase()
  return state.teams.filter((t) => `${t.name} ${t.representante} ${t.city || ''}`.toLowerCase().includes(q))
})
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Equipos" add-label="Añadir equipo" @add="m.abrir('form')" />
    <p v-if="m.aviso.value" class="notice" role="status">{{ m.aviso.value }}</p>

    <div class="filter-bar">
      <label class="search-field">Buscar en equipos<input v-model="busqueda" type="search" placeholder="Nombre, representante o ciudad…" /></label>
      <span>{{ filas.length }} registros</span>
    </div>

    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>Equipo</th><th>Representante</th><th class="hide-mobile">Teléfono</th><th>Jugadores</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="t in filas" :key="t.id">
            <td><span class="table-person"><TeamBadge :team="t" :size="30" />{{ t.name }}</span></td>
            <td class="muted">{{ t.representante }}</td>
            <td class="hide-mobile muted">{{ t.telefono }}</td>
            <td>{{ t.players_count }}</td>
            <td>
              <div class="admin-tools">
                <button class="icon-btn" :aria-label="'Editar ' + t.name" title="Editar" @click="m.abrir('form', t)"><PencilIcon :size="14" /></button>
                <button class="icon-btn" :aria-label="'Eliminar ' + t.name" title="Eliminar" @click="m.abrir('delete', t)"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!filas.length"><td colspan="5" class="empty">No hay registros que mostrar.</td></tr>
        </tbody>
      </table>
    </div>

    <ModalDialog v-if="m.modo.value === 'form'" :title="m.item.value ? 'Editar equipo' : 'Añadir equipo'" :busy="m.ocupado.value" @close="m.cerrar">
      <p v-if="m.error.value" class="error-text" role="alert">{{ m.error.value }}</p>
      <TeamForm
        :team="m.item.value"
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d, f) => m.ejecutar(() => admin.guardarEquipo(d, f), 'Cambios guardados correctamente.')"
      />
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'delete'" title="Confirmar eliminación" :busy="m.ocupado.value" @close="m.cerrar">
      <ConfirmDelete
        :texto="`¿Eliminar ${m.item.value.name}? Se elimina también el acceso de su encargado. Esta acción no se puede deshacer.`"
        :busy="m.ocupado.value"
        :error="m.error.value"
        @cancelar="m.cerrar"
        @confirmar="m.ejecutar(() => admin.eliminarEquipo(m.item.value.id), 'Equipo eliminado.')"
      />
    </ModalDialog>
  </div>
</template>
