<script setup>
import { computed } from 'vue'
import { PencilIcon } from 'lucide-vue-next'
import { usePanel } from '../../composables/usePanel'
import { useAuth } from '../../composables/useAuth'
import { useAdminData } from '../../composables/useAdminData'
import { useCrudModal } from '../../composables/useCrudModal'
import { imgUrl } from '../../services/api'
import AdminHeading from '../../components/admin/AdminHeading.vue'
import ModalDialog from '../../components/admin/ModalDialog.vue'
import TeamForm from '../../components/admin/TeamForm.vue'
import TeamBadge from '../../components/pulse/TeamBadge.vue'

const { state } = usePanel()
const { sesion } = useAuth()
const admin = useAdminData()
const m = useCrudModal()

const equipo = computed(() => state.teams.find((t) => +t.id === +sesion.equipo_id))

const datos = computed(() => [
  ['Representante', equipo.value.representante],
  ['Teléfono', equipo.value.telefono],
  ['DPI', equipo.value.dpi],
  ['Sub‑representante', equipo.value.sub_representante_nombre],
  ['DPI sub‑representante', equipo.value.sub_representante_dpi],
  ['Teléfono sub‑representante', equipo.value.sub_representante_telefono],
  ['Ciudad', equipo.value.city],
  ['Estadio', equipo.value.stadium],
  ['Entrenador', equipo.value.coach],
  ['Año de fundación', equipo.value.founded_year],
  ['Usuario de acceso', equipo.value.usuario],
])
</script>

<template>
  <div v-if="equipo" class="page-enter">
    <AdminHeading title="Mi equipo">
      <template #actions><button class="btn btn-primary" @click="m.abrir('form', equipo)"><PencilIcon :size="16" />Editar equipo</button></template>
    </AdminHeading>

    <section class="profile-header" :style="{ '--team-color': equipo.primary_color }">
      <TeamBadge :team="equipo" :size="110" />
      <div>
        <p class="eyebrow">{{ equipo.players_count }} jugadores activos</p>
        <h1>{{ equipo.name }}</h1>
      </div>
    </section>

    <div class="panel">
      <dl class="bio-grid">
        <div v-for="[nombre, valor] in datos" :key="nombre"><dt>{{ nombre }}</dt><dd>{{ valor || '—' }}</dd></div>
      </dl>
      <div v-if="equipo.foto_representante_url || equipo.sub_representante_foto_url" class="upload-field">
        <img v-if="equipo.foto_representante_url" :src="imgUrl(equipo.foto_representante_url)" alt="Foto del representante" class="upload-preview" />
        <img v-if="equipo.sub_representante_foto_url" :src="imgUrl(equipo.sub_representante_foto_url)" alt="Foto del sub‑representante" class="upload-preview" />
      </div>
    </div>

    <ModalDialog v-if="m.modo.value === 'form'" title="Editar mi equipo" :busy="m.ocupado.value" @close="m.cerrar">
      <TeamForm
        :team="m.item.value"
        restringido
        :busy="m.ocupado.value"
        :errors="m.campos.value"
        @cancel="m.cerrar"
        @save="(d, f) => m.ejecutar(() => admin.guardarEquipo(d, f), 'Cambios guardados correctamente.')"
      />
    </ModalDialog>
  </div>
  <p v-else class="empty">No encontramos tu equipo.</p>
</template>
