<template>
    <div>
        <div class="flex items-start justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold">Base consolidada de parcelas</h1>
                <p class="text-sm text-slate-500">Busca, filtra, revisa y administra la información del proyecto.</p>
            </div>
            <div class="flex gap-2">
                <button class="btn-secondary" @click="exportCsv">⬇️ Exportar CSV</button>
                <router-link :to="{ name: 'ParcelaNueva' }" class="btn-primary">+ Nueva parcela</router-link>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 mb-4 flex flex-wrap gap-3">
            <input v-model="filters.q" @input="debouncedLoad" placeholder="Buscar por finca, municipio, departamento o responsable" class="field-input flex-1 min-w-[240px]" />
            <select v-model="filters.departamento" @change="load" class="field-input w-48">
                <option value="">Todos los departamentos</option>
                <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
            </select>
            <select v-model="filters.estado" @change="load" class="field-input w-44">
                <option value="">Todos los estados</option>
                <option v-for="e in ESTADOS_PROCESO" :key="e" :value="e">{{ e }}</option>
            </select>
            <select v-model="filters.estadoValidacion" @change="load" class="field-input w-52">
                <option value="">Toda validación</option>
                <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e">{{ e }}</option>
            </select>
        </div>

        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <tr>
                        <th class="p-3 text-left">Parcela</th>
                        <th class="p-3 text-left">Ubicación</th>
                        <th class="p-3 text-left">Área</th>
                        <th class="p-3 text-left">Estado</th>
                        <th class="p-3 text-left">Validación</th>
                        <th class="p-3 text-left">Técnico</th>
                        <th class="p-3 text-left">Fotos</th>
                        <th class="p-3 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading"><td colspan="8" class="p-8 text-center text-slate-400">Cargando…</td></tr>
                    <tr v-else-if="!parcelas.length"><td colspan="8" class="p-8 text-center text-slate-400">No hay parcelas que coincidan con los filtros actuales.</td></tr>
                    <tr v-for="p in parcelas" :key="p.id" class="border-t border-slate-100">
                        <td class="p-3">
                            <strong>{{ p.nombreParcela }}</strong><br />
                            <span class="text-xs text-slate-400">{{ p.codigo }} · {{ p.fechaRegistro }}</span>
                        </td>
                        <td class="p-3">
                            {{ p.departamento }} / {{ p.municipio }}<br />
                            <span class="text-xs text-slate-400">{{ p.latitud !== '' && p.latitud !== null ? `${p.latitud}, ${p.longitud}` : 'Sin GPS' }}</span>
                        </td>
                        <td class="p-3">{{ p.areaHa }} ha</td>
                        <td class="p-3"><span class="tag" :class="ESTADO_COLORS[p.estado]">{{ p.estado }}</span></td>
                        <td class="p-3"><span class="tag" :class="VALIDACION_COLORS[p.estadoValidacion]">{{ p.estadoValidacion }}</span></td>
                        <td class="p-3">{{ p.tecnicoNombre }}</td>
                        <td class="p-3">{{ p.fotos?.length ? `📷 ${p.fotos.length}` : '—' }}</td>
                        <td class="p-3">
                            <div class="flex gap-2">
                                <button class="btn-secondary btn-sm" @click="openDetail(p)">Ver</button>
                                <router-link :to="{ name: 'ParcelaEditar', params: { id: p.id } }" class="btn-secondary btn-sm">Editar</router-link>
                                <button class="btn-warning btn-sm" @click="openReview(p)">Revisar</button>
                                <button v-if="auth.role === 'administrador'" class="btn-danger btn-sm" @click="eliminar(p)">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal detalle -->
        <div v-if="detail" class="modal-overlay" @click.self="detail = null">
            <div class="modal-box">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold">{{ detail.nombreParcela }}</h3>
                    <button @click="detail = null" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>
                <div class="flex gap-2 mb-4">
                    <span class="tag" :class="ESTADO_COLORS[detail.estado]">{{ detail.estado }}</span>
                    <span class="tag" :class="VALIDACION_COLORS[detail.estadoValidacion]">{{ detail.estadoValidacion }}</span>
                    <span class="tag bg-slate-100 text-slate-600">{{ detail.codigo }}</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div><span class="detail-label">Ubicación</span><div>{{ detail.departamento }} / {{ detail.municipio }} {{ detail.comunidad ? '· ' + detail.comunidad : '' }}</div></div>
                    <div><span class="detail-label">GPS</span><div>{{ detail.latitud !== '' && detail.latitud !== null ? `${detail.latitud}, ${detail.longitud}` : 'No registrado' }}</div></div>
                    <div><span class="detail-label">Área</span><div>{{ detail.areaHa }} ha</div></div>
                    <div><span class="detail-label">Uso actual</span><div>{{ detail.usoActual || 'N/D' }}</div></div>
                    <div><span class="detail-label">Suelo</span><div>{{ detail.tipoSuelo || 'N/D' }} · Prof. {{ detail.profundidadSuelo || 'N/D' }} cm</div></div>
                    <div><span class="detail-label">Talpetate / Encharca</span><div>{{ detail.talpetate || 'N/D' }} / {{ detail.encharca || 'N/D' }}</div></div>
                    <div><span class="detail-label">Agua</span><div>{{ detail.agua || 'N/D' }} · {{ detail.fuenteAgua || '' }}</div></div>
                    <div><span class="detail-label">Lluvia anual</span><div>{{ detail.lluviaAnual !== '' && detail.lluviaAnual !== null ? detail.lluviaAnual + ' mm' : 'N/D' }}</div></div>
                    <div class="md:col-span-2"><span class="detail-label">Bioindicadores</span><div>{{ detail.bioindicadores || 'Sin registro' }}</div></div>
                    <div class="md:col-span-2"><span class="detail-label">Intervenciones</span><div>{{ detail.intervenciones || 'Sin detalle' }}</div></div>
                    <div class="md:col-span-2"><span class="detail-label">Observaciones</span><div>{{ detail.observaciones || '—' }}</div></div>
                    <div><span class="detail-label">Responsable / técnico</span><div>{{ detail.propietario || 'N/D' }} · Cargado por {{ detail.tecnicoNombre }}</div></div>
                    <div v-if="detail.comentarioSupervisor" class="md:col-span-2"><span class="detail-label">Comentario del supervisor</span><div>{{ detail.comentarioSupervisor }}</div></div>
                </div>
                <div v-if="detail.fotos?.length" class="mt-4">
                    <span class="detail-label">Fotografías</span>
                    <div class="grid grid-cols-3 md:grid-cols-4 gap-2 mt-2">
                        <img v-for="f in detail.fotos" :key="f.id" :src="fotoUrl(detail.id, f.miniatura || f.archivo)" class="w-full h-24 object-cover rounded-lg" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal revisión -->
        <div v-if="reviewing" class="modal-overlay" @click.self="reviewing = null">
            <div class="modal-box max-w-md">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold">Revisar parcela</h3>
                    <button @click="reviewing = null" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>
                <p class="text-sm text-slate-500 mb-4">{{ reviewing.nombreParcela }} · {{ reviewing.codigo }}</p>
                <label class="field-label">Estado de validación</label>
                <select v-model="reviewForm.estadoValidacion" class="field-input mb-4">
                    <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e">{{ e }}</option>
                </select>
                <label class="field-label">Comentario para el técnico</label>
                <textarea v-model="reviewForm.comentario" rows="3" class="field-input mb-4" placeholder="Observaciones, correcciones solicitadas..."></textarea>
                <div class="flex justify-end gap-2">
                    <button class="btn-secondary" @click="reviewing = null">Cancelar</button>
                    <button class="btn-primary" :disabled="savingReview" @click="saveReview">Guardar revisión</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import parcelaService from '../../services/parcelaService';
