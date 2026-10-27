import { reactive } from 'vue';

const estado = reactive({ visible: false, titulo: '', filas: [], pie: '', x: 0, y: 0 });

/** Tooltip flotante de los gráficos. `filas`: [{ l: etiqueta, v: valor, c?: color }]. */
export function useTooltip() {
    function mostrar({ titulo, filas = [], pie = '' }, x, y) {
        Object.assign(estado, { visible: true, titulo, filas, pie, x, y });
    }
    function ocultar() {
        estado.visible = false;
    }
    return { estado, mostrar, ocultar };
}
