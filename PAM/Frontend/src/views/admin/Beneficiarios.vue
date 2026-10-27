<template>
    <VistaHeader eyebrow="Gestión" titulo="Beneficiarios" desc="Búsqueda por DPI, número de expediente, nombre, teléfono o apoderado. Ignora tildes, mayúsculas y espacios extra. Los filtros del tablero también aplican." />
    <div class="card">
        <div class="ch">
            <div style="flex:1;min-width:220px">
                <div class="search" style="max-width:none">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.6-3.6" /></svg>
                    <input id="bq" v-model="texto" type="search" placeholder="DPI, expediente, nombre, teléfono o apoderado" aria-label="Buscar en beneficiarios" @input="programar">
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span v-if="regla" class="chip">Alerta: {{ regla.t }}<button aria-label="Quitar" @click="ui.bRule = null"><Icono name="close" :size="12" /></button></span>
                <button v-if="auth.puede('solicitud.registrar')" class="btn pri" @click="expediente.abrirForm('nueva')"><Icono name="file" :size="16" /> Nueva solicitud</button>
            </div>
        </div>
        <div>
            <div v-if="error" class="empty-state">{{ error }}</div>
            <div v-else-if="!datos" class="empty-state">Cargando expedientes…</div>
            <template v-else>
                <div class="tw">
                    <table>
                        <thead>
                            <tr>
                                <th v-for="[k, l, n] in columnas" :key="l" :class="{ n }">
                                    <button v-if="k" @click="ordenar(k)">{{ l }}<template v-if="ui.bSort[0] === k"> <span class="so">{{ ui.bSort[1] < 0 ? '▼' : '▲' }}</span></template></button><template v-else>{{ l }}</template>
                                </th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in datos.items" :key="p.id">
                                <td class="tnum">{{ p.numero || 's/n' }}</td>
                                <td><b>{{ p.nombre }}</b></td>
                                <td class="mask">{{ p.dpi || '—' }}</td>
                                <td>{{ p.departamento }}</td>
                                <td>{{ p.municipio }}</td>
                                <td class="n">{{ p.edad ?? '—' }}</td>
                                <td class="tnum">{{ fd(p.fecha_solicitud) }}</td>
                                <td><EstadoBadge :i="p.estado_orden" /></td>
                                <td><NominaBadge :n="p.situacion_nomina" /></td>
                                <td class="tnum">{{ fd(p.fecha_estado) }}</td>
                                <td><button class="btn" @click="expediente.abrir(p.id)">Ver expediente</button></td>
                            </tr>
                            <tr v-if="!datos.items.length"><td colspan="11" class="empty">Sin resultados con estos filtros. Quite un filtro o pruebe otra búsqueda.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager">
                    <span>{{ totalTxt ? totalTxt + ' expedientes · ' : '' }}página {{ pagina + 1 }} · 25 por página · {{ tablero.ambito }}</span>
                    <span style="display:flex;gap:6px">
                        <button class="btn" :disabled="!pagina" @click="cambiarPagina(-1)">Anterior</button>
                        <button class="btn" :disabled="!datos.siguiente" @click="cambiarPagina(1)">Siguiente</button>
                    </span>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import EstadoBadge from '../../components/common/EstadoBadge.vue';
import Icono from '../../components/common/Icono.vue';
import NominaBadge from '../../components/common/NominaBadge.vue';
import VistaHeader from '../../components/common/VistaHeader.vue';
import expedienteService from '../../services/expedienteService';
import { useAuthStore } from '../../stores/auth';
import { useExpedienteStore } from '../../stores/expediente';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { fd, fmt } from '../../utils/formato';
import { RULES } from '../../utils/reglas';

const auth = useAuthStore();
const expediente = useExpedienteStore();
const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();

const texto = ref(ui.bq);
const datos = ref(null);
const error = ref('');
const pagina = ref(0);
const cursores = ref([null]);
const total = ref(null);
const minimo = ref(false);
let temporizador = 0;
let secuencia = 0;

const columnas = [['numero', 'Expediente'], ['nombre', 'Nombre'], ['', 'DPI'], ['', 'Depto.'], ['', 'Municipio'], ['', 'Edad', true], ['fsol', 'Solicitud'], ['', 'Estado actual'], ['', 'Nómina'], ['actualizado', 'Actualizado']];
const regla = computed(() => (ui.bRule ? RULES.find((r) => r.k === ui.bRule) : null));
const totalTxt = computed(() => (total.value == null ? '' : (minimo.value ? 'más de 10,000' : fmt(total.value))));

/** Parámetros de la consulta: filtros del tablero + búsqueda + orden. */
const params = computed(() => {
    const relevancia = ui.bq && /[a-záéíóúñ]/i.test(ui.bq) && !ui.bSortManual;
    return filtros.paraApi({
        q: ui.bq || undefined,
        regla: ui.bRule || undefined,
        orden: relevancia ? 'relevancia' : ui.bSort[0],
        dir: relevancia ? 'desc' : (ui.bSort[1] < 0 ? 'desc' : 'asc'),
    });
});

async function cargar() {
    const token = ++secuencia;
    try {
        const d = await expedienteService.listar({ ...params.value, cursor: cursores.value[pagina.value] || undefined, limite: 25 });
        if (token !== secuencia) return;
        if (d.total != null) { total.value = d.total; minimo.value = d.total_es_minimo; }
        cursores.value[pagina.value + 1] = d.siguiente;
        datos.value = d;
        error.value = '';
    } catch (e) {
        if (token === secuencia) error.value = e.message;
    }
}

function reiniciar() {
    cursores.value = [null];
    pagina.value = 0;
    total.value = null;
    cargar();
}

function programar() {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => { ui.bq = texto.value; ui.bSortManual = false; }, 350);
}

function cambiarPagina(delta) {
    pagina.value += delta;
    cargar();
}

function ordenar(k) {
    ui.bSortManual = true;
    ui.bSort = [k, ui.bSort[0] === k ? -ui.bSort[1] : (k === 'nombre' || k === 'numero' ? 1 : -1)];
}

// cualquier cambio de filtro, búsqueda u orden vuelve a la primera página
watch(params, reiniciar, { immediate: true });
// la búsqueda de la barra superior también llega por aquí
watch(() => ui.bq, (v) => { texto.value = v; });
onBeforeUnmount(() => clearTimeout(temporizador));
</script>
