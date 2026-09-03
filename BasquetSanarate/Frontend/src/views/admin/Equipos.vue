<template>
  <div class="flex flex-col w-full">
    <AdminPageHeader
      eyebrow="Módulo operativo"
      title="Administración de equipos y franquicias"
      subtitle="Padrón oficial de clubes, altas directivas y asignación de canchas del torneo."
    >
      <template #actions>
        <button
          type="button"
          class="inline-flex items-center gap-space-xs px-space-md py-space-xs rounded-full bg-surface-container-high text-on-surface hover:bg-surface-variant transition-colors font-label-pill text-label-pill uppercase shadow-sm"
          @click="exportCsv"
        >
          <span class="material-symbols-outlined text-[18px] text-secondary">file_download</span>
          <span>Exportar CSV</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-space-xs px-space-lg py-space-xs rounded-full bg-primary-container text-on-primary-fixed hover:bg-primary-fixed transition-transform active:scale-95 font-label-pill text-label-pill uppercase font-bold tracking-wide shadow-[0_8px_24px_-4px_rgba(204,255,0,0.5)]"
          @click="openCreate"
        >
          <span class="material-symbols-outlined text-[20px]">add_circle</span>
          <span>Agregar equipo</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Telemetría -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-space-md mb-space-lg">
      <StatTile label="Equipos activos" :value="equipos.length" />
      <StatTile label="Rama masculina" :value="countRama('Masculina Mayor')" />
      <StatTile label="Rama femenina" :value="countRama('Femenina Libre')" />
      <StatTile label="Juvenil Sub-18" :value="countRama('Juvenil Sub-18')" />
    </div>

    <!-- Filtros -->
    <div class="bg-surface-container-low rounded-xl p-space-sm md:p-space-md shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-space-md mb-space-lg">
      <div class="relative flex-1">
        <span class="material-symbols-outlined absolute left-space-md top-1/2 -translate-y-1/2 text-secondary text-[20px]">search</span>
        <input
          v-model="search"
          type="text"
          placeholder="Buscar por nombre, DT o sede..."
          class="w-full pl-11 pr-space-md py-space-xs bg-surface-container-lowest rounded-full font-body-md text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary-container shadow-sm"
        />
      </div>
      <div class="flex flex-wrap items-center gap-space-sm">
        <div class="flex items-center bg-surface-container-lowest p-1 rounded-full shadow-sm">
          <button
            v-for="opt in ['Todas', 'Masculina Mayor', 'Femenina Libre', 'Juvenil Sub-18']"
            :key="opt"
            type="button"
            class="px-space-md py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors"
            :class="ramaFilter === opt ? 'bg-primary-container text-on-primary-fixed' : 'text-secondary hover:text-on-surface'"
            @click="ramaFilter = opt"
          >{{ ramaShort(opt) }}</button>
        </div>
        <div class="flex items-center bg-surface-container-lowest p-1 rounded-full shadow-sm">
          <button
            v-for="opt in ['Todas', 'Norte', 'Sur']"
            :key="opt"
            type="button"
            class="px-space-md py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors"
            :class="confFilter === opt ? 'bg-inverse-surface text-surface-bright' : 'text-secondary hover:text-on-surface'"
            @click="confFilter = opt"
          >{{ opt === 'Todas' ? 'Todas conf.' : opt }}</button>
        </div>
        <button
          type="button"
          title="Restablecer filtros"
          class="w-10 h-10 rounded-full bg-surface-container-lowest text-secondary hover:text-on-surface flex items-center justify-center shadow-sm"
          @click="resetFilters"
        >
          <span class="material-symbols-outlined text-[20px]">filter_alt_off</span>
        </button>
      </div>
    </div>

    <!-- Estados -->
    <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-sm">Cargando equipos…</div>
    <div v-else-if="!filtered.length" class="py-space-3xl text-center text-secondary font-body-sm">
      No hay equipos que coincidan con los filtros.
    </div>

    <!-- Grid -->
    <section v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-lg">
      <article
        v-for="team in filtered"
        :key="team.id"
        class="bg-surface-container-lowest rounded-xl p-space-lg shadow-[0_10px_30px_-4px_rgba(15,23,42,0.05)] flex flex-col justify-between relative overflow-hidden transition-all hover:-translate-y-1 hover:shadow-xl"
      >
        <div
          class="absolute top-0 left-0 h-full w-1.5"
          :style="{ backgroundColor: team.color_hex || 'transparent' }"
        ></div>
        <div class="absolute top-0 right-0 w-36 h-36 bg-gradient-to-bl from-primary-container/20 via-transparent to-transparent rounded-bl-full pointer-events-none"></div>
        <div>
          <div class="flex items-start justify-between gap-space-xs mb-space-md">
            <div class="flex items-center gap-space-sm min-w-0">
              <div
                class="w-16 h-16 rounded-full bg-inverse-surface flex items-center justify-center shadow-md p-1.5 shrink-0 overflow-hidden border-4"
                :style="{ borderColor: team.color_hex || 'rgba(204,255,0,0.35)' }"
              >
                <img v-if="team.logo_ruta" :src="assetUrl(team.logo_ruta)" alt="" class="w-full h-full object-contain" />
                <span v-else class="material-symbols-outlined text-primary-container text-[28px]">shield</span>
              </div>
              <div class="flex flex-col min-w-0">
                <span class="font-label-meta text-label-meta uppercase tracking-wider text-primary font-bold">Conf. {{ team.conferencia }}</span>
                <h2 class="font-headline-md text-headline-md uppercase text-on-surface tracking-tight leading-tight truncate">{{ team.nombre }}</h2>
                <span class="font-label-meta text-label-meta text-secondary truncate">{{ team.sede }}</span>
              </div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
              <IconButton icon="edit" label="Editar equipo" variant="primary" size="sm" @click="openEdit(team)" />
              <IconButton icon="delete" label="Eliminar equipo" variant="danger" size="sm" @click="askDelete(team)" />
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-space-2xs mb-space-md">
            <StatusPill :label="team.rama" tone="neutral" />
            <StatusPill :label="`${team.jugadores_count} jugadores`" tone="info" />
          </div>

          <div class="grid grid-cols-3 gap-space-2xs bg-surface-container-low rounded-DEFAULT p-space-sm mb-space-md text-center">
            <div class="flex flex-col">
              <span class="font-label-meta text-label-meta text-secondary uppercase">Récord</span>
              <span class="font-headline-md text-headline-md text-on-surface font-bold">{{ team.clasificacion.record }}</span>
            </div>
            <div class="flex flex-col">
              <span class="font-label-meta text-label-meta text-secondary uppercase">PPG Atq</span>
              <span class="font-headline-md text-headline-md text-primary font-bold">{{ team.clasificacion.ppg }}</span>
            </div>
            <div class="flex flex-col">
              <span class="font-label-meta text-label-meta text-secondary uppercase">PPG Def</span>
              <span class="font-headline-md text-headline-md text-secondary font-bold">{{ team.clasificacion.oppg }}</span>
            </div>
          </div>

          <div class="flex items-center justify-between py-space-xs px-space-sm rounded-DEFAULT bg-surface-container-lowest shadow-sm">
            <div class="flex items-center gap-space-xs min-w-0">
              <span class="material-symbols-outlined text-[18px] text-primary">sports</span>
              <div class="flex flex-col min-w-0">
                <span class="font-label-meta text-label-meta uppercase text-secondary">Director técnico</span>
                <span class="font-label-pill text-label-pill text-on-surface font-bold truncate">{{ team.director_tecnico }}</span>
              </div>
            </div>
            <span class="font-label-meta text-label-meta text-tertiary font-bold uppercase shrink-0">Activo</span>
          </div>
        </div>
      </article>
    </section>

    <!-- Modal crear / editar -->
    <AdminModal
      v-model:open="modalOpen"
      :title="editingId ? 'Editar franquicia' : 'Registrar franquicia'"
      eyebrow="Padrón de clubes"
      icon="shield"
      size="lg"
    >
      <form id="team-form" class="flex flex-col gap-space-md" @submit.prevent="save">
        <div class="flex flex-col gap-1">
          <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Nombre oficial del club *</label>
          <input v-model.trim="formData.nombre" type="text" placeholder="Ej. Cobras del Valle" class="form-input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Sede / cancha local *</label>
            <input v-model.trim="formData.sede" type="text" placeholder="Nombre del gimnasio" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Conferencia *</label>
            <div class="relative">
              <select v-model="formData.conferencia" class="form-select">
                <option value="Norte">Conferencia Norte</option>
                <option value="Sur">Conferencia Sur</option>
              </select>
              <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-secondary pointer-events-none text-[20px]">expand_more</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Categoría / rama deportiva *</label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-xs">
            <label
              v-for="r in ramas"
              :key="r.value"
              class="flex items-center gap-space-xs p-space-xs rounded-DEFAULT bg-surface-container-lowest shadow-sm cursor-pointer hover:bg-surface-container transition-colors"
            >
              <input v-model="formData.rama" :value="r.value" type="radio" name="rama" class="accent-primary-container w-4 h-4" />
              <div class="flex flex-col">
                <span class="font-label-pill text-label-pill text-on-surface font-bold">{{ r.label }}</span>
                <span class="font-label-meta text-label-meta text-secondary">{{ r.hint }}</span>
              </div>
            </label>
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Escudo / logotipo</label>
          <ImageUploader
            v-model="logoFile"
            :current-url="assetUrl(editingLogo)"
            hint="SVG o PNG transparente, 500×500px, máx 2MB."
            icon="shield"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Director técnico *</label>
            <input v-model.trim="formData.director_tecnico" type="text" placeholder="Nombre del coach" class="form-input" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Teléfono del delegado *</label>
            <input v-model.trim="formData.telefono_delegado" type="tel" placeholder="+502 0000-0000" class="form-input" />
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="font-label-pill text-label-pill uppercase text-on-surface font-bold">Color representativo</label>
          <div class="flex items-center gap-space-sm">
            <label v-for="c in colores" :key="c" class="cursor-pointer relative flex items-center justify-center">
              <input v-model="formData.color_hex" :value="c" type="radio" name="color" class="peer sr-only" />
              <span
                class="w-9 h-9 rounded-full peer-checked:ring-4 peer-checked:ring-on-surface peer-checked:scale-110 transition-all flex items-center justify-center"
                :style="{ backgroundColor: c }"
              >
                <span class="material-symbols-outlined text-surface text-[16px] opacity-0 peer-checked:opacity-100 font-bold">check</span>
              </span>
            </label>
            <button type="button" class="font-label-meta text-label-meta uppercase text-secondary hover:text-on-surface hover:underline" @click="formData.color_hex = null">
              Sin color
            </button>
          </div>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="modalOpen = false">Cancelar</button>
        <button type="submit" form="team-form" :disabled="saving" class="btn-primary">
          {{ saving ? 'Guardando…' : 'Guardar' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';
import StatTile from '../../components/admin/StatTile.vue';
import StatusPill from '../../components/admin/StatusPill.vue';
import IconButton from '../../components/admin/IconButton.vue';
import AdminModal from '../../components/admin/AdminModal.vue';
import ImageUploader from '../../components/admin/ImageUploader.vue';
import { useToast } from '../../composables/useToast';
import { useConfirm } from '../../composables/useConfirm';
import { useAlert } from '../../composables/useAlert';
import { assetUrl } from '../../services/assets';
import equiposService from '../../services/equiposService';

const toast = useToast();
const { confirm } = useConfirm();
const alert = useAlert();

const equipos = ref([]);
const loading = ref(true);

const search = ref('');
const ramaFilter = ref('Todas');
const confFilter = ref('Todas');

const modalOpen = ref(false);
const saving = ref(false);
const editingId = ref(null);
const editingLogo = ref(null);
const logoFile = ref(null);

const ramas = [
  { value: 'Masculina Mayor', label: 'Masculina Mayor', hint: '1ª división' },
  { value: 'Femenina Libre', label: 'Femenina Libre', hint: 'Categoría abierta' },
  { value: 'Juvenil Sub-18', label: 'Juvenil Sub-18', hint: 'Formativo' }
];
const colores = ['#ccff00', '#0F172A', '#3B82F6', '#EF4444'];

const emptyForm = () => ({
  nombre: '',
  sede: '',
  conferencia: 'Norte',
  rama: 'Masculina Mayor',
  director_tecnico: '',
  telefono_delegado: '',
  color_hex: null
});
const formData = reactive(emptyForm());

const filtered = computed(() => {
  const q = search.value.toLowerCase().trim();
  return equipos.value.filter((t) => {
    const mRama = ramaFilter.value === 'Todas' || t.rama === ramaFilter.value;
    const mConf = confFilter.value === 'Todas' || t.conferencia === confFilter.value;
    const mSearch =
      !q ||
      t.nombre.toLowerCase().includes(q) ||
      t.sede.toLowerCase().includes(q) ||
      (t.director_tecnico || '').toLowerCase().includes(q);
    return mRama && mConf && mSearch;
  });
});

function countRama(r) {
  return equipos.value.filter((t) => t.rama === r).length;
}
function ramaShort(r) {
  return { Todas: 'Todas', 'Masculina Mayor': 'Masc', 'Femenina Libre': 'Fem', 'Juvenil Sub-18': 'Sub-18' }[r];
}
function resetFilters() {
  search.value = '';
  ramaFilter.value = 'Todas';
  confFilter.value = 'Todas';
}

async function load() {
  loading.value = true;
  try {
    equipos.value = await equiposService.list();
  } catch {
    toast.error('No se pudieron cargar los equipos');
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editingId.value = null;
  editingLogo.value = null;
  logoFile.value = null;
  Object.assign(formData, emptyForm());
  modalOpen.value = true;
}

function openEdit(team) {
  editingId.value = team.id;
  editingLogo.value = team.logo_ruta;
  logoFile.value = null;
  Object.assign(formData, {
    nombre: team.nombre,
    sede: team.sede,
    conferencia: team.conferencia,
    rama: team.rama,
    director_tecnico: team.director_tecnico,
    telefono_delegado: team.telefono_delegado,
    color_hex: team.color_hex
  });
  modalOpen.value = true;
}

function validar() {
  if (!formData.nombre.trim()) return 'El nombre del club es obligatorio.';
  if (!formData.sede.trim()) return 'La sede o cancha es obligatoria.';
  if (!formData.director_tecnico.trim()) return 'El director técnico es obligatorio.';
  if (!formData.telefono_delegado.trim()) return 'El teléfono del delegado es obligatorio.';
  return null;
}

async function save() {
  const error = validar();
  if (error) {
    alert.error('Faltan datos', error);
    return;
  }
  saving.value = true;
  try {
    const payload = { ...formData };
    const saved = editingId.value
      ? await equiposService.update(editingId.value, payload)
      : await equiposService.create(payload);

    if (logoFile.value) {
      await equiposService.uploadLogo(saved.id, logoFile.value);
    }
    modalOpen.value = false;
    await load();
    toast.success(editingId.value ? 'Equipo actualizado' : 'Equipo registrado');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    saving.value = false;
  }
}

async function askDelete(team) {
  const ok = await confirm({
    title: `¿Eliminar "${team.nombre}"?`,
    text: 'Si el equipo tiene partidos registrados se dará de baja en lugar de borrarse.',
    confirmText: 'Sí, eliminar'
  });
  if (!ok) return;
  try {
    const res = await equiposService.remove(team.id);
    await load();
    toast.success(res.modo === 'baja_logica' ? 'Equipo dado de baja' : 'Equipo eliminado');
  } catch (err) {
    alert.error('No se pudo eliminar', err?.response?.data?.message || 'Intenta de nuevo.');
  }
}

function exportCsv() {
  const rows = [
    ['Nombre', 'Sede', 'Conferencia', 'Rama', 'Director técnico', 'Teléfono', 'Jugadores', 'Récord'],
    ...filtered.value.map((t) => [
      t.nombre, t.sede, t.conferencia, t.rama, t.director_tecnico,
      t.telefono_delegado, t.jugadores_count, t.clasificacion.record
    ])
  ];
  const csv = rows.map((r) => r.map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(',')).join('\n');
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'equipos.csv';
  a.click();
  URL.revokeObjectURL(url);
}

onMounted(load);
</script>
