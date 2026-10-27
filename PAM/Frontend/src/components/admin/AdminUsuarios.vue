<template>
    <div v-if="error" class="empty-state">{{ error }}</div>
    <div v-else-if="!usuarios" class="empty-state">Cargando…</div>
    <template v-else>
        <div style="display:flex;justify-content:flex-end;margin:10px 0">
            <button class="btn pri" @click="abrirForm(null)"><Icono name="users" :size="16" /> Nuevo usuario</button>
        </div>
        <div class="tw">
            <table>
                <thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Ámbito</th><th>Último acceso</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="u in usuarios" :key="u.id">
                        <td><b>{{ u.usuario }}</b></td>
                        <td>{{ u.nombre }}<br><span class="note">{{ u.correo || '' }}</span></td>
                        <td>{{ u.rol }}</td>
                        <td>{{ u.municipio || u.departamento || 'Nacional' }}</td>
                        <td class="tnum">{{ fts(u.ultimo_acceso) }}</td>
                        <td>
                            <template v-if="u.activo"><span v-if="bloqueado(u)" class="st pend">Bloqueado</span><span v-else class="st ok">Activo</span></template>
                            <span v-else class="st err">Inactivo</span>
                            <template v-if="u.debe_cambiar_clave"><br><span class="note">Debe cambiar clave</span></template>
                        </td>
                        <td style="display:flex;gap:6px">
                            <button class="btn" @click="abrirForm(u)">Editar</button>
                            <button class="btn" @click="restablecer(u.id)">Restablecer clave</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </template>

    <ModalForm v-if="form" :titulo="editando ? 'Editar usuario ' + editando.usuario : 'Nuevo usuario'" :ancho="680" :guardar="guardar" @cerrar="form = null">
        <div class="form-grid">
            <div v-if="!editando" class="field"><label for="m_usuario">Usuario (sin espacios)</label><input id="m_usuario" v-model="form.usuario" type="text" required pattern="[A-Za-z0-9._-]{3,40}"></div>
            <div class="field" :class="{ full: editando }"><label for="m_nombre">Nombre completo</label><input id="m_nombre" v-model="form.nombre" type="text"></div>
            <div class="field full"><label for="m_correo">Correo institucional</label><input id="m_correo" v-model="form.correo" type="email"></div>
            <div class="field"><label for="m_rol_id">Rol</label>
                <select id="m_rol_id" v-model="form.rol_id"><option v-for="r in roles" :key="r.id" :value="r.id">{{ r.nombre }}</option></select></div>
            <div class="field"><label for="m_ambito">Ámbito territorial</label>
                <select id="m_ambito" v-model="form.ambito">
                    <option value="">Nacional</option>
                    <option v-for="d in cat.departamentos" :key="'d' + d.id" :value="'d' + d.id">Departamento: {{ d.nombre }}</option>
                    <option v-for="m in cat.municipios" :key="'m' + m.id" :value="'m' + m.id">Municipio: {{ m.nombre }}</option>
                </select></div>
            <div v-if="editando" class="field"><label for="m_activo">Estado</label>
                <select id="m_activo" v-model="form.activo"><option :value="true">Activo</option><option :value="false">Inactivo</option></select></div>
        </div>
    </ModalForm>

    <ModalForm v-if="clave" :titulo="clave.titulo" @cerrar="clave = null">
        <p>Entregue esta contraseña temporal al usuario por un canal seguro. Se mostrará solo esta vez y el sistema le pedirá cambiarla al ingresar.</p>
        <div class="clave">{{ clave.valor }}</div>
    </ModalForm>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Icono from '../common/Icono.vue';
import ModalForm from '../common/ModalForm.vue';
import { useToast } from '../../composables/useToast';
import adminService from '../../services/adminService';
import { useAuthStore } from '../../stores/auth';
import { useCatalogosStore } from '../../stores/catalogos';
import { fts } from '../../utils/formato';

const auth = useAuthStore();
const cat = useCatalogosStore().cat;
const { toast } = useToast();

const usuarios = ref(null);
const roles = ref([]);
const error = ref('');
const form = ref(null);
const editando = ref(null);
const clave = ref(null);

const bloqueado = (u) => u.bloqueado_hasta && new Date(String(u.bloqueado_hasta).replace(' ', 'T')) > new Date();

async function cargar() {
    try {
        const [us, rr] = await Promise.all([adminService.usuarios(), adminService.roles()]);
        usuarios.value = us;
        roles.value = rr.roles;
        error.value = '';
    } catch (e) {
        error.value = e.message;
    }
}

function abrirForm(u) {
    editando.value = u;
    form.value = u
        ? { nombre: u.nombre, correo: u.correo || '', rol_id: u.rol_id, activo: !!u.activo, ambito: u.municipio_id ? 'm' + u.municipio_id : u.departamento_id ? 'd' + u.departamento_id : '' }
        : { usuario: '', nombre: '', correo: '', rol_id: roles.value[0]?.id, ambito: '' };
}

async function guardar() {
    const d = form.value;
    const amb = d.ambito || '';
    const cuerpo = { nombre: d.nombre, correo: d.correo || null, rol_id: +d.rol_id };
    if (amb.startsWith('d')) { cuerpo.departamento_id = +amb.slice(1); cuerpo.municipio_id = null; }
    else if (amb.startsWith('m')) { cuerpo.municipio_id = +amb.slice(1); }

    if (editando.value) {
        if (!amb) cuerpo.ambito_nacional = true;
        cuerpo.activo = d.activo;
        if (editando.value.id === auth.usuario.id) { delete cuerpo.rol_id; delete cuerpo.activo; }
        await adminService.editarUsuario(editando.value.id, cuerpo);
        form.value = null;
        toast('Usuario actualizado');
    } else {
        const r = await adminService.crearUsuario({ ...cuerpo, usuario: d.usuario });
        form.value = null;
        clave.value = { titulo: 'Usuario creado', valor: r.clave_temporal };
    }
    cargar();
}

async function restablecer(id) {
    try {
        const r = await adminService.restablecerClave(id);
        clave.value = { titulo: 'Clave restablecida', valor: r.clave_temporal };
    } catch (e) {
        toast(e.message);
    }
}

onMounted(cargar);
</script>
