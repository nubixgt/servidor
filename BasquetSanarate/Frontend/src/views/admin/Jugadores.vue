<template>
  <div class="flex flex-col w-full">
    <AdminPageHeader
      eyebrow="Padrón federado"
      title="Padrón y gestión de jugadores"
      subtitle="Altas, fichas de inscripción, estado de habilitación y estadísticas base por atleta."
    >
      <template #actions>
        <button
          type="button"
          class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-high text-on-surface hover:bg-surface-variant transition-colors font-label-pill text-label-pill uppercase shadow-sm"
          @click="toast.info('Importar planilla: próximamente')"
        >
          <span class="material-symbols-outlined text-[18px] text-secondary">upload_file</span>
          <span>Importar Excel</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-space-xs px-space-lg py-space-xs rounded-full bg-primary-container text-on-primary-fixed hover:bg-primary-fixed transition-transform active:scale-95 font-label-pill text-label-pill uppercase font-bold tracking-wide shadow-[0_8px_24px_-4px_rgba(204,255,0,0.5)]"
          @click="openCreate"
        >
          <span class="material-symbols-outlined text-[20px]">person_add</span>
          <span>Agregar jugador</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Filtros -->
    <div class="bg-surface-container-low rounded-xl p-space-sm md:p-space-md shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-space-md mb-space-lg">
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-space-md top-1/2 -translate-y-1/2 text-secondary text-[20px]">search</span>
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por nombre, dorsal o CUI/DPI..."
          class="w-full pl-11 pr-space-md py-space-xs bg-surface-container-lowest rounded-full font-body-md text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"
        />
      </div>
      <div class="flex flex-wrap items-center gap-space-sm">
        <div class="relative">
          <select v-model="equipoFilter" class="appearance-none bg-surface-container-lowest pl-space-md pr-10 py-space-xs rounded-full font-body-sm text-body-sm text-on-surface outline-none focus:ring-2 focus:ring-primary-container shadow-sm cursor-pointer">
            <option value="">Todas las franquicias</option>
            <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
          </select>
          <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
        </div>
        <div class="flex items-center gap-space-2xs overflow-x-auto">
          <button
            v-for="p in ['Todas', ...posiciones]"
            :key="p"
            type="button"
            class="px-space-md py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors whitespace-nowrap shadow-sm"
            :class="posFilter === p ? 'bg-primary-container text-on-primary-fixed' : 'bg-surface-container-lowest text-secondary hover:text-on-surface'"
            @click="posFilter = p"
          >{{ p }}</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-sm">Cargando jugadores…</div>
    <div v-else-if="!jugadores.length" class="py-space-3xl text-center text-secondary font-body-sm">
      No hay jugadores que coincidan con los filtros.
    </div>

    <!-- Grid -->
    <section v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-space-lg">
      <article
        v-for="j in jugadores"
        :key="j.id"
        class="bg-surface-container-lowest rounded-xl p-space-md shadow-[0_10px_30px_-4px_rgba(15,23,42,0.05)] flex flex-col transition-all hover:-translate-y-1 hover:shadow-xl"
        :class="{ 'opacity-70': j.estado === 'Suspendido' }"
      >
        <div class="flex items-center justify-between mb-space-xs">
          <StatusPill
            :label="j.estado"
            :tone="j.estado === 'Habilitado' ? 'success' : 'danger'"
            dot
          />
          <div class="flex items-center gap-1">
            <IconButton icon="edit" label="Editar jugador" variant="primary" size="sm" @click="openEdit(j)" />
            <IconButton icon="person_off" label="Inactivar jugador" variant="danger" size="sm" @click="askDelete(j)" />
          </div>
        </div>

        <div class="relative w-full aspect-[3/4] rounded-lg bg-surface-container-low overflow-hidden flex items-end justify-center mb-space-sm">
          <span class="absolute top-2 left-2 font-stat-display text-stat-display text-surface-container-highest select-none leading-none">{{ j.dorsal ?? '—' }}</span>
          <img v-if="j.foto_ruta" :src="assetUrl(j.foto_ruta)" alt="" class="relative z-10 max-h-full object-contain" />
          <span v-else class="material-symbols-outlined text-[64px] text-outline-variant relative z-10 mb-space-md">person</span>
        </div>

        <span class="font-label-meta text-label-meta uppercase text-secondary">{{ j.equipo_nombre || 'Sin equipo' }} · {{ j.posicion || 'S/P' }}</span>
        <h3 class="font-headline-md text-headline-md uppercase text-on-surface tracking-tight leading-tight truncate">{{ j.nombre_completo }}</h3>

        <div class="grid grid-cols-4 gap-space-2xs bg-surface-container-low rounded-DEFAULT p-space-2xs mt-space-sm text-center">
          <div><div class="font-label-meta text-[9px] text-secondary uppercase">PPG</div><div class="font-headline-md text-headline-md text-on-surface">{{ j.stats.ppg }}</div></div>
          <div><div class="font-label-meta text-[9px] text-secondary uppercase">RPG</div><div class="font-headline-md text-headline-md text-on-surface">{{ j.stats.rpg }}</div></div>
          <div><div class="font-label-meta text-[9px] text-secondary uppercase">APG</div><div class="font-headline-md text-headline-md text-on-surface">{{ j.stats.apg }}</div></div>
          <div><div class="font-label-meta text-[9px] text-secondary uppercase">3P%</div><div class="font-headline-md text-headline-md text-primary">{{ j.stats.tres_pct }}</div></div>
        </div>

        <span class="font-label-meta text-label-meta text-secondary mt-space-xs">
          CUI: {{ j.dpi || '—' }}
          <template v-if="j.estatura_cm"> · {{ (j.estatura_cm / 100).toFixed(2) }} m</template>
        </span>
      </article>
    </section>

    <!-- Paginación -->
    <div v-if="total > perPage" class="flex items-center justify-between mt-space-lg font-label-meta text-label-meta text-secondary uppercase">
      <span>Mostrando {{ (page - 1) * perPage + 1 }}–{{ Math.min(page * perPage, total) }} de {{ total }}</span>
      <div class="flex items-center gap-space-xs">
        <button
          type="button"
          class="w-9 h-9 rounded-full bg-surface-container-lowest shadow-sm flex items-center justify-center disabled:opacity-40"
          :disabled="page === 1"
          @click="goPage(page - 1)"
        ><span class="material-symbols-outlined text-[18px]">chevron_left</span></button>
        <span class="font-label-pill text-label-pill text-on-surface">{{ page }} / {{ totalPages }}</span>
        <button
          type="button"
          class="w-9 h-9 rounded-full bg-surface-container-lowest shadow-sm flex items-center justify-center disabled:opacity-40"
          :disabled="page >= totalPages"
          @click="goPage(page + 1)"
        ><span class="material-symbols-outlined text-[18px]">chevron_right</span></button>
      </div>
    </div>

    <!-- Modal -->
    <AdminModal
      v-model:open="modalOpen"
      :title="editingId ? 'Editar ficha de jugador' : 'Registrar jugador'"
      eyebrow="Cédula de inscripción"
      icon="badge"
      size="xl"
    >
      <form id="player-form" class="flex flex-col gap-space-md" @submit.prevent="save">
        <div class="grid grid-cols-1 md:grid-cols-[auto_1fr] gap-space-md">
          <ImageUploader
            v-model="fotoFile"
            :current-url="assetUrl(editingFoto)"
            hint="JPG o PNG, máx 5MB."
            icon="add_a_photo"
          />
          <div class="flex flex-col gap-space-md">
            <div class="flex flex-col gap-1">
              <label class="form-label">Nombre y apellidos *</label>
              <input v-model.trim="form.nombre_completo" type="text" class="form-input" />
            </div>
            <div class="grid grid-cols-2 gap-space-md">
              <div class="flex flex-col gap-1">
                <label class="form-label"># Camisola</label>
                <input v-model="form.dorsal" type="number" min="0" max="999" class="form-input" />
              </div>
              <div class="flex flex-col gap-1">
                <label class="form-label">CUI / DPI</label>
                <input v-model.trim="form.dpi" type="text" class="form-input" />
              </div>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Fecha de nacimiento</label>
            <input v-model="form.fecha_nacimiento" type="date" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Nacionalidad</label>
            <input v-model.trim="form.nacionalidad" type="text" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Franquicia / club</label>
            <div class="relative">
              <select v-model="form.equipo_id" class="form-select">
                <option :value="null">Sin asignar</option>
                <option v-for="e in equipos" :key="e.id" :value="e.id">{{ e.nombre }}</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Posición</label>
            <div class="relative">
              <select v-model="form.posicion" class="form-select">
                <option :value="null">—</option>
                <option v-for="p in posiciones" :key="p" :value="p">{{ p }}</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Estatura (cm)</label>
            <input v-model="form.estatura_cm" type="number" min="100" max="260" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Peso (kg)</label>
            <input v-model="form.peso_kg" type="number" min="30" max="200" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Estado</label>
            <div class="relative">
              <select v-model="form.estado" class="form-select">
                <option value="Habilitado">Habilitado</option>
                <option value="Suspendido">Suspendido</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
        </div>

        <div class="rounded-DEFAULT bg-surface-container-low p-space-md">
          <span class="font-label-meta text-label-meta uppercase text-secondary tracking-wider">Estadísticas base</span>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-space-md mt-space-2xs">
            <div class="flex flex-col gap-1">
              <label class="form-label">PPG</label>
              <input v-model="form.ppg" type="number" step="0.1" min="0" class="form-input" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="form-label">RPG</label>
              <input v-model="form.rpg" type="number" step="0.1" min="0" class="form-input" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="form-label">APG</label>
              <input v-model="form.apg" type="number" step="0.1" min="0" class="form-input" />
            </div>
            <div class="flex flex-col gap-1">
              <label class="form-label">3P%</label>
              <input v-model="form.tres_pct" type="number" step="0.1" min="0" max="100" class="form-input" />
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="modalOpen = false">Descartar</button>
        <button type="submit" form="player-form" :disabled="saving" class="btn-primary">
          {{ saving ? 'Guardando…' : 'Guardar ficha' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';
import StatusPill from '../../components/admin/StatusPill.vue';
import IconButton from '../../components/admin/IconButton.vue';
import AdminModal from '../../components/admin/AdminModal.vue';
import ImageUploader from '../../components/admin/ImageUploader.vue';
import { useToast } from '../../composables/useToast';
import { useConfirm } from '../../composables/useConfirm';
import { useAlert } from '../../composables/useAlert';
import { assetUrl } from '../../services/assets';
import jugadoresService from '../../services/jugadoresService';
import equiposService from '../../services/equiposService';

const toast = useToast();
const { confirm } = useConfirm();
const alert = useAlert();

const posiciones = ['Base', 'Escolta', 'Alero', 'Ala-Pívot', 'Pívot'];
const perPage = 12;

const jugadores = ref([]);
const equipos = ref([]);
const total = ref(0);
const page = ref(1);
const loading = ref(true);

const search = ref('');
const equipoFilter = ref('');
const posFilter = ref('Todas');

const modalOpen = ref(false);
const saving = ref(false);
const editingId = ref(null);
const editingFoto = ref(null);
const fotoFile = ref(null);

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage)));

