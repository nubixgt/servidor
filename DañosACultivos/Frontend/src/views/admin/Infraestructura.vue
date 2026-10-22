<template>
    <div>
        <section>
            <div class="content-head">
                <div>
                    <h2>Afectaciones en infraestructura</h2>
                    <p>{{ esTecnico ? 'Los registros que has realizado.' : 'Infraestructura productiva afectada y familias impactadas.' }}</p>
                </div>
                <router-link to="/infraestructura/crear" class="btn btn-primary"><PlusIcon class="w-5 h-5" />Crear registro</router-link>
            </div>

            <div class="stats">
                <div v-for="s in stats" :key="s.title" class="panel stat">
                    <div class="stat-icon"><component :is="s.icon" class="w-5 h-5" /></div>
                    <div class="stat-title">{{ s.title }}</div>
                    <strong>{{ s.value }}</strong>
                    <div class="stat-foot">{{ s.foot }}</div>
                </div>
            </div>

            <article class="panel">
                <div class="panel-head">
                    <div>
                        <h3>Bitácora de infraestructura</h3>
                        <p>Filtra por tipo de infraestructura o por período de informe.</p>
                    </div>
                    <button type="button" class="btn" @click="exportarCSV"><ArrowDownTrayIcon class="w-5 h-5" />Exportar CSV</button>
                </div>

                <div class="filters">
                    <div class="searchbox">
                        <MagnifyingGlassIcon />
                        <input v-model="busqueda" type="search" class="field" placeholder="Buscar tipo, causa o municipio…" aria-label="Buscar registros" />
                    </div>
                    <select v-model="filtroTipo" class="field" aria-label="Filtrar por tipo">
                        <option value="">Todos los tipos</option>
                        <option v-for="e in tiposLista" :key="e">{{ e }}</option>
                    </select>
                    <input v-model="desde" type="date" class="field" aria-label="Desde" />
                    <input v-model="hasta" type="date" class="field" aria-label="Hasta" />
                </div>

                <div v-if="loading" class="empty">Cargando…</div>
                <div v-else-if="!filtrados.length" class="empty">{{ registros.length ? 'No se encontraron registros con estos filtros.' : 'Aún no hay registros de infraestructura.' }}</div>

                <div v-else class="tablewrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Fecha informe</th><th>Monitoreo</th><th>Semana</th><th>Ubicación</th><th>Tipo de infraestructura</th><th>Evento reportado</th><th>Causa</th>
                                <th>Familias</th><th>GPS</th><th>Pérdida (Q)</th><th>Registrado por</th><th>Notas</th>
                                <th v-if="puedeEliminar"><span class="sr-only">Eliminar</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in filtrados" :key="r.id">
                                <td>{{ r.fechaInforme }}</td>
                                <td>{{ periodo(r) }}</td>
                                <td>{{ r.semana ? `${r.semana}/${r.anioSemana}` : '—' }}</td>
                                <td>{{ ubicacion(r) }}</td>
                                <td>{{ tipo(r) }}</td>
                                <td>{{ r.eventoReportado || '—' }}</td>
                                <td>{{ causa(r) }}</td>
                                <td><b>{{ r.famAfectadas ?? '—' }}</b></td>
                                <td class="font-mono text-[11px]">{{ r.gps || '—' }}</td>
                                <td>{{ r.perdidaEconomica != null ? num(r.perdidaEconomica) : '—' }}</td>
                                <td>{{ r.registradoPor || '—' }}</td>
                                <td class="max-w-[220px] truncate" :title="r.notas">{{ r.notas || '—' }}</td>
                                <td v-if="puedeEliminar">
                                    <button type="button" class="iconbtn" title="Eliminar" @click="eliminar(r)"><TrashIcon class="w-4 h-4" style="color: var(--red);" /></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="list-footer">
                    <span>{{ filtrados.length }} de {{ registros.length }} registro(s)</span>
                    <button type="button" class="linkbtn" @click="limpiarFiltros">Restablecer filtros</button>
                </div>
            </article>
        </section>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import infraestructuraService from '../../services/infraestructuraService';
import authService from '../../services/authService';
import { descargarCSV } from '../../utils/periodo';
import { toastError, confirmDialog } from '../../utils/alerts';
import { ArrowDownTrayIcon, TrashIcon, PlusIcon, MagnifyingGlassIcon, DocumentTextIcon, UserGroupIcon, MapPinIcon, BanknotesIcon } from '@heroicons/vue/24/outline';

