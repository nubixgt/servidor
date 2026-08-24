<template>
  <div v-if="cargando" class="vidrio">
    <p style="font-size:.85rem;color:var(--texto-suave);">Cargando curso...</p>
  </div>

  <div v-else-if="error" class="vidrio">
    <p style="font-size:.85rem;color:var(--rojo);margin-bottom:12px;">{{ error }}</p>
    <button class="btn btn-verde" @click="cargarCurso">Reintentar</button>
  </div>

  <div v-else-if="curso">
    <!-- Volver -->
    <button class="volver" @click="router.push('/catalogo')">‹ Catálogo de cursos</button>

    <!-- Cabecera del curso -->
    <div class="vidrio" style="margin-bottom:16px;">
      <div class="cab-detalle">
        <div class="icono-grande">{{ curso.icono }}</div>
        <div style="flex:1;min-width:200px;">
          <small class="etiqueta">{{ curso.lecciones.length }} lecciones · Evaluación final</small>
          <h2>{{ curso.titulo }}</h2>
          <p>{{ curso.descripcion }}</p>
        </div>
        <div style="text-align:center;min-width:90px;">
          <b style="font-size:1.6rem;color:var(--verde);">{{ pct }}%</b>
          <small style="display:block;font-size:.66rem;color:var(--texto-suave);text-transform:uppercase;">Avance</small>
        </div>
      </div>
      <div class="pista" style="height:9px;">
        <div class="pista-fill" :style="{ width: pct + '%' }"></div>
      </div>
    </div>

    <!-- Lecciones -->
    <div
      v-for="(l, i) in curso.lecciones"
      :key="l.id"
      class="leccion"
      :class="{ hecha: prog.leccionesCompletadas.has(l.id), abierta: leccionesAbiertas.includes(i) }"
    >
      <button @click="toggleLeccion(i)">
        <span class="check">{{ prog.leccionesCompletadas.has(l.id) ? '✓' : '' }}</span>
        <span class="tit">{{ i + 1 }}. {{ l.titulo }}</span>
        <span class="flecha">›</span>
      </button>
      <div class="cuerpo-lec" v-if="leccionesAbiertas.includes(i)">
        <video v-if="l.video_url" :src="l.video_url" controls class="video-leccion"></video>
        <p>{{ l.contenido }}</p>
        <p v-if="prog.leccionesCompletadas.has(l.id)" style="color:var(--verde);font-weight:700;font-size:.8rem;">
          ✓ Lección completada
        </p>
        <button v-else class="btn btn-verde" :disabled="marcandoLeccion === l.id" @click="marcarLeccion(l)">
          {{ marcandoLeccion === l.id ? 'Guardando...' : 'Marcar como completada' }}
        </button>
      </div>
    </div>

    <!-- Evaluación -->
    <div class="vidrio" style="margin-top:18px;">
      <h3 style="margin-bottom:6px;">📝 Evaluación del curso</h3>
      <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:13px;">
        <template v-if="prog.aprobado">
          Evaluación aprobada con nota {{ prog.nota }}/{{ curso.quiz.length }} el {{ fechaAprobadoLegible }}.
        </template>
        <template v-else-if="todasLeccionesHechas">
          Responde {{ curso.quiz.length }} preguntas. Necesitas al menos 60% de aciertos para aprobar y obtener tu certificado.
        </template>
        <template v-else>Completa todas las lecciones para habilitar la evaluación.</template>
      </p>

      <!-- Estado: aprobado -->
      <div v-if="prog.aprobado && !quizActivo">
        <button class="btn btn-verde" @click="mostrarCertificado">🎓 Ver mi certificado</button>
        <button class="btn" style="margin-left:8px;" @click="iniciarQuiz">Repetir evaluación</button>
      </div>

      <!-- Botón iniciar -->
      <div v-else-if="!quizActivo">
        <button class="btn btn-verde" :class="{ bloq: !todasLeccionesHechas }" @click="iniciarQuiz">
          Iniciar evaluación
        </button>
      </div>

      <!-- Quiz activo -->
      <div v-if="quizActivo">
        <div v-for="(q, qi) in curso.quiz" :key="qi" class="pregunta">
          <h4>{{ qi + 1 }}. {{ q.pregunta }}</h4>
          <button
            v-for="(o, oi) in q.opciones"
            :key="oi"
            class="opcion"
            :class="opcionClase(qi, oi)"
            @click="elegir(qi, oi)"
          >
            <span class="letra">{{ letraOpcion(oi) }}.</span>
            <span>{{ o.texto }}</span>
          </button>
        </div>
        <button class="btn btn-verde" style="width:100%;" :disabled="calificando" @click="calificar" v-if="!quizCalificado">
          {{ calificando ? 'Enviando...' : 'Enviar respuestas' }}
        </button>

        <!-- Resultado -->
        <div v-if="quizCalificado" class="resultado-quiz zoom-in">
          <div class="nota">{{ notaObtenida }}/{{ curso.quiz.length }}</div>
          <p style="font-weight:700;" :style="{ color: aprobadaEsteIntento ? 'var(--verde)' : 'var(--rojo)' }">
            {{ aprobadaEsteIntento ? '¡Curso aprobado! 🎉' : 'Aún no apruebas' }}
          </p>
          <p style="font-size:.84rem;color:var(--texto-suave);margin-bottom:14px;">
            {{ aprobadaEsteIntento
              ? 'Tu certificado ya está disponible y tu avance quedó registrado.'
              : 'Repasa las lecciones y vuelve a intentarlo. Necesitas al menos 60% de aciertos.' }}
          </p>
          <button v-if="aprobadaEsteIntento" class="btn btn-verde" @click="mostrarCertificado">🎓 Ver mi certificado</button>
          <button v-else class="btn" @click="iniciarQuiz">Intentar de nuevo</button>
        </div>
      </div>
    </div>

    <!-- Modal Certificado -->
    <div v-if="certVisible" class="fondo-modal" @click.self="certVisible = false">
      <div class="certificado">
        <div class="sello">🌽</div>
        <div class="ente">MAGA · Consejo Nacional de Desarrollo Agropecuario · Programa AgroIA</div>
        <h2>Certificado de finalización</h2>
        <p class="otorga">Se otorga el presente certificado a</p>
        <div class="nombre-cert">{{ store.usuario?.nombre }}</div>
        <p class="detalle-cert">
          por haber completado satisfactoriamente el curso<br>
          <b>{{ curso.titulo }}</b><br>
          del programa de capacitación digital AgroIA,<br>
          con evaluación aprobada ({{ prog.nota }}/{{ curso.quiz.length }}) el {{ fechaAprobadoLegible }}.
        </p>
        <div class="firmas">
          <div>Coordinación CONADEA<br>Programa AgroIA</div>
          <div>Red de técnicos validadores<br>Validación académica</div>
        </div>
        <div class="acciones-cert">
          <button class="btn btn-verde" @click="window.print()">🖨️ Imprimir / Guardar PDF</button>
          <button class="btn" @click="certVisible = false">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAppStore } from '../../stores/app.js';
