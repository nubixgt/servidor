<template>
    <div id="app-shell" class="app">
        <Sidebar />
        <div class="main">
            <Topbar />
            <FiltrosBar v-if="tablero.listo && auth.puede('dashboard.ver')" />
            <main id="view" class="view enter">
                <div v-if="route.meta.datos && !auth.puede('dashboard.ver')" class="loading">Su rol no tiene acceso a los indicadores del tablero.</div>
                <div v-else-if="route.meta.datos && tablero.error" class="loading">No se pudieron cargar los datos: {{ tablero.error }}</div>
                <div v-else-if="route.meta.datos && !tablero.listo" class="loading">
                    <img class="lg-load" :src="logo" alt="" width="280"><span>Cargando…</span>
                </div>
                <router-view v-else :key="route.path"></router-view>
            </main>
        </div>
        <ExpedienteDrawer />
        <ExpedienteForm v-if="expediente.form" :key="expediente.form.tipo + (expediente.form.regla || '')" />
        <AskDialog v-if="ui.preguntar" />
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import AskDialog from './AskDialog.vue';
import FiltrosBar from './FiltrosBar.vue';
import Sidebar from './Sidebar.vue';
import Topbar from './Topbar.vue';
import ExpedienteDrawer from '../expediente/ExpedienteDrawer.vue';
import ExpedienteForm from '../expediente/ExpedienteForm.vue';
import logo from '../../assets/img/logo-mintrab.png';
import { useAuthStore } from '../../stores/auth';
import { useCatalogosStore } from '../../stores/catalogos';
import { useExpedienteStore } from '../../stores/expediente';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';

const route = useRoute();
const auth = useAuthStore();
const catalogos = useCatalogosStore();
const tablero = useTableroStore();
const expediente = useExpedienteStore();
const ui = useUiStore();

let refresco = 0;

function teclado(e) {
    if (e.key === 'Escape') {
        expediente.cerrar();
        expediente.form = null;
        ui.preguntar = false;
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k' && auth.puede('dashboard.ver')) {
        e.preventDefault();
        ui.preguntar = true;
    }
}

// reflejo de luz que sigue al cursor sobre tarjetas y KPIs
let spot = 0;
function puntero(e) {
    if (spot) return;
    spot = requestAnimationFrame(() => {
        spot = 0;
        const el = e.target.closest && e.target.closest('.card,.kpi,.hero');
        if (!el) return;
        const r = el.getBoundingClientRect();
        el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        el.style.setProperty('--my', (e.clientY - r.top) + 'px');
    });
}

onMounted(async () => {
    document.addEventListener('keydown', teclado);
    document.addEventListener('pointermove', puntero, { passive: true });
    try {
        await catalogos.cargar();
        if (auth.puede('dashboard.ver')) await tablero.cargar();
    } catch (e) { /* el error se muestra en la vista */ }

    // Refresco silencioso de los agregados cada 15 minutos si hubo cambios
    refresco = setInterval(async () => {
        if (ui.datosDesactualizados && route.name !== 'importaciones' && !expediente.abierto && !expediente.form) {
            ui.datosDesactualizados = false;
            try { await tablero.cargar(); } catch (e) { /* se reintenta en el siguiente ciclo */ }
        }
    }, 15 * 60 * 1000);
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', teclado);
    document.removeEventListener('pointermove', puntero);
    clearInterval(refresco);
});
</script>
