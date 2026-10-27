<template>
    <VistaHeader eyebrow="Control" titulo="Importaciones" desc="Cargue la matriz completa en CSV (en Excel: Guardar como → CSV). El sistema valida la estructura, detecta errores y duplicados, y actualiza los expedientes sin duplicarlos. Puede importar la matriz las veces que necesite: cada carga actualiza estados y conserva el historial." />
    <div class="card">
        <div class="ch">
            <div>
                <h2>Nueva importación</h2>
                <p>Hasta 600 MB · columnas reconocidas por nombre (DEPARTAMENTO, MUNICIPIO, NO. EXPEDIENTE, NOMBRE, DPI, … ESTADO ACTUAL)</p>
            </div>
            <div class="seg" role="group" aria-label="Modo">
                <button :class="{ on: ui.modoImp !== 'VALIDAR' }" @click="ui.modoImp = 'INCREMENTAL'">Importar</button>
                <button :class="{ on: ui.modoImp === 'VALIDAR' }" @click="ui.modoImp = 'VALIDAR'">Solo validar</button>
            </div>
        </div>
        <div class="drop" :class="{ over }" tabindex="0" role="button" @click="archivo.click()" @keydown.enter="archivo.click()"
            @dragover.prevent="over = true" @dragleave="over = false" @drop.prevent="soltar">
            <b>Arrastre aquí el archivo o haga clic para elegirlo</b>
            <span class="note">{{ ui.modoImp === 'VALIDAR' ? 'Modo validación: revisa el archivo y genera el reporte sin modificar la base.' : 'Modo importación: inserta expedientes nuevos y actualiza los existentes.' }}</span>
            <div style="margin-top:10px">
                <template v-if="subiendo">
                    <div class="bar" style="max-width:360px;margin:0 auto"><i :style="{ width: avance + '%' }"></i></div>
                    <span class="note">Subiendo {{ subiendo.name }} ({{ (subiendo.size / 1048576).toFixed(1) }} MB)…</span>
                </template>
                <span v-else-if="errorSubida" class="err">{{ errorSubida }}</span>
            </div>
        </div>
        <input ref="archivo" type="file" accept=".csv,.txt" hidden @change="elegido">
    </div>

    <div class="card">
        <div class="ch"><div><h2>Historial de cargas</h2><p>Se actualiza automáticamente</p></div></div>
        <div v-if="!lista.length" class="empty-state">Aún no hay importaciones.</div>
        <div v-else class="tw">
            <table>
                <thead><tr><th>Lote</th><th>Archivo</th><th>Estado</th><th>Avance</th><th class="n">Filas</th><th class="n">Nuevas</th><th class="n">Actualizadas</th><th class="n">Rechazadas</th><th class="n">Advertencias</th><th>Usuario</th><th>Fecha</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="l in lista" :key="l.id">
                        <td>#{{ l.id }}</td>
                        <td class="wrap">{{ l.archivo }}<br><span class="note">{{ l.modo === 'VALIDAR' ? 'Solo validación' : 'Importación' }}{{ l.codificacion ? ' · ' + l.codificacion : '' }}</span></td>
                        <td><span class="st" :class="claseEstado(l.estado)">{{ l.estado }}</span></td>
                        <td><div class="bar"><i :style="{ width: l.progreso + '%' }"></i></div><span class="note">{{ l.mensaje || '' }}</span></td>
                        <td class="n">{{ fmt(l.total_filas) }}</td><td class="n">{{ fmt(l.filas_insertadas) }}</td><td class="n">{{ fmt(l.filas_actualizadas) }}</td>
                        <td class="n">{{ fmt(l.filas_rechazadas) }}</td><td class="n">{{ fmt(l.advertencias) }}</td>
                        <td>{{ l.usuario || 'consola' }}</td><td class="tnum">{{ fts(l.creado_en) }}</td>
                        <td style="display:flex;gap:6px">
                            <button class="btn" @click="verLote(l.id)">Detalle</button>
                            <button v-if="l.advertencias || l.filas_rechazadas" class="btn" @click="descargarErrores(l.id)"><Icono name="download" :size="14" /> Errores</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <ModalForm v-if="detalle" :titulo="`Lote #${detalle.id} · ${detalle.archivo}`" :ancho="760" @cerrar="detalle = null">
        <div class="kvs">
            <div><small>Estado</small><b>{{ detalle.estado }}</b></div><div><small>Filas leídas</small><b>{{ fmt(detalle.total_filas) }}</b></div>
            <div><small>Nuevas</small><b>{{ fmt(detalle.filas_insertadas) }}</b></div><div><small>Actualizadas</small><b>{{ fmt(detalle.filas_actualizadas) }}</b></div>
            <div><small>Rechazadas</small><b>{{ fmt(detalle.filas_rechazadas) }}</b></div><div><small>Advertencias</small><b>{{ fmt(detalle.advertencias) }}</b></div>
        </div>
        <p class="note">{{ detalle.mensaje || '' }}</p>
        <h3 style="font:700 14px var(--f-disp);margin:6px 0 0">Conciliación matriz vs. base</h3>
        <p class="note">
            <template v-if="rep.total_matriz != null">
                Matriz: <b>{{ fmt(rep.total_matriz) }}</b> filas · en base para este lote: <b>{{ fmt(rep.total_base_lote) }}</b> ·
                <b v-if="dif.length" style="color:var(--crit)">{{ dif.length }} diferencias por departamento × estado</b>
                <b v-else style="color:var(--ok)">sin diferencias por departamento × estado</b>
            </template>
            <template v-else>Sin reporte.</template>
        </p>
        <div v-if="detalle.resumen_errores.length" class="tw">
            <table>
                <thead><tr><th>Nivel</th><th>Regla</th><th class="n">Filas</th><th class="n">Primera línea</th></tr></thead>
                <tbody><tr v-for="(er, i) in detalle.resumen_errores" :key="i"><td>{{ er.nivel }}</td><td>{{ er.regla }}</td><td class="n">{{ fmt(er.n) }}</td><td class="n">{{ er.primera_linea ?? '' }}</td></tr></tbody>
            </table>
        </div>
    </ModalForm>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Icono from '../../components/common/Icono.vue';
