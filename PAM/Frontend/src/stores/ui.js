import { defineStore } from 'pinia';
import { reactive, ref } from 'vue';

/** Estado de interfaz que sobrevive al cambio de vista (modo de gráficos, orden de tablas, etc.). */
export const useUiStore = defineStore('ui', () => {
    const mapMetric = ref('c');
    const evoMode = ref('y');
    const evoOn = reactive(new Set(['sol', 'vpos', 'ing', 'fall']));
    const cohort = ref('rec');
    const terrSort = ref(['c', -1]);
    const terrOpen = reactive(new Set());
    const moreF = ref(false);
    const filtrosAbiertos = ref(false);

    // beneficiarios
    const bq = ref('');
    const bRule = ref(null);
    const bSort = ref(['fsol', -1]);
    const bSortManual = ref(false);

    const rep = ref(0);
    const modoImp = ref('INCREMENTAL');

    // chrome
    const menuMin = ref(false);
    const menuAbierto = ref(false);
    const preguntar = ref(false);
    const datosDesactualizados = ref(false);

    return { mapMetric, evoMode, evoOn, cohort, terrSort, terrOpen, moreF, filtrosAbiertos, bq, bRule, bSort, bSortManual, rep, modoImp, menuMin, menuAbierto, preguntar, datosDesactualizados };
});
