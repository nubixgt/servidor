<template>
    <div>
        <label class="label" for="p-fecha-informe">Fecha de informe *</label>
        <input id="p-fecha-informe" v-model="fechaInforme" type="date" required class="field" />
        <p class="hint">Semana {{ semanaIso(fechaInforme) }} del año (se guarda automáticamente).</p>
    </div>
    <div>
        <label class="label" for="p-mon-ini">Monitoreo: inicio</label>
        <input id="p-mon-ini" v-model="inicio" type="date" :required="!!fin" :max="fin || undefined" class="field" />
    </div>
    <div>
        <label class="label" for="p-mon-fin">Monitoreo: fin</label>
        <input id="p-mon-fin" v-model="fin" type="date" :required="!!inicio" :min="inicio || undefined" class="field" />
    </div>
</template>

<script setup>
import { semanaIso } from '../../utils/periodo';

// Campos del período compartidos por los formularios de Pecuario e Infraestructura.
const fechaInforme = defineModel('fechaInforme', { type: String, default: '' });
const inicio = defineModel('inicio', { type: String, default: '' });
const fin = defineModel('fin', { type: String, default: '' });
</script>