import { useCursosStore } from '../../stores/cursos.js';
import { alertaError } from '../../utils/alertas.js';
import cursoService from '../../services/cursoService.js';

const route = useRoute();
const router = useRouter();
const store = useAppStore();
const cursosStore = useCursosStore();

const curso = ref(null);
const cargando = ref(true);
const error = ref('');

const leccionesAbiertas = ref([]);
const marcandoLeccion = ref(null);
const quizActivo = ref(false);
const quizCalificado = ref(false);
const calificando = ref(false);
const quizSel = ref({});
const notaObtenida = ref(0);
const aprobadaEsteIntento = ref(false);
const certVisible = ref(false);
const opcionesEstado = ref({}); // { 'qi-oi': 'ok' | 'mal' }

const prog = computed(() => cursosStore.progresoDe(Number(route.params.id)));
const pct = computed(() => (curso.value ? cursosStore.pctCurso(curso.value) : 0));
const todasLeccionesHechas = computed(() => curso.value && cursosStore.todasLeccionesHechas(curso.value));

const fechaAprobadoLegible = computed(() => {
  if (!prog.value.fechaAprobado) return '';
  const f = new Date(prog.value.fechaAprobado.replace(' ', 'T'));
  return f.toLocaleDateString('es-GT', { day: 'numeric', month: 'long', year: 'numeric' });
});

function letraOpcion(i) {
  return String.fromCharCode(65 + i); // A, B, C, D...
}

