<template>
    <VistaHeader eyebrow="Gestión" titulo="Solicitudes" desc="Recepción, bandejas por etapa, antigüedad y re-solicitudes de la misma persona." />
    <div v-if="auth.puede('solicitud.registrar')">
        <button class="btn pri" @click="expediente.abrirForm('nueva')"><Icono name="file" :size="16" /> Registrar nueva solicitud</button>
    </div>
    <div class="grid g2e"><AntigCard /><FlowCard /></div>
    <EvoCard :keys="['sol']" title="Solicitudes recibidas" />
</template>

<script setup>
import Icono from '../../components/common/Icono.vue';
import VistaHeader from '../../components/common/VistaHeader.vue';
import AntigCard from '../../components/dashboard/AntigCard.vue';
import EvoCard from '../../components/dashboard/EvoCard.vue';
import FlowCard from '../../components/dashboard/FlowCard.vue';
import { useAuthStore } from '../../stores/auth';
import { useExpedienteStore } from '../../stores/expediente';

const auth = useAuthStore();
const expediente = useExpedienteStore();
</script>
