<template>
  <div class="space-y-5">
    <!-- Dónde está el atleta HOY (los datos son los suyos, del servidor: AccessService::estado). Lo
         que regala la app va primero (reciprocidad) y la fecha real en la que termina (escasez de
         verdad, sin cuentas atrás inventadas). -->
    <div v-if="acceso" class="rounded-2xl p-4 border flex gap-3 items-start" :class="banda.clase">
      <span class="material-symbols-outlined text-[22px] shrink-0" :class="banda.icono_clase">{{ banda.icono }}</span>
      <p class="text-sm text-[#f1f0ee] leading-relaxed">{{ banda.texto }}</p>
    </div>
    <p v-else-if="cargando" class="text-xs text-[#8f9378]">Cargando tus semanas…</p>

    <!-- Lo que el atleta se propuso (compromiso y coherencia): su carrera, con su fecha. -->
    <div v-if="carrera" class="flex items-center gap-3 text-sm">
      <span class="material-symbols-outlined text-[#caf300] text-[20px]">flag</span>
      <p class="text-[#c5c9ac] leading-snug">
        Tu <b class="text-white">{{ carrera.name }}</b> es el {{ fechaLarga(carrera.date) }}:
        faltan <b class="text-white tabular-nums">{{ semanasHasta(carrera.date) }}</b> semanas
        <template v-if="mesesHastaLaCarrera < 10">(unos {{ mesesHastaLaCarrera }} meses: Q{{ mesesHastaLaCarrera * precio.gtq }} en mensual).
          El anual compensa si sigues entrenando después.</template>
        <template v-else-if="diasHasta(carrera.date) <= 365">: el plan anual te cubre hasta ella.</template>
      </p>
    </div>

    <!-- Los dos planes. El anual va primero (contraste: su precio junto a lo que costarían doce meses sueltos) y
         viene elegido sólo si le compensa para su carrera. Ninguna etiqueta de «el más elegido»: no tenemos ese dato. -->
    <div role="radiogroup" aria-label="Elige tu plan" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <button
        v-for="p in planes"
        :key="p.id"
        type="button"
        role="radio"
        :aria-checked="elegido === p.id"
        @click="elegido = p.id"
        class="relative text-left rounded-2xl p-4 border transition-all active:scale-[0.98] focus-visible:outline focus-visible:outline-2 focus-visible:outline-[#caf300]"
        :class="elegido === p.id
          ? 'border-[#caf300] bg-[#caf300]/10 shadow-[0_0_18px_rgba(202,243,0,0.18)]'
          : 'border-white/15 bg-white/5 hover:border-white/35'"
      >
        <span
          v-if="p.etiqueta"
          class="absolute -top-2.5 right-3 px-2 py-0.5 rounded-full bg-[#caf300] text-[#121700] text-[10px] font-black uppercase tracking-wider"
        >{{ p.etiqueta }}</span>
        <span class="flex items-center justify-between">
          <span class="text-[10px] uppercase font-black tracking-wider text-[#8f9378]">{{ p.nombre }}</span>
          <span
            class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
            :class="elegido === p.id ? 'border-[#caf300]' : 'border-white/30'"
          ><span v-if="elegido === p.id" class="w-2 h-2 rounded-full bg-[#caf300]"></span></span>
        </span>
        <span class="mt-2 flex items-baseline gap-1.5">
          <span class="text-3xl font-black text-white tabular-nums">Q{{ p.gtq }}</span>
          <span class="text-xs text-[#c5c9ac]">{{ p.por }}</span>
          <s v-if="p.tachado" class="text-xs text-[#8f9378] tabular-nums ml-1">Q{{ p.tachado }}</s>
        </span>
        <span class="block text-[12px] text-white/60 tabular-nums">{{ p.usd }} USD</span>
        <span class="block mt-2 text-xs leading-snug" :class="p.id === 'anual' ? 'text-[#caf300] font-bold' : 'text-[#c5c9ac]'">
          {{ p.detalle }}
        </span>
      </button>
    </div>

    <button
      type="button"
      @click="quieroEste"
      class="w-full px-4 py-3 rounded-xl bg-[#caf300] hover:bg-[#bbf400] text-[#121700] text-sm font-black uppercase tracking-wider transition-all active:scale-95"
    >
      {{ acceso?.estado === 'pagado' && !acceso?.cortesia ? 'Renovar con el plan' : 'Pedir el plan' }} {{ elegido === 'anual' ? `anual · Q${anual.gtq}` : `mensual · Q${precio.gtq}` }}
    </button>
    <!-- Lo que pasa al pulsar, junto al botón: abre «Ayuda» y el pago es fuera de la app. -->
    <p class="text-[12px] text-white/70 text-center -mt-2">Se abre «Ayuda» con tu mensaje escrito; te contestamos cómo pagar
      (por ejemplo, una transferencia) y lo activamos.</p>

    <!-- Sin riesgo: lo que de verdad pasa si no renueva (Legal, punto 8). -->
    <p class="text-[11px] text-[#c5c9ac] leading-relaxed flex gap-2">
      <span class="material-symbols-outlined text-[16px] text-[#a5eeff] shrink-0">verified_user</span>
      <span>
        Sin permanencia ni cobro automático. Si no renuevas no pierdes nada: tu plan, tu reloj, tu progreso
        y el chat siguen; sólo se pausa generar semanas nuevas.
      </span>
    </p>

    <!-- Qué paga (autoridad: lo que la app hace de verdad y de dónde sale). -->
    <div class="space-y-2.5 border-t border-white/10 pt-4">
      <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Qué incluye</span>
      <ul class="space-y-2">
        <li v-for="i in incluye" :key="i.texto" class="flex gap-2.5 text-xs text-[#f1f0ee] leading-snug">
          <span class="material-symbols-outlined text-[18px] text-[#caf300] shrink-0">{{ i.icono }}</span>
          <span>{{ i.texto }}</span>
        </li>
      </ul>
    </div>

    <!-- Su entrenador lo dice (simpatía: el coach que eligió, con su cara). -->
    <div v-if="store.coach" class="flex items-center gap-3 rounded-2xl bg-white/5 border border-white/10 p-3">
      <img v-if="store.coach.src" :src="store.coach.src" :alt="store.coach.name"
           class="w-11 h-11 rounded-xl object-cover object-top shrink-0" />
      <p class="text-xs text-[#c5c9ac] leading-snug">
        <b class="text-white">{{ store.coach.name }}:</b>
        «Yo me encargo de que cada semana tenga sentido para ti; tú, de entrenarla.»
      </p>
    </div>

    <!-- Cómo se paga: el orden importa, por eso va numerado. -->
    <div class="space-y-2.5 border-t border-white/10 pt-4">
      <span class="text-[10px] uppercase font-black text-[#8f9378] tracking-wider">Cómo se paga</span>
      <ol class="space-y-2">
        <li v-for="(paso, n) in pasos" :key="paso" class="flex gap-2.5 text-xs text-[#c5c9ac] leading-snug">
          <span class="w-5 h-5 rounded-full bg-white/10 text-white text-[10px] font-black flex items-center justify-center shrink-0">{{ n + 1 }}</span>
          <span>{{ paso }}</span>
        </li>
      </ol>
    </div>

    <!-- Antes de pagar, ganar semanas invitando (reciprocidad hacia otro atleta). -->
    <div class="rounded-2xl border border-[#a5eeff]/30 bg-[#a5eeff]/5 p-4 flex flex-wrap items-center gap-3">
      <p class="flex-1 min-w-[200px] text-xs text-[#f1f0ee] leading-snug">
        ¿Todavía no? Invita a un atleta: si conecta un Garmin nuevo, los dos ganan semanas gratis.
      </p>
      <button type="button" @click="invitar"
              class="px-3 py-2 rounded-xl border border-[#a5eeff]/50 text-[#a5eeff] text-[11px] font-black uppercase tracking-wider hover:bg-[#a5eeff]/10">
        Invitar a un atleta
      </button>
    </div>

    <p class="text-[10px] text-[#8f9378] text-center">Hecho en Guatemala · precios en quetzales</p>
  </div>