async function cargarCurso() {
  cargando.value = true;
  error.value = '';
  try {
    const [, { data }] = await Promise.all([
      cursosStore.cargar(),
      cursoService.obtener(route.params.id)
    ]);
    curso.value = data.data;
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo conectar con el servidor.';
  } finally {
    cargando.value = false;
  }
}

watch(() => route.params.id, () => {
  quizActivo.value = false;
  quizCalificado.value = false;
  quizSel.value = {};
  opcionesEstado.value = {};
  leccionesAbiertas.value = [];
  cargarCurso();
}, { immediate: true });

function toggleLeccion(i) {
  const idx = leccionesAbiertas.value.indexOf(i);
  if (idx === -1) leccionesAbiertas.value.push(i);
  else leccionesAbiertas.value.splice(idx, 1);
}

async function marcarLeccion(leccion) {
  marcandoLeccion.value = leccion.id;
  try {
    await cursosStore.completarLeccion(curso.value.id, leccion.id);
    const i = curso.value.lecciones.findIndex((l) => l.id === leccion.id);
    const sig = i + 1;
    if (sig < curso.value.lecciones.length && !leccionesAbiertas.value.includes(sig)) {
      leccionesAbiertas.value.push(sig);
    }
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo guardar tu avance. Intenta de nuevo.');
  } finally {
    marcandoLeccion.value = null;
  }
}

function iniciarQuiz() {
  quizActivo.value = true;
  quizCalificado.value = false;
  quizSel.value = {};
  opcionesEstado.value = {};
  notaObtenida.value = 0;
}

function elegir(qi, oi) {
  if (quizCalificado.value) return;
  quizSel.value[qi] = oi;
}

function opcionClase(qi, oi) {
  const key = `${qi}-${oi}`;
  if (opcionesEstado.value[key]) return opcionesEstado.value[key];
  if (quizSel.value[qi] === oi && !quizCalificado.value) return 'sel';
  return '';
}

async function calificar() {
  const c = curso.value;
  if (Object.keys(quizSel.value).length < c.quiz.length) {
    store.mostrarToastGlobal('✍️', 'Responde todas las preguntas', 'Te falta al menos una por contestar.');
    return;
  }

  let nota = 0;
  c.quiz.forEach((q, qi) => {
    const respuesta = q.opciones.findIndex((o) => o.es_correcta);
    q.opciones.forEach((_, oi) => {
      const key = `${qi}-${oi}`;
      if (oi === respuesta) opcionesEstado.value[key] = 'ok';
      else if (quizSel.value[qi] === oi) opcionesEstado.value[key] = 'mal';
    });
    if (quizSel.value[qi] === respuesta) nota++;
  });

  notaObtenida.value = nota;
  aprobadaEsteIntento.value = c.quiz.length > 0 && nota / c.quiz.length >= 0.6;
  quizCalificado.value = true;

  calificando.value = true;
  try {
    await cursosStore.aprobarCurso(c.id, nota, c.quiz.length);
  } catch (e) {
    alertaError(e.response?.data?.message || 'No se pudo guardar tu evaluación. Intenta de nuevo.');
  } finally {
    calificando.value = false;
  }
}

function mostrarCertificado() {
  certVisible.value = true;
}
</script>

<style scoped>
.volver { display: inline-flex; align-items: center; gap: 7px; color: var(--verde); font-weight: 700; font-size: 0.9rem; margin-bottom: 18px; transition: all 0.2s; background: none; border: none; cursor: pointer; }
.volver:hover { color: #FFFFFF; transform: translateX(-2px); }
.cab-detalle { display: flex; gap: 20px; align-items: center; margin-bottom: 18px; flex-wrap: wrap; }
.icono-grande { width: 72px; height: 72px; border-radius: 18px; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; flex: none; box-shadow: 0 4px 14px rgba(0,0,0,0.25); background: linear-gradient(135deg, var(--verde), var(--verde-fuerte)); }
.cab-detalle h2 { font-size: 1.55rem; font-family: 'Outfit', sans-serif; font-weight: 800; }
.cab-detalle p { font-size: 0.88rem; color: var(--texto-suave); margin-top: 2px; }
.etiqueta { font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--texto-suave); font-weight: 800; }

