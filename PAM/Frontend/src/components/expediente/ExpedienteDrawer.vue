<template>
    <Teleport to="body">
        <template v-if="e.abierto && d">
            <div class="scrim" @click="e.cerrar()"></div>
            <aside class="drawer" role="dialog" aria-modal="true" aria-label="Expediente 360">
                <div class="dh">
                    <button class="ibtn x" aria-label="Cerrar" @click="e.cerrar()"><Icono name="close" /></button>
                    <div class="eyebrow" style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--blue7);font-weight:700">Expediente 360°</div>
                    <h2>{{ p.nombre }}</h2>
                    <div class="idrow">
                        <span>DPI <b class="mask">{{ dpiTxt }}</b> <button v-if="d.puede_ver_dpi && !e.dpi" class="link" @click="e.mostrarDpi()">Mostrar</button></span>
                        <span>Exp. <b>{{ x.numero || 's/n' }}</b></span>
                        <span>{{ x.departamento }} / <b>{{ x.municipio }}</b></span>
                        <span><b>{{ p.edad ?? '—' }}</b> años</span>
                    </div>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <EstadoBadge :i="x.estado_orden" /><NominaBadge :n="x.situacion_nomina" />
                        <span v-if="x.fecha_suspension" class="badge" style="color:var(--warn);background:var(--warn-bg)">Suspensión registrada</span>
                        <span v-if="x.fecha_fallecimiento" class="badge" style="color:var(--dead);background:color-mix(in srgb,var(--dead) 12%,transparent)">Fallecimiento registrado</span>
                    </div>
                    <div class="tabs" role="tablist">
                        <button v-for="[k, l] in tabs" :key="k" role="tab" :class="{ on: e.tab === k }" :aria-selected="e.tab === k" @click="e.tab = k">{{ l }}</button>
                    </div>
                </div>

                <div class="db">
                    <!-- Resumen -->
                    <template v-if="e.tab === 'resumen'">
                        <div class="sumgrid">
                            <div class="sg"><small>Solicitud</small><b>{{ fd(x.fecha_solicitud) }}</b></div>
                            <div class="sg"><small>Última visita</small><b>{{ lv ? lv.resultado + ' · ' + fd(lv.fecha) : 'Sin visita' }}</b></div>
                            <div class="sg"><small>Última resolución</small><b>{{ lr ? lr.codigo + ' · ' + fd(lr.fecha) : 'Sin resolución' }}</b></div>
                            <div class="sg"><small>Nómina</small><b>{{ x.fecha_nomina_desde ? 'Desde ' + fd(x.fecha_nomina_desde) : 'No ha ingresado' }}</b></div>
                        </div>
                        <template v-if="d.alertas.length">
                            <h3 class="sec">Alertas del expediente</h3>
                            <div class="alist" style="margin-bottom:18px">
                                <div v-for="a in d.alertas" :key="a.regla" class="al">
                                    <span class="band" :style="{ background: PRC[regla(a.regla).p] }"></span>
                                    <span class="ai" :style="{ color: PRC[regla(a.regla).p] }"><Icono :name="regla(a.regla).icon" /></span>
                                    <div>
                                        <div class="at">{{ a.titulo }}<span class="pr" :class="regla(a.regla).p">{{ PRN[regla(a.regla).p] }}</span></div>
                                        <div class="ad">{{ caso(a.regla) ? 'Revisión: ' + caso(a.regla).estado.replace('_', ' ').toLowerCase() + (caso(a.regla).comentario ? ' · ' + caso(a.regla).comentario : '') : regla(a.regla).d }}</div>
                                    </div>
                                    <span></span>
                                    <button v-if="auth.puede('alertas.gestionar') || auth.puede('calidad.gestionar')" class="btn" @click="e.abrirForm('caso', { regla: a.regla })">Revisar</button>
                                    <span v-else></span>
                                </div>
                            </div>
                        </template>
                        <p v-if="d.otros_expedientes.length" class="note" style="margin:0 0 14px">
                            <b>La persona tiene {{ d.otros_expedientes.length }} expediente(s) más:</b>
                            <template v-for="(o, k) in d.otros_expedientes" :key="o.id">{{ k ? ', ' : ' ' }}<button class="link" @click="e.abrir(o.id)">{{ o.numero || 's/n' }} ({{ fd(o.fecha_solicitud) }})</button></template>
                        </p>
                        <h3 class="sec">Última observación</h3>
                        <p style="margin:0 0 18px;color:var(--ink2)">
                            <template v-if="x.ult_observacion">{{ x.ult_observacion }} <span class="note">· {{ fd(x.ult_observacion_fecha) }}</span></template>
                            <template v-else>Sin observaciones.</template>
                        </p>
                        <h3 class="sec" style="margin-bottom:12px">Línea de tiempo</h3>
                        <LineaTiempo :eventos="d.eventos" />
                    </template>

                    <!-- Datos personales -->
                    <dl v-else-if="e.tab === 'datos'" class="kv">
                        <dt>Nombre</dt><dd>{{ p.nombre }}</dd>
                        <dt>Documento</dt>
                        <dd>
                            <span class="mask">{{ dpiTxt }}</span>
                            <span v-if="p.cui_valido" class="badge" style="color:var(--ok);background:var(--ok-bg)">CUI válido</span>
                            <span v-else class="badge" style="color:var(--warn);background:var(--warn-bg)">{{ p.documento_tipo === 'CEDULA' ? 'Cédula / no CUI' : p.documento_tipo === 'CUI' ? 'CUI con verificador incorrecto' : 'Sin documento' }}</span>
                        </dd>
                        <dt>Fecha de nacimiento</dt><dd>{{ fd(p.fecha_nacimiento) }}</dd>
                        <dt>Edad calculada</dt><dd>{{ p.edad != null ? p.edad + ' años' : '—' }}</dd>
                        <dt>Teléfono</dt><dd><template v-if="p.telefono">{{ p.telefono }}</template><span v-else class="note">No registrado</span></dd>
                        <dt>Dirección</dt><dd>{{ p.direccion || '—' }}</dd>
                        <dt>Departamento</dt><dd>{{ x.departamento }}</dd>
                        <dt>Municipio</dt><dd>{{ x.municipio }} <span class="note">({{ x.municipio_id }})</span></dd>
                    </dl>

                    <!-- Solicitud -->
                    <dl v-else-if="e.tab === 'sol'" class="kv">
                        <dt>No. expediente</dt><dd>{{ x.numero || 's/n' }}</dd>
                        <dt>Fecha de solicitud</dt><dd>{{ fdl(x.fecha_solicitud) }}</dd>
                        <dt>Año de solicitud</dt><dd><template v-if="x.fecha_solicitud">{{ x.fecha_solicitud.slice(0, 4) }} <span class="note">(derivado de la fecha)</span></template><template v-else>—</template></dd>
                        <dt>Días transcurridos</dt><dd>{{ x.fecha_solicitud ? fmt((Date.now() - new Date(x.fecha_solicitud)) / 864e5) : '—' }}</dd>
                    </dl>

                    <!-- Visitas -->
                    <template v-else-if="e.tab === 'vis'">
                        <div v-if="!d.visitas.length" class="empty">Sin registros.</div>
                        <div v-else class="tw"><table>
                            <thead><tr><th>Fecha</th><th>Resultado</th><th>Observación</th><th>Técnico</th><th>Origen</th></tr></thead>
                            <tbody><tr v-for="v in d.visitas" :key="v.id">
                                <td class="wrap">{{ fd(v.fecha) }}</td>
                                <td class="wrap"><b v-if="v.resultado === 'POSITIVA'" style="color:var(--ok)">Positiva</b><b v-else style="color:var(--warn)">{{ v.resultado === 'NEGATIVA' ? 'Negativa' : 'No localizado' }}</b></td>
                                <td class="wrap">{{ v.observacion }}</td><td class="wrap">{{ v.tecnico }}</td><td class="wrap"><OrigenBadge :origen="v.origen" /></td>
                            </tr></tbody></table></div>
                    </template>

                    <!-- Resoluciones -->
                    <template v-else-if="e.tab === 'res'">
                        <div v-if="!d.resoluciones.length" class="empty">Sin registros.</div>
                        <div v-else class="tw"><table>
                            <thead><tr><th>Fecha</th><th>Código</th><th>Tipo</th><th>Familia</th><th>Número</th><th>Origen</th></tr></thead>
                            <tbody><tr v-for="(r, i) in d.resoluciones" :key="r.id">
                                <td class="wrap">{{ fd(r.fecha) }} <span v-if="i === 0" class="badge" style="color:var(--blue7);background:var(--blue1)">Más reciente</span></td>
                                <td class="wrap"><b>{{ r.codigo }}</b></td><td class="wrap">{{ r.tipo }}</td><td class="wrap">{{ r.familia }}</td><td class="wrap">{{ r.numero }}</td><td class="wrap"><OrigenBadge :origen="r.origen" /></td>
                            </tr></tbody></table></div>
                    </template>

                    <!-- Nómina -->
                    <template v-else-if="e.tab === 'nom'">
                        <dl v-if="x.fecha_nomina_desde" class="kv">
                            <dt>En nómina desde</dt><dd>{{ fdl(x.fecha_nomina_desde) }}</dd>
                            <dt>En nómina hasta</dt><dd>{{ x.situacion_nomina === 1 ? 'Vigente' : fdl(x.fecha_nomina_hasta) }}</dd>
                            <dt>Situación</dt><dd><NominaBadge :n="x.situacion_nomina" /></dd>
                            <dt>Tiempo solicitud → nómina</dt><dd>{{ x.fecha_solicitud ? fmt((new Date(x.fecha_nomina_desde) - new Date(x.fecha_solicitud)) / 864e5) + ' días' : '—' }}</dd>
                        </dl>
                        <div v-else class="empty">No ha ingresado a nómina.</div>
                        <h3 class="sec" style="margin:18px 0 8px">Movimientos</h3>
                        <div v-if="!d.nomina.length" class="empty">Sin registros.</div>
                        <div v-else class="tw"><table>
                            <thead><tr><th>Fecha</th><th>Movimiento</th><th>Motivo</th><th>Origen</th></tr></thead>
                            <tbody><tr v-for="m in d.nomina" :key="m.id">
                                <td class="wrap">{{ fd(m.fecha) }}</td><td class="wrap">{{ m.tipo === 'ALTA' ? 'Alta' : 'Baja' }}</td><td class="wrap">{{ m.motivo }}</td><td class="wrap"><OrigenBadge :origen="m.origen" /></td>
                            </tr></tbody></table></div>
                    </template>

                    <!-- Apoderado -->
                    <dl v-else-if="e.tab === 'apo' && d.apoderado" class="kv">
                        <dt>Tipo de identificación</dt><dd>{{ d.apoderado.tipo_id || '—' }}</dd>
                        <dt>No. identificación</dt><dd><span class="mask">{{ d.apoderado.numero_id || '—' }}</span></dd>
                        <dt>Nombre</dt><dd>{{ d.apoderado.nombre || '—' }}</dd>
                    </dl>
                    <div v-else-if="e.tab === 'apo'" class="empty">Sin apoderado registrado.</div>

                    <!-- Observaciones -->
                    <template v-else-if="e.tab === 'obs'">
                        <div v-if="!d.observaciones.length" class="empty">Sin registros.</div>
                        <div v-else class="tw"><table>
                            <thead><tr><th>Fecha</th><th>Observación</th><th>Usuario</th><th>Origen</th></tr></thead>
                            <tbody><tr v-for="o in d.observaciones" :key="o.id">
                                <td class="wrap">{{ fd(o.fecha) }}</td><td class="wrap">{{ o.texto }}</td><td class="wrap">{{ o.usuario }}</td><td class="wrap"><OrigenBadge :origen="o.origen" /></td>
                            </tr></tbody></table></div>
                    </template>

                    <!-- Estado -->
                    <template v-else-if="e.tab === 'est'">
                        <div style="margin-bottom:14px"><EstadoBadge :i="x.estado_orden" /> <span class="note">desde {{ fd(x.fecha_estado) }}</span></div>
                        <div v-if="!d.estados.length" class="empty">Sin registros.</div>
                        <div v-else class="tw"><table>
                            <thead><tr><th>Fecha</th><th>Estado</th><th>Motivo</th><th>Usuario</th><th>Vigente</th></tr></thead>
                            <tbody><tr v-for="s in d.estados" :key="s.id">
                                <td class="wrap">{{ fd(s.fecha) }}</td><td class="wrap">{{ s.estado }}</td><td class="wrap">{{ s.motivo }}</td>
                                <td class="wrap">{{ s.usuario || (s.origen === 'MATRIZ' ? 'Importación' : '') }}</td><td class="wrap">{{ s.vigente ? 'Sí' : '' }}</td>
                            </tr></tbody></table></div>
                    </template>

                    <!-- Historial -->
                    <LineaTiempo v-else-if="e.tab === 'his'" :eventos="d.eventos" />

                    <!-- Gestionar -->
                    <template v-else-if="e.tab === 'ges'">
                        <div class="acc-grid">
                            <button v-for="a in acciones" :key="a[1]" class="acc" @click="e.abrirForm(a[1])">
                                <Icono :name="a[4]" :size="18" /><span><b>{{ a[2] }}</b><small>{{ a[3] }}</small></span>
                            </button>
                        </div>
                        <p class="note" style="margin-top:14px">Cada registro queda en el historial del expediente y en la bitácora de auditoría con su usuario, fecha e IP.</p>
                    </template>
                </div>
            </aside>
        </template>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import LineaTiempo from './LineaTiempo.vue';
