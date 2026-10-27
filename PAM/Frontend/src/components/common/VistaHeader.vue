<template>
    <div class="vh">
        <div>
            <div class="eyebrow">{{ eyebrow }}</div>
            <h1>{{ titulo }}</h1>
            <p v-if="desc">{{ desc }}</p>
        </div>
        <div class="meta">Corte de datos <b>{{ tablero.corte || '—' }}</b><br>{{ fmt(tablero.cube ? tablero.cube.TOTAL_NAC : 0) }} expedientes en su ámbito</div>
    </div>
</template>

<script setup>
import { useTableroStore } from '../../stores/tablero';
import { fmt } from '../../utils/formato';

defineProps({
    eyebrow: { type: String, default: '' },
    titulo: { type: String, required: true },
    desc: { type: String, default: '' }
});
const tablero = useTableroStore();
</script>
