<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Nuevo registro de afectación</h2>
                    <p>Completa la información del levantamiento en campo.</p>
                </div>
                <button type="button" class="btn" @click="limpiar"><ArrowPathIcon class="w-5 h-5" />Limpiar formulario</button>
            </div>

            <div class="page-tabs" role="tablist" aria-label="Pasos del registro">
                <button v-for="(st, i) in steps" :key="st.id" type="button" class="step" :class="{ active: i === paso, done: st.hecho && i !== paso }" role="tab" :aria-selected="i === paso" @click="irA(i)">
                    <span><CheckIcon v-if="st.hecho && i !== paso" class="w-4 h-4" /><template v-else>0{{ st.id }}</template></span>
                    <div><b>{{ st.label }}</b><small>{{ st.sub }}</small></div>
                </button>
            </div>

            <div class="form-grid">
                <form ref="formEl" class="panel form-panel" novalidate @submit.prevent="enviar">
                    <!-- 1. Ubicación -->
                    <div v-show="paso === 0" data-formstep="0">
                        <div class="section-head">
                            <div class="round-icon"><MapPinIcon class="w-5 h-5" /></div>
                            <div>
                                <h2>Ubicación del registro</h2>
                                <p>Identifica al productor y el lugar de la afectación.</p>
                            </div>
                        </div>
                        <div class="fieldgrid">
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
                                <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Agricultor en RUA">
                                    <button
                                        v-for="op in ['Sí', 'No']" :key="op" type="button" role="radio"
                                        :aria-checked="form.agricultorRua === op"
                                        class="btn" :class="form.agricultorRua === op ? 'btn-primary' : 'btn-outline'"
                                        @click="form.agricultorRua = op"
                                    >{{ op }}</button>
                                </div>
                            </div>
                            <div v-if="form.agricultorRua === 'Sí'">
                                <label class="label" for="f-rua">Código de RUA *</label>
                                <input id="f-rua" v-model="form.codigoRua" required placeholder="Escriba el código de RUA" class="field" />
                            </div>
                            <div class="full">
                                <label class="label" for="f-lote">Lote / parcela *</label>
                                <input id="f-lote" v-model="form.lote" required placeholder="Ej. Lote 3 - El Potrero" class="field" />
                            </div>
                        </div>

                        <div class="gps-box">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2"><ViewfinderCircleIcon class="w-5 h-5" style="color: var(--green);" /><b class="text-[12px]">Ubicación GPS *</b></div>
                                <span class="badge" :class="gps.lat !== null ? '' : 'gray'">{{ gps.lat !== null ? 'Capturada' : 'Sin capturar' }}</span>
                            </div>
                            <div class="gps-values">
                                <div><small>Latitud</small><b>{{ gps.lat ?? '—' }}</b></div>
                                <div><small>Longitud</small><b>{{ gps.lon ?? '—' }}</b></div>
                                <div><small>Precisión</small><b>{{ gps.acc ?? '—' }} m</b></div>
                            </div>
                            <button type="button" class="btn w-full" :disabled="gpsBuscando" @click="capturarGPS">
                                <ViewfinderCircleIcon class="w-5 h-5" />
                                {{ gpsBuscando ? 'Buscando…' : 'Usar mi ubicación actual' }}
                            </button>
                            <p class="hint">En campo, espere a que la precisión sea menor a 10 m. La ubicación actual es obligatoria.</p>
                        </div>
                    </div>

                    <!-- 2. Cultivo -->
                    <div v-show="paso === 1" data-formstep="1">
                        <div class="section-head">
                            <div class="round-icon"><SparklesIcon class="w-5 h-5" /></div>
                            <div class="min-w-0 flex-1">
                                <h2>Información del cultivo</h2>
                                <p>Registra el cultivo y la superficie observada.</p>
                            </div>
                            <button type="button" class="btn btn-sm flex-shrink-0" @click="agregarAsociado"><PlusIcon class="w-5 h-5" />Agregar cultivo asociado</button>
                        </div>
                        <div class="fieldgrid">
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

                        <div v-for="(a, i) in form.asociados" :key="a.uid" class="card-inner mt-5 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[13px] font-bold">Cultivo asociado {{ i + 1 }}</span>
                                <button type="button" class="btn danger btn-sm" @click="quitarAsociado(i)"><TrashIcon class="w-4 h-4" />Quitar</button>
                            </div>
                            <div class="fieldgrid">
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
                    </div>

                    <!-- 3. Daños -->
                    <div v-show="paso === 2" data-formstep="2">
                        <div class="section-head">
                            <div class="round-icon" style="background: #fff0d8; color: #af7d2b;"><ExclamationTriangleIcon class="w-5 h-5" /></div>
                            <div>
                                <h2>Evaluación de daños</h2>
                                <p>Causa, porcentaje de daño y pérdida estimada.</p>
                            </div>
                        </div>
                        <div class="fieldgrid">
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
                            <div class="full">
                                <label class="label" for="f-perdida">Pérdida de rendimiento estimada</label>
                                <input id="f-perdida" v-model="form.perdidaEstimada" type="number" min="0" step="0.01" placeholder="Se calcula automáticamente" class="field" @input="perdidaManual = true" />
                                <p class="hint">= Sup. afectada × Rend. esperado × (% daño ÷ 100). Puede editarla.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Evidencia -->
                    <div v-show="paso === 3" data-formstep="3">
                        <div class="section-head">
                            <div class="round-icon"><CameraIcon class="w-5 h-5" /></div>
                            <div class="min-w-0 flex-1">
                                <h2>Evidencia del daño</h2>
                                <p>Agrega fotografías y describe lo observado.</p>
                            </div>
                            <span class="text-[12px] font-semibold" style="color: var(--muted);">{{ fotos.length }}/{{ MAX_FOTOS }}</span>
                        </div>
                        <input ref="fotosInput" type="file" accept="image/*" multiple capture="environment" class="hidden" @change="onFotos" />
                        <button type="button" class="uploadbox" @click="fotosInput.click()">
                            <CameraIcon />
                            <b>Agregar fotografías</b>
                            <small>Toca para capturar o seleccionar · hasta {{ MAX_FOTOS }} imágenes</small>
                        </button>
                        <div v-if="fotos.length" class="grid grid-cols-3 sm:grid-cols-5 gap-2.5 mt-3.5">
                            <div v-for="(f, i) in fotos" :key="f.url" class="relative aspect-square">
                                <img :src="f.url" :title="f.nombre" class="w-full h-full object-cover rounded-[10px]" />
                                <button type="button" class="absolute top-1 right-1 w-6 h-6 rounded-full flex items-center justify-center bg-white" style="color: #a03737;" title="Quitar foto" @click="quitarFoto(i)">
                                    <XMarkIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div class="mt-5">
                            <label class="label" for="f-notas">Descripción del daño</label>
                            <textarea id="f-notas" v-model="form.notas" rows="5" placeholder="Describe los daños observados, las condiciones del cultivo y cualquier información relevante…" class="field" style="resize: vertical;"></textarea>
                        </div>
                    </div>

                    <div class="form-buttons">
                        <button type="button" class="btn" :disabled="paso === 0" @click="irA(paso - 1)"><ArrowLeftIcon class="w-5 h-5" />Anterior</button>
                        <button v-if="paso < 3" type="button" class="btn btn-primary" @click="siguiente">Continuar<ArrowRightIcon class="w-5 h-5" /></button>
                        <button v-else type="submit" class="btn btn-primary" :disabled="guardando">
                            <PaperAirplaneIcon class="w-5 h-5" />{{ guardando ? 'Enviando…' : 'Enviar registro' }}
                        </button>
                    </div>
                </form>

                <aside class="summary">
                    <div class="panel" aria-live="polite">
                        <div class="flex items-center gap-2.5">
                            <DocumentTextIcon class="w-5 h-5" style="color: var(--green);" />
                            <h3 class="text-[14px] font-bold">Resumen del registro</h3>
                        </div>
                        <div class="progressline"><i :style="{ width: ((paso + 1) / 4 * 100) + '%' }"></i></div>
                        <small class="text-[9px]" style="color: #829687;">Paso {{ paso + 1 }} de 4</small>
                        <div class="summary-list">
                            <div v-for="r in resumen" :key="r.label"><span>{{ r.label }}</span><b>{{ r.value || '—' }}</b></div>
                        </div>
                        <div class="summary-score">
                            <div class="flex items-center justify-between"><small>ÁREA AFECTADA</small><ChartBarIcon class="w-5 h-5" style="color: #3b9165;" /></div>
                            <strong>{{ form.areaAfectada !== '' ? form.areaAfectada : 0 }} <span class="text-[13px]">ha</span></strong>
                            <small>Superficie registrada en hectáreas</small>
                        </div>
                    </div>
                    <div class="field-note">
                        <SparklesIcon />
                        <h3>Un buen registro hace la diferencia.</h3>
                        <p>Verifica la ubicación, conserva la misma unidad de medida y toma fotografías claras del daño.</p>
                    </div>
                </aside>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick, onBeforeUnmount } from 'vue';
