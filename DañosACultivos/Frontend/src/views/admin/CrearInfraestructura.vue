<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Nuevo registro de infraestructura</h2>
                    <p>Afectaciones en infraestructura productiva: tipo, ubicación y pérdidas.</p>
                </div>
                <button type="button" class="btn" @click="limpiar"><ArrowPathIcon class="w-5 h-5" />Limpiar formulario</button>
            </div>

            <form class="panel form-panel" @submit.prevent="guardar">
                <div class="section-head">
                    <div class="round-icon"><MapPinIcon class="w-5 h-5" /></div>
                    <div><h2>Período y ubicación</h2><p>Cuándo se monitoreó y dónde ocurrió.</p></div>
                </div>
                <div class="fieldgrid">
                    <PeriodoFields v-model:fecha-informe="form.fechaInforme" v-model:inicio="form.monitoreoInicio" v-model:fin="form.monitoreoFin" />
                    <div>
                        <label class="label">Departamento *</label>
                        <SearchSelect v-model="form.departamento" :options="DEPARTAMENTOS" placeholder="Buscar departamento…" required />
                    </div>
                    <div>
                        <label class="label">Municipio *</label>
                        <SearchSelect v-model="form.municipio" :options="municipios" :placeholder="form.departamento ? 'Buscar municipio…' : 'Seleccione primero el departamento'" required />
                    </div>
                    <div>
                        <label class="label" for="in-centro">Centro poblado *</label>
                        <input id="in-centro" v-model="form.centroPoblado" required placeholder="Ej. Aldea El Rosario" class="field" />
                    </div>
                </div>

                <div class="gps-box">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2"><ViewfinderCircleIcon class="w-5 h-5" style="color: var(--green);" /><b class="text-[12px]">Ubicación GPS (opcional)</b></div>
                        <span class="badge" :class="gps.lat !== null ? '' : 'gray'">{{ gps.lat !== null ? 'Capturada' : 'Sin capturar' }}</span>
                    </div>
                    <div class="gps-values">
                        <div><small>Latitud</small><b>{{ gps.lat ?? '—' }}</b></div>
                        <div><small>Longitud</small><b>{{ gps.lon ?? '—' }}</b></div>
                        <div><small>Precisión</small><b>{{ gps.acc ?? '—' }} m</b></div>
                    </div>
                    <button type="button" class="btn w-full" :disabled="gpsBuscando" @click="capturarGPS">
                        <ViewfinderCircleIcon class="w-5 h-5" />{{ gpsBuscando ? 'Buscando…' : 'Usar mi ubicación actual' }}
                    </button>
                </div>

                <div class="section-head" style="margin-top: 32px;">
                    <div class="round-icon" style="background: #fff0d8; color: #af7d2b;"><ExclamationTriangleIcon class="w-5 h-5" /></div>
                    <div><h2>Afectación</h2><p>Tipo de infraestructura, evento y magnitud del daño.</p></div>
                </div>
                <div class="fieldgrid">
                    <div>
                        <label class="label">Tipo de infraestructura *</label>
                        <SearchSelect v-model="form.tipoInfraestructura" :options="TIPOS_INFRAESTRUCTURA" placeholder="Buscar tipo…" required />
                    </div>
                    <div v-if="form.tipoInfraestructura === 'Otra'">
                        <label class="label" for="in-tipo-otra">Especifique el tipo *</label>
                        <input id="in-tipo-otra" v-model="form.tipoOtra" required placeholder="Ej. Beneficio húmedo" class="field" />
                    </div>
                    <div>
                        <label class="label">Causa del daño *</label>
                        <SearchSelect v-model="form.causa" :options="CAUSAS" placeholder="Buscar causa…" required />
                    </div>
                    <div v-if="form.causa === 'Otra'">
                        <label class="label" for="in-causa-otra">Especifique la causa *</label>
                        <input id="in-causa-otra" v-model="form.causaOtra" required placeholder="Describa la causa" class="field" />
                    </div>
                    <div>
                        <label class="label" for="in-evento">Evento reportado</label>
                        <input id="in-evento" v-model="form.eventoReportado" maxlength="150" placeholder="Como lo reportó el técnico (ej. Derrumbe)" class="field" />
                    </div>
                    <div>
                        <label class="label" for="in-fam">Familias afectadas</label>
                        <input id="in-fam" v-model="form.famAfectadas" type="number" min="0" step="1" placeholder="Ej. 7" class="field" />
                    </div>
                    <div>
                        <label class="label" for="in-perdida">Pérdida económica (Q)</label>
                        <input id="in-perdida" v-model="form.perdidaEconomica" type="number" min="0" step="0.01" placeholder="Ej. 12000" class="field" />
                    </div>
                    <div class="full">
                        <label class="label" for="in-notas">Descripción</label>
                        <textarea id="in-notas" v-model="form.notas" rows="4" placeholder="Describe lo observado…" class="field" style="resize: vertical;"></textarea>
                    </div>
                </div>

                <div class="form-buttons">
                    <router-link to="/infraestructura" class="btn"><ArrowLeftIcon class="w-5 h-5" />Ver registros</router-link>
                    <button type="submit" class="btn btn-primary" :disabled="guardando">
                        <PaperAirplaneIcon class="w-5 h-5" />{{ guardando ? 'Enviando…' : 'Enviar registro' }}
                    </button>
                </div>
            </form>
        </section>
    </div>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import SearchSelect from '../../components/ui/SearchSelect.vue';
