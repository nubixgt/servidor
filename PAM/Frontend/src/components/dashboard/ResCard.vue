<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Última resolución por familia</h2>
                <p>47 códigos originales agrupados en 9 familias</p>
            </div>
        </div>
        <div class="hb">
            <div v-for="i in order" :key="i" class="row" :class="{ dim: F.r != null && F.r !== i }" tabindex="0"
                @click="filtros.setF('r', i)">
                <span class="lab" :title="RG[i]">{{ RG[i] }}</span>
                <span class="trk"><span class="fill" :style="{ display: 'block', width: ag.byR[i] / max * 100 + '%', background: i === cube.RSIN ? 'var(--off)' : 'var(--seq4)' }"></span></span>
                <span class="v">{{ fmt(ag.byR[i]) }}</span>
                <span class="p">{{ pc(ag.byR[i], tot) }}%</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { fmt, pc } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const F = filtros.f;
const ag = computed(() => tablero.ag);
const cube = computed(() => tablero.cube);
const RG = computed(() => tablero.cube.RG);
const tot = computed(() => ag.value.byR.reduce((a, b) => a + b, 0));
const order = computed(() => RG.value.map((r, i) => i).sort((a, b) => ag.value.byR[b] - ag.value.byR[a]));
const max = computed(() => Math.max(1, ...ag.value.byR));
</script>
