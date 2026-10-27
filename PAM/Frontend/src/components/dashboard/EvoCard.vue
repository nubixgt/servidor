<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>{{ title }}</h2>
                <p>{{ tablero.ambito }}{{ F.e != null ? ' · ' + EST[F.e] : '' }} · {{ ui.evoMode === 'y' ? `por año (${ANIO} parcial)` : `por mes, 2018–${ANIO}` }}{{ ui.evoMode === 'm' && F.mun != null ? ' · mensual a nivel departamental' : '' }}</p>
            </div>
            <div class="seg">
                <button :class="{ on: ui.evoMode === 'y' }" @click="ui.evoMode = 'y'">Año</button>
                <button :class="{ on: ui.evoMode === 'm' }" @click="ui.evoMode = 'm'">Mes</button>
            </div>
        </div>
        <div v-if="keys.length > 3" class="leg" style="margin-bottom:8px">
            <button v-for="s in series" :key="s.k" :class="{ off: !s.on }" :aria-pressed="s.on" @click="alternar(s.k)"><i :style="{ background: s.c }"></i>{{ s.l }}</button>
        </div>
        <div v-else class="leg" style="margin-bottom:8px">
            <span v-for="s in series" :key="s.k" class="note" style="display:inline-flex;align-items:center;gap:6px;margin-right:10px"><i style="width:14px;height:3px;border-radius:2px;display:block" :style="{ background: s.c }"></i>{{ s.l }}</span>
        </div>
        <div class="chartbox"><LineChart :labels="labels" :series="series" partial :aria="title" /></div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import LineChart from './LineChart.vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { seriesMonth, seriesYear } from '../../utils/cubo';
import { ANIO, MES, MESES, SER } from '../../utils/formato';

const props = defineProps({
    keys: { type: Array, default: () => SER.map((s) => s[0]) },
    title: { type: String, default: 'Evolución histórica' }
});

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();
const F = filtros.f;
const EST = computed(() => tablero.cube.EST);

const labels = computed(() => {
    const out = [];
    if (ui.evoMode === 'y') {
        for (let y = tablero.series.Y0; y <= ANIO; y++) out.push(String(y));
    } else {
        for (let y = 2018; y <= ANIO; y++) for (let m = 0; m < 12; m++) {
            if (y === ANIO && m > MES) break;
            out.push(MESES[m] + ' ' + String(y).slice(2));
        }
    }
    return out;
});

const series = computed(() => {
    const data = ui.evoMode === 'y'
        ? seriesYear(tablero.series, F, tablero.cube.MDEP, props.keys)
        : seriesMonth(tablero.series, F, props.keys);
    return props.keys.map((k) => {
        const si = SER.findIndex((s) => s[0] === k);
        return { k, l: SER[si][1], c: `var(--s${si + 1})`, data: data[k], on: props.keys.length <= 3 || ui.evoOn.has(k) };
    });
});

function alternar(k) {
    if (ui.evoOn.has(k)) ui.evoOn.delete(k);
    else ui.evoOn.add(k);
    if (!ui.evoOn.size) ui.evoOn.add(k);
}
</script>
