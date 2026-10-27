<template>
    <div class="alist">
        <div v-for="r in rows" :key="r.k" class="al">
            <span class="band" :style="{ background: PRC[r.p] }"></span>
            <span class="ai" :style="{ color: PRC[r.p], background: `color-mix(in srgb,${PRC[r.p]} 14%,transparent)` }"><Icono :name="r.icon" /></span>
            <div>
                <div class="at">{{ r.t }}<span class="pr" :class="r.p">{{ PRN[r.p] }}</span></div>
                <div class="ad">{{ r.d }} · actualizado {{ tablero.corte }}</div>
            </div>
            <div class="ac">{{ fmt(r.n) }}</div>
            <button class="btn" @click="verCasos(r.k)">Ver casos</button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import Icono from '../common/Icono.vue';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { fmt } from '../../utils/formato';
import { PRC, PRN, RULES } from '../../utils/reglas';

const props = defineProps({ limite: { type: Number, default: 99 } });
const router = useRouter();
const tablero = useTableroStore();
const ui = useUiStore();

const rows = computed(() => RULES.map((r) => ({ ...r, n: tablero.ag.al[r.k] })).filter((r) => r.n > 0).slice(0, props.limite));

function verCasos(regla) {
    ui.bRule = regla;
    router.push('/beneficiarios');
}
</script>
