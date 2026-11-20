<template>
    <div>
        <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-ink-900">Beneficiarios</h1>
                <p class="mt-1 text-sm text-ink-500">Base de entrega con comentario e imagen de respaldo por fila.</p>
            </div>

            <dl class="flex divide-x divide-rule rounded border border-rule bg-white text-sm">
                <div class="px-5 py-2.5">
                    <dt class="text-xs uppercase tracking-wide text-ink-500">Registros</dt>
                    <dd class="text-lg font-semibold tabular-nums text-ink-900">{{ numero(resumen.total) }}</dd>
                </div>
                <div class="px-5 py-2.5">
                    <dt class="text-xs uppercase tracking-wide text-ink-500">Con comentario</dt>
                    <dd class="text-lg font-semibold tabular-nums text-ink-900">{{ numero(resumen.con_comentario) }}</dd>
                </div>
                <div class="px-5 py-2.5">
                    <dt class="text-xs uppercase tracking-wide text-ink-500">Con imagen</dt>
                    <dd class="text-lg font-semibold tabular-nums text-ink-900">{{ numero(resumen.con_imagen) }}</dd>
                </div>
            </dl>
        </div>

        <section class="overflow-hidden rounded border border-rule bg-white">
            <FiltrosBar v-model="filtros" :catalogos="catalogos" @limpiar="limpiar" />

            <div v-if="error" class="flex items-center justify-between gap-3 bg-danger/5 px-4 py-3 text-sm text-danger">
                <span>{{ error }}</span>
                <button class="btn-neutro px-3 py-1" @click="cargar">Reintentar</button>
            </div>

            <TablaBeneficiarios :filas="datos.items" :cargando="cargando" :seleccionado-id="seleccionada?.id ?? null"
                @abrir="seleccionada = $event" />

            <Paginacion :pagina="datos.pagina" :paginas="datos.paginas" :por-pagina="datos.por_pagina"
                :total="datos.total" @ir="irAPagina" @cambiar-tamano="cambiarTamano" />
        </section>

        <PanelSeguimiento v-if="seleccionada" :key="seleccionada.id" :fila="seleccionada" :es-admin="auth.esAdmin"
            @cerrar="seleccionada = null" @guardado="alGuardar" />
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import FiltrosBar from '../../components/beneficiarios/FiltrosBar.vue';
import TablaBeneficiarios from '../../components/beneficiarios/TablaBeneficiarios.vue';
import PanelSeguimiento from '../../components/beneficiarios/PanelSeguimiento.vue';
import Paginacion from '../../components/common/Paginacion.vue';
import beneficiarioService from '../../services/beneficiarioService';
import { mensajeDeError } from '../../services/api';
import { useAuthStore } from '../../stores/auth';
import { numero } from '../../utils/formato';

const auth = useAuthStore();

const FILTROS_VACIOS = { q: '', comunidad_id: '', comunidad_base_id: '', estado: '', resaltado: '' };

const filtros = ref({ ...FILTROS_VACIOS });
const pagina = ref(1);
const porPagina = ref(50);
const datos = reactive({ items: [], pagina: 1, paginas: 1, por_pagina: 50, total: 0 });
const resumen = reactive({ total: 0, con_comentario: 0, con_imagen: 0 });
const catalogos = reactive({ comunidades: [], comunidades_base: [] });
const cargando = ref(false);
const error = ref('');
const seleccionada = ref(null);

// Si el usuario escribe rápido, solo cuenta la respuesta de la última petición
let ultimaPeticion = 0;

const cargar = async () => {
    const id = ++ultimaPeticion;
    cargando.value = true;
    error.value = '';
    try {
        const params = { pagina: pagina.value, por_pagina: porPagina.value };
        for (const [k, v] of Object.entries(filtros.value)) {
            if (v !== '') params[k] = v;
        }
        const r = await beneficiarioService.listar(params);
        if (id !== ultimaPeticion) return;
        Object.assign(datos, r);
    } catch (e) {
        if (id !== ultimaPeticion) return;
        error.value = mensajeDeError(e, 'No se pudo cargar la lista');
    } finally {
        if (id === ultimaPeticion) cargando.value = false;
    }
};

const cargarResumen = async () => {
    try {
        Object.assign(resumen, await beneficiarioService.resumen());
    } catch { /* el resumen es informativo: si falla, la tabla sigue funcionando */ }
};

let temporizador = null;
watch(filtros, (nuevo, anterior) => {
    pagina.value = 1;
    clearTimeout(temporizador);
    // la búsqueda por texto espera a que termine de escribir; los selectores responden al instante
    temporizador = setTimeout(cargar, nuevo.q !== anterior.q ? 350 : 0);
}, { deep: true });

const irAPagina = (n) => {
    pagina.value = n;
    cargar();
};

const cambiarTamano = (n) => {
    porPagina.value = n;
    pagina.value = 1;
    cargar();
};

const limpiar = () => {
    filtros.value = { ...FILTROS_VACIOS };
};

/** Refleja lo guardado en la fila sin recargar toda la tabla. */
const alGuardar = (id, seguimiento) => {
    const fila = datos.items.find(f => f.id === id);
    if (fila) fila.seguimiento = seguimiento;
    cargarResumen();
};

onMounted(async () => {
    cargar();
    cargarResumen();
    try {
        Object.assign(catalogos, await beneficiarioService.catalogos());
    } catch { /* sin catálogos los filtros por lista quedan vacíos */ }
});
</script>
