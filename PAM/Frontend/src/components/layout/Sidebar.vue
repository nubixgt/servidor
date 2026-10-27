<template>
    <aside id="side" class="side" :class="{ min: ui.menuMin, open: ui.menuAbierto }" aria-label="Navegación principal">
        <div class="brand">
            <img class="lg-full" :src="logoBlanco" alt="Ministerio de Trabajo y Previsión Social">
            <img class="lg-em" :src="escudoBlanco" alt="Ministerio de Trabajo y Previsión Social">
            <div class="sysname"><b>Programa del Adulto Mayor</b><small>Centro de Monitoreo</small></div>
        </div>
        <nav id="nav">
            <template v-for="[grupo, items] in grupos" :key="grupo">
                <div class="navgrp">{{ grupo }}</div>
                <button v-for="[k, l, icono] in items" :key="k" class="nav" :class="{ on: ruta.name === k }" :title="l"
                    :aria-current="ruta.name === k ? 'page' : null" @click="ir(k)">
                    <Icono :name="icono" :size="19" /><span class="navl">{{ l }}</span>
                    <span v-if="k === 'alertas' && crit" class="pill">{{ fmt(crit) }}</span>
                </button>
            </template>
        </nav>
        <div class="sidefoot">
            <button id="collapse" aria-label="Contraer menú" @click="ui.menuMin = !ui.menuMin">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M15 18l-6-6 6-6" /></svg>
                <span>Contraer menú</span>
            </button>
        </div>
    </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icono from '../common/Icono.vue';
import logoBlanco from '../../assets/img/logo-mintrab-blanco.png';
import escudoBlanco from '../../assets/img/escudo-blanco.png';
import { useAuthStore } from '../../stores/auth';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { fmt } from '../../utils/formato';

const ruta = useRoute();
const router = useRouter();
const auth = useAuthStore();
const tablero = useTableroStore();
const ui = useUiStore();

/** [grupo, [[ruta, etiqueta, icono, permiso(s)]]] */
const NAV = [
    ['Monitoreo', [['dashboard', 'Dashboard', 'grid', 'dashboard.ver'], ['territorio', 'Territorio', 'map', 'dashboard.ver'], ['alertas', 'Alertas', 'alert', 'alertas.ver']]],
    ['Gestión', [['beneficiarios', 'Beneficiarios', 'users', 'beneficiarios.ver'], ['solicitudes', 'Solicitudes', 'file', 'dashboard.ver'], ['visitas', 'Visitas', 'pin', 'dashboard.ver'],
        ['resoluciones', 'Resoluciones', 'stamp', 'dashboard.ver'], ['nomina', 'Nómina', 'wallet', 'dashboard.ver'], ['estados', 'Estados', 'pulse', 'dashboard.ver']]],
    ['Control', [['reportes', 'Reportes', 'chart', 'reportes.ver'], ['calidad', 'Calidad de datos', 'shield', 'calidad.ver'], ['importaciones', 'Importaciones', 'upload', 'importacion.ejecutar'],
        ['admin', 'Administración', 'sliders', ['usuarios.gestionar', 'roles.gestionar', 'auditoria.ver', 'catalogos.gestionar']]]],
];

const grupos = computed(() => NAV
    .map(([g, items]) => [g, items.filter((it) => auth.puedeVer(it[3]))])
    .filter(([, items]) => items.length));

const crit = computed(() => (tablero.ag ? tablero.ag.al.qa : 0));

function ir(k) {
    router.push('/' + k);
    ui.menuAbierto = false;
}
</script>
