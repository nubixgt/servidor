<template>
    <div class="space-y-6 max-w-[1600px] mx-auto p-4 sm:p-8">
        <div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">Daños a cultivos</h2>
            <p class="text-xs sm:text-sm text-slate-600 mt-0.5">Levantamiento de campo de daños por granizo, helada, plagas y otras causas. Los campos con * son obligatorios.</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <span class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Registros</span>
                <div class="font-mono text-3xl font-bold text-slate-800 mt-2">{{ registros.length }}</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <span class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Superficie afectada</span>
                <div class="font-mono text-3xl font-bold text-slate-800 mt-2">{{ totalHa.toFixed(2) }} <span class="text-sm font-semibold text-slate-500">ha</span></div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <span class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">Pérdida estimada acumulada</span>
                <div class="font-mono text-3xl font-bold text-slate-800 mt-2">{{ totalPerdida.toFixed(2) }}</div>
            </div>
        </div>

        <!-- Formulario -->
        <form class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6" @submit.prevent="guardar">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-200 pb-3">
                <ClipboardList class="w-5 h-5 text-emerald-600" />
                <span>Nuevo registro de daño</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label :class="lbl">Fecha de levantamiento *</label>
                    <input v-model="form.fecha" type="date" required :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Técnico / productor *</label>
                    <input v-model="form.tecnico" required placeholder="Ej. Juan Pérez" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Lote / parcela *</label>
                    <input v-model="form.lote" required placeholder="Ej. Lote 3 - El Potrero" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Cultivo *</label>
                    <SelectField v-model="form.cultivo" :options="CULTIVOS" placeholder="Seleccione..." />
                </div>
                <div v-if="form.cultivo === 'Otro'">
                    <label :class="lbl">Especifique el cultivo *</label>
                    <input v-model="form.cultivoOtro" required placeholder="Escriba el cultivo" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Estado fenológico</label>
                    <SelectField v-model="form.etapa" :options="ETAPAS" placeholder="Seleccione..." />
                </div>
                <div>
                    <label :class="lbl">Causa del daño *</label>
                    <SelectField v-model="form.causa" :options="CAUSAS" placeholder="Seleccione..." />
                </div>
                <div v-if="form.causa === 'Otra'">
                    <label :class="lbl">Especifique la causa *</label>
                    <input v-model="form.causaOtra" required placeholder="Describa la causa" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Superficie total del lote (ha)</label>
                    <input v-model="form.areaLote" type="number" min="0" step="0.01" placeholder="Ej. 25.5" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Superficie afectada (ha) *</label>
                    <input v-model="form.areaAfectada" type="number" min="0" step="0.01" required placeholder="Ej. 8.2" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">% de daño promedio del lote *</label>
                    <input v-model="form.danoPorc" type="number" min="0" max="100" step="1" required placeholder="0–100" :class="inp" />
                </div>
                <div>
                    <label :class="lbl">Rendimiento esperado sin daño</label>
                    <div class="flex gap-2">
                        <input v-model="form.rendEsperado" type="number" min="0" step="0.01" placeholder="Ej. 8.5" :class="inp" />
                        <div class="w-28 flex-shrink-0">
                            <SelectField v-model="form.unidad" :options="['t/ha', 'kg/ha']" />
                        </div>
                    </div>
                    <p class="text-[10.5px] text-slate-500 mt-1">Mantenga la misma unidad en todo el relevamiento.</p>
                </div>
                <div>
                    <label :class="lbl">Pérdida de rendimiento estimada</label>
                    <input v-model="form.perdidaEstimada" type="number" min="0" step="0.01" placeholder="Se calcula automáticamente" :class="inp" @input="perdidaManual = true" />
                    <p class="text-[10.5px] text-slate-500 mt-1">= Sup. afectada × Rend. esperado × (% daño ÷ 100). Puede editarla.</p>
                </div>
            </div>

            <!-- Puntos de muestreo -->
            <div class="space-y-3">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-200 pb-3">
                    <MapPin class="w-5 h-5 text-blue-600" />
                    <span>Puntos de muestreo — severidad</span>
                </h3>
                <div v-for="(p, i) in form.puntos" :key="p.uid" class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-800">Punto de muestreo {{ i + 1 }}</span>
                        <button v-if="form.puntos.length > 1" type="button" class="text-[11px] font-semibold text-red-600 flex items-center gap-1" @click="quitarPunto(i)">
                            <Trash2 class="w-3.5 h-3.5" /> Quitar
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <div>
                            <label :class="lbl">Ubicación / referencia</label>
                            <input v-model="p.ref" placeholder="Ej. sector norte" :class="inp" />
                        </div>
                        <div>
                            <label :class="lbl">% plantas afectadas</label>
                            <input v-model="p.incidencia" type="number" min="0" max="100" placeholder="0–100" :class="inp" />
                        </div>
                        <div>
                            <label :class="lbl">Severidad</label>
                            <SelectField v-model="p.severidad" :options="SEVERIDADES" placeholder="—" />
                        </div>
                        <div>
                            <label :class="lbl">Observación</label>
                            <input v-model="p.obs" placeholder="Ej. rotura de tallo" :class="inp" />
                        </div>
                    </div>
                </div>
                <button type="button" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold px-4 py-2 rounded-xl flex items-center gap-1.5" @click="agregarPunto">
                    <Plus class="w-4 h-4" /> Agregar punto de muestreo
                </button>
            </div>

            <!-- GPS -->
            <div class="space-y-3">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-200 pb-3">
                    <Crosshair class="w-5 h-5 text-emerald-600" />
                    <span>Ubicación GPS</span>
                </h3>
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-wrap items-center gap-x-6 gap-y-3 text-xs text-slate-600">
                    <span>Latitud: <b class="font-mono text-slate-800">{{ gps.lat ?? '—' }}</b></span>
                    <span>Longitud: <b class="font-mono text-slate-800">{{ gps.lon ?? '—' }}</b></span>
                    <span>Precisión: <b class="font-mono text-slate-800">{{ gps.acc ?? '—' }}</b> m</span>
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-60 text-xs font-semibold px-4 py-2 rounded-xl flex items-center gap-1.5" :disabled="gpsBuscando" @click="capturarGPS">
                        <Crosshair class="w-4 h-4" /> {{ gpsBuscando ? 'Buscando…' : 'Capturar ubicación actual' }}
                    </button>
                    <span class="text-[10.5px] text-slate-500">En campo, espere a que la precisión sea menor a 10 m.</span>
                </div>
            </div>

            <!-- Fotos -->
            <div class="space-y-3">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 border-b border-slate-200 pb-3">
                    <Camera class="w-5 h-5 text-amber-600" />
                    <span>Fotografías</span>
                </h3>
                <input ref="fotosInput" type="file" accept="image/*" multiple capture="environment" class="hidden" @change="onFotos" />
                <button type="button" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold px-4 py-2 rounded-xl flex items-center gap-1.5" @click="fotosInput.click()">
                    <Camera class="w-4 h-4" /> Tomar / adjuntar fotos
                </button>
                <div v-if="fotos.length" class="flex flex-wrap gap-2">
                    <div v-for="(f, i) in fotos" :key="f.url" class="relative">
                        <img :src="f.url" :title="f.nombre" class="w-16 h-16 object-cover rounded-lg" style="border: 1px solid #e2e8f0;" />
                        <button type="button" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center" title="Quitar" @click="quitarFoto(i)">
                            <X class="w-3 h-3" />
                        </button>
                    </div>
                </div>
            </div>

            <div>
                <label :class="lbl">Observaciones / descripción del daño</label>
                <textarea v-model="form.notas" rows="3" placeholder="Ej. Granizo de 2 cm el 15/09, daño en hojas y tallo en el sector norte del lote…" :class="inp"></textarea>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
                <button type="submit" :disabled="guardando" class="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-60 text-xs font-semibold px-5 py-2.5 rounded-xl flex items-center gap-1.5">
                    <Save class="w-4 h-4" /> {{ guardando ? 'Guardando…' : 'Guardar registro' }}
                </button>
                <button type="button" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold px-5 py-2.5 rounded-xl flex items-center gap-1.5" @click="limpiar">
                    <Eraser class="w-4 h-4" /> Limpiar formulario
                </button>
            </div>
        </form>

        <!-- Registros -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <Table2 class="w-5 h-5 text-emerald-600" />
                    <span>Registros guardados</span>
                </h3>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-60 text-xs font-semibold px-4 py-2 rounded-xl flex items-center gap-1.5" @click="exportarCSV">
                        <Download class="w-4 h-4" /> Exportar CSV
                    </button>
                </div>
            </div>

            <div v-if="loading" class="py-10 text-center text-xs text-slate-500">Cargando…</div>
            <div v-else-if="!registros.length" class="py-10 text-center text-xs text-slate-500">Aún no hay registros guardados.</div>
            <div v-else class="overflow-x-auto">
                <table class="tabla w-full">
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Lote</th><th>Cultivo</th><th>Etapa</th><th>Causa</th>
                            <th>ha afect.</th><th>% daño</th><th>Pérdida est.</th><th>Severidad</th>
                            <th>GPS</th><th>Fotos</th><th>Técnico</th><th>Notas</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in registros" :key="r.id">
                            <td class="whitespace-nowrap">{{ r.fecha }}</td>
                            <td>{{ r.lote }}</td>
                            <td>{{ r.cultivo }}</td>
                            <td>{{ r.etapa || '—' }}</td>
                            <td>{{ r.causa }}</td>
                            <td>{{ r.areaAfectada }}</td>
                            <td>{{ r.danoPorc }}%</td>
                            <td class="whitespace-nowrap">{{ r.perdidaEstimada ? `${r.perdidaEstimada} ${r.unidad}` : '—' }}</td>
                            <td class="whitespace-nowrap">
                                <template v-if="sevMax(r) !== null">
                                    <span class="badge" :class="SEV_CLASE[sevMax(r)]">{{ SEV_LABEL[sevMax(r)] }}</span>
                                    <span class="text-[10.5px] text-slate-500 ml-1">({{ r.puntos.filter((p) => p.severidad !== '').length }} pts)</span>
                                </template>
                                <template v-else>—</template>
                            </td>
                            <td class="whitespace-nowrap font-mono text-[11px]">{{ r.gps || '—' }}</td>
                            <td>{{ r.fotos?.length || '—' }}</td>
                            <td>{{ r.tecnico }}</td>
                            <td class="max-w-[220px] truncate" :title="r.notas">{{ r.notas || '—' }}</td>
                            <td>
                                <button type="button" class="p-1.5 rounded-lg hover:bg-red-50" title="Eliminar" @click="eliminar(r)">
                                    <Trash2 class="w-4 h-4 text-red-600" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import SelectField from '../../components/ui/SelectField.vue';
