<template>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold">{{ isEditing ? 'Editar parcela' : 'Registrar nueva parcela' }}</h1>
                <p class="text-sm text-slate-500">Completa los datos técnicos de la parcela keyline. Los campos con * son obligatorios.</p>
            </div>
            <span v-if="parcela?.codigo" class="text-xs font-semibold bg-slate-100 text-slate-600 px-2 py-1 rounded">{{ parcela.codigo }}</span>
        </div>

        <div v-if="loadingParcela" class="py-12 text-center text-slate-400">Cargando…</div>

        <template v-else>
            <!-- Pasos -->
            <div class="flex gap-2 mb-6 flex-wrap">
                <button
                    v-for="(step, i) in STEPS"
                    :key="step.key"
                    type="button"
                    @click="stepIndex = i"
                    class="px-3 py-1.5 rounded-full text-sm font-medium border"
                    :class="i === stepIndex
                        ? 'bg-primary-500 text-white border-primary-500'
                        : i < stepIndex
                            ? 'bg-primary-50 text-primary-600 border-primary-200'
                            : 'bg-white text-slate-500 border-slate-200'"
                >
                    {{ step.icon }} {{ step.label }}
                </button>
            </div>

            <form @submit.prevent="submit" ref="formRef">
                <!-- Paso 1: Ubicación -->
                <div v-show="stepIndex === 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="field-label">Nombre de la parcela / finca / terreno *</label>
                        <input v-model="form.nombreParcela" required class="field-input" placeholder="Ej. Finca El Pinar" />
                    </div>
                    <div>
                        <label class="field-label">Departamento *</label>
                        <select v-model="form.departamento" required class="field-input">
                            <option value="">Seleccione...</option>
                            <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Municipio *</label>
                        <input v-model="form.municipio" required class="field-input" placeholder="Ej. Cobán" />
                    </div>
                    <div>
                        <label class="field-label">Aldea / comunidad</label>
                        <input v-model="form.comunidad" class="field-input" placeholder="Ej. Chisecito" />
                    </div>
                    <div>
                        <label class="field-label">Fecha de registro</label>
                        <input v-model="form.fechaRegistro" type="date" class="field-input" />
                    </div>
                    <div>
                        <label class="field-label">Productor / responsable</label>
                        <input v-model="form.propietario" class="field-input" placeholder="Nombre del responsable" />
                    </div>
                    <div>
                        <label class="field-label">Teléfono / contacto</label>
                        <input v-model="form.telefono" class="field-input" placeholder="Opcional" />
                    </div>
                    <div>
                        <label class="field-label">Tenencia de la tierra</label>
                        <select v-model="form.tenenciaTierra" class="field-input">
                            <option value="">Sin especificar</option>
                            <option v-for="t in TENENCIA_TIERRA" :key="t" :value="t">{{ t }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Familias beneficiadas</label>
                        <input v-model.number="form.numFamiliasBeneficiadas" type="number" min="0" step="1" class="field-input" placeholder="Ej. 4" />
                    </div>

                    <div class="md:col-span-2 border border-dashed border-slate-300 rounded-lg p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Ubicación GPS</p>
                        <div class="flex items-center gap-3 mb-3">
                            <button type="button" @click="capturarGeo" class="btn-secondary">📍 Capturar ubicación actual</button>
                            <span class="text-sm text-slate-500">{{ geoStatus }}</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div><label class="field-label">Latitud</label><input v-model="form.latitud" type="number" step="any" class="field-input" placeholder="15.4700" /></div>
                            <div><label class="field-label">Longitud</label><input v-model="form.longitud" type="number" step="any" class="field-input" placeholder="-90.3700" /></div>
                            <div><label class="field-label">Altitud (msnm)</label><input v-model="form.altitud" type="number" min="0" step="1" class="field-input" placeholder="650" /></div>
                            <div><label class="field-label">Precisión GPS (m)</label><input v-model="form.gpsPrecision" type="number" min="0" step="1" class="field-input" readonly placeholder="Automático" /></div>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Área (hectáreas) *</label>
                        <input v-model.number="form.areaHa" type="number" min="0" step="0.01" required class="field-input" placeholder="12.50" />
                    </div>
                    <div>
                        <label class="field-label">Estado del proceso *</label>
                        <select v-model="form.estado" required class="field-input">
                            <option v-for="e in ESTADOS_PROCESO" :key="e" :value="e">{{ e }}</option>
                        </select>
                    </div>
                </div>

                <!-- Paso 2: Suelo y agua -->
                <div v-show="stepIndex === 1" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Uso actual del suelo</label>
                        <select v-model="form.usoActual" class="field-input">
                            <option value=""></option>
                            <option v-for="u in USOS_ACTUALES" :key="u" :value="u">{{ u }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Cultivo principal</label>
                        <input v-model="form.cultivoPrincipal" class="field-input" placeholder="Maíz, café, pastos..." />
                    </div>
                    <div>
                        <label class="field-label">Tipo de suelo</label>
                        <input v-model="form.tipoSuelo" class="field-input" placeholder="Franco, arcilloso, limoso..." />
                    </div>
                    <div>
                        <label class="field-label">Pendiente estimada (%)</label>
                        <input v-model.number="form.pendiente" type="number" min="0" step="0.1" class="field-input" placeholder="8.5" />
                    </div>
                    <div>
                        <label class="field-label">Disponibilidad de agua</label>
                        <select v-model="form.agua" class="field-input">
                            <option value=""></option>
                            <option v-for="a in NIVELES_AGUA" :key="a" :value="a">{{ a }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Fuente de agua</label>
                        <select v-model="form.fuenteAgua" class="field-input">
                            <option value="">Sin especificar</option>
                            <option v-for="f in FUENTE_AGUA" :key="f" :value="f">{{ f }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Riesgo de erosión</label>
                        <select v-model="form.riesgoErosion" class="field-input">
                            <option value=""></option>
                            <option v-for="r in RIESGO_EROSION" :key="r" :value="r">{{ r }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">Sistema de riego</label>
                        <input v-model="form.sistemaRiego" class="field-input" placeholder="Goteo, aspersión, ninguno..." />
                    </div>

                    <div class="md:col-span-2 border border-slate-200 rounded-lg p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">Diagnóstico físico del suelo</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="field-label">Profundidad de suelo (cm)</label><input v-model.number="form.profundidadSuelo" type="number" min="0" step="1" class="field-input" placeholder="45" /></div>
                            <div>
                                <label class="field-label">Presencia de talpetate</label>
                                <select v-model="form.talpetate" class="field-input">
                                    <option value="">Sin evaluar</option>
                                    <option value="No">No</option>
                                    <option value="Sí">Sí</option>
                                </select>
                            </div>
                            <div>
                                <label class="field-label">¿Se encharca el agua en la parcela?</label>
                                <select v-model="form.encharca" class="field-input">
                                    <option value="">Sin evaluar</option>
                                    <option value="No">No</option>
                                    <option value="Sí">Sí</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="field-label">Bioindicadores de suelo</label>
                                <input v-model="form.bioindicadores" class="field-input" placeholder="Lombrices, hormigas, hongos, hojarasca..." />
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 border border-slate-200 rounded-lg p-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-3">Lluvia (opcional)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="field-label">Lluvia acumulada anual (mm)</label><input v-model.number="form.lluviaAnual" type="number" min="0" step="1" class="field-input" placeholder="1800" /></div>
                            <div><label class="field-label">Fuente / año</label><input v-model="form.lluviaFuente" class="field-input" placeholder="INSIVUMEH 2025, estación local..." /></div>
                        </div>
                    </div>
                </div>

                <!-- Paso 3: Intervención -->
                <div v-show="stepIndex === 2" class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="field-label">Intervenciones previstas / ejecutadas</label>
                        <input v-model="form.intervenciones" class="field-input" placeholder="Canales keyline, reservorios, reforestación, zanjas de infiltración..." />
                    </div>
                    <div>
                        <label class="field-label">Especies usadas en reforestación / cobertura</label>
                        <input v-model="form.especiesReforestacion" class="field-input" placeholder="Ej. madrecacao, gravilea, pasto de corte..." />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label">Fecha próxima visita de seguimiento</label>
                            <input v-model="form.fechaProximaVisita" type="date" class="field-input" />
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input v-model="form.consentimientoProductor" type="checkbox" />
                                El productor autoriza el uso de esta información
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="4" class="field-input" placeholder="Notas técnicas, restricciones, acuerdos con el productor, próximos pasos..."></textarea>
                    </div>
                </div>

                <!-- Paso 4: Fotos -->
                <div v-show="stepIndex === 3">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">Fotografías de la parcela</p>
                    <p class="text-sm text-slate-500 mb-3">Sube fotos del terreno, obras keyline, suelo o cualquier evidencia relevante.</p>
                    <label class="block border-2 border-dashed border-slate-300 rounded-lg p-6 text-center cursor-pointer hover:border-primary-400">
                        <input type="file" accept="image/*" multiple capture="environment" class="hidden" @change="onPhotoInput" />
                        <div>📷 Toca para tomar o seleccionar fotos</div>
                        <div class="text-xs text-slate-400 mt-1">JPG, PNG o WEBP, máx. 12MB cada una</div>
                    </label>

                    <div v-if="existingFotos.length" class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                        <div v-for="f in existingFotos" :key="f.id" class="relative">
                            <img :src="fotoUrl(f.miniatura || f.archivo)" class="w-full h-28 object-cover rounded-lg" />
                            <button type="button" @click="removeExistingFoto(f)" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full w-6 h-6 text-xs">✕</button>
                        </div>
                    </div>

                    <div v-if="pendingPhotos.length" class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                        <div v-for="(file, i) in pendingPhotos" :key="i" class="relative">
                            <img :src="previewUrl(file)" class="w-full h-28 object-cover rounded-lg" />
                            <button type="button" @click="pendingPhotos.splice(i, 1)" class="absolute -top-2 -right-2 bg-rose-500 text-white rounded-full w-6 h-6 text-xs">✕</button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-8 pt-4 border-t">
                    <button type="button" v-show="stepIndex > 0" @click="stepIndex--" class="btn-ghost">← Anterior</button>
                    <div class="flex gap-2 ml-auto">
                        <button type="button" @click="cancelar" class="btn-secondary">Cancelar</button>
                        <button v-if="stepIndex < STEPS.length - 1" type="button" @click="nextStep" class="btn-primary">Siguiente →</button>
                        <button v-else type="submit" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Guardando…' : '💾 Guardar parcela' }}
                        </button>
                    </div>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import parcelaService from '../../services/parcelaService';
import { parcelaFotoUrl } from '../../services/api';
import { toastSuccess, toastInfo, alertError, confirmDialog } from '../../utils/alerts';
import {
    DEPARTAMENTOS, ESTADOS_PROCESO, USOS_ACTUALES, NIVELES_AGUA,
    RIESGO_EROSION, TENENCIA_TIERRA, FUENTE_AGUA,
} from '../../constants/keyline';

const props = defineProps({ id: { type: String, default: null } });
const route = useRoute();
const router = useRouter();

const STEPS = [
    { key: 'ubicacion', label: '1. Ubicación', icon: '📍' },
    { key: 'suelo', label: '2. Suelo y agua', icon: '🌱' },
    { key: 'intervencion', label: '3. Intervención', icon: '🛠️' },
    { key: 'fotos', label: '4. Fotos', icon: '📷' },
];

const editingId = computed(() => props.id || route.params.id || null);
const isEditing = computed(() => !!editingId.value);

const stepIndex = ref(0);
const loadingParcela = ref(false);
const saving = ref(false);
const parcela = ref(null);
const geoStatus = ref('Aún no capturada. Puedes ingresarla manualmente si lo prefieres.');
const existingFotos = ref([]);
const pendingPhotos = ref([]);
const formRef = ref(null);

function blankForm() {
    return {
        nombreParcela: '', departamento: '', municipio: '', comunidad: '', fechaRegistro: todayISO(),
        propietario: '', telefono: '', tenenciaTierra: '', numFamiliasBeneficiadas: '',
        latitud: '', longitud: '', altitud: '', gpsPrecision: '', areaHa: '', estado: 'Levantamiento',
        usoActual: '', cultivoPrincipal: '', tipoSuelo: '', pendiente: '', agua: '', fuenteAgua: '',
        riesgoErosion: '', sistemaRiego: '', profundidadSuelo: '', talpetate: '', encharca: '',
        bioindicadores: '', lluviaAnual: '', lluviaFuente: '', intervenciones: '', especiesReforestacion: '',
        fechaProximaVisita: '', consentimientoProductor: false, observaciones: '',
    };
}

const form = reactive(blankForm());

function todayISO() {
    const now = new Date();
    const tz = now.getTimezoneOffset();
    return new Date(now.getTime() - tz * 60000).toISOString().slice(0, 10);
}

onMounted(async () => {
    if (!editingId.value) return;
    loadingParcela.value = true;
    try {
        const { data } = await parcelaService.obtener(editingId.value);
        parcela.value = data.parcela;
        Object.keys(form).forEach((key) => {
            if (data.parcela[key] !== undefined && data.parcela[key] !== null) {
                form[key] = data.parcela[key];
            }
        });
        existingFotos.value = data.parcela.fotos || [];
        if (form.latitud !== '' && form.longitud !== '') {
            geoStatus.value = `Ubicación guardada: ${form.latitud}, ${form.longitud}`;
        }
    } finally {
        loadingParcela.value = false;
    }
});

function nextStep() {
    const panel = formRef.value?.querySelectorAll('[required]');
    let firstInvalid = null;
    panel?.forEach((el) => {
        if (isVisible(el) && !el.checkValidity() && !firstInvalid) firstInvalid = el;
    });
    if (firstInvalid) {
        firstInvalid.reportValidity();
        return;
    }
    stepIndex.value = Math.min(STEPS.length - 1, stepIndex.value + 1);
}

function isVisible(el) {
    return el.offsetParent !== null;
}

function capturarGeo() {
    if (!navigator.geolocation) {
        geoStatus.value = 'Tu navegador no soporta geolocalización.';
        return;
    }
    geoStatus.value = 'Obteniendo ubicación…';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            form.latitud = Number(pos.coords.latitude.toFixed(6));
            form.longitud = Number(pos.coords.longitude.toFixed(6));
            form.gpsPrecision = Math.round(pos.coords.accuracy);
            if (pos.coords.altitude) form.altitud = Math.round(pos.coords.altitude);
            geoStatus.value = `✅ Ubicación capturada (precisión ±${Math.round(pos.coords.accuracy)} m)`;
        },
        (err) => { geoStatus.value = `No se pudo obtener la ubicación (${err.message}). Puedes ingresarla manualmente.`; },
        { enableHighAccuracy: true, timeout: 12000 }
    );
}

function onPhotoInput(e) {
    pendingPhotos.value = pendingPhotos.value.concat(Array.from(e.target.files || []));
    e.target.value = '';
}

function previewUrl(file) {
    return URL.createObjectURL(file);
}

function fotoUrl(name) {
    return parcelaFotoUrl(editingId.value, name);
}

async function removeExistingFoto(foto) {
    const ok = await confirmDialog('Esta foto se eliminará de la parcela.', { title: '¿Eliminar esta foto?', danger: true, confirmText: 'Eliminar' });
    if (!ok) return;
    await parcelaService.eliminarFoto(editingId.value, foto.id);
    existingFotos.value = existingFotos.value.filter((f) => f.id !== foto.id);
    toastInfo('Foto eliminada.');
}

async function submit() {
    if (formRef.value && !formRef.value.checkValidity()) {
        formRef.value.reportValidity();
        return;
    }
    saving.value = true;
    try {
        const payload = { ...form };
        let saved;
        if (isEditing.value) {
            const { data } = await parcelaService.actualizar(editingId.value, payload);
            saved = data.parcela;
        } else {
            const { data } = await parcelaService.crear(payload);
            saved = data.parcela;
        }
        if (pendingPhotos.value.length) {
            const fd = new FormData();
            pendingPhotos.value.forEach((f) => fd.append('fotos[]', f));
            await parcelaService.subirFotos(saved.id, fd);
        }
        await toastSuccess(isEditing.value ? 'Parcela actualizada correctamente.' : `Parcela guardada (código ${saved.codigo}).`);
        router.push(homeAfterSave());
    } catch (err) {
        alertError(err.message || 'No se pudo guardar la parcela.');
    } finally {
        saving.value = false;
    }
}

function homeAfterSave() {
    const auth = JSON.parse(localStorage.getItem('user') || 'null');
    if (auth?.role === 'tecnico') return { name: 'MisParcelas' };
    return { name: 'ParcelasList' };
}

function cancelar() {
    router.push(homeAfterSave());
}
</script>

<style scoped>
.field-label { @apply block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide; }
.field-input { @apply w-full px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500; }
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600 disabled:opacity-60; }
.btn-secondary { @apply px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm font-semibold hover:bg-slate-200; }
.btn-ghost { @apply px-4 py-2 text-slate-500 text-sm font-semibold hover:text-slate-700; }
</style>
