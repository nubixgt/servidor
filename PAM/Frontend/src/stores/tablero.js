import { defineStore } from 'pinia';
import { computed, ref, shallowRef } from 'vue';
import tableroService from '../services/tableroService';
import geoJson from '../assets/data/geo.json';
import { bboxOf, compute, prepCube } from '../utils/cubo';
import { DEPS, fd } from '../utils/formato';
import { useAuthStore } from './auth';
import { useFiltrosStore } from './filtros';

export const useTableroStore = defineStore('tablero', () => {
    // El cubo pesa decenas de miles de filas: no se vuelve reactivo por dentro
    const cube = shallowRef(null);
    const series = shallowRef(null);
    const geo = geoJson;
    const bbox = Object.fromEntries(Object.entries(geo.paths).map(([k, p]) => [k, bboxOf(p)]));
    const corte = ref('');
    const refrescadoEn = ref(null);
    const cargando = ref(false);
    const error = ref('');

    const listo = computed(() => !!cube.value);

    /** Agregados para los filtros activos (se recalculan al cambiar un filtro). */
    const ag = computed(() => (cube.value ? compute(cube.value, useFiltrosStore().f) : null));

    /** Nombre del ámbito que se está mirando (filtro, o ámbito territorial del usuario). */
    const ambito = computed(() => {
        const F = useFiltrosStore().f;
        const c = cube.value;
        if (c) {
            if (F.mun != null) return `${c.MN[F.mun]}, ${DEPS[F.dep]}`;
            if (F.dep != null) return DEPS[F.dep];
            const u = useAuthStore().usuario;
            if (u && u.municipio_id && c.MU.length === 1) return `${c.MN[0]}, ${DEPS[c.MDEP[0]]}`;
            if (u && u.departamento_id) return DEPS[u.departamento_id - 1];
        }
        return 'Nacional';
    });

    async function cargar() {
        cargando.value = true;
        error.value = '';
        try {
            const d = await tableroService.datos();
            useFiltrosStore().limpiarSilencioso();
            cube.value = prepCube(d.cubo);
            series.value = d.series;
            corte.value = d.meta.fecha_corte ? fd(d.meta.fecha_corte) : '';
            refrescadoEn.value = d.meta.refrescado_en ? new Date(String(d.meta.refrescado_en).replace(' ', 'T')) : null;
        } catch (e) {
            error.value = e.message;
            throw e;
        } finally {
            cargando.value = false;
        }
    }

    return { cube, series, geo, bbox, corte, refrescadoEn, cargando, error, listo, ag, ambito, cargar };
});
