<template>
  <button
    ref="boton"
    type="button"
    @click="llamar"
    :disabled="ocupada"
    :class="clase"
  >
    <span v-if="icono" class="material-symbols-outlined text-[18px]">{{ icono }}</span>
    <span>{{ etiqueta }}</span>
  </button>
</template>

<script setup>
/**
 * Una entrada visible a algo que todavía NO existe.
 *
 * ## Para qué sirve
 *
 * Compra información sobre demanda al precio de un botón, en vez de al precio
 * de una función. La beta dejó una lista de peticiones —app nativa, otros
 * relojes, vídeos, nutrición— y construirlas todas para descubrir cuál
 * importaba costaría meses.
 *
 * Con cinco atletas ninguna dará significancia estadística. Dará algo más útil
 * a esta escala: a quién le importó lo bastante como para tocarla.
 *
 * ## Las cuatro reglas de una puerta honesta
 *
 * Son cinco personas que conocemos por WhatsApp. Una puerta que se siente a
 * engaño cuesta más que cualquier función, así que este componente las impone
 * y no las deja como buena intención:
 *
 *  1. **Nunca cobra.** No hay tarjeta, ni pago, ni nada que lo parezca.
 *  2. **Dice la verdad al otro lado.** El diálogo empieza por "todavía no
 *     existe" — no una carga eterna ni un error falso.
 *  3. **No pide datos que no vamos a usar.** Ninguna variante pide correo:
 *     ya tenemos el suyo y ya hablamos con ellos.
 *  4. **Deja constancia de que se apuntó**, para poder volver a él.
 *
 * ## Por qué no se esconde después de tocarla
 *
 * Porque volver a llamar a la misma puerta ES la señal que se está comprando.
 * Un segundo toque de la misma persona dice más que el primero, y esconderla
 * borraría justo eso. Quien decide retirarla es el operador, mirando el panel
 * (regla: a los 30 días o a los 3 toques, lo que llegue antes).
 *
 * ## Las tres etapas que se miden
 *
 *  - **vista**: estuvo en pantalla (la mitad del botón, al menos). Una vez por
 *    sesión. Es el denominador: sin él, "3 toques" no se puede leer.
 *  - **toque**: abrió el diálogo. Curiosidad.
 *  - **confirmada**: dijo que sí, o eligió una opción. Intención.
 *
 * Quien toca y luego pulsa "Ahora no" queda como toque sin confirmación: leyó
 * lo que sería y no le interesó, que también es un dato.
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import swal, { textoDe } from '../../utils/swal';
import { useAppStore } from '../../stores/appStore';
import { tocarPuerta, verPuerta } from '../../services/experimentos';

const props = defineProps({
  /** Clave de `ExperimentController::PUERTAS`. Lista cerrada en el backend. */
  puerta: { type: String, required: true },
  etiqueta: { type: String, required: true },
  icono: { type: String, default: '' },
  /** Título del diálogo honesto. */
  titulo: { type: String, required: true },
  /** Qué sería si existiera, en una o dos frases. */
  texto: { type: String, required: true },
  /**
   * Opcional: convierte la puerta en pregunta. `[{ valor, etiqueta }]`.
   * La respuesta se anota junto al toque.
   */
  opciones: { type: Array, default: () => [] },
  clase: {
    type: String,
    default: 'flex items-center gap-2 px-4 py-2.5 glass-pill rounded-xl text-xs font-black uppercase tracking-wider text-[#c5c9ac] hover:text-[#caf300] transition-colors',
  },
});

const store = useAppStore();
const ocupada = ref(false);
const boton = ref(null);
let observador = null;

onMounted(() => {
  if (!boton.value || typeof IntersectionObserver === 'undefined') {
    verPuerta(props.puerta);
    return;
  }
  observador = new IntersectionObserver((entradas) => {
    if (entradas.some((e) => e.isIntersecting)) {
      verPuerta(props.puerta);
      observador?.disconnect();
      observador = null;
    }
  }, { threshold: 0.5 });
  observador.observe(boton.value);
});

onBeforeUnmount(() => observador?.disconnect());

const llamar = async () => {
  if (ocupada.value) return;
  ocupada.value = true;
  tocarPuerta(props.puerta, '', 'toque');
  try {
    const preguntando = props.opciones.length > 0;
    const r = await swal.fire({
      icon: 'info',
      title: props.titulo,
      html: props.texto,
      ...(preguntando
        ? {
          input: 'radio',
          inputOptions: Object.fromEntries(props.opciones.map((o) => [o.valor, o.etiqueta])),
          confirmButtonText: 'Enviar',
        }
        : { confirmButtonText: 'Sí, me interesa' }),
      showCancelButton: true,
      cancelButtonText: 'Ahora no',
    });
    if (!r.isConfirmed) return;

    const respuesta = preguntando ? textoDe(r) : '';
    // Si preguntaba y no eligió nada, no hay señal que anotar.
    if (preguntando && !respuesta) return;

    await tocarPuerta(props.puerta, respuesta);
    // Se confirma SIEMPRE, aunque la anotación falle: lo que el atleta ve no
    // puede depender de si nuestra medición funcionó.
    store.showToast('Anotado. Te avisamos en cuanto esté.');
  } finally {
    ocupada.value = false;
  }
};
</script>
