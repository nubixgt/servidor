<template>
    <div id="fbar" class="fbar" :class="{ open: ui.filtrosAbiertos }">
        <button id="ftog" class="btn ftog" :aria-expanded="ui.filtrosAbiertos" @click="ui.filtrosAbiertos = !ui.filtrosAbiertos">
            <Icono name="sliders" :size="16" /> Filtros{{ nAct ? ' (' + nAct + ')' : '' }}
        </button>
        <div v-for="[k, l, opts] in visibles" :key="k" class="fsel" :class="{ on: F[k] != null }">
            <label :for="'f_' + k">{{ l }}</label>
            <select :id="'f_' + k" :disabled="k === 'mun' && F.dep == null" :title="k === 'mun' && F.dep == null ? 'Seleccione primero un departamento' : null"
                @change="cambiar(k, $event)">
                <option value="" :selected="F[k] == null">{{ l }}{{ k === 'mun' && F.dep == null ? ' (elija depto.)' : '' }}</option>
                <option v-for="[v, t] in opts()" :key="v" :value="v" :selected="F[k] === v">{{ t }}</option>
            </select>
        </div>
        <button id="moreF" class="btn more" :aria-expanded="mostrarSec" @click="masFiltros">
            <Icono name="sliders" :size="15" /> {{ mostrarSec ? 'Menos filtros' : 'Más filtros (6)' }}
        </button>
        <button id="clearF" class="btn" :disabled="!nAct" @click="filtros.limpiar()">Limpiar filtros</button>
    </div>
    <div id="chips" class="chips">
        <span v-for="[k, v] in activos" :key="k" class="chip">{{ filtros.etiqueta(k, v) }}<button aria-label="Quitar filtro" @click="filtros.quitar(k)"><Icono name="close" :size="12" /></button></span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Icono from '../common/Icono.vue';
import { PRIMARY, useFiltrosStore } from '../../stores/filtros';
import { useUiStore } from '../../stores/ui';

const filtros = useFiltrosStore();
const ui = useUiStore();
const F = filtros.f;

const activos = computed(() => Object.entries(F).filter(([, v]) => v != null));
const nAct = computed(() => activos.value.length);
const secAct = computed(() => filtros.FDEF.filter((d) => !PRIMARY.includes(d[0]) && F[d[0]] != null).length);
const mostrarSec = computed(() => ui.moreF || secAct.value > 0);
const visibles = computed(() => filtros.FDEF.filter((d) => mostrarSec.value || PRIMARY.includes(d[0])));

function cambiar(k, e) {
    filtros.setF(k, e.target.value === '' ? null : +e.target.value, { toggle: false });
}

function masFiltros() {
    const mostrar = mostrarSec.value;
    ui.moreF = !mostrar;
    // al contraer se quitan los filtros secundarios activos
    if (!ui.moreF && secAct.value) {
        filtros.FDEF.forEach((d) => { if (!PRIMARY.includes(d[0])) F[d[0]] = null; });
    }
}
</script>
