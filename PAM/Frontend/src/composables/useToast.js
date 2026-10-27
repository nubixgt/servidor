import { ref } from 'vue';

const mensaje = ref('');
let timer = 0;

/** Aviso breve en la parte inferior de la pantalla. */
export function useToast() {
    function toast(m) {
        mensaje.value = m;
        clearTimeout(timer);
        timer = setTimeout(() => { mensaje.value = ''; }, 2600);
    }
    return { mensaje, toast };
}
