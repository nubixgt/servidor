import { defineStore } from 'pinia';
import { reactive } from 'vue';
import { AB, ANT, DEPS, NOM, VIS } from '../utils/formato';
import { useTableroStore } from './tablero';

const VACIOS = () => ({ dep: null, mun: null, y: null, e: null, r: null, n: null, v: null, s: null, f: null, a: null, am: null, t: null });

/** Filtros principales (siempre visibles); el resto aparece con "Más filtros". */
export const PRIMARY = ['dep', 'mun', 'y', 'e'];

export const useFiltrosStore = defineStore('filtros', () => {
    const f = reactive(VACIOS());

    /** [clave, etiqueta, opciones() → [[valor, texto]]] */
    const FDEF = [
        ['dep', 'Departamento', () => DEPS.map((d, i) => [i, d]).sort((a, b) => a[1].localeCompare(b[1]))],
        ['mun', 'Municipio', () => {
            const c = useTableroStore().cube;
            return f.dep == null || !c ? [] : c.MUNI_BY_DEP[f.dep].map((i) => [i, c.MN[i]]).sort((a, b) => a[1].localeCompare(b[1]));
        }],
        ['y', 'Año de solicitud', () => (useTableroStore().cube?.YS || []).map((y, i) => [i, y]).reverse()],
        ['e', 'Estado actual', () => (useTableroStore().cube?.EST || []).map((e, i) => [i, e])],
        ['r', 'Tipo de resolución', () => (useTableroStore().cube?.RG || []).map((e, i) => [i, e])],
        ['n', 'Situación de nómina', () => NOM.map((e, i) => [i, e])],
        ['v', 'Resultado de visita', () => VIS.map((e, i) => [i, e])],
        ['s', 'Suspendido', () => [[1, 'Con suspensión registrada'], [0, 'Sin suspensión']]],
        ['f', 'Fallecido', () => [[1, 'Con fallecimiento registrado'], [0, 'Sin fallecimiento']]],
        ['a', 'Rango de edad', () => AB.map((e, i) => [i, e + ' años'])],
    ];
    const FLABEL = {
        t: (v) => 'Antigüedad: ' + ANT[v],
        am: (v) => 'Edad: ' + [65, 70, 75, 80, 85, 90][v] + ' años o más',
    };

    function etiqueta(k, v) {
        if (FLABEL[k]) return FLABEL[k](v);
        const d = FDEF.find((x) => x[0] === k);
        const o = d[2]().find((x) => x[0] === v);
        return d[1] + ': ' + (o ? o[1] : v);
    }

    function setF(k, v, { toggle = true } = {}) {
        const MDEP = useTableroStore().cube?.MDEP;
        if (toggle && f[k] === v) v = null;
        f[k] = v;
        if (k === 'dep' && f.mun != null && MDEP && (v == null || MDEP[f.mun] !== v)) f.mun = null;
        if (k === 'mun' && v != null && MDEP) f.dep = MDEP[v];
    }

    function limpiar() {
        Object.assign(f, VACIOS());
    }
    // Al recargar el cubo se parte de cero (igual que el diseño original)
    const limpiarSilencioso = limpiar;

    function quitar(k) {
        f[k] = null;
        if (k === 'dep') f.mun = null;
    }

    /** Reemplaza todos los filtros (consulta en lenguaje natural). */
    function aplicar(nuevos) {
        limpiar();
        Object.assign(f, nuevos);
    }

    /** Parámetros del API a partir de los filtros del tablero. */
    function paraApi(extra = {}) {
        const c = useTableroStore().cube;
        const p = {};
        if (f.dep != null) p.dep = f.dep + 1;
        if (f.mun != null && c) p.mun = parseInt(c.MU[f.mun], 10);
        if (f.y != null && c) { const y = c.YS[f.y]; p.anio = y === 's/f' ? 0 : +y; }
        for (const k of ['e', 'r', 'n', 'v', 's', 'f', 'a', 'am', 't']) if (f[k] != null) p[k] = f[k];
        return { ...p, ...extra };
    }

    return { f, FDEF, etiqueta, setF, limpiar, limpiarSilencioso, quitar, aplicar, paraApi };
});