import ModalForm from '../../components/common/ModalForm.vue';
import VistaHeader from '../../components/common/VistaHeader.vue';
import { useToast } from '../../composables/useToast';
import importacionService from '../../services/importacionService';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { fmt, fts } from '../../utils/formato';

const ui = useUiStore();
const tablero = useTableroStore();
const { toast } = useToast();

const FINALES = ['COMPLETADO', 'VALIDADO', 'ERROR', 'CANCELADO'];
const lista = ref([]);
const archivo = ref(null);
const over = ref(false);
const subiendo = ref(null);
const avance = ref(0);
const errorSubida = ref('');
const detalle = ref(null);
let temporizador = 0;
let activo = true;

const rep = computed(() => detalle.value?.reporte || {});
const dif = computed(() => rep.value.diferencias || []);
const claseEstado = (s) => ({ COMPLETADO: 'ok', VALIDADO: 'ok', ERROR: 'err', CANCELADO: 'err', PENDIENTE: 'pend' }[s] || 'run');

async function refrescar() {
    clearTimeout(temporizador);
    try {
        const nueva = await importacionService.listar();
        const antes = lista.value;
        lista.value = nueva;
        const habiaActivos = antes.some((x) => !FINALES.includes(x.estado));
        const terminoAlguna = nueva.some((x) => x.estado === 'COMPLETADO' && antes.find((a) => a.id === x.id && a.estado !== 'COMPLETADO'));
        if (habiaActivos && terminoAlguna) {
            toast('Importación completada · actualizando el tablero');
            await tablero.cargar();
        }
        // mientras haya cargas en curso se consulta el avance cada 3 s
        if (activo && nueva.some((x) => !FINALES.includes(x.estado))) temporizador = setTimeout(refrescar, 3000);
    } catch (e) { /* se reintenta al volver a entrar */ }
}

async function subir(f) {
    errorSubida.value = '';
    subiendo.value = f;
    avance.value = 0;
    try {
        await importacionService.subir(f, ui.modoImp === 'VALIDAR' ? 'VALIDAR' : 'INCREMENTAL', (p) => { avance.value = p; });
        toast('Archivo recibido · en cola de procesamiento');
        subiendo.value = null;
        await refrescar();
        // el procesamiento arranca justo después de la subida
        clearTimeout(temporizador);
        temporizador = setTimeout(refrescar, 1500);
    } catch (e) {
        subiendo.value = null;
        errorSubida.value = e.message;
    }
}

function soltar(e) {
    over.value = false;
    if (e.dataTransfer.files[0]) subir(e.dataTransfer.files[0]);
}

function elegido() {
    if (archivo.value.files[0]) subir(archivo.value.files[0]);
    archivo.value.value = '';
}

async function verLote(id) {
    try { detalle.value = await importacionService.detalle(id); } catch (e) { toast(e.message); }
}

function descargarErrores(id) {
    importacionService.erroresCsv(id).catch((e) => toast(e.message));
}

onMounted(refrescar);
onBeforeUnmount(() => { activo = false; clearTimeout(temporizador); });
</script>
