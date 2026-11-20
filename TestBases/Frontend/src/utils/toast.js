import { reactive } from 'vue';

export const toasts = reactive([]);
let siguiente = 1;

const mostrar = (tipo, texto, ms = 4500) => {
    const id = siguiente++;
    toasts.push({ id, tipo, texto });
    setTimeout(() => cerrar(id), ms);
};

export const cerrar = (id) => {
    const i = toasts.findIndex(t => t.id === id);
    if (i !== -1) toasts.splice(i, 1);
};

export const toast = {
    ok: (texto) => mostrar('ok', texto),
    error: (texto) => mostrar('error', texto, 7000),
};
