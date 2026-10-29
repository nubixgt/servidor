<template>
    <div v-if="!cat || !columnas" class="empty-state">Cargando…</div>
    <div v-else class="rg">
        <div class="ch"><div><h2>Reporte geográfico</h2><p>Seleccione ubicación y columnas, consulte una vista previa y genere el Excel con todos los registros.</p></div></div>

        <!-- Panel de filtros -->
        <section class="rg-sec">
            <h3>1. Seleccionar departamento</h3>
            <div class="field" style="max-width:380px">
                <label for="rg-dep">Departamento</label>
                <select id="rg-dep" v-model="dep" @change="alCambiarDepartamento">
                    <option value="">Seleccione…</option>
                    <option v-if="nacional" value="0">Todos los departamentos</option>
                    <option v-for="d in departamentos" :key="d.id" :value="String(d.id)">{{ d.nombre }}</option>
                </select>
            </div>
            <template v-if="dep !== '' && dep !== '0'">
                <h3 style="margin-top:16px">2. Seleccionar municipios</h3>
                <SelectorMunicipios v-model="muns" :municipios="municipiosDep" />
            </template>
            <p v-else-if="dep === '0'" class="note" style="margin-top:10px">Se incluirán todos los municipios autorizados para su usuario.</p>
        </section>

        <!-- Panel de configuración -->
        <section class="rg-sec">
            <h3>{{ dep === '0' ? '2' : '3' }}. Configurar columnas del reporte</h3>
            <SelectorColumnas v-model="cols" :categorias="columnas.categorias" />
            <p v-if="!columnas.dpi_completo" class="note" style="margin-top:8px">Su rol no tiene permiso para ver el DPI completo: en el reporte aparecerá enmascarado.</p>
        </section>

        <!-- Resumen y acciones -->
        <section class="rg-sec">
            <div class="rg-resumen">
                <div class="note"><b>Filtros activos:</b>
                    <span class="chip" style="margin-left:6px">{{ etiquetaDep }}</span>
                    <span class="chip">{{ etiquetaMuns }}</span>
                    <span class="chip">{{ cols.length }} columnas</span>
                </div>
                <div class="note"><b>Registros estimados a exportar:</b> {{ resumen ? fmt(resumen.registros) : '— (pulse «Consultar registros»)' }}</div>
            </div>
            <div class="err">{{ error }}</div>
            <div class="rg-acciones">
                <button class="btn pri" type="button" :disabled="!puedeConsultar || consultando" @click="consultar(true)">
                    {{ consultando ? 'Consultando…' : 'Consultar registros' }}
                </button>
                <button class="btn" type="button" @click="limpiar">Limpiar filtros</button>
                <button class="btn pri" type="button" :disabled="!puedeExportar || generando" @click="generar">
                    <template v-if="generando">Generando…</template>
                    <template v-else><Icono name="download" :size="16" /> Generar Reporte Excel</template>
                </button>
            </div>
            <p v-if="!auth.puede('reportes.exportar')" class="note">Su rol puede consultar pero no exportar datos personales.</p>
            <p v-if="resumen && resumen.registros > resumen.limite" class="err">La consulta supera el máximo de {{ fmt(resumen.limite) }} filas por exportación para su rol. Reduzca los municipios seleccionados.</p>
        </section>

        <!-- Panel de resultados -->
        <section v-if="resumen" class="rg-sec">
            <h3>Resultados</h3>
            <div class="rg-kpis">
                <div><b>{{ fmt(resumen.registros) }}</b><span>Registros encontrados</span></div>
                <div><b>{{ resumen.departamentos_seleccionados }}</b><span>Departamentos seleccionados</span></div>
                <div><b>{{ resumen.municipios_seleccionados }}</b><span>Municipios seleccionados</span></div>
                <div><b>{{ fmt(resumen.beneficiarios) }}</b><span>Beneficiarios únicos</span></div>
                <div><b>{{ fmt(resumen.expedientes) }}</b><span>Expedientes</span></div>
            </div>

            <div v-if="previa" style="margin-top:14px">
                <div class="rg-bar">
                    <form class="rg-buscar" @submit.prevent="buscar">
                        <input v-model="q" type="search" placeholder="Buscar en la vista previa (nombre, DPI, expediente…)" aria-label="Buscar en la vista previa">
                        <button class="btn" type="submit">Buscar</button>
                    </form>
                    <span class="note">Vista previa de las primeras filas; el Excel incluirá <b>todos</b> los registros ({{ fmt(resumen.registros) }}).</span>
                </div>
                <div class="tw">
                    <table>
                        <thead><tr><th v-for="c in previa.columnas" :key="c">{{ c }}</th></tr></thead>
                        <tbody>
                            <tr v-for="(f, i) in previa.filas" :key="i"><td v-for="(v, j) in f" :key="j">{{ v ?? '' }}</td></tr>
                            <tr v-if="!previa.filas.length"><td :colspan="previa.columnas.length"><span class="note">Sin resultados para la búsqueda.</span></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="pager">
                    <span>{{ fmt(previa.total) }} registros · página {{ previa.pagina + 1 }} de {{ Math.max(1, Math.ceil(previa.total / previa.tam_pagina)) }}</span>
                    <span>
                        <button class="btn" type="button" :disabled="previa.pagina === 0 || cargandoPagina" @click="irA(previa.pagina - 1)">Anterior</button>
                        <button class="btn" type="button" :disabled="(previa.pagina + 1) * previa.tam_pagina >= previa.total || cargandoPagina" @click="irA(previa.pagina + 1)">Siguiente</button>
                    </span>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Icono from '../common/Icono.vue';