</template>

<script setup>
/**
 * «Precios» (decisión del responsable, 2026-10-21): Q60 (8 USD) al mes o Q600 (80 USD) al año, con el
 * ahorro, ofrecidos con los principios de Cialdini SIN inventar nada: reciprocidad (lo que ya se le
 * regaló), escasez real (la fecha en la que terminan sus semanas), compromiso (su carrera), contraste
 * (el anual junto a doce meses sueltos), autoridad (lo que la app hace y de dónde sale), simpatía (su
 * entrenador) y unidad (Guatemala, quetzales). La prueba social queda fuera hasta tener datos o
 * testimonios reales: un «el más elegido» sin medirlo sería mentir.
 *
 * Todos los números salen del servidor (`AccessService::precio()`); aquí sólo se dividen.
 */
import { computed, onMounted, ref } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { getAcceso } from '../../services/plan';

const store = useAppStore();
const acceso = ref(null);
const cargando = ref(true);
const elegidoPorElAtleta = ref('');

// Mientras carga, los del Legal: la pantalla nunca dice un precio distinto del servidor por más de
// un instante, y una prueba fija que estos valen lo mismo que las constantes de PHP.
const PRECIO_DE_RESPALDO = {
  gtq: 60, usd: 8, texto: 'Q60 (8 USD) al mes',
  anual: { gtq: 600, usd: 80, texto: 'Q600 (80 USD) al año', sin_descuento_gtq: 720, ahorro_gtq: 120, ahorro_usd: 16, meses_gratis: 2 },
};
const precio = computed(() => acceso.value?.precio || PRECIO_DE_RESPALDO);
const anual = computed(() => precio.value.anual || PRECIO_DE_RESPALDO.anual);

