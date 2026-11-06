<template>
  <div class="space-y-7 pb-20 select-none animate-fade-in-up">
    <header class="pb-5 border-b border-white/10">
      <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Informes</h2>
      <p class="text-sm text-white/80 mt-1 font-medium">
        Tu semana y tu plan de temporada para guardar o imprimir, y el informe de tu coach.
        Tus números semana a semana están en
        <router-link to="/admin/analytics" class="text-[#caf300] font-bold underline underline-offset-2">Progreso</router-link>.
      </p>
    </header>

    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <button
        v-for="r in reports"
        :key="r.id"
        @click="open(r)"
        :disabled="!r.available || !store.planLoaded"
        class="glass-card rounded-3xl p-5 sm:p-6 flex flex-col items-stretch gap-4 text-left border border-white/10 shadow-2xl transition-all"
        :class="r.available && store.planLoaded ? 'cursor-pointer hover:border-[#caf300] hover:scale-[1.01]' : 'opacity-50 cursor-not-allowed'"
      >
        <div class="flex items-start gap-4 flex-1">
        <div class="w-12 h-12 rounded-2xl bg-[#caf300]/15 border border-[#caf300]/50 flex items-center justify-center text-[#caf300] shrink-0">
          <span class="material-symbols-outlined text-[26px]">{{ r.icon }}</span>
        </div>
        <div class="flex-1 min-w-0">
          <h3 class="text-base font-black text-white">{{ r.title }}</h3>
          <p class="text-[13px] text-white/75 mt-1 font-medium">
            <!-- Mientras carga el plan no se sabe si existe: decía "Genera una
                 semana" a quien sí la tenía (~4 s, auditoría del 2026-09-23). -->
            {{ !store.planLoaded ? 'Cargando…' : r.available ? r.desc : r.missing }}
          </p>
        </div>
        </div>
        <span v-if="r.available && store.planLoaded" class="w-full px-4 min-h-[44px] inline-flex items-center justify-center gap-1.5 rounded-xl border border-white/20 text-[13px] font-bold text-white/90">
          <span class="material-symbols-outlined text-[18px]">{{ r.id === 'analysis' ? 'menu_book' : 'print' }}</span>{{ r.id === 'analysis' ? 'Leer el informe' : 'Abrir para imprimir o guardar en PDF' }}
        </span>
      </button>
    </section>

    <!-- Print overlay -->
    <Teleport to="body">
      <div v-if="active" class="report-print-overlay fixed inset-0 z-[100] bg-[#0b0d10] overflow-y-auto">
        <div class="no-print sticky top-0 flex items-center justify-between gap-3 px-4 sm:px-8 py-3 bg-black/60 backdrop-blur-xl border-b border-white/10">
          <span class="text-sm font-black text-white">{{ active.title }}</span>
          <div class="flex items-center gap-2.5">
            <button
              @click="print"
              :disabled="loading || !!error"
              class="flex items-center gap-2 px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(202,243,0,0.3)] disabled:opacity-50"
            >
              <span class="material-symbols-outlined text-[18px]">print</span>
              <span>Guardar en PDF</span>
            </button>
            <button @click="close" class="p-2.5 text-[#c5c9ac] hover:text-white rounded-xl hover:bg-white/10">
              <span class="material-symbols-outlined text-2xl">close</span>
            </button>
          </div>
        </div>

        <div v-if="loading" class="p-16 text-center text-[#c5c9ac]">Cargando informe…</div>
        <div v-else-if="error" class="p-16 text-center text-[#ffb4ab] font-bold">{{ error }}</div>

        <!-- Los CUATRO informes se pintan igual: un documento HTML completo
             que renderiza el servidor. Ver el comentario de `reports`. -->
        <iframe
          v-else
          ref="frame"
          :srcdoc="htmlDoc"
          class="w-full border-0 bg-white"
          style="min-height: calc(100vh - 60px)"
        ></iframe>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
