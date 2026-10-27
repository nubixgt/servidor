<template>
    <div v-if="error" class="empty-state">{{ error }}</div>
    <div v-else-if="!parametros" class="empty-state">Cargando…</div>
    <template v-else>
        <div class="tw" style="margin-top:12px">
            <table>
                <thead><tr><th>Parámetro</th><th>Valor</th><th>Descripción</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="p in parametros" :key="p.clave">
                        <td><b>{{ p.clave }}</b></td>
                        <td><div class="json">{{ JSON.stringify(p.valor) }}</div></td>
                        <td class="wrap">{{ p.descripcion || '' }}</td>
                        <td><button class="btn" @click="editarParametro(p)">Editar</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h3 style="font:700 14px var(--f-disp);margin:20px 0 8px">Catálogo de tipos de resolución</h3>
        <div class="tw">
            <table>
                <thead><tr><th>Código</th><th>Nombre</th><th>Familia</th><th>Activo</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="t in cat.tipos_resolucion" :key="t.codigo">
                        <td><b>{{ t.codigo }}</b></td>
                        <td class="wrap">{{ t.nombre }}</td>
                        <td>{{ (cat.familias_resolucion.find((f) => f.id === t.familia_id) || {}).nombre || '' }}</td>
                        <td>{{ t.activo ? 'Sí' : 'No' }}</td>
                        <td><button class="btn" @click="editarTipo(t)">Editar</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>

    <ModalForm v-if="par" :titulo="'Parámetro ' + par.clave" :guardar="guardarParametro" @cerrar="par = null">
        <p class="note">{{ par.descripcion || '' }}</p>
        <div class="field"><label for="m_valor">Valor (JSON)</label><textarea id="m_valor" v-model="par.texto"></textarea></div>
    </ModalForm>

    <ModalForm v-if="tipo" :titulo="'Tipo de resolución ' + tipo.codigo" :guardar="guardarTipo" @cerrar="tipo = null">
        <div class="field"><label for="m_nombre">Nombre</label><input id="m_nombre" v-model="tipo.nombre" type="text"></div>
        <div class="field"><label for="m_familia_id">Familia</label>
            <select id="m_familia_id" v-model="tipo.familia_id"><option v-for="f in cat.familias_resolucion" :key="f.id" :value="f.id">{{ f.nombre }}</option></select></div>
        <div class="field"><label for="m_activo">Activo</label>
            <select id="m_activo" v-model="tipo.activo"><option :value="true">Sí</option><option :value="false">No</option></select></div>
    </ModalForm>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import ModalForm from '../common/ModalForm.vue';
import { useToast } from '../../composables/useToast';
import adminService from '../../services/adminService';
import { useCatalogosStore } from '../../stores/catalogos';

const catalogos = useCatalogosStore();
const { toast } = useToast();
const parametros = ref(null);
const error = ref('');
const par = ref(null);
const tipo = ref(null);

// catálogo reactivo: se refresca tras editar un tipo de resolución
const cat = computed(() => catalogos.cat);

async function cargar() {
    try {
        parametros.value = await adminService.parametros();
        error.value = '';
    } catch (e) {
        error.value = e.message;
    }
}

function editarParametro(p) {
    par.value = { clave: p.clave, descripcion: p.descripcion, texto: JSON.stringify(p.valor, null, 2) };
}

async function guardarParametro() {
    let valor;
    try { valor = JSON.parse(par.value.texto); } catch (e) { throw new Error('El valor no es JSON válido'); }
    await adminService.cambiarParametro(par.value.clave, valor);
    par.value = null;
    toast('Parámetro guardado');
    cargar();
}

function editarTipo(t) {
    tipo.value = { codigo: t.codigo, nombre: t.nombre, familia_id: t.familia_id, activo: !!t.activo };
}

async function guardarTipo() {
    const t = tipo.value;
    await adminService.guardarTipoResolucion({ codigo: t.codigo, nombre: t.nombre, familia_id: +t.familia_id, activo: t.activo });
    await catalogos.cargar();
    tipo.value = null;
    toast('Catálogo actualizado · el tablero se recalcula en el próximo refresco');
}

onMounted(cargar);
</script>
