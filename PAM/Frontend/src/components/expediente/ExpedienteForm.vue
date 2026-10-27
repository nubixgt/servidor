<template>
    <ModalForm :titulo="titulo" :boton="boton" :ancho="ancho" :guardar="guardar" @cerrar="e.form = null">
        <!-- Observación -->
        <template v-if="tipo === 'obs'">
            <div class="field"><label for="m_fecha">Fecha</label><input id="m_fecha" v-model="datos.fecha" type="date" :max="hoy"></div>
            <div class="field"><label for="m_texto">Observación</label><textarea id="m_texto" v-model="datos.texto"></textarea></div>
        </template>

        <!-- Visita -->
        <div v-else-if="tipo === 'vis'" class="form-grid">
            <div class="field"><label for="m_fecha">Fecha de la visita</label><input id="m_fecha" v-model="datos.fecha" type="date" :max="hoy"></div>
            <div class="field"><label for="m_resultado">Resultado</label>
                <select id="m_resultado" v-model="datos.resultado">
                    <option value="POSITIVA">Positiva</option><option value="NEGATIVA">Negativa</option><option value="NO_LOCALIZADO">No localizado</option>
                </select></div>
            <div class="field full"><label for="m_tecnico">Técnico</label><input id="m_tecnico" v-model="datos.tecnico" type="text"></div>
            <div class="field full"><label for="m_observacion">Observación</label><textarea id="m_observacion" v-model="datos.observacion"></textarea></div>
        </div>

        <!-- Resolución -->
        <div v-else-if="tipo === 'res'" class="form-grid">
            <div class="field"><label for="m_fecha">Fecha</label><input id="m_fecha" v-model="datos.fecha" type="date" :max="hoy"></div>
            <div class="field"><label for="m_numero">Número de resolución</label><input id="m_numero" v-model="datos.numero" type="text" maxlength="60"></div>
            <div class="field full"><label for="m_tipo_codigo">Tipo de resolución</label>
                <select id="m_tipo_codigo" v-model="datos.tipo_codigo">
                    <option v-for="t in cat.tipos_resolucion.filter((t) => t.activo)" :key="t.codigo" :value="t.codigo">{{ t.codigo }} · {{ t.nombre }}</option>
                </select></div>
            <div class="field full"><label for="m_descripcion">Descripción</label><textarea id="m_descripcion" v-model="datos.descripcion"></textarea></div>
        </div>

        <!-- Estado -->
        <template v-else-if="tipo === 'est'">
            <div class="form-grid">
                <div class="field"><label for="m_estado_codigo">Nuevo estado</label>
                    <select id="m_estado_codigo" v-model="datos.estado_codigo">
                        <option v-for="s in estadosBaja" :key="s.codigo" :value="s.codigo">{{ s.nombre }}</option>
                    </select></div>
                <div class="field"><label for="m_fecha">Fecha</label><input id="m_fecha" v-model="datos.fecha" type="date" :max="hoy"></div>
                <div class="field full"><label for="m_motivo">Motivo</label><textarea id="m_motivo" v-model="datos.motivo"></textarea></div>
            </div>
            <p class="note">Si el expediente está en nómina activa, se registra automáticamente la baja.</p>
        </template>

        <!-- Nómina -->
        <div v-else-if="tipo === 'nom'" class="form-grid">
            <div class="field"><label for="m_tipo">Movimiento</label>
                <select id="m_tipo" v-model="datos.tipo">
                    <option v-if="activo" value="BAJA">Baja de nómina</option><option v-else value="ALTA">Alta en nómina</option>
                </select></div>
            <div class="field"><label for="m_fecha">Fecha</label><input id="m_fecha" v-model="datos.fecha" type="date" :max="hoy"></div>
            <div v-if="activo" class="field full"><label for="m_estado_codigo">Estado resultante</label>
                <select id="m_estado_codigo" v-model="datos.estado_codigo">
                    <option v-for="s in estadosBaja" :key="s.codigo" :value="s.codigo">{{ s.nombre }}</option>
                </select></div>
            <div class="field full"><label for="m_motivo">Motivo / resolución que lo respalda</label><textarea id="m_motivo" v-model="datos.motivo"></textarea></div>
        </div>

        <!-- Contacto -->
        <template v-else-if="tipo === 'con'">
            <div class="field"><label for="m_telefono">Teléfono</label><input id="m_telefono" v-model="datos.telefono" type="tel"></div>
            <div class="field"><label for="m_direccion">Dirección</label><input id="m_direccion" v-model="datos.direccion" type="text" maxlength="400"></div>
        </template>

        <!-- Revisión de un caso -->
        <template v-else-if="tipo === 'caso'">
            <div class="field"><label for="m_estado">Resultado de la revisión</label>
                <select id="m_estado" v-model="datos.estado">
                    <option value="EN_REVISION">En revisión</option><option value="CONFIRMADO">Confirmado: requiere acción</option>
                    <option value="CORREGIDO">Corregido</option><option value="DESCARTADO">Descartado: no es un problema</option>
                </select></div>
            <div class="field"><label for="m_comentario">Comentario</label><textarea id="m_comentario" v-model="datos.comentario"></textarea></div>
        </template>

        <!-- Nueva solicitud -->
        <template v-else-if="tipo === 'nueva'">
            <div class="form-grid">
                <div class="field full"><label for="m_nombre">Nombre completo</label><input id="m_nombre" v-model="datos.nombre" type="text" required maxlength="200"></div>
                <div class="field"><label for="m_dpi">DPI / CUI (13 dígitos)</label><input id="m_dpi" v-model="datos.dpi" type="text" inputmode="numeric" maxlength="15"></div>
                <div class="field"><label for="m_fecha_nacimiento">Fecha de nacimiento</label><input id="m_fecha_nacimiento" v-model="datos.fecha_nacimiento" type="date" required></div>
                <div class="field"><label for="m_telefono">Teléfono</label><input id="m_telefono" v-model="datos.telefono" type="tel"></div>
                <div class="field"><label for="m_fecha_solicitud">Fecha de solicitud</label><input id="m_fecha_solicitud" v-model="datos.fecha_solicitud" type="date" required :max="hoy"></div>
                <div class="field full"><label for="m_municipio_id">Municipio</label>
                    <select id="m_municipio_id" v-model="datos.municipio_id">
                        <option v-for="m in municipios" :key="m.id" :value="m.id">{{ m.nombre }} · {{ deptos[m.departamento_id] }}</option>
                    </select></div>
                <div class="field full"><label for="m_direccion">Dirección</label><input id="m_direccion" v-model="datos.direccion" type="text" maxlength="400"></div>
            </div>
            <p class="note">El sistema valida el dígito verificador del CUI y evita registrar una segunda solicitud activa para la misma persona.</p>
        </template>
    </ModalForm>
