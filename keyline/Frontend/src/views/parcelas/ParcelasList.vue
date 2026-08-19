<template>
    <div>
        <div style="display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-secondary" @click="exportCsv">⬇️ Exportar CSV</button>
            <router-link :to="{ name: 'ParcelaNueva' }" class="btn btn-primary">+ Nueva parcela</router-link>
        </div>

        <div class="panel glass" style="display: flex; flex-wrap: wrap; gap: 12px;">
            <input v-model="filters.q" @input="debouncedLoad" placeholder="Buscar por finca, municipio, departamento o responsable" style="flex: 1; min-width: 240px;" />
            <select v-model="filters.departamento" @change="load" style="width: auto; min-width: 190px;">
                <option value="">Todos los departamentos</option>
                <option v-for="d in DEPARTAMENTOS" :key="d" :value="d">{{ d }}</option>
            </select>
            <select v-model="filters.estado" @change="load" style="width: auto; min-width: 170px;">
                <option value="">Todos los estados</option>
                <option v-for="e in ESTADOS_PROCESO" :key="e" :value="e">{{ e }}</option>
            </select>
            <select v-model="filters.estadoValidacion" @change="load" style="width: auto; min-width: 190px;">
                <option value="">Toda validación</option>
                <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e">{{ e }}</option>
            </select>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Parcela</th>
                        <th>Ubicación</th>
                        <th>Área</th>
                        <th>Estado</th>
                        <th>Validación</th>
                        <th>Técnico</th>
                        <th>Fotos</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="loading"><td colspan="8" class="empty-state">Cargando…</td></tr>
                    <tr v-else-if="!parcelas.length"><td colspan="8" class="empty-state">No hay parcelas que coincidan con los filtros actuales.</td></tr>
                    <tr v-for="p in parcelas" :key="p.id">
                        <td>
                            <strong>{{ p.nombreParcela }}</strong><br />
                            <span class="hint">{{ p.codigo }} · {{ p.fechaRegistro }}</span>
                        </td>
                        <td>
                            {{ p.departamento }} / {{ p.municipio }}<br />
                            <span class="hint">{{ p.latitud !== '' && p.latitud !== null ? `${p.latitud}, ${p.longitud}` : 'Sin GPS' }}</span>
                        </td>
                        <td>{{ p.areaHa }} ha</td>
                        <td><span class="tag" :class="ESTADO_COLORS[p.estado]">{{ p.estado }}</span></td>
                        <td><span class="tag" :class="VALIDACION_COLORS[p.estadoValidacion]">{{ p.estadoValidacion }}</span></td>
                        <td>{{ p.tecnicoNombre }}</td>
                        <td>{{ p.fotos?.length ? `📷 ${p.fotos.length}` : '—' }}</td>
                        <td>
                            <div class="row-actions">
                                <button class="btn btn-secondary btn-sm" @click="openDetail(p)">Ver</button>
                                <router-link :to="{ name: 'ParcelaEditar', params: { id: p.id } }" class="btn btn-secondary btn-sm">Editar</router-link>
                                <button class="btn btn-warning btn-sm" @click="openReview(p)">Revisar</button>
                                <button v-if="auth.role === 'administrador'" class="btn btn-danger btn-sm" @click="eliminar(p)">Eliminar</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal detalle -->
        <div v-if="detail" class="modal-overlay" @click.self="detail = null">
            <div class="modal-box glass wide">
                <div class="modal-head">
                    <h3>{{ detail.nombreParcela }}</h3>
                    <button class="modal-close" @click="detail = null">✕</button>
                </div>
                <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                    <span class="tag" :class="ESTADO_COLORS[detail.estado]">{{ detail.estado }}</span>
                    <span class="tag" :class="VALIDACION_COLORS[detail.estadoValidacion]">{{ detail.estadoValidacion }}</span>
                    <span class="badge">{{ detail.codigo }}</span>
                </div>
                <div class="form-grid" style="font-size: 13.5px;">
                    <div><span class="detail-label">Ubicación</span><div>{{ detail.departamento }} / {{ detail.municipio }} {{ detail.comunidad ? '· ' + detail.comunidad : '' }}</div></div>
                    <div><span class="detail-label">GPS</span><div>{{ detail.latitud !== '' && detail.latitud !== null ? `${detail.latitud}, ${detail.longitud}` : 'No registrado' }}</div></div>
                    <div><span class="detail-label">Área</span><div>{{ detail.areaHa }} ha</div></div>
                    <div><span class="detail-label">Uso actual</span><div>{{ detail.usoActual || 'N/D' }}</div></div>
                    <div><span class="detail-label">Suelo</span><div>{{ detail.tipoSuelo || 'N/D' }} · Prof. {{ detail.profundidadSuelo || 'N/D' }} cm</div></div>
                    <div><span class="detail-label">Talpetate / Encharca</span><div>{{ detail.talpetate || 'N/D' }} / {{ detail.encharca || 'N/D' }}</div></div>
                    <div><span class="detail-label">Agua</span><div>{{ detail.agua || 'N/D' }} · {{ detail.fuenteAgua || '' }}</div></div>
                    <div><span class="detail-label">Lluvia anual</span><div>{{ detail.lluviaAnual !== '' && detail.lluviaAnual !== null ? detail.lluviaAnual + ' mm' : 'N/D' }}</div></div>
                    <div class="full"><span class="detail-label">Bioindicadores</span><div>{{ detail.bioindicadores || 'Sin registro' }}</div></div>
                    <div class="full"><span class="detail-label">Intervenciones</span><div>{{ detail.intervenciones || 'Sin detalle' }}</div></div>
                    <div class="full"><span class="detail-label">Observaciones</span><div>{{ detail.observaciones || '—' }}</div></div>
                    <div><span class="detail-label">Responsable / técnico</span><div>{{ detail.propietario || 'N/D' }} · Cargado por {{ detail.tecnicoNombre }}</div></div>
                    <div v-if="detail.comentarioSupervisor" class="full"><span class="detail-label">Comentario del supervisor</span><div>{{ detail.comentarioSupervisor }}</div></div>
                </div>
                <div v-if="detail.fotos?.length" style="margin-top: 16px;">
                    <span class="detail-label">Fotografías</span>
                    <div class="photo-grid">
                        <div v-for="f in detail.fotos" :key="f.id" class="photo-thumb">
                            <img :src="fotoUrl(detail.id, f.miniatura || f.archivo)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal revisión -->
        <div v-if="reviewing" class="modal-overlay" @click.self="reviewing = null">
            <div class="modal-box glass">
                <div class="modal-head">
                    <h3>Revisar parcela</h3>
                    <button class="modal-close" @click="reviewing = null">✕</button>
                </div>
                <p class="hint" style="margin-bottom: 16px;">{{ reviewing.nombreParcela }} · {{ reviewing.codigo }}</p>
                <div class="field">
                    <label>Estado de validación</label>
                    <select v-model="reviewForm.estadoValidacion">
                        <option v-for="e in ESTADOS_VALIDACION" :key="e" :value="e">{{ e }}</option>
                    </select>
                </div>
                <div class="field">
                    <label>Comentario para el técnico</label>
                    <textarea v-model="reviewForm.comentario" rows="3" placeholder="Observaciones, correcciones solicitadas..."></textarea>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary" @click="reviewing = null">Cancelar</button>
                    <button class="btn btn-primary" :disabled="savingReview" @click="saveReview">Guardar revisión</button>
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