const emptyForm = () => ({
  nombre_completo: '',
  dorsal: '',
  dpi: '',
  fecha_nacimiento: '',
  nacionalidad: 'Guatemalteca',
  equipo_id: null,
  posicion: null,
  estatura_cm: '',
  peso_kg: '',
  estado: 'Habilitado',
  ppg: '',
  rpg: '',
  apg: '',
  tres_pct: ''
});
const form = reactive(emptyForm());

let searchTimer;
watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    page.value = 1;
    load();
  }, 350);
});
watch([equipoFilter, posFilter], () => {
  page.value = 1;
  load();
});

async function load() {
  loading.value = true;
  try {
    const res = await jugadoresService.list({
      search: search.value || undefined,
      equipo_id: equipoFilter.value || undefined,
      posicion: posFilter.value !== 'Todas' ? posFilter.value : undefined,
      page: page.value,
      perPage
    });
    jugadores.value = res.items;
    total.value = res.total;
  } catch {
    toast.error('No se pudieron cargar los jugadores');
  } finally {
    loading.value = false;
  }
}

function goPage(p) {
  page.value = p;
  load();
}

function openCreate() {
  editingId.value = null;
  editingFoto.value = null;
  fotoFile.value = null;
  Object.assign(form, emptyForm());
  modalOpen.value = true;
}

