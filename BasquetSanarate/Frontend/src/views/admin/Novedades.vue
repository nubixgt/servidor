<template>
  <div class="flex flex-col w-full">
    <AdminPageHeader
      eyebrow="Módulo de difusión"
      title="Gestión editorial, noticias y circulares"
      subtitle="Redacta boletines, adjunta circulares en PDF y controla qué se publica en el portal."
    >
      <template #actions>
        <button type="button" class="btn-primary inline-flex items-center gap-space-xs" @click="openCreate">
          <span class="material-symbols-outlined text-[20px]">post_add</span>
          <span>Nueva publicación</span>
        </button>
      </template>
    </AdminPageHeader>

    <!-- Filtro por estado -->
    <div class="flex items-center gap-space-xs bg-surface-container-lowest p-1 rounded-full shadow-sm w-fit mb-space-lg">
      <button
        v-for="f in estadoFilters"
        :key="f.value"
        type="button"
        class="px-space-md py-space-2xs rounded-full font-label-pill text-label-pill uppercase transition-colors"
        :class="estadoFilter === f.value ? 'bg-primary-container text-on-primary-fixed' : 'text-secondary hover:text-on-surface'"
        @click="estadoFilter = f.value"
      >{{ f.label }} <span v-if="counts[f.value]" class="opacity-70">({{ counts[f.value] }})</span></button>
    </div>

    <div v-if="loading" class="py-space-3xl text-center text-secondary font-body-sm">Cargando…</div>
    <div v-else-if="!filtered.length" class="py-space-3xl text-center text-secondary font-body-sm">
      No hay publicaciones.
    </div>

    <section v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-lg">
      <article v-for="n in filtered" :key="n.id" class="bg-surface-container-lowest rounded-xl shadow-[0_10px_30px_-4px_rgba(15,23,42,0.05)] flex flex-col overflow-hidden">
        <div class="relative h-40 bg-surface-container-low flex items-center justify-center">
          <img v-if="n.portada_ruta" :src="assetUrl(n.portada_ruta)" alt="" class="w-full h-full object-cover" />
          <span v-else class="material-symbols-outlined text-[48px] text-outline-variant">image</span>
          <span class="absolute top-space-sm left-space-sm px-space-sm py-space-2xs rounded-full bg-inverse-surface/90 text-surface font-label-meta text-label-meta uppercase">{{ n.categoria }}</span>
          <span v-if="n.fijado" class="absolute top-space-sm right-space-sm px-space-sm py-space-2xs rounded-full bg-primary-container text-on-primary-fixed font-label-meta text-label-meta uppercase font-bold">Portada</span>
        </div>
        <div class="p-space-md flex flex-col gap-space-xs flex-1">
          <span class="font-label-meta text-label-meta text-secondary">{{ n.fecha_emision || '—' }} · {{ n.autor_nombre || 'Sistema' }}</span>
          <h3 class="font-headline-md text-headline-md uppercase text-on-surface leading-tight line-clamp-2">{{ n.titulo }}</h3>
          <p class="font-body-sm text-body-sm text-secondary line-clamp-3 flex-1">{{ n.cuerpo || 'Sin contenido.' }}</p>

          <div class="flex items-center justify-between pt-space-xs border-t border-surface-container mt-space-xs">
            <label class="inline-flex items-center gap-space-xs cursor-pointer">
              <input type="checkbox" class="sr-only peer" :checked="n.estado === 'publicado'" @change="togglePublicado(n)" />
              <span class="w-9 h-5 rounded-full bg-surface-container-high peer-checked:bg-primary transition-colors relative after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-surface-container-lowest after:transition-transform peer-checked:after:translate-x-4"></span>
              <span class="font-label-meta text-label-meta uppercase" :class="n.estado === 'publicado' ? 'text-tertiary' : 'text-secondary'">
                {{ n.estado === 'publicado' ? 'Publicado' : 'Borrador' }}
              </span>
            </label>
            <div class="flex items-center gap-1">
              <IconButton icon="edit" label="Editar" variant="primary" size="sm" @click="openEdit(n)" />
              <IconButton icon="delete" label="Eliminar" variant="danger" size="sm" @click="askDelete(n)" />
            </div>
          </div>
          <a
            v-if="n.pdf_ruta"
            :href="assetUrl(n.pdf_ruta)"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center gap-space-2xs font-label-meta text-label-meta uppercase text-primary hover:underline"
          >
            <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span> Circular adjunta
          </a>
        </div>
      </article>
    </section>

    <!-- Modal -->
    <AdminModal
      v-model:open="modalOpen"
      :title="editingId ? 'Editar noticia' : 'Nueva noticia'"
      eyebrow="Consola editorial"
      icon="post_add"
      size="xl"
    >
      <form id="news-form" class="flex flex-col gap-space-md" @submit.prevent="save">
        <div class="flex flex-col gap-1">
          <label class="form-label">Título *</label>
          <input v-model.trim="form.titulo" type="text" class="form-input" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Categoría</label>
            <input v-model.trim="form.categoria" type="text" list="cats" class="form-input" />
            <datalist id="cats">
              <option v-for="c in categoriasSugeridas" :key="c" :value="c" />
            </datalist>
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Fecha de emisión</label>
            <input v-model="form.fecha_emision" type="date" class="form-input" />
          </div>
          <label class="flex items-center gap-space-xs pt-space-lg">
            <input v-model="form.fijado" type="checkbox" class="accent-primary-container w-4 h-4" />
            <span class="form-label">Fijar en portada</span>
          </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-md">
          <div class="flex flex-col gap-1">
            <label class="form-label">Fotografía de portada (16:9)</label>
            <ImageUploader v-model="portadaFile" :current-url="assetUrl(editingPortada)" :round="false" hint="JPG/PNG/WEBP, máx 8MB." icon="add_photo_alternate" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="form-label">Circular adjunta (PDF)</label>
            <div class="rounded-DEFAULT bg-surface-container-lowest p-space-md shadow-sm flex items-center gap-space-md">
              <span class="material-symbols-outlined text-[28px] text-secondary">picture_as_pdf</span>
              <div class="flex flex-col flex-1 min-w-0">
                <button type="button" class="px-space-md py-space-2xs rounded-full bg-surface-container-high hover:bg-surface-variant font-label-pill text-label-pill uppercase text-on-surface w-fit" @click="pdfInput?.click()">
                  {{ pdfFile ? pdfFile.name : (editingPdf ? 'Reemplazar PDF' : 'Subir PDF') }}
                </button>
                <span class="font-label-meta text-label-meta text-secondary mt-space-2xs">Opcional, máx 10MB.</span>
              </div>
              <input ref="pdfInput" type="file" accept="application/pdf" class="hidden" @change="onPdf" />
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="form-label">Cuerpo editorial</label>
          <textarea v-model="form.cuerpo" rows="7" class="form-input rounded-DEFAULT" placeholder="Contenido del boletín…"></textarea>
        </div>
      </form>

      <template #footer>
        <button type="button" class="btn-ghost" @click="saveAs('borrador')" :disabled="saving">Guardar borrador</button>
        <button type="button" class="btn-primary" @click="saveAs('publicado')" :disabled="saving">
          {{ saving ? 'Guardando…' : 'Publicar' }}
        </button>
      </template>
    </AdminModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue';