/**
 * Los informes, con las plantillas que ya existían.
 *
 * Esta vista rehacía a mano en Vue el plan semanal, el macrociclo y el informe
 * de salud, mientras las tres plantillas de verdad -- con sus tests, en
 * `services/ai/rendering/templates/` -- llevaban meses sin que nadie las
 * pidiera. Sólo el informe de análisis iba por el camino bueno, y ya lo hacía
 * dentro de un `<iframe>`: la tubería existía y faltaba la puerta del lado del
 * servicio (`POST /bridge/report`).
 *
 * Lo que se recupera al dejar de duplicarlas:
 *
 *   - El CALENDARIO de semanas del macrociclo, con las de descarga marcadas y
 *     la actual resaltada. Existía en `printable_season.html.jinja2` y esta
 *     vista sólo pintaba una tabla de rangos.
 *   - Las zonas y ritmos reales del atleta al pie de cada hoja, resueltos POR
 *     DEPORTE (una Z2 de natación y una de carrera se llevan 28 ppm en un
 *     atleta real).
 *   - Los objetivos declarados contra lo que predice su forma actual.
 *   - Y el fin de una familia entera de fallos: el desglose de fuerza que no
 *     se imprimía y el bloque de repeticiones contado dos veces eran los dos
 *     de este renderizador de aquí, no del de las plantillas.
 *
 * La regla que queda: **una sola implementación de cada informe, y vive en el
 * servidor.** Si falta algo en una hoja, se arregla la plantilla.
 */
import { ref, computed, nextTick } from 'vue';
import { useAppStore } from '../../stores/appStore';
import { getReportHtml } from '../../services/plan';

const store = useAppStore();

const active = ref(null);
const loading = ref(false);
const error = ref('');
const htmlDoc = ref('');
const frame = ref(null);

// Aqui habia un "Informe de salud" que leia `doctor_performance_report` y
// mandaba al atleta a "Perfil -> Salud -> Revisar mi ficha". Las dos cosas se
// retiraron en la v1.2 con la ficha clinica (migracion 017) y el chat del
// asistente de salud, y su ruta (`/api/reports/medical`) ya no existe: la
// tarjeta decia "complétalo" sobre algo que no se puede completar.
const reports = computed(() => [
  {
    id: 'plan', title: 'Tu semana', icon: 'calendar_month',
    desc: 'Una hoja con tu semana para tenerla a mano o pegarla en la refri.',
    missing: 'Genera una semana en el apartado Plan.',
    available: !!store.plan?.has_week,
  },
  {
    id: 'season', title: 'Tu plan hasta tu carrera', icon: 'timeline',
    desc: 'Las fases hasta tu carrera, tus metas y tus zonas y ritmos.',
    missing: 'Genera el plan de temporada en el apartado Plan.',
    available: !!store.plan?.has_season,
  },
  {
    id: 'analysis', title: 'El informe de tu coach', icon: 'analytics',
    desc: 'Tu entrenamiento reciente: lo que va bien y qué mejorar.',
    missing: 'Se crea al generar el plan de temporada.',
    available: !!store.plan?.has_analysis_html,
  },
]);

const open = async (r) => {
  if (!r.available) return;
  active.value = r;
  error.value = '';
  htmlDoc.value = '';
  loading.value = true;
  try {
    htmlDoc.value = await getReportHtml(r.id);
  } catch (e) {
    error.value = e?.response?.data?.message || 'No se pudo cargar el informe.';
  } finally {
    loading.value = false;
  }
};

const close = () => {
  active.value = null;
  htmlDoc.value = '';
  error.value = '';
};

/**
 * Imprime el documento del iframe, no la página que lo contiene.
 *
 * `window.print()` sobre la página exterior imprimiría la app entera con el
 * informe encajado dentro. El `focus()` antes del `print()` es necesario en
 * Chrome: sin él el diálogo sale con el documento equivocado.
 */
const print = () => {
  const w = frame.value?.contentWindow;
  if (!w) return nextTick(() => window.print());
  w.focus();
  w.print();
};
</script>