import PeriodoFields from '../../components/ui/PeriodoFields.vue';
import infraestructuraService from '../../services/infraestructuraService';
import { CAUSAS_DANO, TIPOS_INFRAESTRUCTURA } from '../../data/catalogos';
import { DEPARTAMENTOS, MUNICIPIOS } from '../../data/guatemala';
import { toastSuccess, toastError, alertError } from '../../utils/alerts';
import { MapPinIcon, ViewfinderCircleIcon, ExclamationTriangleIcon, ArrowPathIcon, ArrowLeftIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const CAUSAS = [...CAUSAS_DANO, 'Otra'];
const hoy = () => new Date().toISOString().slice(0, 10);
const formVacio = () => ({
    fechaInforme: hoy(), monitoreoInicio: '', monitoreoFin: '', departamento: '', municipio: '', centroPoblado: '',
    tipoInfraestructura: '', tipoOtra: '', causa: '', causaOtra: '', eventoReportado: '',
    famAfectadas: '', perdidaEconomica: '', notas: '',
});

const form = reactive(formVacio());
const gps = reactive({ lat: null, lon: null, acc: null });
const gpsBuscando = ref(false);
const guardando = ref(false);
const municipios = computed(() => MUNICIPIOS[form.departamento] || []);
watch(() => form.departamento, () => { form.municipio = ''; });

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

function limpiar() {
    Object.assign(form, formVacio());
    Object.assign(gps, { lat: null, lon: null, acc: null });
}

async function guardar() {
    if (!form.departamento || !MUNICIPIOS[form.departamento]?.includes(form.municipio)) return alertError('Selecciona el departamento y el municipio de la lista.', 'Falta un dato');
    if (!TIPOS_INFRAESTRUCTURA.includes(form.tipoInfraestructura)) return alertError('Selecciona el tipo de infraestructura de la lista.', 'Falta un dato');
    if (!CAUSAS.includes(form.causa)) return alertError('Selecciona la causa del daño de la lista.', 'Falta un dato');
    guardando.value = true;
    try {
        await infraestructuraService.create({
            ...form,
            tipoOtra: form.tipoInfraestructura === 'Otra' ? form.tipoOtra : '',
            causaOtra: form.causa === 'Otra' ? form.causaOtra : '',
            gpsLat: gps.lat,
            gpsLon: gps.lon,
            gpsPrecision: gps.acc,
        });
        limpiar();
        toastSuccess('Registro guardado. Puedes verlo en Infraestructura.');
    } catch (e) {
        toastError(e.message);
    } finally {
        guardando.value = false;
    }
}
</script>
