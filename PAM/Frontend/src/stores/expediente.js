import { defineStore } from 'pinia';
import { ref } from 'vue';
import expedienteService from '../services/expedienteService';
import { useUiStore } from './ui';
import { useToast } from '../composables/useToast';

/** Expediente 360° abierto en el panel lateral y el formulario de gestión activo. */
export const useExpedienteStore = defineStore('expediente', () => {
    const abierto = ref(false);
    const id = ref(null);
    const d = ref(null);
    const tab = ref('resumen');
    const dpi = ref(null);
    /** { tipo: 'obs'|'vis'|'res'|'est'|'nom'|'con'|'caso'|'nueva', regla? } */
    const form = ref(null);

    async function abrir(eid) {
        try {
            const data = await expedienteService.detalle(eid);
            if (id.value !== eid) tab.value = 'resumen';
            id.value = eid;
            d.value = data;
            dpi.value = null;
            abierto.value = true;
        } catch (e) {
            useToast().toast(e.message);
        }
    }

    function cerrar() {
        abierto.value = false;
    }

    async function mostrarDpi() {
        try {
            dpi.value = (await expedienteService.dpi(id.value)).dpi;
            useToast().toast('Consulta de DPI registrada en auditoría');
        } catch (e) {
            useToast().toast(e.message);
        }
    }

    /** Registra una gestión y recarga el expediente; los datos del tablero quedan por refrescar. */
    async function registrar(ruta, cuerpo, mensaje) {
        await expedienteService.registrar(id.value, ruta, cuerpo);
        form.value = null;
        useToast().toast(mensaje);
        useUiStore().datosDesactualizados = true;
        await abrir(id.value);
    }

    function abrirForm(tipo, extra = {}) {
        form.value = { tipo, ...extra };
    }

    return { abierto, id, d, tab, dpi, form, abrir, cerrar, mostrarDpi, registrar, abrirForm };
});
