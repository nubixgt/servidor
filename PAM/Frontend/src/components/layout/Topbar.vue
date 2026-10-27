<template>
    <header class="top">
        <button id="menuM" class="ibtn menu-m" aria-label="Abrir menú" @click="ui.menuAbierto = !ui.menuAbierto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18" /></svg>
        </button>
        <div class="search" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.6-3.6" /></svg>
            <input id="q" ref="entrada" v-model="q" type="search" autocomplete="off" placeholder="Buscar por nombre, DPI, expediente o teléfono…"
                aria-label="Buscar beneficiario o expediente" @input="buscar" @keydown="teclas" @blur="cerrarSugerencias">
            <kbd>/</kbd>
            <div v-if="abierto && res.length" id="sugg" class="suggest">
                <button v-for="(p, i) in res.slice(0, 6)" :key="p.id" :class="{ act: i === act }" @mousedown.prevent="abrirExp(p.id)">
                    <b>{{ p.nombre }}</b><span><EstadoBadge :i="p.estado_orden" /></span>
                    <small>Exp. {{ p.numero || 's/n' }} · {{ p.dpi || '—' }} · {{ p.municipio }}, {{ p.departamento }}</small>
                </button>
                <button @mousedown.prevent="verTodo">
                    <b style="color:var(--blue7)">Ver todos los resultados en Beneficiarios</b>
                    <small>{{ res.length >= 6 ? 'Hay más coincidencias' : '' }}</small>
                </button>
            </div>
        </div>
        <div class="topr">
            <span class="live" :title="detalleDatos"><i></i><span>{{ tablero.listo ? `Datos al ${tablero.corte || '—'}` : 'Datos' }}</span></span>
            <button v-if="auth.puede('dashboard.ver')" id="askBtn" class="ask" aria-label="Preguntar en lenguaje natural" @click="ui.preguntar = true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z" /><path d="M19 17l.8 2.2L22 20l-2.2.8L19 23l-.8-2.2L16 20l2.2-.8z" /></svg>
                <span>Preguntar</span><kbd>Ctrl K</kbd>
            </button>
            <button id="bell" class="ibtn" aria-label="Alertas" @click="router.push('/alertas')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" /><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" /></svg>
                <span class="dot"></span>
            </button>
            <button id="theme" class="ibtn" aria-label="Cambiar tema" @click="cambiarTema">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" /></svg>
            </button>
            <div class="user">
                <div class="ava">{{ iniciales }}</div>
                <div><b>{{ auth.usuario?.nombre || '—' }}</b><small>{{ auth.usuario?.rol_nombre || '—' }}</small></div>
                <button class="salir" title="Cerrar sesión" aria-label="Cerrar sesión" @click="salir">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><path d="m16 17 5-5-5-5" /><path d="M21 12H9" /></svg>
                </button>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import EstadoBadge from '../common/EstadoBadge.vue';
import expedienteService from '../../services/expedienteService';
import { useAuthStore } from '../../stores/auth';
import { useExpedienteStore } from '../../stores/expediente';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';

const router = useRouter();
const auth = useAuthStore();
const tablero = useTableroStore();
const ui = useUiStore();
const expediente = useExpedienteStore();

const entrada = ref(null);
const q = ref('');
const res = ref([]);
const act = ref(-1);
const abierto = ref(false);
let temporizador = 0;
let secuencia = 0;

const iniciales = computed(() => {
    const n = auth.usuario?.nombre || '';
    return n.split(/\s+/).filter(Boolean).slice(0, 2).map((s) => s[0]).join('').toUpperCase() || '··';
});
const detalleDatos = computed(() => {
    const r = tablero.refrescadoEn;
    return `Cifras calculadas en el servidor sobre la base del programa. Agregados actualizados: ${r ? r.toLocaleString('es-GT') : '—'}.`;
});

// ---- búsqueda universal con sugerencias
function buscar() {
    clearTimeout(temporizador);
    const v = q.value.trim();
    if (v.length < 3 || !auth.puede('beneficiarios.ver')) { res.value = []; return; }
    temporizador = setTimeout(async () => {
        const s = ++secuencia;
        try {
            const d = await expedienteService.listar({ q: v, limite: 6, orden: /[a-z]/i.test(v) ? 'relevancia' : 'fsol' });
            if (s === secuencia) { res.value = d.items; act.value = -1; abierto.value = true; }
        } catch (e) { /* sin sugerencias */ }
    }, 280);
}

function abrirExp(id) {
    expediente.abrir(id);
    abierto.value = false;
}

function verTodo() {
    ui.bq = q.value;
    ui.bRule = null;
    abierto.value = false;
    router.push('/beneficiarios');
}

function teclas(e) {
    const n = Math.min(res.value.length, 6);
    if (e.key === 'ArrowDown') { act.value = Math.min(act.value + 1, n - 1); e.preventDefault(); }
    else if (e.key === 'ArrowUp') { act.value = Math.max(act.value - 1, 0); e.preventDefault(); }
    else if (e.key === 'Enter') {
        if (act.value >= 0 && res.value[act.value]) abrirExp(res.value[act.value].id);
        else verTodo();
    } else if (e.key === 'Escape') abierto.value = false;
}

function cerrarSugerencias() {
    setTimeout(() => { abierto.value = false; }, 150);
}

// ---- tecla "/" enfoca la búsqueda
function atajo(e) {
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
        e.preventDefault();
        entrada.value.focus();
    }
}
onMounted(() => document.addEventListener('keydown', atajo));
onBeforeUnmount(() => document.removeEventListener('keydown', atajo));

// ---- tema claro/oscuro
function cambiarTema() {
    const r = document.documentElement;
    const oscuro = r.dataset.theme ? r.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    r.dataset.theme = oscuro ? 'light' : 'dark';
    try { localStorage.setItem('pam-tema', r.dataset.theme); } catch (e) { /* sin almacenamiento */ }
}

async function salir() {
    await auth.logout();
    window.location.href = router.resolve('/login').href; // recarga completa: limpia todo el estado
}
</script>
