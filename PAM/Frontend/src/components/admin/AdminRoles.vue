<template>
    <div v-if="error" class="empty-state">{{ error }}</div>
    <div v-else-if="!datos" class="empty-state">Cargando…</div>
    <template v-else>
        <p class="note" style="margin:12px 0">Marque los permisos de cada rol y guarde. Los cambios aplican de inmediato y quedan en la bitácora.</p>
        <div class="tw">
            <table class="perm">
                <thead>
                    <tr>
                        <th>Permiso</th>
                        <th v-for="r in datos.roles" :key="r.id" :title="r.descripcion || ''">{{ r.nombre }}<br><span class="note">{{ r.usuarios }} usuario(s)</span></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="m in modulos" :key="m">
                        <tr><td :colspan="datos.roles.length + 1" style="font-weight:700;color:var(--blue7);background:var(--glass-2)">{{ m }}</td></tr>
                        <tr v-for="p in datos.permisos.filter((x) => x.modulo === m)" :key="p.codigo">
                            <td>{{ p.descripcion }}<br><span class="note">{{ p.codigo }}</span></td>
                            <td v-for="r in datos.roles" :key="r.id">
                                <input v-model="seleccion[r.id]" type="checkbox" :value="p.codigo" :aria-label="`${p.descripcion} · ${r.nombre}`">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div style="display:flex;justify-content:space-between;gap:8px;margin-top:12px">
            <button class="btn" @click="nuevo = { codigo: '', nombre: '', descripcion: '' }">Nuevo rol</button>
            <button class="btn pri" @click="guardarPermisos">Guardar permisos</button>
        </div>
    </template>

    <ModalForm v-if="nuevo" titulo="Nuevo rol" :guardar="crearRol" @cerrar="nuevo = null">
        <div class="field"><label for="m_codigo">Código (MAYÚSCULAS, sin espacios)</label><input id="m_codigo" v-model="nuevo.codigo" type="text" pattern="[A-Z_]{3,30}"></div>
        <div class="field"><label for="m_nombre">Nombre</label><input id="m_nombre" v-model="nuevo.nombre" type="text"></div>
        <div class="field"><label for="m_descripcion">Descripción</label><textarea id="m_descripcion" v-model="nuevo.descripcion"></textarea></div>
    </ModalForm>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import ModalForm from '../common/ModalForm.vue';
import { useToast } from '../../composables/useToast';
import adminService from '../../services/adminService';

const { toast } = useToast();
const datos = ref(null);
const error = ref('');
const seleccion = ref({}); // rol_id -> [permisos marcados]
const nuevo = ref(null);

const modulos = computed(() => [...new Set(datos.value.permisos.map((p) => p.modulo))]);

async function cargar() {
    try {
        datos.value = await adminService.roles();
        seleccion.value = Object.fromEntries(datos.value.roles.map((r) => [r.id, [...r.permisos]]));
        error.value = '';
    } catch (e) {
        error.value = e.message;
    }
}

async function guardarPermisos() {
    try {
        for (const r of datos.value.roles) {
            const sel = [...seleccion.value[r.id]].sort();
            if (JSON.stringify(sel) !== JSON.stringify([...r.permisos].sort())) await adminService.asignarPermisos(r.id, sel);
        }
        toast('Permisos guardados');
        cargar();
    } catch (e) {
        toast(e.message);
    }
}

async function crearRol() {
    await adminService.crearRol({ ...nuevo.value });
    nuevo.value = null;
    toast('Rol creado');
    cargar();
}

onMounted(cargar);
</script>
