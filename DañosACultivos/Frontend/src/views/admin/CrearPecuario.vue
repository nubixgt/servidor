<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Nuevo registro pecuario</h2>
                    <p>Afectaciones en animales: especie, familias y pérdidas.</p>
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
                        <label class="label" for="pe-centro">Centro poblado *</label>
                        <input id="pe-centro" v-model="form.centroPoblado" required placeholder="Ej. Aldea El Rosario" class="field" />
                    </div>
                </div>

                <div class="section-head" style="margin-top: 32px;">
                    <div class="round-icon" style="background: #fff0d8; color: #af7d2b;"><ExclamationTriangleIcon class="w-5 h-5" /></div>
                    <div><h2>Afectación</h2><p>Especie, evento y magnitud del daño.</p></div>
                </div>
                <div class="fieldgrid">
                    <div>
                        <label class="label">Especie *</label>
                        <SearchSelect v-model="form.especie" :options="ESPECIES" placeholder="Buscar especie…" required />
                    </div>
                    <div v-if="form.especie === 'Otra'">
                        <label class="label" for="pe-esp-otra">Especifique la especie *</label>
                        <input id="pe-esp-otra" v-model="form.especieOtra" required placeholder="Ej. Conejos" class="field" />
                    </div>
                    <div>
                        <label class="label">Causa del daño *</label>
                        <SearchSelect v-model="form.causa" :options="CAUSAS" placeholder="Buscar causa…" required />
                    </div>
                    <div v-if="form.causa === 'Otra'">
                        <label class="label" for="pe-causa-otra">Especifique la causa *</label>
                        <input id="pe-causa-otra" v-model="form.causaOtra" required placeholder="Describa la causa" class="field" />
                    </div>
                    <div>
                        <label class="label" for="pe-evento">Evento reportado</label>
                        <input id="pe-evento" v-model="form.eventoReportado" maxlength="150" placeholder="Como lo reportó el técnico (ej. Inundación)" class="field" />
                    </div>
                    <div>
                        <label class="label" for="pe-fam">Familias afectadas</label>
                        <input id="pe-fam" v-model="form.famAfectadas" type="number" min="0" step="1" placeholder="Ej. 12" class="field" />
                    </div>
                    <div>
                        <label class="label" for="pe-ani">Animales perdidos</label>
                        <input id="pe-ani" v-model="form.animalesPerdidos" type="number" min="0" step="1" placeholder="Ej. 40" class="field" />
                    </div>
                    <div>
                        <label class="label" for="pe-perdida">Pérdida económica (Q)</label>
                        <input id="pe-perdida" v-model="form.perdidaEconomica" type="number" min="0" step="0.01" placeholder="Ej. 15000" class="field" />
                    </div>
                    <div class="full">
                        <label class="label" for="pe-notas">Descripción</label>
                        <textarea id="pe-notas" v-model="form.notas" rows="4" placeholder="Describe lo observado…" class="field" style="resize: vertical;"></textarea>
                    </div>
                </div>

                <div class="form-buttons">
                    <router-link to="/pecuario" class="btn"><ArrowLeftIcon class="w-5 h-5" />Ver registros</router-link>
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
import pecuarioService from '../../services/pecuarioService';
import { CAUSAS_DANO, ESPECIES } from '../../data/catalogos';
import { DEPARTAMENTOS, MUNICIPIOS } from '../../data/guatemala';
import { toastSuccess, toastError, alertError } from '../../utils/alerts';
import { MapPinIcon, ExclamationTriangleIcon, ArrowPathIcon, ArrowLeftIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const CAUSAS = [...CAUSAS_DANO, 'Otra'];
const hoy = () => new Date().toISOString().slice(0, 10);
const formVacio = () => ({
    fechaInforme: hoy(), monitoreoInicio: '', monitoreoFin: '', departamento: '', municipio: '', centroPoblado: '',
    especie: '', especieOtra: '', causa: '', causaOtra: '', eventoReportado: '',
    famAfectadas: '', animalesPerdidos: '', perdidaEconomica: '', notas: '',
});

const form = reactive(formVacio());
const guardando = ref(false);
const municipios = computed(() => MUNICIPIOS[form.departamento] || []);
// Al cambiar de departamento, el municipio elegido deja de ser válido.
watch(() => form.departamento, () => { form.municipio = ''; });

function limpiar() { Object.assign(form, formVacio()); }

async function guardar() {
    if (!form.departamento || !MUNICIPIOS[form.departamento]?.includes(form.municipio)) return alertError('Selecciona el departamento y el municipio de la lista.', 'Falta un dato');
    if (!ESPECIES.includes(form.especie)) return alertError('Selecciona la especie de la lista.', 'Falta un dato');
    if (!CAUSAS.includes(form.causa)) return alertError('Selecciona la causa del daño de la lista.', 'Falta un dato');
    guardando.value = true;
    try {
        await pecuarioService.create({
            ...form,
            especieOtra: form.especie === 'Otra' ? form.especieOtra : '',
            causaOtra: form.causa === 'Otra' ? form.causaOtra : '',
        });
        limpiar();
        toastSuccess('Registro guardado. Puedes verlo en Pecuario.');
    } catch (e) {
        toastError(e.message);
    } finally {
        guardando.value = false;
    }
}
</script>