import danoCultivoService from '../../services/danoCultivoService';
import { toastSuccess, toastError, toastInfo, alertError, confirmDialog } from '../../utils/alerts';
import {
    ClipboardDocumentListIcon as ClipboardList, MapPinIcon as MapPin, ViewfinderCircleIcon as Crosshair,
    CameraIcon as Camera, PlusIcon as Plus, TrashIcon as Trash2, XMarkIcon as X, CheckIcon as Save,
    ArrowPathIcon as Eraser, TableCellsIcon as Table2, ArrowDownTrayIcon as Download,
} from '@heroicons/vue/24/outline';

const lbl = 'text-xs font-medium text-slate-600 block mb-1';
const inp = 'w-full bg-white border border-slate-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl p-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none transition-colors';

const CULTIVOS = [
    'Maíz', 'Frijol', 'Arroz', 'Sorgo', 'Trigo', 'Papa', 'Tomate', 'Cebolla', 'Chile', 'Café',
    'Caña de azúcar', 'Banano / Plátano', 'Cardamomo', 'Palma africana', 'Hortalizas', 'Frutales', 'Pasturas', 'Otro',
];
const ETAPAS = ['Siembra / Emergencia', 'Vegetativo', 'Floración', 'Llenado de grano / Fructificación', 'Madurez', 'Cosechado'];
const CAUSAS = [
    'Granizo', 'Helada', 'Sequía / Estrés hídrico', 'Inundación / Exceso de lluvia', 'Viento / Tormenta',
    'Plaga (insectos)', 'Enfermedad', 'Fauna silvestre / Aves', 'Incendio', 'Malezas (competencia)',
    'Fitotoxicidad / Error de aplicación', 'Otra',
];
const SEVERIDADES = [
    { value: '0', label: '0 — Sin daño' },
    { value: '1', label: '1 — Leve' },
    { value: '2', label: '2 — Moderado' },
    { value: '3', label: '3 — Grave' },
];
const SEV_LABEL = { 0: 'Sin daño', 1: 'Leve', 2: 'Moderado', 3: 'Grave' };
const SEV_CLASE = { 0: 'ok', 1: 'ok', 2: 'mid', 3: 'high' };

