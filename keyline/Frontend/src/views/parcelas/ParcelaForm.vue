<template>
    <div class="panel glass">
        <div class="panel-head">
            <div>
                <h3>{{ isEditing ? 'Editar parcela' : 'Registrar nueva parcela' }}</h3>
                <p>Completa los datos técnicos de la parcela keyline. Los campos con * son obligatorios.</p>
            </div>
            <span v-if="parcela?.codigo" class="badge">{{ parcela.codigo }}</span>
        </div>

        <div v-if="loadingParcela" class="py-12 text-center hint">Cargando…</div>

        <template v-else>
            <div class="wizard">
                <div class="wizard-steps">
                    <button
                        v-for="(step, i) in STEPS"
                        :key="step.key"
                        type="button"
                        @click="stepIndex = i"
                        class="wizard-step"
                        :class="{ active: i === stepIndex, done: i < stepIndex }"
                    >
                        {{ step.icon }} {{ step.label }}
                    </button>
                </div>

                <form @submit.prevent="submit" ref="formRef">
                    <!-- Paso 1: Ubicación -->
                    <div v-show="stepIndex === 0" class="form-grid">
                        <div class="full">
                            <label class="field-label">Nombre de la parcela / finca / terreno *</label>
                            <input v-model="form.nombreParcela" required placeholder="Ej. Finca El Pinar" />
                        </div>
                        <div>
                            <label class="field-label">Departamento *</label>
                            <select v-model="form.departamento" required>
                                <option value="">Seleccione...</option>
                                <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Municipio *</label>
                            <input v-model="form.municipio" required placeholder="Ej. Cobán" />
                        </div>
                        <div>
                            <label class="field-label">Aldea / comunidad</label>
                            <input v-model="form.comunidad" placeholder="Ej. Chisecito" />
                        </div>
                        <div>
                            <label class="field-label">Fecha de registro</label>
                            <input v-model="form.fechaRegistro" type="date" />
                        </div>
                        <div>
                            <label class="field-label">Productor / responsable</label>
                            <input v-model="form.propietario" placeholder="Nombre del responsable" />
                        </div>
                        <div>
                            <label class="field-label">Teléfono / contacto</label>
                            <input v-model="form.telefono" placeholder="Opcional" />
                        </div>
                        <div>
                            <label class="field-label">Tenencia de la tierra</label>
                            <select v-model="form.tenenciaTierra">
                                <option value="">Sin especificar</option>
                                <option v-for="t in TENENCIA_TIERRA" :key="t" :value="t">{{ t }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Familias beneficiadas</label>
                            <input v-model.number="form.numFamiliasBeneficiadas" type="number" min="0" step="1" placeholder="Ej. 4" />
                        </div>

                        <div class="subgroup">
                            <div class="subgroup-title"><span class="dot"></span>Ubicación GPS</div>
                            <div class="geo-box" style="margin-bottom: 12px;">
                                <button type="button" @click="capturarGeo" class="btn btn-secondary btn-sm">📍 Capturar ubicación actual</button>
                                <span class="geo-status">{{ geoStatus }}</span>
                            </div>
                            <div class="form-grid">
                                <div><label class="field-label">Latitud</label><input v-model="form.latitud" type="number" step="any" placeholder="15.4700" /></div>
                                <div><label class="field-label">Longitud</label><input v-model="form.longitud" type="number" step="any" placeholder="-90.3700" /></div>
                                <div><label class="field-label">Altitud (msnm)</label><input v-model="form.altitud" type="number" min="0" step="1" placeholder="650" /></div>
                                <div><label class="field-label">Precisión GPS (m)</label><input v-model="form.gpsPrecision" type="number" min="0" step="1" readonly placeholder="Automático" /></div>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Área (hectáreas) *</label>
                            <input v-model.number="form.areaHa" type="number" min="0" step="0.01" required placeholder="12.50" />
                        </div>
                        <div>
                            <label class="field-label">Estado del proceso *</label>
                            <select v-model="form.estado" required>
                                <option v-for="e in ESTADOS_PROCESO" :key="e" :value="e">{{ e }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Paso 2: Suelo y agua -->
                    <div v-show="stepIndex === 1" class="form-grid">
                        <div>
                            <label class="field-label">Uso actual del suelo</label>
                            <select v-model="form.usoActual">
                                <option value=""></option>
                                <option v-for="u in USOS_ACTUALES" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Cultivo principal</label>
                            <input v-model="form.cultivoPrincipal" placeholder="Maíz, café, pastos..." />
                        </div>
                        <div>
                            <label class="field-label">Tipo de suelo</label>
                            <input v-model="form.tipoSuelo" placeholder="Franco, arcilloso, limoso..." />
                        </div>
                        <div>
                            <label class="field-label">Pendiente estimada (%)</label>
                            <input v-model.number="form.pendiente" type="number" min="0" step="0.1" placeholder="8.5" />
                        </div>
                        <div>
                            <label class="field-label">Disponibilidad de agua</label>
                            <select v-model="form.agua">
                                <option value=""></option>
                                <option v-for="a in NIVELES_AGUA" :key="a" :value="a">{{ a }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Fuente de agua</label>
                            <select v-model="form.fuenteAgua">
                                <option value="">Sin especificar</option>
                                <option v-for="f in FUENTE_AGUA" :key="f" :value="f">{{ f }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Riesgo de erosión</label>
                            <select v-model="form.riesgoErosion">
                                <option value=""></option>
                                <option v-for="r in RIESGO_EROSION" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Sistema de riego</label>
                            <input v-model="form.sistemaRiego" placeholder="Goteo, aspersión, ninguno..." />
                        </div>

                        <div class="subgroup">
                            <div class="subgroup-title"><span class="dot"></span>Diagnóstico físico del suelo</div>
                            <div class="form-grid">
                                <div><label class="field-label">Profundidad de suelo (cm)</label><input v-model.number="form.profundidadSuelo" type="number" min="0" step="1" placeholder="45" /></div>
                                <div>
                                    <label class="field-label">Presencia de talpetate</label>
                                    <select v-model="form.talpetate">
                                        <option value="">Sin evaluar</option>
                                        <option value="No">No</option>
                                        <option value="Sí">Sí</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="field-label">¿Se encharca el agua en la parcela?</label>
                                    <select v-model="form.encharca">
                                        <option value="">Sin evaluar</option>
                                        <option value="No">No</option>
                                        <option value="Sí">Sí</option>
                                    </select>
                                </div>
                                <div class="full">
                                    <label class="field-label">Bioindicadores de suelo</label>
                                    <input v-model="form.bioindicadores" placeholder="Lombrices, hormigas, hongos, hojarasca..." />
                                </div>
                            </div>
                        </div>

                        <div class="subgroup">
                            <div class="subgroup-title"><span class="dot"></span>Lluvia (opcional)</div>
                            <div class="form-grid">
                                <div><label class="field-label">Lluvia acumulada anual (mm)</label><input v-model.number="form.lluviaAnual" type="number" min="0" step="1" placeholder="1800" /></div>
                                <div><label class="field-label">Fuente / año</label><input v-model="form.lluviaFuente" placeholder="INSIVUMEH 2025, estación local..." /></div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Intervención -->
                    <div v-show="stepIndex === 2" class="form-grid">
                        <div class="full">
                            <label class="field-label">Intervenciones previstas / ejecutadas</label>
                            <input v-model="form.intervenciones" placeholder="Canales keyline, reservorios, reforestación, zanjas de infiltración..." />
                        </div>
                        <div class="full">
                            <label class="field-label">Especies usadas en reforestación / cobertura</label>
                            <input v-model="form.especiesReforestacion" placeholder="Ej. madrecacao, gravilea, pasto de corte..." />
                        </div>
                        <div>
                            <label class="field-label">Fecha próxima visita de seguimiento</label>
                            <input v-model="form.fechaProximaVisita" type="date" />
                        </div>
                        <div style="display: flex; align-items: flex-end;">
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 500;">
                                <input v-model="form.consentimientoProductor" type="checkbox" style="width: auto;" />
                                El productor autoriza el uso de esta información
                            </label>
                        </div>
                        <div class="full">
                            <label class="field-label">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="4" placeholder="Notas técnicas, restricciones, acuerdos con el productor, próximos pasos..."></textarea>
                        </div>
                    </div>

                    <!-- Paso 4: Fotos -->
                    <div v-show="stepIndex === 3">
                        <label class="photo-drop">
                            <input type="file" accept="image/*" multiple capture="environment" @change="onPhotoInput" />
                            <div>📷 Toca para tomar o seleccionar fotos</div>
                            <div class="hint" style="margin-top: 4px;">JPG, PNG o WEBP, máx. 12MB cada una</div>
                        </label>

                        <div v-if="existingFotos.length" class="photo-grid">
                            <div v-for="f in existingFotos" :key="f.id" class="photo-thumb">
                                <img :src="fotoUrl(f.miniatura || f.archivo)" />
                                <button type="button" @click="removeExistingFoto(f)" class="rm">✕</button>
                            </div>
                        </div>

                        <div v-if="pendingPhotos.length" class="photo-grid">
                            <div v-for="(file, i) in pendingPhotos" :key="i" class="photo-thumb">
                                <img :src="previewUrl(file)" />
                                <button type="button" @click="pendingPhotos.splice(i, 1)" class="rm">✕</button>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" v-show="stepIndex > 0" @click="stepIndex--" class="btn btn-ghost">← Anterior</button>
                        <div style="display: flex; gap: 10px; margin-left: auto;">
                            <button type="button" @click="cancelar" class="btn btn-secondary">Cancelar</button>
                            <button v-if="stepIndex < STEPS.length - 1" type="button" @click="nextStep" class="btn btn-primary">Siguiente →</button>
                            <button v-else type="submit" :disabled="saving" class="btn btn-primary">
                                {{ saving ? 'Guardando…' : '💾 Guardar parcela' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
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