.leccion { border: 1px solid var(--borde); border-radius: 16px; margin-bottom: 12px; overflow: hidden; background: var(--vidrio); transition: all 0.3s ease; }
.leccion:hover { border-color: rgba(255,255,255,0.25); }
.leccion > button { width: 100%; display: flex; align-items: center; gap: 14px; padding: 16px; text-align: left; }
.check { width: 26px; height: 26px; border-radius: 50%; flex: none; border: 2px solid var(--borde-claro); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; transition: all 0.2s; }
.leccion.hecha .check { background: var(--verde); border-color: var(--verde); color: #06281A; font-weight: 900; box-shadow: 0 0 8px rgba(74,222,128,0.4); }
.tit { flex: 1; font-weight: 600; font-size: 0.95rem; font-family: 'Outfit', sans-serif; }
.flecha { color: var(--texto-suave); font-size: 1.1rem; }
.leccion.abierta .flecha { transform: rotate(90deg); }
.cuerpo-lec { padding: 0 20px 20px 58px; font-size: 0.92rem; color: var(--texto-suave); line-height: 1.65; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 16px; }

.video-leccion { width: 100%; max-height: 360px; border-radius: 12px; margin-bottom: 14px; background: #000; display: block; }

.pregunta { margin-bottom: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 18px; }
.pregunta:last-child { border-bottom: none; padding-bottom: 0; }
.pregunta h4 { font-size: 1rem; margin-bottom: 14px; font-family: 'Outfit', sans-serif; font-weight: 700; }
.opcion { display: flex; gap: 12px; align-items: flex-start; width: 100%; text-align: left; padding: 12px 16px 15px; border: 1.5px solid var(--borde); border-bottom: 4.5px solid rgba(255,255,255,0.1); border-radius: 14px; margin-bottom: 10px; font-size: 0.9rem; background: var(--vidrio); transition: all 0.1s ease; cursor: pointer; }
.opcion:hover { border-color: rgba(255,255,255,0.3); transform: translateY(-1.5px); }
.opcion.sel { border-color: var(--verde); background: rgba(74,222,128,.12); box-shadow: 0 0 10px rgba(74,222,128,0.25); }
.opcion.ok  { border-color: var(--verde); background: rgba(74,222,128,.2); box-shadow: 0 0 10px rgba(74,222,128,0.3); pointer-events: none; }
.opcion.mal { border-color: var(--rojo);  background: rgba(248,113,113,.15); pointer-events: none; }
.letra { font-weight: 800; color: var(--verde); font-family: 'Outfit', sans-serif; }
.resultado-quiz { text-align: center; padding: 18px 0 6px; }
.nota { font-size: 3.5rem; font-weight: 900; color: var(--verde); font-family: 'Outfit', sans-serif; line-height: 1.1; }
.bloq { opacity: 0.35; pointer-events: none; }

/* Certificado Modal */
.fondo-modal { position: fixed; inset: 0; background: rgba(5,20,26,0.85); z-index: 100; display: flex; align-items: center; justify-content: center; padding: 16px; backdrop-filter: blur(8px); }
.certificado { background: #FFFDF4; color: #21302A; max-width: 680px; width: 100%; border-radius: 12px; border: 3px double #C8932B; padding: 40px 36px; text-align: center; position: relative; max-height: 92vh; overflow: auto; box-shadow: 0 16px 48px rgba(0,0,0,0.5); }
.certificado::before { content: ""; position: absolute; inset: 9px; border: 1px solid #E9B44C; border-radius: 8px; pointer-events: none; }
.sello { font-size: 2.8rem; }
.ente { font-size: 0.72rem; letter-spacing: 0.18em; text-transform: uppercase; color: #8C5E35; margin: 8px 0 16px; font-weight: 700; }
.certificado h2 { font-size: 1.8rem; color: #16382B; margin-bottom: 8px; font-family: Georgia, serif; font-weight: 700; }
.otorga { font-size: 0.9rem; color: #5C6B62; }
.nombre-cert { font-family: Georgia, serif; font-size: 1.8rem; color: #2D6A4F; font-weight: 700; margin: 16px 0; border-bottom: 1.5px solid #E9B44C; display: inline-block; padding: 0 30px 8px; }
.detalle-cert { font-size: 0.9rem; margin-bottom: 20px; line-height: 1.65; }
.firmas { display: flex; justify-content: space-around; margin-top: 30px; font-size: 0.74rem; color: #5C6B62; }
.firmas div { border-top: 1px solid #5C6B62; padding-top: 8px; width: 38%; font-weight: 600; }
.acciones-cert { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
</style>
