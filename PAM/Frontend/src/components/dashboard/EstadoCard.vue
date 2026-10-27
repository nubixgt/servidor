<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Estado actual de los expedientes</h2>
                <p>{{ fmt(tot) }} registros · clic en un estado para filtrar todo el tablero</p>
            </div>
        </div>
        <div class="hb" role="list">
            <div v-for="i in order" :key="i" class="row" :class="{ dim: F.e != null && F.e !== i }" role="listitem" tabindex="0"
                @click="filtros.setF('e', i)" @keydown.enter="filtros.setF('e', i)">
                <span class="lab" style="color:var(--ink)"><Icono :name="EST_ICON[i]" :size="15" :style="{ color: stc(i) }" /> {{ EST[i] }}</span>
                <span class="trk"><span class="fill" :style="{ display: 'block', width: ag.byE[i] / max * 100 + '%', background: stc(i) }"></span></span>
                <span class="v">{{ fmt(ag.byE[i]) }}</span>
                <span class="p">{{ pc(ag.byE[i], tot) }}%</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Icono from '../common/Icono.vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { EST_ICON, fmt, pc, stc } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const F = filtros.f;
const ag = computed(() => tablero.ag);
const EST = computed(() => tablero.cube.EST);
const tot = computed(() => ag.value.byE.reduce((a, b) => a + b, 0));
const order = computed(() => EST.value.map((e, i) => i).sort((a, b) => ag.value.byE[b] - ag.value.byE[a]));
const max = computed(() => Math.max(...ag.value.byE, 1));
</script>
