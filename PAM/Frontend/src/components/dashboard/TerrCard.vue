<template>
    <div class="card">
        <div class="ch">
            <div>
                <h2>Análisis territorial</h2>
                <p>Departamentos y municipios · clic en el encabezado para ordenar, en el departamento para expandir</p>
            </div>
        </div>
        <div class="tw" style="max-height:520px">
            <table>
                <thead>
                    <tr>
                        <th><button @click="ordenar('n')">Departamento <span v-if="ui.terrSort[0] === 'n'" class="so">{{ ui.terrSort[1] < 0 ? '▼' : '▲' }}</span></button></th>
                        <th v-for="[k, l] in cols" :key="k" class="n"><button @click="ordenar(k)">{{ l }} <span v-if="ui.terrSort[0] === k" class="so">{{ ui.terrSort[1] < 0 ? '▼' : '▲' }}</span></button></th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="d in deps" :key="d.i">
                        <tr>
                            <td>
                                <button class="tog" :class="{ open: ui.terrOpen.has(d.i) }" :aria-expanded="ui.terrOpen.has(d.i)" @click="alternar(d.i)">
                                    <Icono name="chev" :size="14" />{{ d.n }}
                                </button>
                            </td>
                            <td v-for="[k] in cols" :key="k" class="n">{{ fmt(d.a[k]) }}</td>
                        </tr>
                        <template v-if="ui.terrOpen.has(d.i)">
                            <tr v-for="x in municipios(d.i)" :key="x.m" class="sub">
                                <td><button class="link" @click="filtros.setF('mun', x.m)">{{ tablero.cube.MN[x.m] }}</button></td>
                                <td v-for="[k] in cols" :key="k" class="n">{{ fmt(x.a[k]) }}</td>
                            </tr>
                        </template>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <td><b>Total {{ tablero.ambito }}</b></td>
                        <td v-for="[k] in cols" :key="k" class="n"><b>{{ fmt(tablero.ag.T[k]) }}</b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import Icono from '../common/Icono.vue';
import { useFiltrosStore } from '../../stores/filtros';
import { useTableroStore } from '../../stores/tablero';
import { useUiStore } from '../../stores/ui';
import { DEPS, fmt } from '../../utils/formato';

const filtros = useFiltrosStore();
const tablero = useTableroStore();
const ui = useUiStore();
const cols = [['p', 'Personas'], ['c', 'Solicitudes'], ['nom', 'En nómina'], ['tram', 'Pendientes'], ['vp', 'Visitas +'], ['vn', 'Visitas −'], ['susp', 'Suspendidos'], ['fall', 'Fallecidos']];

// al filtrar por departamento, su fila se muestra expandida
watch(() => filtros.f.dep, (d) => { if (d != null) ui.terrOpen.add(d); }, { immediate: true });

const deps = computed(() => {
    const [sk, sd] = ui.terrSort;
    const lista = DEPS.map((n, i) => ({ i, n, a: tablero.ag.byDep[i] })).filter((d) => d.a.c > 0);
    lista.sort((a, b) => (sk === 'n' ? a.n.localeCompare(b.n) * sd : (a.a[sk] - b.a[sk]) * sd));
    return lista;
});

function municipios(dep) {
    const [sk, sd] = ui.terrSort;
    const MN = tablero.cube.MN;
    const ms = tablero.cube.MUNI_BY_DEP[dep].map((m) => ({ m, a: tablero.ag.byMun.get(m) })).filter((x) => x.a);
    ms.sort((a, b) => (sk === 'n' ? MN[a.m].localeCompare(MN[b.m]) * sd : (a.a[sk] - b.a[sk]) * sd));
    return ms;
}

function ordenar(k) {
    ui.terrSort = [k, ui.terrSort[0] === k ? -ui.terrSort[1] : (k === 'n' ? 1 : -1)];
}

function alternar(d) {
    if (ui.terrOpen.has(d)) ui.terrOpen.delete(d);
    else ui.terrOpen.add(d);
}
</script>
