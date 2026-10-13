<script setup>
import { computed } from 'vue'
import { RotateCcwIcon } from 'lucide-vue-next'
import { usePulse } from '../../composables/usePulse'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import ConfirmDelete from '../../components/admin/ConfirmDelete.vue'
import ScorersTable from '../../components/pulse/ScorersTable.vue'

const { state } = usePulse()
const admin = useAdminData()
const m = useCrudModal()

const finalizados = computed(() => state.matches.filter((p) => p.status === 'finished').length)
const resumen = computed(() => [
  ['Equipos', state.teams.length],
  ['Jugadores', state.players.length],
  ['Partidos', state.matches.length],
  ['Finalizados', finalizados.value],
])
</script>

<template>
  <div class="page-enter">
    <AdminHeading title="Resumen">
      <template #actions>
        <button class="btn btn-secondary" @click="m.abrir('reset')"><RotateCcwIcon :size="15" />Restablecer datos de ejemplo</button>
      </template>
    </AdminHeading>

    <p v-if="m.aviso.value" class="notice" role="status">{{ m.aviso.value }}</p>

    <div class="stat-grid">
      <div v-for="[nombre, valor] in resumen" :key="nombre" class="stat-box"><strong>{{ valor }}</strong><span>{{ nombre }}</span></div>
    </div>

    <p class="notice info">Registra los goles y tarjetas desde Partidos → Eventos. El marcador es automático; al finalizar el partido se actualizan los rankings.</p>

    <div class="section-heading">
      <h2>Goleadores de la liga</h2>
      <RouterLink to="/estadisticas" class="text-link">Ver vista pública ↗</RouterLink>
    </div>
    <div class="panel"><ScorersTable :players="state.players" :limit="5" /></div>

    <ModalDialog v-if="m.modo.value === 'reset'" title="Restablecer datos" :busy="m.ocupado.value" @close="m.cerrar">
      <ConfirmDelete
        texto="Se reemplazarán los cambios guardados en este navegador por los datos de ejemplo originales."
        boton="Restablecer"
        :busy="m.ocupado.value"
        :error="m.error.value"
        @cancelar="m.cerrar"
        @confirmar="m.ejecutar(admin.restablecer, 'Datos restablecidos.')"
      />
    </ModalDialog>
  </div>
</template>