</template>

<script setup>
import { computed, reactive } from 'vue';
import ModalForm from '../common/ModalForm.vue';
import { useToast } from '../../composables/useToast';
import expedienteService from '../../services/expedienteService';
import { useAuthStore } from '../../stores/auth';
import { useCatalogosStore } from '../../stores/catalogos';
import { useExpedienteStore } from '../../stores/expediente';
import { useUiStore } from '../../stores/ui';
import { hoyISO } from '../../utils/formato';
import { RULES } from '../../utils/reglas';

const e = useExpedienteStore();
const auth = useAuthStore();
const ui = useUiStore();
const { toast } = useToast();
const cat = useCatalogosStore().cat;

const tipo = e.form.tipo;
const hoy = hoyISO();
const activo = computed(() => e.d?.expediente.situacion_nomina === 1);
const estadosBaja = cat.estados.filter((s) => s.codigo !== 'EN_NOMINA_ACTIVA');

// municipios dentro del ámbito territorial del usuario
const u = auth.usuario;
const municipios = cat.municipios.filter((m) => (!u.municipio_id || m.id === u.municipio_id) && (!u.departamento_id || m.departamento_id === u.departamento_id));
const deptos = Object.fromEntries(cat.departamentos.map((d) => [d.id, d.nombre]));

