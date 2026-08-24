import { defineStore } from 'pinia';
import { ref } from 'vue';
import cursoService from '../services/cursoService.js';
import progresoService from '../services/progresoService.js';

/**
 * Cursos reales del Backend + progreso del usuario — equivalente web de
 * app_conadea/lib/data/state/progreso_controller.dart (mismo singleton
 * compartido por Catálogo, Mis cursos y el detalle de curso, para que
 * completar una lección se refleje en todas las pantallas sin recargar).
 *
 * No mezclar con useAppStore (Frontend/src/stores/app.js): ese sigue
 * siendo el catálogo de ejemplo local (Frontend/src/data/local.js) que
 * usan Dashboard, Rutas, Insignias y Certificados mientras esas pantallas
 * no se conectan al Backend. Los cursos reales tienen sus propios ids
 * autoincrementales que podrían coincidir por casualidad con los del
 * catálogo de ejemplo — por eso las tarjetas reales navegan a /curso/:id
 * (esta store) y no a /catalogo/:id (mock).
 */
export const useCursosStore = defineStore('cursosReales', () => {
  const cursos = ref([]); // listado general (sin lecciones/quiz — ver GET /cursos)
  const progreso = ref({}); // { [cursoId]: { leccionesCompletadas: Set<leccionId>, aprobado, nota, fechaAprobado } }
  const cargando = ref(false);
  const cargado = ref(false);
  const error = ref('');

  function entradaProgreso(cursoId) {
    if (!progreso.value[cursoId]) {
      progreso.value[cursoId] = { leccionesCompletadas: new Set(), aprobado: false, nota: null, fechaAprobado: null };
    }
    return progreso.value[cursoId];
  }

  async function cargar({ forzar = false } = {}) {
    if (cargado.value && !forzar) return;
    cargando.value = true;
    error.value = '';
    try {
      const [resCursos, resProgreso] = await Promise.all([
        cursoService.listar(),
        progresoService.obtenerTodo()
      ]);

      cursos.value = resCursos.data.data;

      const mapa = {};
      progreso.value = mapa;
      resProgreso.data.data.lecciones.forEach((l) => {
        if (l.completada) entradaProgreso(l.curso_id).leccionesCompletadas.add(l.leccion_id);
      });
      resProgreso.data.data.cursos.forEach((c) => {
        const p = entradaProgreso(c.curso_id);
        p.aprobado = c.aprobado;
        p.nota = c.nota;
        p.fechaAprobado = c.fecha_aprobado;
      });
      cargado.value = true;
    } catch (e) {
      error.value = e.response?.data?.message || 'No se pudo conectar con el servidor.';
    } finally {
      cargando.value = false;
    }
  }

  function progresoDe(cursoId) {
    return progreso.value[cursoId] || { leccionesCompletadas: new Set(), aprobado: false, nota: null, fechaAprobado: null };
  }

  // Igual que ProgresoController.pctCurso: lecciones + evaluación final.
  function pctCurso(curso) {
    const p = progresoDe(curso.id);
    const total = (curso.total_lecciones ?? curso.lecciones?.length ?? 0) + 1;
    const hechas = p.leccionesCompletadas.size + (p.aprobado ? 1 : 0);
    return Math.round((hechas / total) * 100);
  }

  function aprobado(cursoId) {
    return progresoDe(cursoId).aprobado;
  }

  function enProgreso(curso) {
    const p = progresoDe(curso.id);
    return !p.aprobado && p.leccionesCompletadas.size > 0;
  }

  function todasLeccionesHechas(curso) {
    const completadas = progresoDe(curso.id).leccionesCompletadas;
    return curso.lecciones.length > 0 && curso.lecciones.every((l) => completadas.has(l.id));
  }

  // Optimista: marca de una vez en pantalla y revierte si el guardado falla.
  async function completarLeccion(cursoId, leccionId) {
    const p = entradaProgreso(cursoId);
    if (p.leccionesCompletadas.has(leccionId)) return;
    p.leccionesCompletadas.add(leccionId);
    try {
      await progresoService.guardarLeccion(leccionId, { completada: true });
    } catch (e) {
      p.leccionesCompletadas.delete(leccionId);
      throw e;
    }
  }

  // Aprobado es "pegajoso" — igual que ProgresoService::guardarEvaluacion
  // en el Backend, una vez aprobado no se puede desaprobar reintentando.
  async function aprobarCurso(cursoId, nota, total) {
    const { data } = await progresoService.guardarEvaluacion(cursoId, nota, total);
    const remoto = data.data;
    const p = entradaProgreso(cursoId);
    p.nota = remoto.nota;
    p.aprobado = remoto.aprobado;
    p.fechaAprobado = remoto.fecha_aprobado;
  }

  return {
    cursos, cargando, cargado, error, cargar,
    progresoDe, pctCurso, aprobado, enProgreso, todasLeccionesHechas,
    completarLeccion, aprobarCurso
  };
});