function openEdit(j) {
  editingId.value = j.id;
  editingFoto.value = j.foto_ruta;
  fotoFile.value = null;
  Object.assign(form, {
    nombre_completo: j.nombre_completo,
    dorsal: j.dorsal ?? '',
    dpi: j.dpi ?? '',
    fecha_nacimiento: j.fecha_nacimiento ?? '',
    nacionalidad: j.nacionalidad ?? 'Guatemalteca',
    equipo_id: j.equipo_id,
    posicion: j.posicion,
    estatura_cm: j.estatura_cm ?? '',
    peso_kg: j.peso_kg ?? '',
    estado: j.estado,
    ppg: j.stats.ppg,
    rpg: j.stats.rpg,
    apg: j.stats.apg,
    tres_pct: j.stats.tres_pct
  });
  modalOpen.value = true;
}

async function save() {
  if (!form.nombre_completo.trim()) {
    alert.error('Faltan datos', 'El nombre completo del jugador es obligatorio.');
    return;
  }
  saving.value = true;
  try {
    const saved = editingId.value
      ? await jugadoresService.update(editingId.value, { ...form })
      : await jugadoresService.create({ ...form });
    if (fotoFile.value) {
      await jugadoresService.uploadFoto(saved.id, fotoFile.value);
    }
    modalOpen.value = false;
    await load();
    toast.success(editingId.value ? 'Ficha actualizada' : 'Jugador registrado');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    saving.value = false;
  }
}

async function askDelete(j) {
  const ok = await confirm({
    title: `¿Inactivar a "${j.nombre_completo}"?`,
    text: 'El jugador dejará de aparecer en los listados públicos.',
    confirmText: 'Sí, inactivar'
  });
  if (!ok) return;
  try {
    await jugadoresService.remove(j.id);
    if (jugadores.value.length === 1 && page.value > 1) page.value--;
    await load();
    toast.success('Jugador inactivado');
  } catch (err) {
    alert.error('No se pudo inactivar', err?.response?.data?.message || 'Intenta de nuevo.');
  }
}

onMounted(async () => {
  try {
    equipos.value = await equiposService.list();
  } catch { /* noop */ }
  await load();
});
</script>
