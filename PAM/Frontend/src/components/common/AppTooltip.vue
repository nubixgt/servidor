<template>
    <div ref="caja" class="tt" :hidden="!estado.visible" :style="posicion">
        <h4>{{ estado.titulo }}</h4>
        <div v-for="(fila, k) in estado.filas" :key="k" class="r">
            <span><i v-if="fila.c" :style="{ background: fila.c }"></i>{{ fila.l }}</span><b>{{ fila.v }}</b>
        </div>
        <div v-if="estado.pie" class="r" style="margin-top:4px"><span>{{ estado.pie }}</span></div>
    </div>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import { useTooltip } from '../../composables/useTooltip';

const { estado } = useTooltip();
const caja = ref(null);
const posicion = ref({ left: '0px', top: '0px' });

// Se mide la caja ya pintada para que no se salga de la ventana
watch(() => [estado.visible, estado.x, estado.y, estado.titulo, estado.filas], async () => {
    if (!estado.visible) return;
    await nextTick();
    const w = caja.value.offsetWidth, h = caja.value.offsetHeight;
    let L = estado.x + 14, T = estado.y + 14;
    if (L + w > innerWidth - 8) L = estado.x - w - 14;
    if (T + h > innerHeight - 8) T = estado.y - h - 14;
    posicion.value = { left: Math.max(8, L) + 'px', top: Math.max(8, T) + 'px' };
});
</script>