import IconButton from '../../components/admin/IconButton.vue';
import AdminModal from '../../components/admin/AdminModal.vue';
import ImageUploader from '../../components/admin/ImageUploader.vue';
import { useToast } from '../../composables/useToast';
import { useConfirm } from '../../composables/useConfirm';
import { useAlert } from '../../composables/useAlert';
import { assetUrl } from '../../services/assets';
import novedadesService from '../../services/novedadesService';

const toast = useToast();
const { confirm } = useConfirm();
const alert = useAlert();

const categoriasSugeridas = ['Noticias', 'Playoffs', 'Comisión Disciplinaria', 'Clínicas', 'Comunidad', 'Infraestructura'];
const estadoFilters = [
  { value: 'Todos', label: 'Todos' },
  { value: 'publicado', label: 'Publicados' },
  { value: 'borrador', label: 'Borradores' }
];

const novedades = ref([]);
const loading = ref(true);
const estadoFilter = ref('Todos');

const modalOpen = ref(false);
const saving = ref(false);
const editingId = ref(null);
const editingPortada = ref(null);
const editingPdf = ref(null);
const portadaFile = ref(null);
const pdfFile = ref(null);
const pdfInput = ref(null);

const counts = computed(() => {
  const c = { Todos: novedades.value.length };
  for (const n of novedades.value) c[n.estado] = (c[n.estado] || 0) + 1;
  return c;
});