const hoy = () => new Date().toISOString().slice(0, 10);
let uidSeq = 0;
const nuevoPunto = () => ({ uid: ++uidSeq, ref: '', incidencia: '', severidad: '', obs: '' });
const formVacio = () => ({
    fecha: hoy(), tecnico: '', lote: '', cultivo: '', cultivoOtro: '', etapa: '',
    causa: '', causaOtra: '', areaLote: '', areaAfectada: '', danoPorc: '', rendEsperado: '',
    unidad: 't/ha', perdidaEstimada: '', notas: '', puntos: [nuevoPunto()],
});

const form = reactive(formVacio());
const gps = reactive({ lat: null, lon: null, acc: null });
const gpsBuscando = ref(false);
const fotos = ref([]); // { nombre, url }
const fotosInput = ref(null);
const perdidaManual = ref(false);
const registros = ref([]);
const loading = ref(true);
const guardando = ref(false);

const totalHa = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.areaAfectada) || 0), 0));
const totalPerdida = computed(() => registros.value.reduce((s, r) => s + (parseFloat(r.perdidaEstimada) || 0), 0));

// Pérdida = ha afectadas × rendimiento × %daño, salvo que el usuario la haya editado.
watch(() => [form.areaAfectada, form.rendEsperado, form.danoPorc], ([sup, rend, porc]) => {
    if (perdidaManual.value) return;
    const s = parseFloat(sup), r = parseFloat(rend), p = parseFloat(porc);
    form.perdidaEstimada = s > 0 && r > 0 && p > 0 ? (s * r * p / 100).toFixed(2) : '';
});