const coma = (n) => n.toLocaleString('es', { maximumFractionDigits: 2 });

const planes = computed(() => [
  {
    id: 'anual', nombre: 'Anual', gtq: anual.value.gtq, usd: anual.value.usd, por: '/ año',
    tachado: anual.value.sin_descuento_gtq,
    etiqueta: `Ahorras Q${anual.value.ahorro_gtq}`,
    detalle: `Sale a Q${coma(anual.value.gtq / 12)} al mes: te ahorras Q${anual.value.ahorro_gtq}, ${anual.value.meses_gratis} meses gratis.`,
  },
  {
    id: 'mensual', nombre: 'Mensual', gtq: precio.value.gtq, usd: precio.value.usd, por: '/ mes',
    detalle: `Unos Q${coma(Math.round((precio.value.gtq / 30) * 100) / 100)} al día. Pagas mes a mes.`,
  },
]);

const fechaLarga = (iso) => {
  const f = new Date(`${iso}T12:00:00`);
  // Con el año si no es el de hoy: un plan anual termina el año que viene.
  const anio = f.getFullYear() !== new Date().getFullYear() ? ` de ${f.getFullYear()}` : '';
  return `${f.toLocaleDateString('es', { weekday: 'long' })} ${f.toLocaleDateString('es', { day: 'numeric', month: 'long' })}${anio}`;
};
const hoyISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};
const diasHasta = (iso) => Math.max(0, Math.round((new Date(`${iso}T12:00:00`) - new Date(`${hoyISO()}T12:00:00`)) / 86400000));
const semanasHasta = (iso) => Math.max(1, Math.ceil(diasHasta(iso) / 7));

// Meses hasta su carrera (redondeado arriba): si son menos de 10, el mensual le sale más barato hasta ella.
const mesesHastaLaCarrera = computed(() => (carrera.value ? Math.max(1, Math.ceil(diasHasta(carrera.value.date) / 30.4)) : 12));
// Viene elegido el que le compensa; el atleta puede cambiarlo.
const elegido = computed({
  get: () => elegidoPorElAtleta.value || (mesesHastaLaCarrera.value < 10 ? 'mensual' : 'anual'),
  set: (v) => { elegidoPorElAtleta.value = v; },
});

// La carrera A más cercana; si no tiene A, la siguiente que venga.
const carrera = computed(() => {
  const hoy = hoyISO();
  const lista = (store.profile?.competitions || [])
    .filter((c) => c.date && c.date >= hoy && c.name)
    .sort((a, b) => a.date.localeCompare(b.date));
  return lista.find((c) => c.priority === 'A') || lista[0] || null;
});

