<template>
  <!-- Cubre la app entera a propósito. No es un aviso: es la condición para
       usarla, y dejar ver el plan por detrás invita a ignorarlo. -->
  <div
    v-if="hayQueAceptar"
    class="fixed inset-0 z-[100] bg-[#07080a] flex flex-col"
    role="dialog"
    aria-modal="true"
    aria-labelledby="legal-titulo"
  >
    <header class="shrink-0 px-5 sm:px-8 pt-7 pb-5 border-b border-white/10 max-w-3xl w-full mx-auto">
      <p class="text-[11px] uppercase font-black text-[#caf300] tracking-[0.2em]">Antes de entrar</p>
      <h2 id="legal-titulo" class="text-2xl sm:text-3xl font-black text-white tracking-tight mt-1">
        Condiciones de uso y privacidad
      </h2>
      <p v-if="yaAceptoOtra" class="text-sm text-[#c5c9ac] mt-2 leading-relaxed">
        Han cambiado desde la última vez que las aceptaste. Léelas otra vez, por favor.
      </p>
    </header>

    <div class="flex-1 overflow-y-auto px-5 sm:px-8 py-6">
      <div v-if="cargando" class="max-w-3xl mx-auto text-sm text-[#8f9378]">Cargando…</div>
      <p v-else-if="error" class="max-w-3xl mx-auto text-sm text-[#ffb4ab] leading-relaxed">
        {{ error }}
      </p>
      <div
        v-else
        class="max-w-3xl mx-auto text-sm text-[#e5e2e1] leading-relaxed coach-md"
        v-html="renderDocumento(doc.markdown)"
      ></div>
    </div>

    <footer class="shrink-0 border-t border-white/10 px-5 sm:px-8 py-5">
      <div class="max-w-3xl mx-auto flex flex-col sm:flex-row sm:items-center gap-4">
        <!-- Una casilla y no sólo una frase: la edad mínima y el aviso de salud
             son las dos cosas que no pueden quedar en "lo pulsé sin leer". -->
        <label class="flex items-start gap-3 flex-1 cursor-pointer">
          <input
            type="checkbox"
            v-model="confirma"
            class="w-4 h-4 mt-0.5 accent-[#caf300] shrink-0"
          />
          <span class="text-[12px] text-[#c5c9ac] leading-relaxed">
            Tengo <span class="text-white font-bold">18 años o más</span>, he leído el
            <span class="text-white font-bold">aviso de salud y riesgo</span>, entiendo que esto no es un servicio
            médico y que entreno bajo mi propia responsabilidad.
          </span>
        </label>
        <div class="flex items-center gap-2.5 shrink-0">
          <button
            type="button"
            @click="salir"
            class="px-4 py-3 glass-pill text-[#8f9378] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all"
          >
            Salir
          </button>
          <button
            type="button"
            @click="aceptar"
            :disabled="cargando || guardando || !!error || !confirma"
            class="flex items-center gap-2 px-6 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95 disabled:opacity-40"
          >
            <span class="material-symbols-outlined text-[18px]">{{ guardando ? 'sync' : 'check' }}</span>
            <span>{{ guardando ? 'Guardando…' : 'Acepto' }}</span>
          </button>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
/**
 * La puerta legal. Se cierra sobre la app hasta que el atleta acepta.
 *
 * ## Por qué bloquea de verdad
 *
 * Porque la app guarda datos de salud —lesiones con escala de dolor,
 * medicación, condiciones crónicas, ciclo menstrual— y los manda a modelos de
 * terceros para escribir el plan. Eso hay que decirlo ANTES, no en una pestaña
 * que nadie abre. Hasta la v1.1 lo único parecido era un descargo médico
 * opcional en la octava pestaña del perfil, que ninguna ruta comprobaba.
 *
 * ## Por qué compara VERSIONES y no un booleano
 *
 * `termsAcceptedAt` sólo dice cuándo. Cuando el texto cambie hay que poder
 * volver a pedirlo **sólo a quien vio una versión anterior**, y no volver a
 * molestar a quien ya aceptó la vigente. Las dos viajan en `/auth/me`
 * (`termsVersion` y `termsVersionRequired`), que el frontend ya pide al
 * arrancar.
 *
 * ## Qué pasa si el backend todavía no tiene la columna
 *
 * `termsVersionRequired` llega vacío y la puerta NO se enseña. Es deliberado:
 * un frontend por delante de la migración 015 no puede dejar a nadie fuera de
 * su propia app. Ver Database/015.
 */
import { ref, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import { renderDocumento } from '../../utils/markdown';
import { getLegal, acceptLegal } from '../../services/legal';

const store = useAppStore();
const router = useRouter();

const doc = ref({ version: '', markdown: '' });
const cargando = ref(false);
const guardando = ref(false);
const error = ref('');
const aceptadoAhora = ref(false);
const confirma = ref(false);

const requerida = computed(() => store.authUser?.termsVersionRequired || '');
const aceptada = computed(() => store.authUser?.termsVersion || '');
const yaAceptoOtra = computed(() => !!aceptada.value && aceptada.value !== requerida.value);

const hayQueAceptar = computed(() => {
  if (!store.token || !requerida.value) return false;   // sin migración: no bloquea
  if (aceptadoAhora.value) return false;
  return aceptada.value !== requerida.value;
});

const cargar = async () => {
  if (cargando.value || doc.value.markdown) return;
  cargando.value = true;
  error.value = '';
  try {
    doc.value = await getLegal();
  } catch (e) {
    error.value = 'No se pudieron cargar las condiciones. Recarga la página.';
  } finally {
    cargando.value = false;
  }
};

watch(hayQueAceptar, (hay) => { if (hay) cargar(); }, { immediate: true });

const aceptar = async () => {
  if (guardando.value || !confirma.value) return;
  guardando.value = true;
  try {
    await acceptLegal(doc.value.version);
    // Se refleja en el usuario que ya está en memoria para que la puerta se
    // cierre sin recargar. `aceptadoAhora` cubre el caso de que `authUser` sea
    // de sólo lectura en algún camino.
    if (store.authUser) {
      store.authUser.termsVersion = doc.value.version;
      store.authUser.termsAcceptedAt = new Date().toISOString();
    }
    aceptadoAhora.value = true;
  } catch (e) {
    // 409 = el texto cambió mientras lo leía. Recargar es la salida honesta:
    // sellar la versión vieja dejaría constancia de una aceptación falsa.
    error.value = e?.response?.status === 409
      ? 'Las condiciones cambiaron mientras las leías. Recarga la página.'
      : 'No se pudo guardar tu aceptación. Reinténtalo.';
  } finally {
    guardando.value = false;
  }
};

// `logout()` limpia la sesion pero no navega: el guardian del router solo actua
// en el siguiente cambio de ruta, y aqui no hay ninguno. Sin el `replace`, el
// atleta se quedaria mirando la misma pantalla con la sesion ya cerrada.
const salir = () => {
  store.logout();
  router.replace('/login');
};
</script>