import SelectorColumnas from './SelectorColumnas.vue';
import SelectorMunicipios from './SelectorMunicipios.vue';
import { useToast } from '../../composables/useToast';
import reporteService from '../../services/reporteService';
import { useAuthStore } from '../../stores/auth';
import { useCatalogosStore } from '../../stores/catalogos';
import { fmt } from '../../utils/formato';

const auth = useAuthStore();
const catStore = useCatalogosStore();
const { toast } = useToast();

const cat = computed(() => catStore.cat);
const columnas = ref(null);
const dep = ref('');
const muns = ref([]);
const cols = ref([]);
const q = ref('');
const resumen = ref(null);
const previa = ref(null);
const error = ref('');
const consultando = ref(false);
const cargandoPagina = ref(false);
const generando = ref(false);

const u = computed(() => auth.usuario || {});
const nacional = computed(() => !u.value.departamento_id && !u.value.municipio_id);
const depUsuario = computed(() => u.value.departamento_id || (u.value.municipio_id ? Math.floor(u.value.municipio_id / 100) : null));

const departamentos = computed(() => {
    const todos = cat.value?.departamentos || [];
    return nacional.value ? todos : todos.filter((d) => d.id === depUsuario.value);
});
const municipiosDep = computed(() => {
    let l = (cat.value?.municipios || []).filter((m) => String(m.departamento_id) === dep.value);
    if (u.value.municipio_id) l = l.filter((m) => m.id === u.value.municipio_id);
    return l;
});

const etiquetaDep = computed(() => dep.value === '' ? 'Sin departamento' : dep.value === '0' ? 'Todos los departamentos'
    : (departamentos.value.find((d) => String(d.id) === dep.value)?.nombre || ''));
const etiquetaMuns = computed(() => dep.value === '0' ? 'Todos los municipios autorizados'
    : `${muns.value.length} municipio${muns.value.length === 1 ? '' : 's'}`);

const territorioListo = computed(() => dep.value === '0' || (dep.value !== '' && muns.value.length > 0));
const puedeConsultar = computed(() => territorioListo.value && cols.value.length > 0);
const puedeExportar = computed(() => puedeConsultar.value && auth.puede('reportes.exportar')
    && !(resumen.value && resumen.value.registros > resumen.value.limite) && !(resumen.value && resumen.value.registros === 0));

const cuerpo = (extra = {}) => ({ departamento: Number(dep.value) || 0, municipios: muns.value, columnas: cols.value, ...extra });

function alCambiarDepartamento() {
    // evita combinaciones geográficas incorrectas: los municipios de otro departamento no se conservan
    muns.value = [];
    resumen.value = null;
    previa.value = null;
    error.value = '';
}

// cualquier cambio de selección invalida lo consultado antes
watch([muns, cols], () => { resumen.value = null; previa.value = null; }, { deep: true });

async function consultar(reiniciarBusqueda) {
    error.value = '';
    consultando.value = true;
    try {
        if (reiniciarBusqueda) q.value = '';
        const [r, p] = await Promise.all([reporteService.geograficoResumen(cuerpo()), reporteService.geograficoPrevia(cuerpo({ pagina: 0, q: '' }))]);
        resumen.value = r;
        previa.value = p;
    } catch (e) {
        error.value = e.message;
    }
    consultando.value = false;
}

async function cargarPrevia(pagina) {
    cargandoPagina.value = true;
    try {
        previa.value = await reporteService.geograficoPrevia(cuerpo({ pagina, q: q.value.trim() }));
    } catch (e) {
        error.value = e.message;
    }
    cargandoPagina.value = false;
}
const buscar = () => cargarPrevia(0);
const irA = (p) => cargarPrevia(p);

function limpiar() {
    dep.value = '';
    muns.value = [];
    cols.value = [...(columnas.value?.predeterminadas || [])];
    q.value = '';
    resumen.value = null;
    previa.value = null;
    error.value = '';
}

async function generar() {
    error.value = '';
    generando.value = true;
    try {
        await reporteService.geograficoExportar(cuerpo());
        toast('Reporte generado · la descarga quedó registrada en la bitácora');
    } catch (e) {
        error.value = e.message;
    }
    generando.value = false;
}

onMounted(async () => {
    try {
        if (!catStore.cat) await catStore.cargar();
        columnas.value = await reporteService.columnas();
        cols.value = [...columnas.value.predeterminadas];
        if (!nacional.value && departamentos.value.length === 1) {
            dep.value = String(departamentos.value[0].id);
            if (u.value.municipio_id) muns.value = [u.value.municipio_id];
        }
    } catch (e) {
        error.value = e.message;
    }
});
</script>

<style scoped>
.rg-sec{padding:16px 0;border-top:1px solid var(--line)}
.rg-sec:first-of-type{border-top:0}
.rg-sec h3{margin:0 0 10px;font:700 14px var(--f-disp);color:var(--ink)}
.rg-resumen{display:flex;flex-direction:column;gap:8px;margin-bottom:10px}
.rg-acciones{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.rg-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
.rg-kpis div{display:flex;flex-direction:column;gap:2px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--glass-2)}
.rg-kpis b{font:700 22px var(--f-disp);color:var(--ink);font-variant-numeric:tabular-nums}
.rg-kpis span{font-size:12px;color:var(--ink3)}
.rg-bar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:10px}
.rg-buscar{display:flex;gap:8px}
.rg-buscar input{height:36px;min-width:260px;border-radius:10px;border:1px solid var(--line2);background:var(--glass);padding:0 10px;color:var(--ink)}
.tw th,.tw td{white-space:nowrap}
</style>