function agregarPunto() { form.puntos.push(nuevoPunto()); }
function quitarPunto(i) { form.puntos.splice(i, 1); }

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
    for (const f of e.target.files) fotos.value.push({ nombre: f.name, file: f, url: URL.createObjectURL(f) });
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
    const reg = {
        fecha: form.fecha,
        tecnico: form.tecnico,
        lote: form.lote,
        cultivo: form.cultivo === 'Otro' ? (form.cultivoOtro || 'Otro') : form.cultivo,
        etapa: form.etapa,
        causa: form.causa === 'Otra' ? (form.causaOtra || 'Otra') : form.causa,
        areaLote: form.areaLote,
        areaAfectada: form.areaAfectada,
        danoPorc: form.danoPorc,
        rendEsperado: form.rendEsperado,
        unidad: form.unidad,
        perdidaEstimada: form.perdidaEstimada,
        puntos: form.puntos.map(({ uid, ...p }) => p),
        gpsLat: gps.lat,
        gpsLon: gps.lon,
        gpsPrecision: gps.acc,
        notas: form.notas,
    };
    if (!reg.cultivo) return alertError('Selecciona el cultivo.', 'Falta un dato');
    if (!reg.causa) return alertError('Selecciona la causa del daño.', 'Falta un dato');
    guardando.value = true;
    try {
        const creado = await danoCultivoService.create(reg, fotos.value.map((f) => f.file));
        registros.value = await danoCultivoService.list();
        limpiar();
        if (creado.fotosError) toastInfo(`Registro guardado, pero las fotos no se subieron: ${creado.fotosError}`);
        else toastSuccess(`Registro guardado (${registros.value.length} en total).`);
    } catch (e) {
        toastError(e.message);
    } finally {
        guardando.value = false;
    }
}