import { parcelaFotoUrl } from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { toastSuccess, toastInfo, alertError, confirmDialog } from '../../utils/alerts';
import {
    DEPARTAMENTOS, ESTADOS_PROCESO, ESTADOS_VALIDACION, ESTADO_COLORS, VALIDACION_COLORS,
} from '../../constants/keyline';

const auth = useAuthStore();
const parcelas = ref([]);
const loading = ref(true);
const detail = ref(null);
const reviewing = ref(null);
const savingReview = ref(false);
const reviewForm = reactive({ estadoValidacion: '', comentario: '' });

const filters = reactive({ q: '', departamento: '', estado: '', estadoValidacion: '' });

let debounceTimer = null;
function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(load, 300);
}

async function load() {
    loading.value = true;
    try {
        const params = {};
        Object.entries(filters).forEach(([k, v]) => { if (v) params[k] = v; });
        const { data } = await parcelaService.listar(params);
        parcelas.value = data.parcelas;
    } finally {
        loading.value = false;
    }
}
load();

function fotoUrl(parcelaId, name) {
    return parcelaFotoUrl(parcelaId, name);
}

async function openDetail(p) {
    const { data } = await parcelaService.obtener(p.id);
    detail.value = data.parcela;
}

function openReview(p) {
    reviewing.value = p;
    reviewForm.estadoValidacion = p.estadoValidacion;
    reviewForm.comentario = p.comentarioSupervisor || '';
}

