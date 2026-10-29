<template>
    <div v-if="!reportes" class="card"><div class="empty-state">Cargando catálogo…</div></div>
    <div v-else class="grid g2">
        <div class="card">
            <div class="ch"><div><h2>Catálogo</h2><p>{{ reportes.length }} reportes</p></div></div>
            <div class="grid g3" style="gap:10px">
                <button v-for="x in reportes" :key="x.id" class="rep" :class="{ on: ui.rep === x.id }" :disabled="!x.permitido"
                    :title="x.permitido ? null : 'Su rol no puede exportar datos personales'" @click="ui.rep = x.id">
                    <b>{{ x.titulo }}</b><small>{{ x.descripcion }}</small>
                    <small v-if="x.datos_personales" style="color:var(--warn)">Contiene datos personales</small>
                </button>
            </div>
        </div>
        <div class="card">
            <div class="ch"><div><h2>{{ actual.titulo }}</h2><p>Parámetros</p></div></div>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div class="field">Filtros aplicados
                    <div class="parsed" style="display:flex;flex-wrap:wrap;gap:6px">
                        <span v-for="[k, v] in activos" :key="k" class="chip">{{ filtros.etiqueta(k, v) }}</span>
                        <span v-if="!activos.length" class="note">Ninguno (todo su ámbito)</span>
                    </div>
                </div>
                <div class="field"><label for="rfmt">Formato</label>
                    <select id="rfmt" v-model="formato"><option value="csv">CSV (se abre directamente en Excel)</option></select></div>
                <div class="field"><label for="motivo">Motivo de la exportación</label>
                    <input id="motivo" v-model="motivo" placeholder="Ej. Informe mensual a Despacho Superior" maxlength="300"></div>
                <div class="err">{{ error }}</div>
                <button class="btn pri" :disabled="!actual.permitido || generando" @click="generar">
                    <template v-if="generando">Generando…</template><template v-else><Icono name="download" :size="16" /> Generar y descargar</template>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import Icono from '../common/Icono.vue';
import { useToast } from '../../composables/useToast';
import reporteService from '../../services/reporteService';
import { useFiltrosStore } from '../../stores/filtros';
import { useUiStore } from '../../stores/ui';

const filtros = useFiltrosStore();
const ui = useUiStore();
const { toast } = useToast();

const reportes = ref(null);
const formato = ref('csv');
const motivo = ref('');
const error = ref('');
const generando = ref(false);

const activos = computed(() => Object.entries(filtros.f).filter(([, v]) => v != null));
const actual = computed(() => reportes.value.find((r) => r.id === ui.rep) || {});

async function generar() {
    const m = motivo.value.trim();
    error.value = '';
    if (m.length < 5) { error.value = 'Escriba el motivo de la exportación (mínimo 5 caracteres).'; return; }
    generando.value = true;
    try {
        await reporteService.exportar({ tipo: ui.rep, formato: formato.value, motivo: m, filtros: filtros.paraApi() });
        toast('Reporte generado · la exportación quedó registrada en la bitácora');
    } catch (e) {
        error.value = e.message;
    }
    generando.value = false;
}

onMounted(async () => {
    try { reportes.value = await reporteService.catalogo(); } catch (e) { reportes.value = []; }
    if (!reportes.value.find((r) => r.id === ui.rep)) ui.rep = reportes.value[0] && reportes.value[0].id;
});
</script>
