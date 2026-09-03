<template>
  <div class="flex flex-col w-full">
    <AdminPageHeader
      eyebrow="Mesa de control"
      title="Gestión de partidos, fixture y marcadores"
      subtitle="Programa encuentros, controla el marcador en vivo y cierra las actas oficiales."
    >
      <template #actions>
        <button type="button" class="btn-primary inline-flex items-center gap-space-xs" @click="openCreate">
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          <span>Crear partido</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Filtros -->
    <div class="bg-surface-container-low rounded-xl p-space-sm md:p-space-md shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-space-md mb-space-lg">
      <div class="flex items-center gap-space-xs bg-surface-container-lowest p-1 rounded-full shadow-sm w-fit">
        <button
          v-for="f in estadoFilters"
          :key="f.value"
          type="button"
          class="px-space-md py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors"
          :class="estadoFilter === f.value ? 'bg-primary-container text-on-primary-fixed' : 'text-secondary hover:text-on-surface'"
          @click="estadoFilter = f.value"
        >{{ f.label }} <span v-if="counts[f.value]" class="opacity-70">({{ counts[f.value] }})</span></button>
      </div>
      <div class="flex items-center gap-space-sm">
        <input
          v-model.number="jornadaFilter"
          type="number"
          min="1"
          placeholder="Jornada"
          class="w-28 px-space-md py-space-xs bg-surface-container-lowest rounded-full font-body-sm text-body-sm text-on-surface shadow-sm outline-none focus:ring-2 focus:ring-primary-container"
        />
        <button
          v-if="jornadaFilter"
          type="button"
          class="font-label-meta text-label-meta uppercase text-secondary hover:text-on-surface hover:underline"
          @click="jornadaFilter = null"
        >Limpiar</button>
      </div>
    </div>

    <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-sm">Cargando partidos…</div>
    <div v-else-if="!filtered.length" class="py-space-3xl text-center text-secondary font-body-sm">
      No hay partidos que coincidan.
    </div>

    <section v-else class="flex flex-col gap-space-lg">
      <article
        v-for="m in filtered"
        :key="m.id"
        class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_10px_30px_-4px_rgba(15,23,42,0.05)]"
        :class="m.estado === 'En Vivo' ? 'border-l-4 border-primary' : ''"
      >
        <div class="flex flex-wrap items-center justify-between gap-space-sm mb-space-md">
          <div class="flex items-center gap-space-xs">
            <StatusPill :label="estadoLabel(m)" :tone="estadoTone(m.estado)" dot />
            <span class="font-label-meta text-label-meta text-secondary uppercase">
              Jornada {{ m.jornada || '—' }} · {{ m.fase }}
              <template v-if="m.fecha"> · {{ m.fecha }}</template>
              <template v-if="m.hora"> {{ m.hora }}</template>
            </span>
          </div>
          <span v-if="m.sede" class="flex items-center gap-space-2xs font-label-meta text-label-meta text-secondary">
            <span class="material-symbols-outlined text-[16px] text-primary">pin_drop</span>{{ m.sede }}
          </span>
        </div>

        <!-- Marcador -->
        <div class="flex items-center justify-between gap-space-md px-space-2xs sm:px-space-lg">
          <div class="flex flex-col items-center gap-space-2xs flex-1 text-center min-w-0">
            <div class="w-14 h-14 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-1.5 ring-2 ring-primary-container/30">
              <img v-if="m.local.logo_ruta" :src="assetUrl(m.local.logo_ruta)" alt="" class="w-full h-full object-contain" />
              <span v-else class="material-symbols-outlined text-primary-container text-[22px]">shield</span>
            </div>
            <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate w-full">{{ m.local.nombre }}</h3>
            <span class="font-label-meta text-label-meta text-secondary uppercase">Local</span>
          </div>

          <div class="flex flex-col items-center gap-space-xs shrink-0">
            <div class="flex items-center gap-space-sm">
              <span class="font-stat-display text-stat-display text-on-surface leading-none">{{ m.marcador_local }}</span>
              <span class="font-headline-xl text-headline-xl text-outline-variant">:</span>
              <span class="font-stat-display text-stat-display text-primary leading-none">{{ m.marcador_visitante }}</span>
            </div>
            <div v-if="m.estado === 'En Vivo'" class="flex items-center gap-space-md">
              <div class="flex items-center gap-1">
                <button v-for="p in [1, 2, 3]" :key="'l' + p" type="button" class="score-btn" :disabled="busyId === m.id" @click="bump(m, 'local', p)">+{{ p }}</button>
              </div>
              <div class="flex items-center gap-1">
                <button v-for="p in [1, 2, 3]" :key="'v' + p" type="button" class="score-btn" :disabled="busyId === m.id" @click="bump(m, 'visitante', p)">+{{ p }}</button>
              </div>
            </div>
          </div>

          <div class="flex flex-col items-center gap-space-2xs flex-1 text-center min-w-0">
            <div class="w-14 h-14 rounded-full bg-inverse-surface flex items-center justify-center overflow-hidden p-1.5 ring-2 ring-secondary-container/40">
              <img v-if="m.visitante.logo_ruta" :src="assetUrl(m.visitante.logo_ruta)" alt="" class="w-full h-full object-contain" />
              <span v-else class="material-symbols-outlined text-primary-container text-[22px]">shield</span>
            </div>
            <h3 class="font-headline-md text-headline-md uppercase text-on-surface truncate w-full">{{ m.visitante.nombre }}</h3>
            <span class="font-label-meta text-label-meta text-secondary uppercase">Visitante</span>
          </div>
        </div>

        <!-- Toolbar -->
        <div class="mt-space-lg pt-space-md border-t border-surface-container-high flex flex-wrap items-center gap-space-xs">
          <button v-if="m.estado === 'Programado' || m.estado === 'Pospuesto'" type="button" class="tool-btn tool-primary" :disabled="busyId === m.id" @click="setEstado(m, 'En Vivo')">
            <span class="material-symbols-outlined text-[16px]">play_circle</span> Iniciar en vivo
          </button>
          <button v-if="m.estado === 'En Vivo'" type="button" class="tool-btn tool-danger" :disabled="busyId === m.id" @click="cerrarActa(m)">
            <span class="material-symbols-outlined text-[16px]">verified</span> Cerrar acta y finalizar
          </button>
          <button type="button" class="tool-btn" @click="openEdit(m)">
            <span class="material-symbols-outlined text-[16px]">edit</span>
            {{ m.estado === 'Finalizado' ? 'Rectificar' : 'Editar detalles' }}
          </button>
          <button v-if="m.estado === 'Programado'" type="button" class="tool-btn" :disabled="busyId === m.id" @click="setEstado(m, 'Pospuesto')">
            <span class="material-symbols-outlined text-[16px]">event_busy</span> Posponer
          </button>
          <button type="button" class="tool-btn tool-danger-ghost ml-auto" @click="askDelete(m)">
            <span class="material-symbols-outlined text-[16px]">delete</span> Eliminar
          </button>
        </div>
      </article>
    </section>

    <!-- Modal -->
    <AdminModal
      v-model:open="modalOpen"
      :title="editingId ? 'Editar partido' : 'Programar partido'"
      eyebrow="Cédula del encuentro"
      icon="calendar_add_on"
      size="xl"
    >
      <form id="match-form" class="flex flex-col gap-space-md" @submit.prevent="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Equipo local *</label>
            <div class="relative">
              <select v-model.number="form.equipo_local_id" class="form-select">
                <option :value="0" disabled>Elegir equipo</option>
                <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Equipo visitante *</label>
            <div class="relative">
              <select v-model.number="form.equipo_visitante_id" class="form-select">
                <option :value="0" disabled>Elegir equipo</option>
                <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Jornada</label>
            <input v-model.number="form.jornada" type="number" min="1" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Fase</label>
            <input v-model.trim="form.fase" type="text" placeholder="Regular" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Fecha</label>
            <input v-model="form.fecha" type="date" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Hora</label>
            <input v-model="form.hora" type="time" class="form-input" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Sede / cancha</label>
            <input v-model.trim="form.sede" type="text" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Estado</label>
            <div class="relative">
              <select v-model="form.estado" class="form-select">
                <option value="Programado">Programado</option>
                <option value="En Vivo">En Vivo</option>
                <option value="Finalizado">Finalizado</option>
                <option value="Pospuesto">Pospuesto</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
          <label class="flex items-center gap-space-xs pt-space-lg">
            <input v-model="form.acta_cerrada" type="checkbox" class="accent-primary-container w-4 h-4" />
            <span class="form-label">Acta cerrada</span>
          </label>
        </div>

        <div class="grid grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Marcador local</label>
            <input v-model.number="form.marcador_local" type="number" min="0" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Marcador visitante</label>
            <input v-model.number="form.marcador_visitante" type="number" min="0" class="form-input" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Árbitro principal</label>
            <input v-model.trim="form.arbitro_principal" type="text" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Juez de mesa</label>
            <input v-model.trim="form.juez_mesa" type="text" class="form-input" />
          </div>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="match-form" :disabled="saving" class="btn-primary">
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';
import StatusPill from '../../components/admin/StatusPill.vue';
import AdminModal from '../../components/admin/AdminModal.vue';
import { useToast } from '../../composables/useToast';
import { useConfirm } from '../../composables/useConfirm';
import { useAlert } from '../../composables/useAlert';
import { assetUrl } from '../../services/assets';
import partidosService from '../../services/partidosService';
import equiposService from '../../services/equiposService';