import SelectField from '../../components/ui/SelectField.vue';
import SearchSelect from '../../components/ui/SearchSelect.vue';
import danoCultivoService from '../../services/danoCultivoService';
import { CULTIVOS, CAUSAS_DANO } from '../../data/catalogos';
import { DEPARTAMENTOS, MUNICIPIOS } from '../../data/guatemala';
import { toastSuccess, toastError, toastInfo, alertError } from '../../utils/alerts';
import {
    MapPinIcon, ViewfinderCircleIcon, CameraIcon, PlusIcon, TrashIcon, XMarkIcon, CheckIcon,
    ArrowPathIcon, ArrowDownTrayIcon, ExclamationTriangleIcon, ChartBarIcon,
    DocumentTextIcon, PaperAirplaneIcon, BeakerIcon, ArrowRightIcon, ArrowLeftIcon, SparklesIcon,
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
    paso.value = 0;
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
    { id: 1, sub: 'Datos del levantamiento', label: 'Ubicación', icon: MapPinIcon, hecho: !!(form.fecha && form.tecnico && form.lote && form.departamento && form.municipio && form.centroPoblado && form.agricultorRua && gps.lat !== null) },
    { id: 2, sub: 'Superficie y etapa', label: 'Cultivo', icon: BeakerIcon, hecho: !!(form.cultivo && form.areaAfectada !== '') },
    { id: 3, sub: 'Causa e impacto', label: 'Daños', icon: ExclamationTriangleIcon, hecho: !!(form.causa && form.danoPorc !== '') },
    { id: 4, sub: 'Fotografías y notas', label: 'Evidencia', icon: CameraIcon, hecho: fotos.value.length > 0 },
]);
// Navegación por pasos (una sección visible a la vez).
const paso = ref(0);
const formEl = ref(null);
const ULTIMO = 3;
const pasoEl = (i) => formEl.value?.querySelector(`[data-formstep="${i}"]`);
const invalido = (i) => [...(pasoEl(i)?.querySelectorAll('input, select, textarea') || [])].find((el) => !el.checkValidity());
function irA(i) { paso.value = Math.max(0, Math.min(ULTIMO, i)); window.scrollTo({ top: 0, behavior: 'smooth' }); }
function siguiente() {
    const el = invalido(paso.value);
    if (el) return el.reportValidity();
    if (paso.value === 0 && gps.lat === null) return alertError('Captura tu ubicación actual con el botón "Usar mi ubicación actual".', 'Ubicación obligatoria');
    irA(paso.value + 1);
}
// Antes de enviar se revisan todos los pasos; si falta algo se vuelve a ese paso.
async function enviar() {
    for (let i = 0; i <= ULTIMO; i++) {
        const el = invalido(i);
        if (el) {
            paso.value = i;
            await nextTick();
            return el.reportValidity();
        }
    }
    guardar();
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