function sevMax(r) {
    const s = (r.puntos || []).filter((p) => p.severidad !== '').map((p) => parseInt(p.severidad, 10));
    return s.length ? Math.max(...s) : null;
}

async function eliminar(r) {
    if (!(await confirmDialog('Este registro se eliminará.', { title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await danoCultivoService.remove(r.id);
        registros.value = await danoCultivoService.list();
    } catch (e) {
        toastError(e.message);
    }
}

function exportarCSV() {
    if (!registros.value.length) return toastError('No hay registros para exportar.');
    const filas = [
        ['REPORTE DE DAÑOS A CULTIVOS'],
        ['Generado', new Date().toLocaleString('es')],
        [],
        ['N°', 'Fecha', 'Técnico/Productor', 'Lote/Parcela', 'Cultivo', 'Etapa fenológica', 'Causa del daño',
            'Sup. total lote (ha)', 'Sup. afectada (ha)', '% daño', 'Rend. esperado', 'Unidad rend.', 'Pérdida estimada',
            'Latitud, Longitud (precisión m)', 'N° puntos muestreo', 'Detalles de puntos (ref | % incidencia | severidad | obs)',
            'N° fotos', 'Nombres de fotos', 'Notas'],
    ];
    registros.value.forEach((r, i) => {
        const pts = (r.puntos || [])
            .filter((p) => p.ref || p.incidencia || p.severidad !== '' || p.obs)
            .map((p) => [p.ref, p.incidencia, p.severidad, p.obs].filter(Boolean).join(' | ')).join(' ;; ');
        filas.push([
            i + 1, r.fecha, r.tecnico, r.lote, r.cultivo, r.etapa, r.causa, r.areaLote, r.areaAfectada, r.danoPorc,
            r.rendEsperado, r.unidad, r.perdidaEstimada, r.gps, (r.puntos || []).length, pts,
            (r.fotos || []).length, (r.fotos || []).join(' ; '), r.notas,
        ]);
    });
    filas.push([], ['', '', '', 'TOTALES', '', '', '', '', totalHa.value.toFixed(2), '', '', '', totalPerdida.value.toFixed(2)]);

    const csv = '﻿' + filas.map((f) => f.map((c) => `"${String(c ?? '').replace(/"/g, '""')}"`).join(';')).join('\r\n');
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
    a.download = `danos_cultivos_${hoy()}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);
}

onMounted(async () => {
    try {
        registros.value = await danoCultivoService.list();
    } catch (e) {
        toastError(e.message);
    } finally {
        loading.value = false;
    }
});
onBeforeUnmount(liberarFotos);
</script>

<style scoped>
.tabla thead th { text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 600; padding: 10px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
.tabla tbody td { padding: 12px 10px; font-size: 12.5px; color: #475569; border-bottom: 1px solid #e2e8f0; }
.tabla tbody tr:last-child td { border-bottom: none; }
.badge { display: inline-flex; align-items: center; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 99px; }
.badge.ok { background: #dcfce7; color: #166534; }
.badge.mid { background: #fef3c7; color: #92400e; }
.badge.high { background: #fee2e2; color: #991b1b; }
</style>
