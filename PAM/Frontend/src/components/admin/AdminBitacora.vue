<template>
    <div class="form-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));margin:12px 0">
        <div class="field"><label for="bu">Usuario</label><input id="bu" v-model="f.usuario"></div>
        <div class="field"><label for="ba">Acción</label>
            <select id="ba" v-model="f.accion"><option value="">Todas</option><option v-for="a in ACCIONES" :key="a">{{ a }}</option></select></div>
        <div class="field"><label for="bd">Desde</label><input id="bd" v-model="f.desde" type="date"></div>
        <div class="field"><label for="bh">Hasta</label><input id="bh" v-model="f.hasta" type="date"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;margin-bottom:10px"><button class="btn pri" @click="cargar">Filtrar</button></div>
    <div v-if="error" class="empty-state">{{ error }}</div>
    <div v-else-if="!filas" class="empty-state">Cargando…</div>
    <div v-else class="tw">
        <table>
            <thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Registro</th><th>Antes</th><th>Después</th><th>IP</th></tr></thead>
            <tbody>
                <tr v-for="x in filas" :key="x.id">
                    <td class="tnum">{{ fts(x.ts) }}</td>
                    <td>{{ x.usuario || '—' }}</td>
                    <td><b>{{ x.accion }}</b><br><span class="note">{{ x.modulo || '' }}</span></td>
                    <td>{{ x.entidad || '' }} {{ x.entidad_id || '' }}</td>
                    <td><div class="json">{{ x.antes ? JSON.stringify(x.antes, null, 1) : '' }}</div></td>
                    <td><div class="json">{{ x.despues ? JSON.stringify(x.despues, null, 1) : '' }}</div></td>
                    <td>{{ x.ip || '' }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import adminService from '../../services/adminService';
import { fts } from '../../utils/formato';

const ACCIONES = ['LOGIN', 'LOGIN_FALLIDO', 'VER_EXPEDIENTE', 'VER_DPI', 'EXPORTAR', 'IMPORTAR', 'SUBIR_IMPORTACION', 'INSERT', 'UPDATE', 'DELETE', 'CAMBIO_CLAVE', 'RESTABLECER_CLAVE'];
const f = reactive({ usuario: '', accion: '', desde: '', hasta: '' });
const filas = ref(null);
const error = ref('');

async function cargar() {
    try {
        filas.value = await adminService.bitacora({ ...f, limite: 100 });
        error.value = '';
    } catch (e) {
        error.value = e.message;
    }
}

onMounted(cargar);
</script>