const regla = RULES.find((r) => r.k === e.form.regla);
const titulos = {
    obs: 'Registrar observación', vis: 'Registrar visita', res: 'Emitir resolución', est: 'Cambiar estado', nom: 'Movimiento de nómina',
    con: 'Actualizar contacto', caso: 'Revisión: ' + (regla ? regla.t : e.form.regla), nueva: 'Registrar nueva solicitud',
};
const titulo = titulos[tipo];
const boton = tipo === 'nueva' ? 'Registrar' : 'Guardar';
const ancho = ['res', 'nueva'].includes(tipo) ? 680 : 0;

const iniciales = {
    obs: () => ({ fecha: hoy, texto: '' }),
    vis: () => ({ fecha: hoy, resultado: 'POSITIVA', tecnico: u.nombre, observacion: '' }),
    res: () => ({ fecha: hoy, numero: '', tipo_codigo: cat.tipos_resolucion.find((t) => t.activo)?.codigo, descripcion: '' }),
    est: () => ({ estado_codigo: estadosBaja[0]?.codigo, fecha: hoy, motivo: '' }),
    nom: () => ({ tipo: activo.value ? 'BAJA' : 'ALTA', fecha: hoy, estado_codigo: estadosBaja[0]?.codigo, motivo: '' }),
    con: () => ({ telefono: e.d.persona.telefono || '', direccion: e.d.persona.direccion || '' }),
    caso: () => ({ estado: 'EN_REVISION', comentario: '' }),
    nueva: () => ({ nombre: '', dpi: '', fecha_nacimiento: '', telefono: '', fecha_solicitud: hoy, municipio_id: municipios[0]?.id, direccion: '' }),
};
const datos = reactive(iniciales[tipo]());

const guardars = {
    obs: () => e.registrar('/observaciones', { fecha: datos.fecha || null, texto: datos.texto }, 'Observación registrada'),
    vis: () => e.registrar('/visitas', { fecha: datos.fecha, resultado: datos.resultado, tecnico: datos.tecnico || null, observacion: datos.observacion || null }, 'Visita registrada'),
    res: () => e.registrar('/resoluciones', { fecha: datos.fecha, tipo_codigo: datos.tipo_codigo, numero: datos.numero || null, descripcion: datos.descripcion || null }, 'Resolución registrada'),
    est: () => e.registrar('/estado', { estado_codigo: datos.estado_codigo, fecha: datos.fecha, motivo: datos.motivo }, 'Estado actualizado'),
    nom: () => e.registrar('/nomina', { tipo: datos.tipo, fecha: datos.fecha, motivo: datos.motivo || null, estado_codigo: activo.value ? datos.estado_codigo : null }, 'Movimiento de nómina registrado'),
    con: async () => {
        await expedienteService.actualizarContacto(e.id, { telefono: datos.telefono, direccion: datos.direccion });
        e.form = null;
        toast('Contacto actualizado');
        await e.abrir(e.id);
    },
    caso: () => e.registrar('/casos', { regla: e.form.regla, estado: datos.estado, comentario: datos.comentario || null }, 'Revisión guardada'),
    nueva: async () => {
        const r = await expedienteService.crear({
            ...datos, municipio_id: +datos.municipio_id, dpi: datos.dpi || null, telefono: datos.telefono || null, direccion: datos.direccion || null,
        });
        e.form = null;
        toast('Solicitud registrada');
        ui.datosDesactualizados = true;
        e.abrir(r.id);
    },
};
const guardar = guardars[tipo];
</script>