const toast = useToast();
const { confirm } = useConfirm();
const alert = useAlert();

const estadoFilters = [
  { value: 'Todos', label: 'Todos' },
  { value: 'En Vivo', label: 'En vivo' },
  { value: 'Programado', label: 'Próximos' },
  { value: 'Finalizado', label: 'Finalizados' }
];

const partidos = ref([]);
const equipos = ref([]);
const loading = ref(true);
const busyId = ref(null);

const estadoFilter = ref('Todos');
const jornadaFilter = ref(null);

const modalOpen = ref(false);
const saving = ref(false);
const editingId = ref(null);

const counts = computed(() => {
  const c = { Todos: partidos.value.length };
  for (const p of partidos.value) c[p.estado] = (c[p.estado] || 0) + 1;
  return c;
});

const filtered = computed(() =>
  partidos.value.filter((p) => {
    const mEstado = estadoFilter.value === 'Todos' || p.estado === estadoFilter.value;
    const mJornada = !jornadaFilter.value || p.jornada === jornadaFilter.value;
    return mEstado && mJornada;
  })
);

const emptyForm = () => ({
  equipo_local_id: 0,
  equipo_visitante_id: 0,
  jornada: null,
  fase: 'Regular',
  fecha: '',
  hora: '',
  sede: '',
  estado: 'Programado',
  marcador_local: 0,
  marcador_visitante: 0,
  arbitro_principal: '',
  juez_mesa: '',
  acta_cerrada: false
});
const form = reactive(emptyForm());

