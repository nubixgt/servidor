<template>
    <div class="fixed inset-0 z-[1000] flex items-center justify-center p-4" style="background: rgba(8, 42, 58, .53); backdrop-filter: blur(5px);" @click.self="$emit('close')">
        <div class="modal-box w-full max-w-[680px] max-h-[88vh] overflow-y-auto" role="dialog" aria-modal="true" aria-label="Detalle del registro">
            <div class="modal-head sticky top-0 z-10" style="background: #fafdf9; border-radius: 23px 23px 0 0;">
                <div class="min-w-0">
                    <h2 class="truncate">{{ d.cultivo }} · {{ d.lote }}</h2>
                    <span class="badge mt-1" :class="nivel.clase">{{ nivel.label }} · {{ num(d.danoPorc) }}% de daño</span>
                </div>
                <div class="flex gap-2 flex-shrink-0">
                    <button v-if="puedeEliminar" class="iconbtn" aria-label="Eliminar" @click="$emit('eliminar')"><TrashIcon class="w-5 h-5" style="color: var(--red);" /></button>
                    <button class="iconbtn" aria-label="Cerrar" @click="$emit('close')"><XMarkIcon class="w-5 h-5" /></button>
                </div>
            </div>

            <div class="modal-content space-y-6">
                <section v-for="g in grupos" :key="g.titulo">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider mb-3" style="color: var(--green-dark);">{{ g.titulo }}</h3>
                    <dl class="grid grid-cols-2 gap-x-5 gap-y-4">
                        <div v-for="f in g.filas" :key="f[0]" :class="f[2] ? 'col-span-2' : ''">
                            <dt class="text-[10px]" style="color: #809689;">{{ f[0] }}</dt>
                            <dd class="text-[13px] font-semibold break-words mt-0.5" style="white-space: pre-line;">{{ f[1] }}</dd>
                        </div>
                    </dl>
                </section>

                <section v-if="d.gpsLat != null && d.gpsLon != null">
                    <a :href="`https://www.openstreetmap.org/?mlat=${d.gpsLat}&mlon=${d.gpsLon}#map=16/${d.gpsLat}/${d.gpsLon}`" target="_blank" rel="noopener" class="btn btn-sm"><MapPinIcon class="w-4 h-4" />Ver ubicación en el mapa</a>
                </section>

                <section>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider mb-3" style="color: var(--green-dark);">Fotografías ({{ d.fotos.length }})</h3>
                    <p v-if="!d.fotos.length" class="text-[12px]" style="color: var(--muted);">Este registro no tiene fotografías.</p>
                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <a v-for="f in d.fotos" :key="f.id" :href="fotoUrl(f)" target="_blank" rel="noopener" :title="f.nombre">
                            <img :src="fotoUrl(f)" :alt="f.nombre" loading="lazy" class="w-full aspect-square object-cover rounded-xl" style="border: 1px solid var(--input-border);" />
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount } from 'vue';
import { XMarkIcon, TrashIcon, MapPinIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    d: { type: Object, required: true },
    puedeEliminar: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'eliminar']);

const num = (v) => Number(v ?? 0).toLocaleString('es-GT', { maximumFractionDigits: 2 });
const vacio = (v) => (v === null || v === undefined || v === '' ? '—' : v);
const fotoUrl = (f) => `/Da%C3%B1osACultivos/Backend/uploads/danos-cultivos/${props.d.id}/fotos/${encodeURIComponent(f.archivo)}`;

const nivel = computed(() => {
    const p = Number(props.d.danoPorc) || 0;
    return p >= 100 ? { label: 'Pérdida total', clase: 'purple' } : p >= 50 ? { label: 'Alto', clase: 'red' } : p >= 25 ? { label: 'Medio', clase: 'amber' } : { label: 'Bajo', clase: '' };
});

const fecha = (f) => (f ? new Date(f.replace(' ', 'T')).toLocaleString('es-GT', { dateStyle: 'medium', timeStyle: f.length > 10 ? 'short' : undefined }) : '—');

const grupos = computed(() => {
    const d = props.d;
    const unidad = (v) => (v != null && v !== '' ? `${num(v)} ${d.unidad}` : '—');
    return [
        {
            titulo: 'Ubicación',
            filas: [
                ['Fecha de levantamiento', vacio(d.fecha)],
                ['Fecha de informe', vacio(d.fechaInforme)],
                ['Período de monitoreo', d.monitoreoInicio ? `${d.monitoreoInicio} al ${d.monitoreoFin}` : '—'],
                ['Semana', d.semana ? `${d.semana} (${d.anioSemana})` : '—'],
                ['Productor', vacio(d.tecnico)],
                ['Departamento', vacio(d.departamento)],
                ['Municipio', vacio(d.municipio)],
                ['Centro poblado', vacio(d.centroPoblado)],
                ['Lote / parcela', vacio(d.lote)],
                ['Agricultor en RUA', d.agricultorRua ? 'Sí' : 'No'],
                ['Código de RUA', d.agricultorRua ? vacio(d.codigoRua) : '—'],
                ['Ubicación GPS', vacio(d.gps), true],
            ],
        },
        {
            titulo: 'Cultivo',
            filas: [
                ['Cultivo', vacio(d.cultivo)],
                ['Estado fenológico', vacio(d.etapa)],
                ['Fecha de siembra', vacio(d.fechaSiembra)],
                ['Superficie total del lote', d.areaLote != null ? `${num(d.areaLote)} ha` : '—'],
                ['Superficie afectada', `${num(d.areaAfectada)} ha`],
                ...(d.asociados || []).map((a, i) => [
                    `Cultivo asociado ${i + 1}`,
                    `${a.cultivo}\nEstado fenológico: ${vacio(a.etapa)}\nSuperficie total del lote: ${a.areaLote != null ? num(a.areaLote) + ' ha' : '—'}\nSuperficie afectada: ${num(a.areaAfectada)} ha`,
                    true,
                ]),
            ],
        },
        {
            titulo: 'Daños',
            filas: [
                ['Causa del daño', d.causa === 'Otra' && d.causaOtra ? `Otra: ${d.causaOtra}` : vacio(d.causa)],
                ['Evento reportado', vacio(d.eventoReportado)],
                ['% de daño promedio', `${num(d.danoPorc)}%`],
                ['Familias afectadas', d.famAfectadas != null ? num(d.famAfectadas) : '—'],
                ['Autoconsumo', d.famAutoconsumo != null ? num(d.famAutoconsumo) : '—'],
                ['Agricultura intermedia', d.famIntermedia != null ? num(d.famIntermedia) : '—'],
                ['Agricultura consolidada', d.famConsolidada != null ? num(d.famConsolidada) : '—'],
                ['Rendimiento esperado sin daño', unidad(d.rendEsperado)],
                ['Pérdida de rendimiento estimada', d.perdidaEstimada != null && d.perdidaEstimada !== '' ? num(d.perdidaEstimada) : '—'],
                ['Descripción del daño', vacio(d.notas), true],
            ],
        },
        {
            titulo: 'Registro',
            filas: [
                ['Registrado por', vacio(d.registradoPor)],
                ['Fecha de registro', fecha(d.creadoEn)],
            ],
        },
    ];
});

const onKey = (e) => { if (e.key === 'Escape') emit('close'); };
onMounted(() => window.addEventListener('keydown', onKey));
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>
