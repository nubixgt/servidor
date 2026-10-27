<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Requieren atención</h2>
                <p>Reglas automáticas sobre los datos filtrados · {{ tablero.ambito }}</p>
            </div>
            <button class="btn" @click="router.push('/alertas')">Todas las alertas</button>
        </div>
        <AlertRows :limite="6" />
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import AlertRows from './AlertRows.vue';
import { useTableroStore } from '../../stores/tablero';

const router = useRouter();
const tablero = useTableroStore();
</script>
