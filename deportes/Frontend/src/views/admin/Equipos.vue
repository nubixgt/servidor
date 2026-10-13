<script setup>
import { computed, ref } from 'vue'
import { PencilIcon, Trash2Icon } from 'lucide-vue-next'
import { usePanel } from '../../composables/usePanel'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import Paginacion from '../../components/admin/Paginacion.vue'
import { usePaginacion } from '../../composables/usePaginacion'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import TeamForm from '../../components/admin/TeamForm.vue'
import EquipoDetalle from '../../components/admin/EquipoDetalle.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'
import { useVisorImagen } from '../../composables/useVisorImagen'

const { state } = usePanel()
const admin = useAdminData()
const m = useCrudModal()
const { verFoto } = useVisorImagen()
const busqueda = ref('')

const eliminar = (t) =>
  m.eliminar({
    title: `¿Eliminar ${t.name}?`,
    text: 'Se elimina también el acceso de su encargado. Esta acción no se puede deshacer.',
    accion: () => admin.eliminarEquipo(t.id),
    mensaje: 'Equipo eliminado.',
  })

const filas = computed(() => {
  const q = busqueda.value.toLowerCase()
  return state.teams.filter((t) => `${t.name} ${t.representante} ${t.city || ''}`.toLowerCase().includes(q))
})

const pag = usePaginacion(filas, { por: 20, reiniciarCon: [busqueda] })
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Equipos" add-label="Añadir equipo" @add="m.abrir('form')" />

    <div class="filter-bar">
      <label class="search-field">Buscar en equipos<input v-model="busqueda" type="search" placeholder="Nombre, representante o ciudad…" /></label>
      <span>{{ filas.length }} registros</span>
    </div>

    <div class="panel table-scroll">
      <table class="data-table">
        <thead><tr><th>Equipo</th><th>Representante</th><th class="hide-mobile">Teléfono</th><th>Jugadores</th><th>Acciones</th></tr></thead>
        <tbody>
          <tr v-for="t in pag.visibles.value" :key="t.id" class="fila-click" tabindex="0" @click="m.abrir('detalle', t)" @keydown.enter="m.abrir('detalle', t)">
            <td>
              <span class="table-person">
                <span :class="{ zoomable: t.logo_url }" :title="t.logo_url ? 'Ver escudo' : undefined" @click.stop="verFoto(t.logo_url, 'Escudo · ' + t.name)"><TeamBadge :team="t" :size="30" /></span>
                {{ t.name }}
              </span>
            </td>
            <td class="muted">{{ t.representante }}</td>
            <td class="hide-mobile muted">{{ t.telefono }}</td>
            <td>{{ t.players_count }}</td>
            <td>
              <div class="admin-tools" @click.stop>
                <button class="icon-btn" :aria-label="'Editar ' + t.name" title="Editar" @click="m.abrir('form', t)"><PencilIcon :size="14" /></button>
                <button class="icon-btn" :aria-label="'Eliminar ' + t.name" title="Eliminar" @click="eliminar(t)"><Trash2Icon :size="14" /></button>
              </div>
            </td>
          </tr>
          <tr v-if="!filas.length"><td colspan="5" class="empty">No hay registros que mostrar.</td></tr>
        </tbody>
      </table>
    </div>
    <Paginacion v-model:pagina="pag.pagina.value" :total-paginas="pag.totalPaginas.value" :desde="pag.desde.value" :hasta="pag.hasta.value" :total="pag.total.value" />

    <ModalDialog v-if="m.modo.value === 'detalle'" title="Detalle del equipo" @close="m.cerrar">
      <EquipoDetalle :equipo="m.item.value" @editar="m.abrir('form', m.item.value)" />
    </ModalDialog>

    <ModalDialog v-if="m.modo.value === 'form'" :title="m.item.value ? 'Editar equipo' : 'Añadir equipo'" :busy="m.ocupado.value" @close="m.cerrar">
      <TeamForm
        :team="m.item.value"
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d, f) => m.ejecutar(() => admin.guardarEquipo(d, f), 'Cambios guardados correctamente.')"
      />
    </ModalDialog>

  </div>
</template>
