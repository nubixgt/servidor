<template>
  <div class="max-w-4xl mx-auto space-y-6 animate-fade-in-up">

    <!-- ══════════ Paso 1: elegir entrenador ══════════ -->
    <template v-if="paso === 'coach'">
      <header class="text-center space-y-2">
        <p class="text-[11px] uppercase font-black text-[#8f9378] tracking-[0.2em]">Último paso</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Elige tu entrenador</h2>
        <p class="text-sm text-[#c5c9ac] max-w-xl mx-auto leading-relaxed">
          Los cuatro trabajan con los mismos datos y te darían el mismo plan. Lo que cambia es
          <span class="text-white font-bold">cómo te lo explican</span> cuando les preguntes.
        </p>
      </header>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <button
          v-for="c in coaches" :key="c.id"
          @click="elegir(c.id)"
          class="glass-panel border rounded-3xl p-4 text-center transition-all active:scale-95 group"
          :class="elegido === c.id
            ? 'border-[#caf300] shadow-[0_0_25px_rgba(202,243,0,0.3)] bg-[#caf300]/10'
            : 'border-white/15 hover:border-white/35'"
        >
          <div class="w-20 h-20 mx-auto rounded-2xl overflow-hidden bg-black/40 border border-white/10 flex items-center justify-center">
            <img v-if="c.src" :src="c.src" :alt="c.name" class="w-full h-full object-cover" />
            <span v-else class="text-3xl font-black text-[#caf300]">{{ c.name.charAt(0) }}</span>
          </div>
          <p class="text-sm font-black text-white mt-3 leading-none">{{ c.name }}</p>
          <p class="text-[10px] font-black text-[#caf300] uppercase tracking-wider mt-1.5">{{ c.toneLabel }}</p>
          <p class="text-[11px] text-[#c5c9ac] mt-2 leading-snug">{{ c.metodo }}</p>
        </button>
      </div>

      <div class="flex items-center justify-between gap-3 flex-wrap pt-1">
        <button @click="saltarCoach"
                class="px-4 py-2.5 glass-pill text-[#8f9378] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all">
          Decidir después
        </button>
        <button @click="paso = 'cuaderno'" :disabled="!elegido"
                class="flex items-center gap-2 px-6 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed">
          <span>Continuar</span>
          <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
      </div>
    </template>

    <!-- ══════════ Paso 2: el cuaderno ══════════
         El cuaderno existía y estaba en el menú, pero fuera del alta: el atleta
         lo veía como una tarjeta más y nadie lo estrenaba. Y es justo donde van
         las cosas que ningún formulario pregunta -- "los martes salgo en grupo",
         "los miércoles suelen ser mi día de calidad" -- que son las que hacen
         que el plan se parezca a su vida. Preguntarlo aquí, con ejemplos
         concretos, es la diferencia entre un cuaderno vacío y uno que sirve. -->
    <template v-else-if="paso === 'cuaderno'">
      <header class="text-center space-y-2">
        <p class="text-[11px] uppercase font-black text-[#8f9378] tracking-[0.2em]">Casi está · tu cuaderno</p>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">¿Qué debería saber de ti?</h2>
        <!-- Este paso ES el Cuaderno, y antes no lo decia: el atleta escribia
             una nota aqui y luego no reconocia la entrada "Cuaderno" del menu
             como el sitio donde habia ido a parar, ni sabia que se sigue
             llenando solo. Tres cosas que tiene que saber: que es, que lo lee
             el entrenador al planificar, y donde lo vuelve a encontrar. -->
        <p class="text-sm text-[#c5c9ac] max-w-xl mx-auto leading-relaxed">
          Esto es tu <span class="text-white font-bold">Cuaderno</span>: lo que tu entrenador sabe de ti y que
          ningún reloj mide — tu grupo de los martes, un viaje, lo que te pidió tu nutricionista. Lo lee cada vez
          que planifica tu semana.
        </p>
        <p class="text-xs text-[#8f9378] max-w-xl mx-auto leading-relaxed">
          Lo tienes siempre en el menú, en <span class="text-[#c5c9ac] font-bold">Cuaderno</span>: ahí
          <span class="text-[#c5c9ac] font-bold">escribes tú</span> lo que quieras cuando quieras, y también se va
          llenando cuando le cuentas algo al coach en el chat. Puedes revisarlo y retirar lo que ya no valga.
          <span class="text-white font-bold">Puedes saltártelo</span> ahora.
        </p>
      </header>

      <div class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-4">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="e in ejemplos" :key="e"
            @click="nota = e"
            class="px-3 py-1.5 glass-pill rounded-lg text-[11px] text-[#c5c9ac] hover:text-[#caf300] transition-colors text-left"
          >{{ e }}</button>
        </div>
        <textarea
          v-model="nota"
          rows="3"
          placeholder="Ej. los martes salgo en grupo en bici y siempre acabo fundido"
          class="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none resize-none"
        ></textarea>
        <p v-if="notaError" class="text-[11px] font-bold text-[#ffb4ab]">{{ notaError }}</p>
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <button @click="paso = 'resumen'"
                  class="px-4 py-2.5 glass-pill text-[#8f9378] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all">
            Saltar
          </button>
          <button @click="guardarNota" :disabled="!nota.trim() || guardandoNota"
                  class="flex items-center gap-2 px-6 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95 disabled:opacity-40">
            <span class="material-symbols-outlined text-[18px]">{{ guardandoNota ? 'sync' : 'check' }}</span>
            <span>{{ guardandoNota ? 'Guardando...' : 'Guardar y continuar' }}</span>
          </button>
        </div>
      </div>
    </template>

    <!-- ══════════ Paso 3: qué es cada cosa ══════════ -->
    <template v-else>
      <header class="text-center space-y-2">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-[#caf300]/15 border border-[#caf300]/40 flex items-center justify-center text-[#caf300] shadow-[0_0_20px_rgba(202,243,0,0.25)]">
          <span class="material-symbols-outlined text-[30px]">check_circle</span>
        </div>
        <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
          Ya está{{ nombre ? `, ${nombre}` : '' }}
        </h2>
        <p class="text-sm text-[#c5c9ac] max-w-xl mx-auto leading-relaxed">
          <template v-if="store.coach">
            <span class="text-white font-bold">{{ store.coach.name }}</span> ya es tu entrenador.
          </template>
          Esto es lo que tienes y para qué sirve cada cosa. No hace falta que lo uses todo hoy.
        </p>
      </header>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <component
          :is="t.ruta ? 'router-link' : 'div'"
          v-for="t in tarjetas" :key="t.titulo"
          :to="t.ruta"
          @click="t.accion && t.accion()"
          class="glass-panel border border-white/15 rounded-2xl p-4 flex gap-3.5 items-start transition-all"
          :class="(t.ruta || t.accion) ? 'hover:border-[#caf300]/50 cursor-pointer active:scale-[0.98]' : ''"
        >
          <div class="w-10 h-10 shrink-0 rounded-xl bg-[#caf300]/12 border border-[#caf300]/30 flex items-center justify-center text-[#caf300]">
            <span class="material-symbols-outlined text-[21px]">{{ t.icono }}</span>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-white leading-tight">{{ t.titulo }}</p>
            <p class="text-[12px] text-[#c5c9ac] mt-1 leading-snug">{{ t.texto }}</p>
          </div>
        </component>
      </div>

      <!-- Avanzado, ofrecido al terminar (el responsable, 2026-09-24). No entra en
           el alta a propósito -- Salud salió del alta en la v1.1 porque la última
           impresión del registro era un cuestionario clínico --, pero lo que hay
           ahí afina el plan, y sin decírselo nadie lo abría. Opcional y dicho así. -->
      <section class="glass-panel border border-white/15 rounded-3xl p-5 sm:p-6 space-y-3">
        <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2 flex-wrap">
          <span class="material-symbols-outlined text-[#caf300] text-[20px]">tune</span>
          <span>Opciones avanzadas</span>
          <span class="text-[10px] font-black text-[#121700] bg-[#caf300] rounded-md px-2 py-0.5 tracking-wider">Recomendado</span>
          <span class="text-[10px] font-black text-[#8f9378] border border-white/15 rounded-md px-2 py-0.5 tracking-wider">Opcional</span>
        </h3>
        <p class="text-[13px] text-[#c5c9ac] leading-relaxed">
          No hace falta para empezar, pero cada dato que pongas es algo que tu entrenador deja de suponer.
          Rellena <span class="text-white font-bold">lo que sepas</span> y deja en blanco lo demás:
        </p>
        <ul class="text-[12.5px] text-[#c5c9ac] leading-relaxed space-y-1.5">
          <li><span class="text-white font-bold">Fisiología y zonas:</span> tu ritmo de rodaje suave y el test de umbral, para que tus ritmos y zonas sean los tuyos.</li>
          <li><span class="text-white font-bold">Equipo y entorno:</span> altitud, clima, terreno y si tienes cinta.</li>
          <li><span class="text-white font-bold">Estilo de vida:</span> trabajo, sueño, estrés y viajes, que cambian cuánto puedes absorber.</li>
          <li><span class="text-white font-bold">Salud:</span> una lesión o una molestia, sólo si la tienes.</li>
        </ul>
        <div class="flex items-center gap-2.5 flex-wrap pt-1">
          <router-link to="/admin/perfil?tab=avanzado"
                       class="flex items-center gap-2 px-5 py-3 glass-pill border border-[#caf300]/40 text-[#caf300] hover:bg-[#caf300]/10 rounded-xl text-xs font-black uppercase tracking-wider transition-all active:scale-95">
            <span class="material-symbols-outlined text-[18px]">tune</span>
            <span>Completar opciones avanzadas</span>
          </router-link>
          <span class="text-[11px] text-[#8f9378]">Puedes hacerlo cuando quieras desde tu perfil.</span>
        </div>
      </section>

      <!-- El primer paso real, separado del resto: de todo lo de arriba, esto
           es lo único que hay que hacer HOY para que la app sirva de algo. -->
      <section class="glass-panel border border-[#caf300]/30 rounded-3xl p-5 sm:p-6 space-y-3">
        <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-2">
          <span class="material-symbols-outlined text-[#caf300] text-[20px]">flag</span>
          <span>Por dónde empezar</span>
        </h3>
        <p class="text-[13px] text-[#c5c9ac] leading-relaxed">
          Genera tu <span class="text-white font-bold">plan de temporada</span>: es el macrociclo del que
          cuelga todo lo demás. Tarda unos minutos porque lee tus últimos seis meses de Garmin. Después,
          la semana.
        </p>
        <div class="flex items-center gap-2.5 flex-wrap pt-1">
          <router-link to="/admin/plan"
                       class="flex items-center gap-2 px-5 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95">
            <span class="material-symbols-outlined text-[18px]">rocket_launch</span>
            <span>Ir a mi plan</span>
          </router-link>
          <!-- Decía "Antes, el asistente de salud": el chat del asistente se
               retiró de la UI en la v1.1 y la pestaña ya no lo tiene. Declarar
               la salud sigue siendo opcional, así que no se empuja antes del plan. -->
          <router-link to="/admin/perfil?tab=salud"
                       class="px-4 py-3 glass-pill text-[#c5c9ac] hover:text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all">
            Declarar una lesión (opcional)
          </router-link>
        </div>
      </section>

      <p class="text-center text-[11px] text-[#8f9378] leading-relaxed px-4">
        Esto es una <span class="text-[#c5c9ac] font-bold">beta</span>. Si algo no cuadra o no encuentras
        cómo hacer algo, el botón <span class="text-[#c5c9ac] font-bold">Ayuda</span> de abajo a la
        derecha manda el mensaje directo a quien la desarrolla.
      </p>

      <div class="text-center pt-1">
        <router-link to="/admin/dashboard"
                     class="text-xs font-black uppercase tracking-wider text-[#8f9378] hover:text-white transition-colors">
          Saltar al panel
        </router-link>
      </div>
    </template>
  </div>
</template>

<script setup>
/**
 * La pantalla final del alta: elegir entrenador y saber qué es cada cosa.
 *
 * ## Por qué el entrenador se elige AQUÍ
 *
 * Estaba sólo en Configuración, y el atleta que nunca abría ese menú se
 * quedaba sin entrenador: el saludo del chat decía "Soy tu entrenador" en vez
 * de "Soy Ben, tu coach, mi método es…", y los cuatro personajes eran
 * invisibles. No hay entrenador por defecto a propósito -- inventarle uno sería
 * elegir por él -- así que la solución no era un default sino preguntárselo, y
 * el momento en que tiene sentido preguntárselo es justo antes de conocerlo.
 *
 * No se añadió como novena pestaña del perfil: el entrenador vive en
 * Configuración, y tenerlo en dos sitios sería tener dos sitios donde cambia
 * lo mismo. Aquí sólo se elige la primera vez; después se cambia donde
 * siempre.
 *
 * ## Por qué el recorrido es esta pantalla y no un tour
 *
 * Un tour por encima de la app se olvida antes de llegar a usarla, y hay que
 * mantenerlo cada vez que se mueve un botón. Esto dice qué es cada cosa una
 * vez, en una pantalla, y deja el enlace para ir. Lo que de verdad enseña son
 * las pistas en contexto la primera vez que se abre cada sección -- eso es
 * otra tarea, y es la buena.
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useAppStore } from '../../stores/appStore';
import { COACHES } from '../../utils/appearance';

const store = useAppStore();
const router = useRouter();
const coaches = COACHES;

const paso = ref('coach');
watch(paso, (p) => { store.altaEnCurso = p !== 'resumen'; }, { immediate: true });
onUnmounted(() => { store.altaEnCurso = false; });
const elegido = ref(null);

const nombre = computed(
  () => (store.authUser?.name || store.athlete?.name || '').split(' ')[0],
);

// Si ya tenía entrenador elegido (volvió a entrar aquí, o lo eligió en
// Configuración antes de terminar el perfil), no se le vuelve a preguntar.
onMounted(() => {
  if (store.settings?.coachId) {
    elegido.value = store.settings.coachId;
    paso.value = 'cuaderno';
  }
});

/**
 * Ejemplos CONCRETOS, no categorias.
 *
 * "Anota lo que tu entrenador deba recordar" no produce nada: es demasiado
 * abierto. Una frase escrita que el atleta puede tocar y editar produce una
 * nota de verdad, y ademas le enseña el nivel de detalle que sirve.
 */
const ejemplos = [
  'Los martes salgo en grupo en bici y siempre acabo fundido',
  'Los miércoles suelen ser mi día de calidad',
  'No puedo nadar por la mañana, la piscina abre tarde',
  'Viajo una semana de cada mes y sólo llevo zapatillas',
  'Odio el rodillo: si puedo, salgo fuera aunque llueva',
];

const nota = ref('');
const guardandoNota = ref(false);
const notaError = ref('');

const guardarNota = async () => {
  const texto = nota.value.trim();
  if (!texto || guardandoNota.value) return;
  guardandoNota.value = true;
  notaError.value = '';
  try {
    await store.addNote({ text: texto, category: 'preferencia', status: 'vigente' });
    paso.value = 'resumen';
  } catch (e) {
    // Que falle NO puede dejarle atrapado en el alta: la nota es opcional y
    // el resto de la app le espera detras.
    notaError.value = 'No se pudo guardar la nota. Puedes añadirla luego desde Cuaderno.';
  } finally {
    guardandoNota.value = false;
  }
};

const elegir = (id) => {
  elegido.value = id;
  store.updateSettings({ coachId: id });
};

const saltarCoach = () => { paso.value = 'cuaderno'; };

// Las secciones de la app, cada una con lo que hace y NO con cómo se llama por
// dentro. El orden es el de utilidad para alguien que acaba de entrar.
const tarjetas = computed(() => [
  {
    icono: 'calendar_month', titulo: 'Plan', ruta: '/admin/plan',
    texto: 'Tu macrociclo y la semana. Aquí los generas y los subes al reloj como entrenos de verdad.',
  },
  {
    icono: 'smart_toy', titulo: `Coach${store.coach ? ` · ${store.coach.name}` : ''}`, ruta: null,
    accion: () => { store.isAiCoachOpen = true; },
    texto: 'El botón verde de abajo a la derecha. Pregúntale por qué tu semana es así: tiene tus zonas, tu plan y lo que has entrenado.',
  },
  {
    icono: 'menu_book', titulo: 'Cuaderno', ruta: '/admin/cuaderno',
    texto: 'Lo que le cuentas y debe recordar entre semanas: que los martes entras a las 5, que odias la piscina, una molestia vieja.',
  },
  {
    icono: 'fact_check', titulo: 'Check-in', ruta: null,
    texto: 'Antes de generar la semana te pregunta cómo dormiste, cómo vas de fatiga y si te molesta algo. Es lo único que ningún sensor ve.',
  },
  {
    icono: 'favorite', titulo: 'Salud', ruta: '/admin/perfil?tab=salud',
    texto: 'Opcional: una lesión o condición que tu entrenador deba tener en cuenta. Si no declaras nada, el plan no supone ninguna.',
  },
  {
    icono: 'insights', titulo: 'Progreso', ruta: '/admin/analytics',
    texto: 'Lo que has entrenado, los entrenos clave que hiciste, tu forma (VO2max) y cómo vas hacia tu meta.',
  },
]);
</script>