const filtered = computed(() =>
  novedades.value.filter((n) => estadoFilter.value === 'Todos' || n.estado === estadoFilter.value)
);

const emptyForm = () => ({
  titulo: '',
  categoria: 'Noticias',
  fecha_emision: '',
  fijado: false,
  cuerpo: '',
  estado: 'borrador'
});
const form = reactive(emptyForm());

function onPdf(e) {
  pdfFile.value = e.target.files?.[0] || null;
}

async function load() {
  loading.value = true;
  try {
    novedades.value = await novedadesService.list();
  } catch {
    toast.error('No se pudieron cargar las novedades');
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editingId.value = null;
  editingPortada.value = null;
  editingPdf.value = null;
  portadaFile.value = null;
  pdfFile.value = null;
  Object.assign(form, emptyForm());
  modalOpen.value = true;
}

function openEdit(n) {
  editingId.value = n.id;
  editingPortada.value = n.portada_ruta;
  editingPdf.value = n.pdf_ruta;
  portadaFile.value = null;
  pdfFile.value = null;
  Object.assign(form, {
    titulo: n.titulo,
    categoria: n.categoria,
    fecha_emision: n.fecha_emision ?? '',
    fijado: n.fijado,
    cuerpo: n.cuerpo ?? '',
    estado: n.estado
  });
  modalOpen.value = true;
}

async function saveAs(estado) {
  form.estado = estado;
  await save();
}

async function save() {
  if (!form.titulo.trim()) {
    alert.error('Faltan datos', 'El título de la publicación es obligatorio.');
    return;
  }
  saving.value = true;
  try {
    const payload = { ...form };
    const saved = editingId.value
      ? await novedadesService.update(editingId.value, payload)
      : await novedadesService.create(payload);

    if (portadaFile.value) await novedadesService.uploadPortada(saved.id, portadaFile.value);
    if (pdfFile.value) await novedadesService.uploadPdf(saved.id, pdfFile.value);

    modalOpen.value = false;
    await load();
    toast.success(form.estado === 'publicado' ? 'Noticia publicada' : 'Borrador guardado');
  } catch (err) {
    alert.error('No se pudo guardar', err?.response?.data?.message || 'Intenta de nuevo.');
  } finally {
    saving.value = false;
  }
}

async function togglePublicado(n) {
  const nuevo = n.estado === 'publicado' ? 'borrador' : 'publicado';
  try {
    await novedadesService.update(n.id, {
      titulo: n.titulo,
      categoria: n.categoria,
      cuerpo: n.cuerpo,
      fijado: n.fijado,
      fecha_emision: n.fecha_emision,
      estado: nuevo
    });
    n.estado = nuevo;
    toast.success(nuevo === 'publicado' ? 'Publicada' : 'Pasó a borrador');
  } catch (err) {
    alert.error('No se pudo cambiar el estado', err?.response?.data?.message || 'Intenta de nuevo.');
  }
}

async function askDelete(n) {
  const ok = await confirm({
    title: `¿Eliminar "${n.titulo}"?`,
    text: 'La publicación se borrará de forma permanente.',
    confirmText: 'Sí, eliminar'
  });
  if (!ok) return;
  try {
    await novedadesService.remove(n.id);
    await load();
    toast.success('Publicación eliminada');
  } catch (err) {
    alert.error('No se pudo eliminar', err?.response?.data?.message || 'Intenta de nuevo.');
  }
}

onMounted(load);
</script>
