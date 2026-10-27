<template>
    <div class="tl">
        <div v-for="(ev, i) in eventos" :key="i" class="ev" :style="{ '--c': COLOR_EV[ev.tipo] || 'var(--blue5)' }">
            <div class="et">{{ ev.titulo }}{{ ev.origen === 'SISTEMA' ? ' · registrado en el sistema' : '' }}</div>
            <div class="ed">{{ fdl(ev.fecha) }}</div>
            <div v-if="ev.detalle" class="ex">{{ ev.detalle }}</div>
        </div>
    </div>
</template>

<script setup>
import { fdl } from '../../utils/formato';

defineProps({ eventos: { type: Array, required: true } });

const COLOR_EV = {
    SOLICITUD: 'var(--blue5)', VISITA_POSITIVA: 'var(--ok)', VISITA_NEGATIVA: 'var(--warnf)', VISITA_NO_LOCALIZADO: 'var(--warnf)',
    OBSERVACION: 'var(--off)', RESOLUCION: 'var(--st3)', NOMINA_ALTA: 'var(--ok)', NOMINA_BAJA: 'var(--st3)',
    SUSPENSION: 'var(--st4)', FALLECIMIENTO: 'var(--dead)', ESTADO: 'var(--s7)',
};
</script>
