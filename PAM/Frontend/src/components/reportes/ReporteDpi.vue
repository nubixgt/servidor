<template>
    <div v-if="!columnas" class="empty-state">Cargando…</div>
    <div v-else>
        <div class="ch"><div><h2>Reporte por DPI</h2><p>Cargue un archivo Excel (.xlsx) con una columna llamada <b>DPI</b>; el reporte incluirá los expedientes de cada DPI encontrado.</p></div></div>

        <section class="rd-sec">
            <h3>1. Archivo con DPI</h3>
            <div class="rd-file">
                <input ref="input" type="file" accept=".xlsx" hidden @change="elegir">
                <button class="btn" type="button" @click="input.click()"><Icono name="upload" :size="16" /> Seleccionar archivo .xlsx</button>
                <span v-if="archivo" class="note"><b>{{ archivo.name }}</b> · {{ (archivo.size / 1048576).toFixed(2) }} MB</span>
                <span v-else class="note">Primera hoja del libro, con «DPI» en el encabezado. Máximo 20 MB.</span>
            </div>
        </section>

        <section class="rd-sec">
            <h3>2. Configurar columnas del reporte</h3>
            <SelectorColumnas v-model="cols" :categorias="columnas.categorias" />
            <p v-if="!columnas.dpi_completo" class="note" style="margin-top:8px">Su rol no tiene permiso para ver el DPI completo: en el reporte aparecerá enmascarado.</p>
        </section>

        <section class="rd-sec">
            <div class="err">{{ error }}</div>
            <div class="rd-acciones">
                <button class="btn pri" type="button" :disabled="!archivo || !cols.length || consultando" @click="consultar">
                    {{ consultando ? 'Consultando…' : 'Consultar registros' }}
                </button>
                <button class="btn" type="button" @click="limpiar">Limpiar</button>
                <button class="btn pri" type="button" :disabled="!puedeExportar || generando" @click="generar">
                    <template v-if="generando">Generando…</template>
                    <template v-else><Icono name="download" :size="16" /> Generar Reporte Excel</template>
                </button>
            </div>
            <p v-if="!auth.puede('reportes.exportar')" class="note">Su rol puede consultar pero no exportar datos personales.</p>
        </section>

        <section v-if="resumen" class="rd-sec">
            <h3>Resultados</h3>
            <div class="rd-kpis">
                <div><b>{{ fmt(resumen.dpi_unicos) }}</b><span>DPI únicos en el archivo</span></div>
                <div><b>{{ fmt(resumen.dpi_encontrados) }}</b><span>DPI encontrados</span></div>
                <div><b>{{ fmt(resumen.dpi_no_encontrados) }}</b><span>DPI no encontrados</span></div>
                <div><b>{{ fmt(resumen.registros) }}</b><span>Registros a exportar</span></div>
            </div>
            <p v-if="resumen.dpi_repetidos || resumen.filas_sin_dpi" class="note" style="margin-top:8px">
                Se omitieron {{ fmt(resumen.dpi_repetidos) }} DPI repetidos y {{ fmt(resumen.filas_sin_dpi) }} filas sin DPI.
            </p>
            <p v-if="resumen.registros > resumen.limite" class="err">El resultado supera el máximo de {{ fmt(resumen.limite) }} filas por exportación para su rol. Divida el archivo.</p>
            <div class="note" style="margin:12px 0 8px">Vista previa de las primeras filas; el Excel incluirá <b>todos</b> los registros y una hoja con los DPI no encontrados.</div>
            <div class="tw">
                <table>
                    <thead><tr><th v-for="c in resumen.columnas" :key="c">{{ c }}</th></tr></thead>
                    <tbody>
                        <tr v-for="(f, i) in resumen.filas" :key="i"><td v-for="(v, j) in f" :key="j">{{ v ?? '' }}</td></tr>
                        <tr v-if="!resumen.filas.length"><td :colspan="resumen.columnas.length"><span class="note">Ningún DPI del archivo se encontró en el sistema.</span></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Icono from '../common/Icono.vue';
import SelectorColumnas from './SelectorColumnas.vue';
import { useToast } from '../../composables/useToast';
import reporteService from '../../services/reporteService';
import { useAuthStore } from '../../stores/auth';
import { fmt } from '../../utils/formato';

const auth = useAuthStore();
const { toast } = useToast();

const input = ref(null);
const columnas = ref(null);
const archivo = ref(null);
const cols = ref([]);
const resumen = ref(null);
const error = ref('');
const consultando = ref(false);
const generando = ref(false);

const puedeExportar = computed(() => !!archivo.value && cols.value.length > 0 && auth.puede('reportes.exportar')
    && !(resumen.value && (resumen.value.registros > resumen.value.limite)));

watch(cols, () => { resumen.value = null; }, { deep: true });

function elegir(e) {
    const f = e.target.files && e.target.files[0];
    error.value = '';
    resumen.value = null;
    if (f && !/\.xlsx$/i.test(f.name)) {
        error.value = 'El archivo debe ser un Excel con extensión .xlsx';
        archivo.value = null;
    } else {
        archivo.value = f || null;
    }
    e.target.value = '';
}

async function consultar() {
    error.value = '';
    consultando.value = true;
    try {
        resumen.value = await reporteService.dpiPrevia(archivo.value, cols.value);
    } catch (e) {
        error.value = e.message;
    }
    consultando.value = false;
}

function limpiar() {
    archivo.value = null;
    resumen.value = null;
    error.value = '';
    cols.value = [...(columnas.value?.predeterminadas || [])];
}

async function generar() {
    error.value = '';
    generando.value = true;
    try {
        await reporteService.dpiExportar(archivo.value, cols.value);
        toast('Reporte generado · la descarga quedó registrada en la bitácora');
    } catch (e) {
        error.value = e.message;
    }
    generando.value = false;
}

onMounted(async () => {
    try {
        columnas.value = await reporteService.columnas();
        cols.value = [...columnas.value.predeterminadas];
    } catch (e) {
        error.value = e.message;
    }
});
</script>

<style scoped>
.rd-sec{padding:16px 0;border-top:1px solid var(--line)}
.rd-sec:first-of-type{border-top:0}
.rd-sec h3{margin:0 0 10px;font:700 14px var(--f-disp);color:var(--ink)}
.rd-file{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.rd-acciones{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.rd-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}
.rd-kpis div{display:flex;flex-direction:column;gap:2px;padding:12px 14px;border:1px solid var(--line);border-radius:12px;background:var(--glass-2)}
.rd-kpis b{font:700 22px var(--f-disp);color:var(--ink);font-variant-numeric:tabular-nums}
.rd-kpis span{font-size:12px;color:var(--ink3)}
.tw th,.tw td{white-space:nowrap}
</style>
