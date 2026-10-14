<template>
    <div>
        <div class="mx-auto max-w-[1480px] px-3.5 sm:px-6 py-4 sm:py-6 pb-32 lg:pb-8">
            <!-- Encabezado -->
            <header class="mb-4">
                <h1 class="text-[28px] sm:text-[34px] leading-tight font-extrabold" style="color: var(--green-dark);">Crear Registros</h1>
                <p class="text-[13px] sm:text-sm" style="color: var(--navy);">Registro de afectaciones en campo</p>
            </header>

            <!-- Progreso -->
            <nav class="glass sticky top-2 z-30 mb-4 lg:mb-5 px-3 sm:px-6 py-3" style="border-radius: 999px;" aria-label="Progreso del registro">
                <ol class="flex items-center">
                    <template v-for="(st, i) in steps" :key="st.id">
                        <li class="flex-shrink-0">
                            <button type="button" class="flex flex-col items-center gap-1 min-w-[48px]" :aria-current="i === pasoActivo ? 'step' : undefined" @click="irA(st.id)">
                                <span
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold transition-all"
                                    :style="st.hecho
                                        ? 'background: var(--green); color:#fff;'
                                        : i === pasoActivo
                                            ? 'background: #fff; color: var(--green); border: 2px solid var(--green); box-shadow: 0 0 0 4px rgba(7,148,71,.14);'
                                            : 'background: #fff; color: #7C8DA8; border: 1px solid var(--input-border);'"
                                >
                                    <CheckIcon v-if="st.hecho" class="w-5 h-5" />
                                    <component :is="st.icon" v-else class="w-5 h-5" />
                                </span>
                                <span class="text-[11px] sm:text-[13px] font-semibold" :style="{ color: st.hecho || i === pasoActivo ? 'var(--navy)' : 'var(--muted)' }">{{ st.label }}</span>
                            </button>
                        </li>
                        <li v-if="i < steps.length - 1" class="flex-1 h-[3px] rounded-full mx-1.5 sm:mx-3 -mt-5" :style="{ background: st.hecho ? 'var(--green)' : '#D5DEEA' }" aria-hidden="true"></li>
                    </template>
                </ol>
            </nav>

            <!-- Formulario -->
            <form class="grid gap-[18px] lg:grid-cols-[minmax(0,1fr)_340px] 2xl:grid-cols-[280px_minmax(0,1fr)_390px] items-start" @submit.prevent="guardar">
                <!-- Panel informativo (solo escritorio ancho) -->
                <aside class="glass hidden 2xl:flex flex-col p-6 min-h-[560px] sticky top-28">
                    <h2 class="text-[30px] leading-[1.1] font-extrabold" style="color: var(--navy);">Registra y monitorea los daños en cultivos</h2>
                    <p class="mt-4 text-[15px]" style="color: var(--navy);">Información real para una mejor respuesta en el campo.</p>
                    <ul class="mt-6 space-y-4">
                        <li v-for="f in features" :key="f.text" class="flex items-center gap-3">
                            <span class="icon-badge" :style="{ background: f.color }"><component :is="f.icon" class="w-5 h-5" /></span>
                            <span class="text-[14px] font-medium leading-snug" style="color: var(--navy);">{{ f.text }}</span>
                        </li>
                    </ul>
                    <p class="mt-auto pt-8 text-lg font-bold leading-snug" style="color: var(--green-dark);">Juntos por un campo más resiliente</p>
                </aside>

                <!-- Columna central: secciones 1 a 3 -->
                <div class="space-y-[18px] min-w-0">
                    <!-- 1. Ubicación -->
                    <section id="sec-1" class="glass p-5 sm:p-6 scroll-mt-28">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="icon-badge" style="background: var(--blue);"><MapPinIcon class="w-6 h-6" /></span>
                            <div>
                                <h2 class="section-title">1. Ubicación</h2>
                                <p class="section-desc">Fecha, responsable, lote y ubicación GPS.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label" for="f-fecha">Fecha de levantamiento *</label>
                                <input id="f-fecha" v-model="form.fecha" type="date" required class="field" />
                            </div>
                            <div>
                                <label class="label" for="f-tecnico">Productor *</label>
                                <input id="f-tecnico" v-model="form.tecnico" required placeholder="Ej. Juan Pérez" class="field" />
                            </div>
                            <div>
                                <label class="label">Departamento *</label>
                                <SearchSelect v-model="form.departamento" :options="DEPARTAMENTOS" placeholder="Buscar departamento…" required />
                            </div>
                            <div>
                                <label class="label">Municipio *</label>
                                <SearchSelect v-model="form.municipio" :options="municipios" :placeholder="form.departamento ? 'Buscar municipio…' : 'Seleccione primero el departamento'" required />
                            </div>
                            <div>
                                <label class="label" for="f-centro">Centro poblado *</label>
                                <input id="f-centro" v-model="form.centroPoblado" required placeholder="Ej. Aldea El Rosario" class="field" />
                            </div>
                            <div>
                                <label class="label">Agricultor en RUA *</label>
                                <SelectField v-model="form.agricultorRua" :options="['Sí', 'No']" placeholder="Seleccione..." />
                            </div>
                            <div v-if="form.agricultorRua === 'Sí'">
                                <label class="label" for="f-rua">Código de RUA *</label>
                                <input id="f-rua" v-model="form.codigoRua" required placeholder="Escriba el código de RUA" class="field" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="f-lote">Lote / parcela *</label>
                                <input id="f-lote" v-model="form.lote" required placeholder="Ej. Lote 3 - El Potrero" class="field" />
                            </div>
                        </div>
                        <div class="card-inner mt-4 p-4 flex flex-wrap items-center gap-x-6 gap-y-3 text-[13px]" style="color: var(--muted);">
                            <span>Latitud: <b class="font-mono" style="color: var(--navy);">{{ gps.lat ?? '—' }}</b></span>
                            <span>Longitud: <b class="font-mono" style="color: var(--navy);">{{ gps.lon ?? '—' }}</b></span>
                            <span>Precisión: <b class="font-mono" style="color: var(--navy);">{{ gps.acc ?? '—' }}</b> m</span>
                            <button type="button" class="btn btn-outline btn-sm sm:ml-auto" :disabled="gpsBuscando" @click="capturarGPS">
                                <ViewfinderCircleIcon class="w-5 h-5" style="color: var(--blue);" />
                                {{ gpsBuscando ? 'Buscando…' : 'Usar mi ubicación actual *' }}
                            </button>
                            <p class="hint w-full !mt-0">En campo, espere a que la precisión sea menor a 10 m. La ubicación actual es obligatoria.</p>
                        </div>
                    </section>

                    <!-- 2. Cultivo -->
                    <section id="sec-2" class="glass p-5 sm:p-6 scroll-mt-28">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="icon-badge" style="background: var(--green);">
                                <svg viewBox="0 0 24 24" class="w-6 h-6" fill="currentColor" aria-hidden="true"><path d="M12 21v-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none" /><path d="M12 14C12 8.5 8 5 3 5c0 5.5 3.5 9 9 9Z" /><path d="M12 12c0-4.5 3-7.500 8-7.500 0 4.500-3 7.500-8 7.500Z" opacity=".8" /></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h2 class="section-title">2. Cultivo</h2>
                                <p class="section-desc">Información del cultivo afectado.</p>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm flex-shrink-0" @click="agregarAsociado"><PlusIcon class="w-5 h-5" /> Agregar cultivo asociado</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Cultivo *</label>
                                <SearchSelect v-model="form.cultivo" :options="CULTIVOS" placeholder="Buscar cultivo…" required />
                            </div>
                            <div>
                                <label class="label">Estado fenológico</label>
                                <SelectField v-model="form.etapa" :options="ETAPAS" placeholder="Seleccione..." />
                            </div>
                            <div>
                                <label class="label" for="f-area-lote">Superficie total del lote (ha)</label>
                                <input id="f-area-lote" v-model="form.areaLote" type="number" min="0" step="0.01" placeholder="Ej. 25.5" class="field" />
                            </div>
                            <div>
                                <label class="label" for="f-area-afectada">Superficie afectada (ha) *</label>
                                <input id="f-area-afectada" v-model="form.areaAfectada" type="number" min="0" step="0.01" required placeholder="Ej. 8.2" class="field" />
                            </div>
                        </div>

                        <div v-for="(a, i) in form.asociados" :key="a.uid" class="card-inner mt-4 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold" style="color: var(--navy);">Cultivo asociado {{ i + 1 }}</span>
                                <button type="button" class="flex items-center gap-1 text-[13px] font-semibold min-h-[44px] px-2" style="color: var(--red);" @click="quitarAsociado(i)">
                                    <TrashIcon class="w-4 h-4" /> Quitar
                                </button>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="label">Cultivo *</label>
                                    <SearchSelect v-model="a.cultivo" :options="CULTIVOS" placeholder="Buscar cultivo…" required />
                                </div>
                                <div>
                                    <label class="label">Estado fenológico</label>
                                    <SelectField v-model="a.etapa" :options="ETAPAS" placeholder="Seleccione..." />
                                </div>
                                <div>
                                    <label class="label">Superficie total del lote (ha)</label>
                                    <input v-model="a.areaLote" type="number" min="0" step="0.01" placeholder="Ej. 25.5" class="field" />
                                </div>
                                <div>
                                    <label class="label">Superficie afectada (ha) *</label>
                                    <input v-model="a.areaAfectada" type="number" min="0" step="0.01" required placeholder="Ej. 8.2" class="field" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- 3. Daño -->
                    <section id="sec-3" class="glass p-5 sm:p-6 scroll-mt-28">
                        <div class="flex items-center gap-3 mb-5">
                            <span class="icon-badge" style="background: var(--orange);"><ExclamationTriangleIcon class="w-6 h-6" /></span>
                            <div>
                                <h2 class="section-title">3. Daños</h2>
                                <p class="section-desc">Causa, porcentaje de daño y pérdida estimada.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Causa del daño *</label>
                                <SearchSelect v-model="form.causa" :options="CAUSAS" placeholder="Buscar causa…" required />
                            </div>
                            <div v-if="form.causa === 'Otra'">
                                <label class="label" for="f-causa-otra">Especifique la causa *</label>
                                <input id="f-causa-otra" v-model="form.causaOtra" required placeholder="Describa la causa" class="field" />
                            </div>
                            <div>
                                <label class="label" for="f-dano-porc">% de daño promedio del lote *</label>
                                <input id="f-dano-porc" v-model="form.danoPorc" type="number" min="0" max="100" step="1" required placeholder="0–100" class="field" />
                            </div>
                            <div>
                                <label class="label" for="f-rend">Rendimiento esperado sin daño</label>
                                <div class="flex gap-2">
                                    <input id="f-rend" v-model="form.rendEsperado" type="number" min="0" step="0.01" placeholder="Ej. 8.5" class="field" />
                                    <div class="w-28 flex-shrink-0">
                                        <SelectField v-model="form.unidad" :options="['t/ha', 'kg/ha']" />
                                    </div>
                                </div>
                                <p class="hint">Mantenga la misma unidad en todo el relevamiento.</p>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="f-perdida">Pérdida de rendimiento estimada</label>
                                <input id="f-perdida" v-model="form.perdidaEstimada" type="number" min="0" step="0.01" placeholder="Se calcula automáticamente" class="field" @input="perdidaManual = true" />
                                <p class="hint">= Sup. afectada × Rend. esperado × (% daño ÷ 100). Puede editarla.</p>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Columna derecha: evidencia, resumen, acciones -->
                <div class="space-y-[18px] min-w-0 lg:sticky lg:top-28">
                    <section id="sec-4" class="glass p-5 sm:p-6 scroll-mt-28">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="icon-badge" style="background: var(--green);"><CameraIcon class="w-6 h-6" /></span>
                            <div class="min-w-0 flex-1">
                                <h2 class="section-title">4. Evidencia fotográfica</h2>
                                <p class="section-desc">Adjunta fotografías del daño observado.</p>
                            </div>
                            <span class="text-sm font-semibold self-start" style="color: var(--muted);">{{ fotos.length }}/{{ MAX_FOTOS }}</span>
                        </div>
                        <input ref="fotosInput" type="file" accept="image/*" multiple capture="environment" class="hidden" @change="onFotos" />
                        <button type="button" class="w-full rounded-[18px] py-6 px-4 flex flex-col items-center gap-1 transition-colors hover:bg-white/70" style="border: 2px dashed #7CCBA0; background: rgba(255,255,255,.5);" @click="fotosInput.click()">
                            <CameraIcon class="w-8 h-8" style="color: var(--green-dark);" />
                            <span class="font-bold" style="color: var(--navy);">Agregar fotografías</span>
                            <span class="text-[13px]" style="color: var(--muted);">Toca para capturar o seleccionar</span>
                        </button>
                        <div v-if="fotos.length" class="grid grid-cols-3 gap-2.5 mt-3">
                            <div v-for="(f, i) in fotos" :key="f.url" class="relative aspect-square">
                                <img :src="f.url" :title="f.nombre" class="w-full h-full object-cover rounded-[14px]" />
                                <button type="button" class="absolute top-1 right-1 w-7 h-7 rounded-full flex items-center justify-center text-white" style="background: rgba(11,31,77,.72);" title="Quitar foto" @click="quitarFoto(i)">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <div class="card-inner mt-4 p-4">
                            <label class="label flex items-center gap-2" for="f-notas" style="color: var(--navy); font-weight: 700;">
                                <DocumentTextIcon class="w-5 h-5" /> Descripción del daño
                            </label>
                            <textarea id="f-notas" v-model="form.notas" rows="4" placeholder="Describe la situación observada en el cultivo…" class="field" style="resize: vertical;"></textarea>
                        </div>
                    </section>

                    <section class="glass p-5 sm:p-6" aria-live="polite">
                        <h2 class="flex items-center gap-2.5 text-lg font-bold mb-3" style="color: var(--navy);">
                            <span class="icon-badge !w-9 !h-9" style="background: var(--green);"><CheckIcon class="w-5 h-5" /></span>
                            Resumen del registro
                        </h2>
                        <dl class="text-[14px]">
                            <div v-for="r in resumen" :key="r.label" class="flex items-start gap-3 py-2.5 border-t first:border-t-0" style="border-color: rgba(96,112,141,.16);">
                                <dt class="w-[38%] flex-shrink-0 flex items-center gap-2" style="color: var(--muted);">
                                    <component :is="r.icon" class="w-4 h-4 flex-shrink-0" :style="{ color: r.color }" /> {{ r.label }}
                                </dt>
                                <dd class="font-semibold min-w-0 break-words" style="color: var(--navy);">{{ r.value || '—' }}</dd>
                            </div>
                        </dl>
                    </section>

                    <!-- Acciones (fijas abajo en móvil) -->
                    <div class="fixed bottom-0 inset-x-0 z-40 p-3 lg:static lg:p-0" style="background: linear-gradient(180deg, rgba(238,246,240,0), rgba(238,246,240,.95) 40%);">
                        <div class="grid grid-cols-2 gap-3 max-w-[620px] mx-auto lg:max-w-none">
                            <button type="button" class="btn btn-outline" @click="limpiar"><ArrowPathIcon class="w-5 h-5" /> Limpiar</button>
                            <button type="submit" class="btn btn-primary" :disabled="guardando">
                                <PaperAirplaneIcon class="w-5 h-5" /> {{ guardando ? 'Enviando…' : 'Enviar registro' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue';
import SelectField from '../../components/ui/SelectField.vue';
import SearchSelect from '../../components/ui/SearchSelect.vue';
import danoCultivoService from '../../services/danoCultivoService';
import { CULTIVOS, CAUSAS_DANO } from '../../data/catalogos';
import { DEPARTAMENTOS, MUNICIPIOS } from '../../data/guatemala';
import { toastSuccess, toastError, toastInfo, alertError } from '../../utils/alerts';
import {
    MapPinIcon, ViewfinderCircleIcon, CameraIcon, PlusIcon, TrashIcon, XMarkIcon, CheckIcon,
    ArrowPathIcon, ArrowDownTrayIcon, ExclamationTriangleIcon, ChartBarIcon,
    DocumentTextIcon, PaperAirplaneIcon, BeakerIcon,
} from '@heroicons/vue/24/outline';

const ETAPAS = ['Siembra / Emergencia', 'Vegetativo', 'Floración', 'Llenado de grano / Fructificación', 'Madurez', 'Cosechado'];
const CAUSAS = [...CAUSAS_DANO, 'Otra'];

const hoy = () => new Date().toISOString().slice(0, 10);
let uidSeq = 0;
const nuevoAsociado = () => ({ uid: ++uidSeq, cultivo: '', etapa: '', areaLote: '', areaAfectada: '' });
const formVacio = () => ({
    fecha: hoy(), tecnico: '', departamento: '', municipio: '', centroPoblado: '', agricultorRua: '', codigoRua: '',
    lote: '', cultivo: '', etapa: '', asociados: [],
    causa: '', causaOtra: '', areaLote: '', areaAfectada: '', danoPorc: '', rendEsperado: '',
    unidad: 't/ha', perdidaEstimada: '', notas: '',
});

const form = reactive(formVacio());
const gps = reactive({ lat: null, lon: null, acc: null });
const gpsBuscando = ref(false);
const fotos = ref([]); // { nombre, url }
const fotosInput = ref(null);
const MAX_FOTOS = 10;
const perdidaManual = ref(false);
const guardando = ref(false);

const municipios = computed(() => MUNICIPIOS[form.departamento] || []);
// Al cambiar de departamento, el municipio elegido deja de ser válido.
watch(() => form.departamento, () => { form.municipio = ''; });
watch(() => form.agricultorRua, (v) => { if (v !== 'Sí') form.codigoRua = ''; });

// Pérdida = ha afectadas × rendimiento × %daño, salvo que el usuario la haya editado.
watch(() => [form.areaAfectada, form.rendEsperado, form.danoPorc], ([sup, rend, porc]) => {
    if (perdidaManual.value) return;
    const s = parseFloat(sup), r = parseFloat(rend), p = parseFloat(porc);
    form.perdidaEstimada = s > 0 && r > 0 && p > 0 ? (s * r * p / 100).toFixed(2) : '';
});

function agregarAsociado() { form.asociados.push(nuevoAsociado()); }
function quitarAsociado(i) { form.asociados.splice(i, 1); }

function capturarGPS() {
    if (!navigator.geolocation) return alertError('Este navegador no soporta geolocalización.');
    gpsBuscando.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            gps.lat = pos.coords.latitude.toFixed(6);
            gps.lon = pos.coords.longitude.toFixed(6);
            gps.acc = Math.round(pos.coords.accuracy);
            gpsBuscando.value = false;
        },
        (err) => {
            gpsBuscando.value = false;
            alertError(err.message, 'No se pudo obtener la ubicación');
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
    );
}

function onFotos(e) {
    for (const f of e.target.files) {
        if (fotos.value.length >= MAX_FOTOS) {
            toastInfo(`Máximo ${MAX_FOTOS} fotos por registro.`);
            break;
        }
        fotos.value.push({ nombre: f.name, file: f, url: URL.createObjectURL(f) });
    }
    e.target.value = '';
}
function quitarFoto(i) {
    URL.revokeObjectURL(fotos.value[i].url);
    fotos.value.splice(i, 1);
}
function liberarFotos() {
    fotos.value.forEach((f) => URL.revokeObjectURL(f.url));
    fotos.value = [];
}

function limpiar() {
    Object.assign(form, formVacio());
    perdidaManual.value = false;
    Object.assign(gps, { lat: null, lon: null, acc: null });
    liberarFotos();
}

async function guardar() {
    if (!form.departamento || !MUNICIPIOS[form.departamento]?.includes(form.municipio)) return alertError('Selecciona el departamento y el municipio de la lista.', 'Falta un dato');
    if (!form.agricultorRua) return alertError('Indica si el agricultor está en RUA.', 'Falta un dato');
    if (!CULTIVOS.includes(form.cultivo)) return alertError('Selecciona el cultivo de la lista.', 'Falta un dato');
    if (form.asociados.some((a) => !CULTIVOS.includes(a.cultivo))) return alertError('Selecciona el cultivo asociado de la lista.', 'Falta un dato');
    if (!CAUSAS.includes(form.causa)) return alertError('Selecciona la causa del daño de la lista.', 'Falta un dato');
    if (gps.lat === null) return alertError('Captura tu ubicación actual con el botón "Usar mi ubicación actual".', 'Ubicación obligatoria');

    const reg = {
        fecha: form.fecha,
        tecnico: form.tecnico,
        departamento: form.departamento,
        municipio: form.municipio,
        centroPoblado: form.centroPoblado,
        agricultorRua: form.agricultorRua === 'Sí',
        codigoRua: form.agricultorRua === 'Sí' ? form.codigoRua : '',
        lote: form.lote,
        cultivo: form.cultivo,
        etapa: form.etapa,
        asociados: form.asociados.map(({ uid, ...a }) => a),
        causa: form.causa,
        causaOtra: form.causa === 'Otra' ? form.causaOtra : '',
        areaLote: form.areaLote,
        areaAfectada: form.areaAfectada,
        danoPorc: form.danoPorc,
        rendEsperado: form.rendEsperado,
        unidad: form.unidad,
        perdidaEstimada: form.perdidaEstimada,
        gpsLat: gps.lat,
        gpsLon: gps.lon,
        gpsPrecision: gps.acc,
        notas: form.notas,
    };
    guardando.value = true;
    try {
        const creado = await danoCultivoService.create(reg, fotos.value.map((f) => f.file));
        limpiar();
        if (creado.fotosError) toastInfo(`Registro guardado, pero las fotos no se subieron: ${creado.fotosError}`);
        else toastSuccess('Registro guardado. Puedes verlo en el apartado Registros.');
    } catch (e) {
        toastError(e.message);
    } finally {
        guardando.value = false;
    }
}

onBeforeUnmount(liberarFotos);

// ---------- Solo presentación: progreso, resumen y panel informativo ----------
const features = [
    { icon: DocumentTextIcon, color: '#079447', text: 'Registro sencillo y rápido' },
    { icon: MapPinIcon, color: '#1677FF', text: 'Georreferenciación con GPS' },
    { icon: ChartBarIcon, color: '#FF7A00', text: 'Apoyo a la toma de decisiones' },
    { icon: ArrowDownTrayIcon, color: '#7C3AED', text: 'Exporta los registros a Excel (CSV)' },
];

const steps = computed(() => [
    { id: 1, label: 'Ubicación', icon: MapPinIcon, hecho: !!(form.fecha && form.tecnico && form.lote && form.departamento && form.municipio && form.centroPoblado && form.agricultorRua && gps.lat !== null) },
    { id: 2, label: 'Cultivo', icon: BeakerIcon, hecho: !!(form.cultivo && form.areaAfectada !== '') },
    { id: 3, label: 'Daños', icon: ExclamationTriangleIcon, hecho: !!(form.causa && form.danoPorc !== '') },
    { id: 4, label: 'Evidencia', icon: CameraIcon, hecho: fotos.value.length > 0 },
]);
// Paso activo = primer paso pendiente (o el último si todos están completos).
const pasoActivo = computed(() => {
    const i = steps.value.findIndex((s) => !s.hecho);
    return i === -1 ? steps.value.length - 1 : i;
});
function irA(id) {
    document.getElementById(`sec-${id}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const resumen = computed(() => {
    const causa = form.causa === 'Otra' ? (form.causaOtra || 'Otra') : form.causa;
    const cultivos = [form.cultivo, ...form.asociados.map((a) => a.cultivo)].filter(Boolean).join(', ');
    return [
        { label: 'Lote', icon: MapPinIcon, color: '#1677FF', value: form.lote },
        { label: 'Municipio', icon: MapPinIcon, color: '#1677FF', value: form.municipio ? `${form.municipio}, ${form.departamento}` : '' },
        { label: 'GPS', icon: ViewfinderCircleIcon, color: '#1677FF', value: gps.lat ? `${gps.lat}, ${gps.lon}` : '' },
        { label: 'Cultivo', icon: BeakerIcon, color: '#079447', value: cultivos },
        { label: 'Área afectada', icon: ChartBarIcon, color: '#079447', value: form.areaAfectada !== '' ? `${form.areaAfectada} ha` : '' },
        { label: 'Causa', icon: ExclamationTriangleIcon, color: '#FF7A00', value: causa },
        { label: '% de daño', icon: ChartBarIcon, color: '#7C3AED', value: form.danoPorc !== '' ? `${form.danoPorc}%` : '' },
        { label: 'Fotografías', icon: CameraIcon, color: '#079447', value: fotos.value.length ? String(fotos.value.length) : '' },
    ];
});
</script>