import OrigenBadge from './OrigenBadge.vue';
import EstadoBadge from '../common/EstadoBadge.vue';
import Icono from '../common/Icono.vue';
import NominaBadge from '../common/NominaBadge.vue';
import { useAuthStore } from '../../stores/auth';
import { useExpedienteStore } from '../../stores/expediente';
import { fd, fdl, fmt } from '../../utils/formato';
import { PRC, PRN, RULES } from '../../utils/reglas';

const e = useExpedienteStore();
const auth = useAuthStore();

const d = computed(() => e.d);
const p = computed(() => d.value.persona);
const x = computed(() => d.value.expediente);
const lv = computed(() => d.value.visitas[0]);
const lr = computed(() => d.value.resoluciones[0]);
const dpiTxt = computed(() => e.dpi || p.value.dpi || '—');

const puedeGestionar = computed(() => ['observacion.registrar', 'visita.registrar', 'resolucion.emitir', 'estado.cambiar', 'nomina.gestionar', 'beneficiarios.editar', 'alertas.gestionar'].some(auth.puede));
const tabs = computed(() => {
    const t = [['resumen', 'Resumen'], ['datos', 'Datos personales'], ['sol', 'Solicitud'], ['vis', 'Visitas'], ['res', 'Resoluciones'], ['nom', 'Nómina'], ['apo', 'Apoderado'], ['obs', 'Observaciones'], ['est', 'Estado'], ['his', 'Historial']];
    if (puedeGestionar.value) t.push(['ges', 'Gestionar']);
    return t;
});

const acciones = computed(() => [
    ['observacion.registrar', 'obs', 'Registrar observación', 'Nota de seguimiento con fecha', 'info'],
    ['visita.registrar', 'vis', 'Registrar visita', 'Positiva, negativa o no localizado', 'pin'],
    ['resolucion.emitir', 'res', 'Emitir resolución', 'Según el catálogo institucional', 'stamp'],
    ['estado.cambiar', 'est', 'Cambiar estado', 'Con motivo obligatorio', 'pulse'],
    ['nomina.gestionar', 'nom', 'Movimiento de nómina', 'Alta o baja', 'wallet'],
    ['beneficiarios.editar', 'con', 'Actualizar contacto', 'Teléfono y dirección', 'users'],
].filter((a) => auth.puede(a[0])));

const regla = (k) => RULES.find((z) => z.k === k) || { p: 'm', icon: 'alert', d: '' };
const caso = (k) => d.value.casos.find((c) => c.regla === k);
</script>

<style scoped>
.sec {
    font: 700 14px var(--f-disp);
    margin: 0 0 8px;
}
</style>