const rol = authService.user()?.rol;
const esTecnico = rol === 'tecnico';
const puedeEliminar = rol === 'admin' || rol === 'supervisor';

const registros = ref([]);
const loading = ref(true);
const busqueda = ref('');
const filtroTipo = ref('');
const desde = ref('');
const hasta = ref('');

const num = (v) => Number(v ?? 0).toLocaleString('es-GT', { maximumFractionDigits: 2 });
const suma = (l, k) => l.reduce((s, r) => s + (Number(r[k]) || 0), 0);
const tipo = (r) => (r.tipoInfraestructura === 'Otra' && r.tipoOtra ? `Otra: ${r.tipoOtra}` : r.tipoInfraestructura);
const causa = (r) => (r.causa === 'Otra' && r.causaOtra ? `Otra: ${r.causaOtra}` : r.causa);
const periodo = (r) => (r.monitoreoInicio ? `${r.monitoreoInicio} al ${r.monitoreoFin}` : '—');
const ubicacion = (r) => [r.centroPoblado, r.municipio, r.departamento].filter(Boolean).join(', ') || '—';
const tiposLista = computed(() => [...new Set(registros.value.map(tipo))].sort());

const filtrados = computed(() => {
    const q = busqueda.value.trim().toLowerCase();
    return registros.value.filter((r) =>
        (!q || [tipo(r), causa(r), r.municipio, r.departamento, r.centroPoblado].join(' ').toLowerCase().includes(q))
        && (!filtroTipo.value || tipo(r) === filtroTipo.value)
        && (!desde.value || r.fechaInforme >= desde.value)
        && (!hasta.value || r.fechaInforme <= hasta.value));
});
const limpiarFiltros = () => { busqueda.value = ''; filtroTipo.value = ''; desde.value = ''; hasta.value = ''; };

const stats = computed(() => {
    const l = filtrados.value;
    return [
        { title: 'Registros', value: l.length, foot: `${new Set(l.map((r) => r.departamento)).size} depto(s). con reportes`, icon: DocumentTextIcon },
        { title: 'Familias afectadas', value: num(suma(l, 'famAfectadas')), foot: 'Suma de los registros filtrados', icon: UserGroupIcon },
        { title: 'Con ubicación GPS', value: l.filter((r) => r.gps).length, foot: 'Registros con coordenadas', icon: MapPinIcon },
        { title: 'Pérdida económica', value: 'Q ' + num(suma(l, 'perdidaEconomica')), foot: 'Suma de los registros filtrados', icon: BanknotesIcon },
    ];
});

async function cargar() {
    registros.value = await infraestructuraService.list();
}

async function eliminar(r) {
    if (!(await confirmDialog('Este registro se eliminará.', { title: '¿Eliminar registro?', danger: true, confirmText: 'Eliminar' }))) return;
    try {
        await infraestructuraService.remove(r.id);
        await cargar();
    } catch (e) {
        toastError(e.message);
    }
}

function exportarCSV() {
    if (!filtrados.value.length) return toastError('No hay registros para exportar.');
    const filas = [
        ['REPORTE DE INFRAESTRUCTURA'],
        ['Generado', new Date().toLocaleString('es')],
        [],
        ['N°', 'Fecha de informe', 'Monitoreo inicio', 'Monitoreo fin', 'Semana', 'Departamento', 'Municipio', 'Centro poblado', 'Tipo de infraestructura', 'Latitud', 'Longitud',
            'Evento reportado', 'Causa', 'Familias afectadas', 'Pérdida económica (Q)', 'Registrado por', 'Notas'],
    ];
    filtrados.value.forEach((r, i) => filas.push([
        i + 1, r.fechaInforme, r.monitoreoInicio, r.monitoreoFin, r.semana ? `${r.semana}/${r.anioSemana}` : '', r.departamento, r.municipio, r.centroPoblado,
        tipo(r), r.gpsLat, r.gpsLon, r.eventoReportado, causa(r), r.famAfectadas, r.perdidaEconomica, r.registradoPor, r.notas,
    ]));
    filas.push([], ['', '', '', '', '', '', '', '', '', '', '', '', 'TOTALES', suma(filtrados.value, 'famAfectadas'), suma(filtrados.value, 'perdidaEconomica').toFixed(2)]);
    descargarCSV(`infraestructura_${new Date().toISOString().slice(0, 10)}.csv`, filas);
}

onMounted(async () => {
    try {
        await cargar();
    } catch (e) {
        toastError(e.message);
    } finally {
        loading.value = false;
    }
});
</script>