const banda = computed(() => {
  const a = acceso.value;
  const dias = a?.dias_restantes === 1 ? 'te queda 1 día' : `te quedan ${a?.dias_restantes} días`;
  const tipos = {
    prueba: {
      icono: 'redeem', clase: 'border-[#caf300]/35 bg-[#caf300]/5', icono_clase: 'text-[#caf300]',
      texto: `Tus semanas gratis van por nuestra cuenta: ${dias} (hasta el ${a?.acceso_hasta ? fechaLarga(a.acceso_hasta) : ''}). Si eliges un plan ahora, empieza cuando terminen: no pierdes ni un día.`,
    },
    vencido: {
      icono: 'lock_clock', clase: 'border-[#ffd166]/45 bg-[#ffd166]/5', icono_clase: 'text-[#ffd166]',
      texto: `Tu acceso terminó el ${a?.acceso_hasta ? fechaLarga(a.acceso_hasta) : ''}. Tu plan, tu reloj y tu progreso siguen aquí; para generar tu próxima semana, elige un plan.`,
    },
    pagado: {
      icono: 'check_circle', clase: 'border-[#caf300]/35 bg-[#caf300]/5', icono_clase: 'text-[#caf300]',
      // Una cortesía (sin cobro, p. ej. la de los atletas de la beta) no es una suscripción que se renueva.
      texto: a?.cortesia
        ? `Tienes acceso sin costo hasta el ${a?.acceso_hasta ? fechaLarga(a.acceso_hasta) : ''}, por ser parte de la beta. No tienes que pagar nada hasta entonces.`
        : `Tu suscripción va hasta el ${a?.acceso_hasta ? fechaLarga(a.acceso_hasta) : ''}. Si renuevas antes, lo nuevo se suma al final.`,
    },
    garmin_usado: {
      icono: 'watch', clase: 'border-[#ffd166]/45 bg-[#ffd166]/5', icono_clase: 'text-[#ffd166]',
      texto: 'No tienes semanas gratis activas. Si conectaste tu Garmin antes de que hubiera semanas gratis, pulsa «Volver a leer mi Garmin» en tu perfil y se activan; si esa cuenta ya las usó, para generar tu próxima semana elige un plan.',
    },
    sin_empezar: {
      icono: 'watch', clase: 'border-[#a5eeff]/35 bg-[#a5eeff]/5', icono_clase: 'text-[#a5eeff]',
      texto: 'Conecta tu Garmin en tu perfil y empiezas con semanas gratis, sin pagar nada. Después eliges plan.',
    },
    admin: {
      icono: 'admin_panel_settings', clase: 'border-white/15 bg-white/5', icono_clase: 'text-[#8f9378]',
      texto: 'Eres administrador: no tienes muro. Así ve esta pestaña un atleta (sin la línea de sus semanas).',
    },
  };
  return tipos[a?.estado] || { icono: 'info', clase: 'border-white/15 bg-white/5', icono_clase: 'text-[#8f9378]', texto: 'Así funcionan los planes de Power Coach AI.' };
});

const incluye = computed(() => [
  { icono: 'monitoring', texto: 'Tu semana escrita con tus datos reales de Garmin: carga, pulso, ritmo y recuperación.' },
  { icono: 'watch', texto: 'Directo a tu reloj, paso a paso, con tus zonas y tus ritmos.' },
  { icono: 'edit_calendar', texto: 'Mueve o ajusta una sesión cuando tu semana cambie, y tu reloj se actualiza.' },
  { icono: 'forum', texto: `Chat con ${store.coach?.name || 'tu entrenador'}, que conoce tu plan y tu cuaderno.` },
  { icono: 'science', texto: 'Métodos con respaldo, como el test de umbral de Joe Friel, y cada decisión con su porqué.' },
]);

const pasos = [
  'Elige tu plan y pulsa el botón: se abre «Ayuda» con el mensaje escrito.',
  'Te contestamos por ahí cómo pagar (por ejemplo, una transferencia).',
  'Lo activamos en tu cuenta y te llega un aviso con la fecha hasta la que puedes generar.',
];

const quieroEste = () => {
  const texto = elegido.value === 'anual' ? `el plan anual: ${anual.value.texto}` : `el plan mensual: ${precio.value.texto}`;
  store.isSettingsOpen = false;
  store.abrirAyuda({ categoria: 'otra', mensaje: `Quiero ${texto}. ¿Cómo pago?` });
};

const invitar = () => {
  store.isSettingsOpen = false;
  store.invitarAbierto = true;
};

onMounted(async () => {
  try { acceso.value = await getAcceso(); } catch (e) { acceso.value = null; } finally { cargando.value = false; }
});
</script>