async function saveReview() {
    savingReview.value = true;
    try {
        await parcelaService.revisar(reviewing.value.id, reviewForm.estadoValidacion, reviewForm.comentario);
        reviewing.value = null;
        toastSuccess('Revisión guardada.');
        await load();
    } catch (err) {
        alertError(err.message || 'No se pudo guardar la revisión.');
    } finally {
        savingReview.value = false;
    }
}

async function eliminar(p) {
    const ok = await confirmDialog('Esta acción eliminará la parcela y sus fotos de forma permanente.', { title: '¿Eliminar parcela?', danger: true, confirmText: 'Eliminar' });
    if (!ok) return;
    await parcelaService.eliminar(p.id);
    toastInfo('Parcela eliminada.');
    await load();
}

function exportCsv() {
    if (!parcelas.value.length) { alertError('No hay datos para exportar.'); return; }
    const cols = ['codigo', 'nombreParcela', 'departamento', 'municipio', 'comunidad', 'propietario', 'telefono', 'areaHa', 'estado', 'estadoValidacion', 'usoActual', 'tipoSuelo', 'pendiente', 'altitud', 'agua', 'fuenteAgua', 'riesgoErosion', 'profundidadSuelo', 'talpetate', 'encharca', 'bioindicadores', 'lluviaAnual', 'lluviaFuente', 'intervenciones', 'especiesReforestacion', 'observaciones', 'tecnicoNombre', 'fechaRegistro', 'latitud', 'longitud'];
    const esc = (v) => { const s = String(v ?? ''); return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s; };
    const rows = [cols.join(',')].concat(parcelas.value.map((p) => cols.map((c) => esc(p[c])).join(',')));
    const blob = new Blob(['﻿' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `parcelas_keyline_${new Date().toISOString().slice(0, 10)}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<style scoped>
.field-label { @apply block mb-1 text-xs font-semibold text-slate-500 uppercase tracking-wide; }
.field-input { @apply px-3 py-2 border border-slate-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary-500; }
.btn-primary { @apply px-4 py-2 bg-primary-500 text-white rounded-md text-sm font-semibold hover:bg-primary-600 disabled:opacity-60; }
.btn-secondary { @apply px-4 py-2 bg-slate-100 text-slate-700 rounded-md text-sm font-semibold hover:bg-slate-200; }
.btn-warning { @apply px-4 py-2 bg-amber-100 text-amber-700 rounded-md text-sm font-semibold hover:bg-amber-200; }
.btn-danger { @apply px-4 py-2 bg-rose-100 text-rose-700 rounded-md text-sm font-semibold hover:bg-rose-200; }
.btn-sm { @apply px-2.5 py-1 text-xs; }
.tag { @apply text-xs font-semibold px-2 py-1 rounded-full; }
.detail-label { @apply block text-xs font-bold uppercase tracking-wide text-slate-400 mb-0.5; }
.modal-overlay { @apply fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50; }
.modal-box { @apply bg-white rounded-lg shadow-xl p-6 w-full max-w-2xl max-h-[85vh] overflow-y-auto; }
</style>
