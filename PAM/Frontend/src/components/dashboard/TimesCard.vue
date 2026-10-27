<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Tiempos del proceso</h2>
                <p>Días calendario · {{ F.dep != null ? DEPS[F.dep] : 'Nacional' }} · la barra va de 0 al percentil 90; la marca es la mediana</p>
            </div>
            <div class="seg" role="group" aria-label="Cohorte">
                <button :class="{ on: ui.cohort === 'rec' }" @click="ui.cohort = 'rec'">Desde 2023</button>
                <button :class="{ on: ui.cohort === 'all' }" @click="ui.cohort = 'all'">Todas</button>
            </div>
        </div>
        <div class="times">
            <div v-for="[k, l] in tramos" :key="k" class="tm">
                <div class="tml">{{ l }}</div>
                <template v-if="dato(k)">
                    <div class="tv">{{ fmt(dato(k)[2]) }}<small>días · mediana</small></div>
                    <div class="rng"><i style="left:0;width:100%"></i><b :style="{ left: `calc(${Math.min(100, dato(k)[2] / Math.max(1, dato(k)[3]) * 100)}% - 1px)` }"></b></div>
                    <div class="ts"><span>Media <b>{{ fmt(dato(k)[1]) }}</b></span><span>P90 <b>{{ fmt(dato(k)[3]) }}</b></span><span>{{ fmt(dato(k)[0]) }} exp.</span></div>
                </template>
                <div v-else class="note">Sin datos</div>
            </div>
        </div>
        <p v-if="otros" class="note" style="margin:10px 0 0">Los tiempos responden al filtro de departamento; el resto de filtros no los modifica.</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { DEPS, fmt } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();
const F = filtros.f;
const tramos = [['sv', 'Solicitud → Visita'], ['vr', 'Visita → Resolución'], ['rn', 'Resolución → Nómina'], ['sn', 'Solicitud → Nómina']];

const alcance = computed(() => (F.dep != null ? String(F.dep) : 'N'));
const tm = computed(() => tablero.series.tm[ui.cohort]);
const dato = (k) => tm.value[k]?.[alcance.value];
const otros = computed(() => ['y', 'e', 'r', 'n', 'v', 's', 'f', 'a', 'am', 't', 'mun'].some((k) => F[k] != null));
</script>
