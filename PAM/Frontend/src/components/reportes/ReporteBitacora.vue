<template>
    <div class="ch"><div><h2>Bitácora de reportes</h2><p>Descargas realizadas desde ambas modalidades. Solo se guardan metadatos, no una copia del Excel.</p></div></div>
    <div class="form-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin:12px 0">
        <div class="field"><label for="rb-u">Usuario</label><input id="rb-u" v-model="f.usuario"></div>
        <div class="field"><label for="rb-m">Modalidad</label>
            <select id="rb-m" v-model="f.modalidad">
                <option value="">Todas</option><option value="dpi">Por DPI</option>
                <option value="geografico">Departamento y Municipio</option><option value="tablero">Reportes del tablero (CSV)</option>
            </select></div>
        <div class="field"><label for="rb-l">Ubicación</label><input id="rb-l" v-model="f.ubicacion" placeholder="Departamento o municipio"></div>
        <div class="field"><label for="rb-e">Estado</label>
            <select id="rb-e" v-model="f.estado">
                <option value="">Todos</option><option value="DESCARGADO">Descargado</option>
                <option value="INTERRUMPIDO">Interrumpido</option><option value="ERROR">Error</option>
            </select></div>
        <div class="field"><label for="rb-d">Desde</label><input id="rb-d" v-model="f.desde" type="date"></div>
        <div class="field"><label for="rb-h">Hasta</label><input id="rb-h" v-model="f.hasta" type="date"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;margin-bottom:10px"><button class="btn pri" @click="cargar">Filtrar</button></div>
    <div v-if="error" class="empty-state">{{ error }}</div>
    <div v-else-if="!filas" class="empty-state">Cargando…</div>
    <div v-else-if="!filas.length" class="empty-state">Sin descargas registradas con esos filtros.</div>
    <div v-else class="tw">
        <table>
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Modalidad</th><th>Departamento</th><th>Municipios</th><th>Registros</th><th>Columnas</th><th>Archivo</th><th>Estado</th></tr></thead>
            <tbody>
                <tr v-for="x in filas" :key="x.id">
                    <td class="tnum">{{ fts(x.ts) }}</td>
                    <td>{{ x.usuario || '—' }}</td>
                    <td>{{ modalidad(x) }}</td>
                    <td>{{ x.despues?.departamento || (x.despues?.filtros?.dep ? 'Código ' + x.despues.filtros.dep : '—') }}</td>
                    <td :title="(x.despues?.municipios || []).join(', ')">{{ munis(x) }}</td>
                    <td class="tnum">{{ x.despues?.filas != null ? fmt(x.despues.filas) : '—' }}</td>
                    <td :title="(x.despues?.columnas || []).join(', ')">{{ x.despues?.columnas ? x.despues.columnas.length : '—' }}</td>
                    <td>{{ x.despues?.archivo || '—' }}</td>
                    <td><b>{{ x.despues?.estado || 'DESCARGADO' }}</b><br><span v-if="x.despues?.error" class="note">{{ x.despues.error }}</span></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div v-if="filas && filas.length >= 100" class="pager"><span>Se muestran las últimas 100 descargas; use los filtros para acotar.</span></div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import reporteService from '../../services/reporteService';
import { fmt, fts } from '../../utils/formato';

const f = reactive({ usuario: '', modalidad: '', ubicacion: '', estado: '', desde: '', hasta: '' });
const filas = ref(null);
const error = ref('');

const modalidad = (x) => ({ reporte_dpi: 'Por DPI', reporte_geografico: 'Departamento y Municipio' }[x.entidad] || 'Tablero (CSV)');
function munis(x) {
    const m = x.despues?.municipios;
    if (!m || !m.length) return '—';
    return m.length > 3 ? `${m.slice(0, 3).join(', ')} y ${m.length - 3} más` : m.join(', ');
}

async function cargar() {
    try {
        const p = Object.fromEntries(Object.entries(f).filter(([, v]) => v));
        filas.value = await reporteService.bitacora({ ...p, limite: 100 });
        error.value = '';
    } catch (e) {
        error.value = e.message;
    }
}

onMounted(cargar);
</script>
