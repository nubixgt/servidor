<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Antigüedad de solicitudes pendientes</h2>
                <p>{{ fmt(tot) }} expedientes EN TRÁMITE · días desde la solicitud al corte {{ tablero.corte }}</p>
            </div>
            <button class="btn" @click="verExpedientes">Ver expedientes</button>
        </div>
        <div class="cols">
            <button v-for="(x, i) in v" :key="i" class="col" :class="{ dim: F.t != null && F.t !== i + 1 }"
                :aria-label="`${ANT[i + 1]}: ${fmt(x)}`" @click="filtros.setF('t', i + 1)">
                <span class="cv">{{ fmt(x) }}</span>
                <span class="cb" :style="{ height: x / max * 120 + 'px', background: col[i] }"></span>
                <span class="cl">{{ ANT[i + 1].replace(' días', '').replace('Más de ', '> ') }}<br><span style="color:var(--ink3)">{{ pc(x, tot, 0) }}%</span></span>
            </button>
        </div>
        <p class="note" style="margin:10px 0 0">Días: 0–30 a más de 365. {{ pc(v[5], tot, 0) }}% de los pendientes supera un año.</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { ANT, fmt, pc } from '../../utils/formato';

const router = useRouter();
const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();
const F = filtros.f;
const col = ['var(--seq2)', 'var(--seq3)', 'var(--seq4)', 'var(--warnf)', 'var(--warn)', 'var(--crit)'];

const v = computed(() => tablero.ag.byT.slice(1));
const tot = computed(() => v.value.reduce((a, b) => a + b, 0));
const max = computed(() => Math.max(1, ...v.value));

function verExpedientes() {
    ui.bRule = null;
    router.push('/beneficiarios');
}
</script>