function estadoLabel(m) {
  if (m.estado === 'Finalizado') return m.acta_cerrada ? 'Finalizado · acta cerrada' : 'Finalizado';
  return m.estado;
}
function estadoTone(e) {
  return { 'En Vivo': 'primary', Programado: 'info', Finalizado: 'neutral', Pospuesto: 'danger' }[e] || 'neutral';
}

function toPayload(m, overrides = {}) {
  return {
    equipo_local_id: m.local.id,
    equipo_visitante_id: m.visitante.id,
    jornada: m.jornada,
    fase: m.fase,
    fecha: m.fecha,
    hora: m.hora,
    sede: m.sede,
    estado: m.estado,
    marcador_local: m.marcador_local,
    marcador_visitante: m.marcador_visitante,
    arbitro_principal: m.arbitro_principal,
    juez_mesa: m.juez_mesa,
    acta_cerrada: m.acta_cerrada,
    ...overrides
  };
}

async function patch(m, overrides, okMsg) {
  busyId.value = m.id;
  try {
    const updated = await partidosService.update(m.id, toPayload(m, overrides));
    const i = partidos.value.findIndex((p) => p.id === m.id);
    if (i !== -1) partidos.value[i] = updated;
    if (okMsg) toast.success(okMsg);
  } catch (err) {
    alert.error('No se pudo actualizar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    busyId.value = null;
  }
}

function bump(m, lado, pts) {
  const key = lado === 'local' ? 'marcador_local' : 'marcador_visitante';
  patch(m, { [key]: m[key] + pts });
}
function setEstado(m, estado) {
  patch(m, { estado }, `Partido marcado como "${estado}"`);
}
function cerrarActa(m) {
  patch(m, { estado: 'Finalizado', acta_cerrada: true }, 'Acta cerrada');
}

function openCreate() {
  editingId.value = null;
  Object.assign(form, emptyForm());
  modalOpen.value = true;
}
function openEdit(m) {
  editingId.value = m.id;
  Object.assign(form, {
    equipo_local_id: m.local.id,
    equipo_visitante_id: m.visitante.id,
    jornada: m.jornada,
    fase: m.fase,
    fecha: m.fecha ?? '',
    hora: m.hora ?? '',
    sede: m.sede ?? '',
    estado: m.estado,
    marcador_local: m.marcador_local,
    marcador_visitante: m.marcador_visitante,
    arbitro_principal: m.arbitro_principal ?? '',
    juez_mesa: m.juez_mesa ?? '',
    acta_cerrada: m.acta_cerrada
  });
  modalOpen.value = true;
}

async function save() {
  if (!form.equipo_local_id || !form.equipo_visitante_id) {
    alert.error('Faltan datos', 'Debes elegir equipo local y visitante.');
    return;
  }
  if (form.equipo_local_id === form.equipo_visitante_id) {
    alert.error('Datos inválidos', 'El equipo local y el visitante no pueden ser el mismo.');
    return;
  }
  saving.value = true;
  try {
    if (editingId.value) {
      await partidosService.update(editingId.value, { ...form });
    } else {
      await partidosService.create({ ...form });
    }
    modalOpen.value = false;
    await load();
    toast.success(editingId.value ? 'Partido actualizado' : 'Partido programado');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    saving.value = false;
  }
}

async function askDelete(m) {
  const ok = await confirm({
    title: '¿Eliminar este partido?',
    text: `${m.local.nombre} vs ${m.visitante.nombre}. Esta acción no se puede deshacer.`,
    confirmText: 'Sí, eliminar'
  });
  if (!ok) return;
  try {
    await partidosService.remove(m.id);
    await load();
    toast.success('Partido eliminado');
  } catch (err) {
    alert.error('No se pudo eliminar', err?.response?.data?.message || 'Intenta de nuevo.');
  }
}

async function load() {
  loading.value = true;
  try {
    partidos.value = await partidosService.list();
  } catch {
    toast.error('No se pudieron cargar los partidos');
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  try {
    equipos.value = await equiposService.list();
  } catch { /* noop */ }
  await load();
});
</script>

<style scoped>
.score-btn {
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  background-color: #edeeef;
  color: #191c1d;
  font-size: 12px;
  font-weight: 700;
}
.score-btn:hover { background-color: #ccff00; }
.score-btn:disabled { opacity: 0.5; }
.tool-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.35rem 0.75rem;
  border-radius: 9999px;
  background-color: #e7e8e9;
  color: #191c1d;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
}
.tool-btn:disabled { opacity: 0.5; }
.tool-primary { background-color: #ccff00; color: #161e00; }
.tool-danger { background-color: #ba1a1a; color: #ffffff; }
.tool-danger-ghost { background-color: transparent; color: #ba1a1a; }
</style>
