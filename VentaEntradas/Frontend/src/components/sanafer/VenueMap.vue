<template>
    <div class="map-column">
        <div class="category-grid">
            <button :class="['all-category', { chosen: category === 'all' }]" @click="category = 'all'">
                <LayoutGrid :size="18" /><span>Todas las<br />localidades</span>
            </button>
            <button
                v-for="key in categoryKeys"
                :key="key"
                :class="['category', key, { chosen: category === key }]"
                :aria-pressed="category === key"
                @click="category = category === key ? 'all' : key"
            >
                <span class="category-top"><i />{{ categories[key].name }}<span class="category-price">{{ money(categories[key].price) }}</span></span>
                <span class="category-note">{{ categories[key].note }}<span> / mesa</span></span>
            </button>
        </div>

        <div class="map-card">
            <div class="map-toolbar">
                <div class="map-title"><MapPin :size="17" /><strong>Mapa del evento</strong></div>
                <div class="map-tools">
                    <label class="search">
                        <Search :size="15" />
                        <input v-model="query" aria-label="Buscar mesa" placeholder="Buscar A12…" maxlength="5" />
                        <button v-if="query" aria-label="Limpiar búsqueda" @click="query = ''"><X :size="13" /></button>
                    </label>
                    <div class="view-toggle">
                        <button :class="{ active: view === 'map' }" aria-label="Ver mapa" @click="view = 'map'"><LayoutGrid :size="16" /></button>
                        <button :class="{ active: view === 'list' }" aria-label="Ver lista" @click="view = 'list'"><List :size="17" /></button>
                    </div>
                </div>
            </div>

            <div class="map-subtoolbar">
                <div class="legend">
                    <span><i class="legend-free" />Disponible</span>
                    <span><i class="legend-selected" />Tu selección</span>
                    <span><i class="legend-occupied">×</i>Ocupada</span>
                </div>
                <label class="switch-label"><input v-model="onlyFree" type="checkbox" />Solo disponibles</label>
            </div>

            <div v-if="error" class="error" role="alert">
                {{ error }}
                <button @click="$emit('reload')"><RefreshCw :size="14" />Reintentar</button>
            </div>

            <div v-if="view === 'map'" class="map-scroll">
                <svg
                    :class="['venue', { loading }]"
                    :style="{ width: `${zoom * 100}%`, minWidth: zoom === 1 ? undefined : `${850 * zoom}px` }"
                    viewBox="0 0 1320 820"
                    role="group"
                    aria-label="Plano de 90 mesas del Baile Social SANAFER"
                >
                    <defs>
                        <pattern id="floor" width="24" height="24" patternUnits="userSpaceOnUse"><path d="M 24 0 L 0 0 0 24" fill="none" stroke="#25354e" stroke-width="0.7" /></pattern>
                        <pattern id="taken" width="8" height="8" patternUnits="userSpaceOnUse"><path d="M0 8L8 0" stroke="#526078" stroke-width="1" /></pattern>
                    </defs>
                    <rect x="15" y="8" width="1280" height="780" rx="18" class="venue-boundary" />
                    <rect x="393" y="25" width="550" height="57" rx="8" class="stage" /><text x="668" y="60" class="stage-label">TARIMA / ESCENARIO</text>
                    <text x="180" y="59" class="service-label">BAÑOS DE HOMBRES</text><text x="1130" y="59" class="service-label">BAÑOS DE MUJERES</text>
                    <path d="M30 94H1280" class="boundary-line" />
                    <rect x="384" y="115" width="580" height="261" rx="12" fill="url(#floor)" stroke="#2e4666" stroke-dasharray="5 5" />
                    <text x="674" y="205" class="dance-star">✳</text><text x="674" y="255" class="dance-label">Aquí se vive la noche.</text><text x="674" y="285" class="dance-sub">PISTA DE BAILE</text><path d="M635 309H713" stroke="#396494" />
                    <rect x="33" y="114" width="100" height="148" rx="8" class="service-box" /><text x="83" y="183" class="service-icon">♧</text><text x="83" y="211" class="service-label">BARRA</text>
                    <rect x="33" y="276" width="100" height="114" rx="8" class="service-box" /><text x="83" y="326" class="service-label">VENTA DE</text><text x="83" y="345" class="service-label">COMIDA</text>
                    <rect x="33" y="687" width="100" height="80" rx="8" class="service-box" /><text x="83" y="715" class="service-label">+</text><text x="83" y="738" class="service-label small">FARMACIA</text><text x="83" y="753" class="service-label small">MUNICIPAL</text>
                    <text x="385" y="409" class="zone-label">SECTOR IZQUIERDO</text><text x="1000" y="409" class="zone-label">SECTOR DERECHO</text>
                    <path d="M678 744v-52m-10 10 10-10 10 10" stroke="#74a2d5" fill="none" stroke-width="2" /><text x="678" y="768" class="entry-label">ENTRADA</text>

                    <g
                        v-for="t in tables"
                        :key="t.id"
                        :transform="`translate(${t.x},${t.y})`"
                        :class="['table', t.category, { occupied: isTaken(t.id), selected: isSelected(t.id), dim: !matches(t) }]"
                        role="button"
                        :tabindex="matches(t) ? 0 : -1"
                        :aria-label="ariaLabel(t)"
                        :aria-disabled="isTaken(t.id) || loading || !!error"
                        :aria-pressed="isSelected(t.id)"
                        @click="matches(t) && $emit('toggle', t.id)"
                        @keydown.enter.prevent="matches(t) && $emit('toggle', t.id)"
                        @keydown.space.prevent="matches(t) && $emit('toggle', t.id)"
                    >
                        <title>{{ `A${t.id} · ${categories[t.category].name} · ${money(categories[t.category].price)} · ${isTaken(t.id) ? 'Ocupada' : '8 entradas'}` }}</title>
                        <rect x="-35" y="-26" width="70" height="52" fill="transparent" />
                        <g v-for="x in [-16, 0, 16]" :key="x">
                            <rect class="chair" :x="x - 5" y="-26" width="10" height="5" rx="2" />
                            <rect class="chair" :x="x - 5" y="21" width="10" height="5" rx="2" />
                        </g>
                        <rect class="chair" x="-36" y="-6" width="5" height="12" rx="2" />
                        <rect class="chair" x="31" y="-6" width="5" height="12" rx="2" />
                        <rect class="tabletop" x="-29" y="-19" width="58" height="38" rx="6" />
                        <rect v-if="isTaken(t.id)" x="-29" y="-19" width="58" height="38" rx="6" fill="url(#taken)" />
                        <text class="table-number" y="6">A{{ t.id }}</text>
                        <template v-if="isSelected(t.id)">
                            <circle cx="25" cy="-17" r="8" fill="#6cafff" />
                            <path d="m21-17 3 3 5-6" fill="none" stroke="#08111e" stroke-width="2" />
                        </template>
                        <text v-if="isTaken(t.id)" x="26" y="-14" class="occupied-x">×</text>
                    </g>
                </svg>
            </div>

            <div v-else class="table-list">
                <button
                    v-for="t in tables.filter(matches)"
                    :key="t.id"
                    :disabled="isTaken(t.id) || loading || !!error"
                    :class="['list-table', t.category, { selected: isSelected(t.id) }]"
                    @click="$emit('toggle', t.id)"
                >
                    <strong>A{{ t.id }}</strong>
                    <span>{{ categories[t.category].name }}</span>
                    <small>{{ isTaken(t.id) ? 'Ocupada' : isSelected(t.id) ? 'Seleccionada' : money(categories[t.category].price) }}</small>
                </button>
                <p v-if="!anyMatch" class="no-match">No hay mesas que coincidan con tu búsqueda.</p>
            </div>

            <p v-if="view === 'map' && !anyMatch" class="no-match">No hay mesas que coincidan con tu búsqueda.</p>

            <div class="map-bottom">
                <span><Info :size="14" />Toca una mesa para seleccionarla</span>
                <div class="zoom">
                    <button aria-label="Alejar mapa" :disabled="zoom <= 1" @click="zoom = Math.max(1, zoom - 0.25)"><Minus :size="15" /></button>
                    <span>{{ Math.round(zoom * 100) }}%</span>
                    <button aria-label="Acercar mapa" :disabled="zoom >= 2" @click="zoom = Math.min(2, zoom + 0.25)"><Plus :size="15" /></button>
                    <button aria-label="Restablecer mapa" @click="zoom = 1"><RotateCcw :size="14" /></button>
                </div>
            </div>
        </div>

        <div class="map-footnote">
            <span>Distribución basada en el croquis oficial de SANAFER.</span>
            <span>{{ lastUpdate && `Actualizado ${lastUpdate}` }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Info, LayoutGrid, List, MapPin, Minus, Plus, RefreshCw, RotateCcw, Search, X } from 'lucide-vue-next';
import { categories, money, tables } from '../../utils/tables';

const props = defineProps({
    selected: { type: Array, required: true },
    occupied: { type: Set, required: true },
    loading: Boolean,
    error: { type: String, default: '' },
    lastUpdate: { type: String, default: '' },
});
defineEmits(['toggle', 'reload']);

const categoryKeys = Object.keys(categories);
const category = ref('all');
const query = ref('');
const onlyFree = ref(false);
const view = ref('map');
const zoom = ref(1);

const isTaken = (id) => props.occupied.has(id);
const isSelected = (id) => props.selected.includes(id);

const matches = (t) =>
    (category.value === 'all' || category.value === t.category) &&
    (!onlyFree.value || !isTaken(t.id)) &&
    (!query.value || ('A' + t.id).includes(query.value.trim().toUpperCase()));

const anyMatch = computed(() => tables.some(matches));

const ariaLabel = (t) => {
    const state = isTaken(t.id) ? 'ocupada' : isSelected(t.id) ? 'seleccionada' : 'disponible';
    return `Mesa A${t.id}, ${categories[t.category].name}, ${money(categories[t.category].price)}, ${state}, 8 entradas`;
};
</script>
