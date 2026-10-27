<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Distribución por edad</h2>
                <p>Edad calculada desde la fecha de nacimiento al corte · verde: en nómina activa</p>
            </div>
        </div>
        <div class="hb">
            <div v-for="(b, i) in ag.byA" :key="i" class="row" :class="{ dim: F.a != null && F.a !== i }" tabindex="0"
                @click="filtros.setF('a', i)" @keydown.enter="filtros.setF('a', i)">
                <span class="lab">{{ AB[i] }} años</span>
                <span class="trk" style="display:flex;gap:2px;background:none">
                    <span class="fill" :style="{ display: 'block', width: b.nom / max * 100 + '%', background: 'var(--ok)', borderRadius: '0' }"></span>
                    <span class="fill" :style="{ display: 'block', width: (b.c - b.nom) / max * 100 + '%', background: 'var(--seq2)' }"></span>
                </span>
                <span class="v">{{ fmt(b.c) }}</span>
                <span class="p">{{ pc(b.c, tot) }}%</span>
            </div>
        </div>
        <div class="legend" style="margin-top:12px"><i style="background:var(--ok)"></i>En nómina activa <i style="background:var(--seq2);margin-left:10px"></i>Otros estados</div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { AB, fmt, pc } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const F = filtros.f;
const ag = computed(() => tablero.ag);
const tot = computed(() => ag.value.byA.reduce((a, b) => a + b.c, 0));
const max = computed(() => Math.max(1, ...ag.value.byA.map((b) => b.c)));
</script>
