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

  // Ahora el progreso es solo en base a lecciones completadas.
  function pctCurso(curso) {
    const p = progresoDe(curso.id);
    const total = (curso.total_lecciones ?? curso.lecciones?.length ?? 0);
    if (total === 0) return 0;
    const hechas = p.leccionesCompletadas.size;
    return Math.round((hechas / total) * 100);
  }

  function aprobado(cursoId) {
    // Check if the user completed all lessons
    const p = progresoDe(cursoId);
    return false; // Not used as global property anymore, but kept for compatibility. We'll derive it from 'todasLeccionesHechas' instead when needed.
  }

  function enProgreso(curso) {
    const p = progresoDe(curso.id);
    return !todasLeccionesHechas(curso) && p.leccionesCompletadas.size > 0;
  }

  function todasLeccionesHechas(curso) {
    const completadas = progresoDe(curso.id).leccionesCompletadas;
    return (curso.lecciones || []).length > 0 && (curso.lecciones || []).every((l) => completadas.has(l.id));
  }

  // Optimista: marca de una vez en pantalla y revierte si el guardado falla.
  // Si mandamos nota y total, la lección solo se completará si nota/total >= 0.6.
  async function completarLeccion(cursoId, leccionId, nota = null, total = null) {
    const p = entradaProgreso(cursoId);
    
    // Si no es un quiz, hacemos optimistic update
    if (nota === null) {
      if (p.leccionesCompletadas.has(leccionId)) return;
      p.leccionesCompletadas.add(leccionId);
    }
    
    try {
      const payload = { completada: true };
      if (nota !== null) {
        payload.nota = nota;
        payload.total = total;
        // The backend determines 'completada' based on nota/total, so we let it decide
        delete payload.completada;
      }
      
      const { data } = await progresoService.guardarLeccion(leccionId, payload);
      
      // Update from backend response
      if (data.data.completada) {
        p.leccionesCompletadas.add(leccionId);
      } else if (nota !== null) {
         // Fail case
      }
      return data.data; // Return result to the UI so it knows if passed
    } catch (e) {
      if (nota === null) p.leccionesCompletadas.delete(leccionId);
      throw e;
    }
  }

  return {
    cursos, cargando, cargado, error, cargar,
    progresoDe, pctCurso, aprobado, enProgreso, todasLeccionesHechas,
    completarLeccion
  };
});
