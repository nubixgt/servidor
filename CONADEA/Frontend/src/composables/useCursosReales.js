import { ref } from 'vue';
import cursoService from '../services/cursoService.js';
import progresoService from '../services/progresoService.js';

/**
 * Cursos reales del Backend + progreso del usuario — equivalente web de
 * app_conadea/lib/data/state/progreso_controller.dart. Usado por
 * Catalogo.vue y MisCursos.vue.
 *
 * No mezclar con store.progDe()/pctModulo(): esos siguen siendo del
 * catálogo de ejemplo (Frontend/src/data/local.js) que usan Dashboard,
 * Rutas, Insignias y Certificados mientras esas pantallas no se conectan
 * al Backend.
 */
export function useCursosReales() {
  const cursos = ref([]);
  const progresoPorCurso = ref({}); // { [cursoId]: { leccionesCompletadas: Set<int>, aprobado, nota, fechaAprobado } }
  const cargando = ref(true);
  const error = ref('');

  function entradaProgreso(cursoId) {
    if (!progresoPorCurso.value[cursoId]) {
      progresoPorCurso.value[cursoId] = { leccionesCompletadas: new Set(), aprobado: false, nota: null, fechaAprobado: null };
    }
    return progresoPorCurso.value[cursoId];
  }

  async function cargar() {
    cargando.value = true;
    error.value = '';
    try {
      const [resCursos, resProgreso] = await Promise.all([
        cursoService.listar(),
        progresoService.obtenerTodo()
      ]);

      cursos.value = resCursos.data.data;

      const mapa = {};
      progresoPorCurso.value = mapa;
      resProgreso.data.data.lecciones.forEach((l) => {
        if (l.completada) entradaProgreso(l.curso_id).leccionesCompletadas.add(l.leccion_id);
      });
      resProgreso.data.data.cursos.forEach((c) => {
        const p = entradaProgreso(c.curso_id);
        p.aprobado = c.aprobado;
        p.nota = c.nota;
        p.fechaAprobado = c.fecha_aprobado;
      });
    } catch (e) {
      error.value = e.response?.data?.message || 'No se pudo conectar con el servidor.';
    } finally {
      cargando.value = false;
    }
  }

  function progresoDe(cursoId) {
    return progresoPorCurso.value[cursoId] || { leccionesCompletadas: new Set(), aprobado: false, nota: null, fechaAprobado: null };
  }

  // Igual que ProgresoController.pctCurso: lecciones + evaluación final.
  function pctCurso(curso) {
    const p = progresoDe(curso.id);
    const total = (curso.total_lecciones ?? 0) + 1;
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

  return { cursos, cargando, error, cargar, progresoDe, pctCurso, aprobado, enProgreso };
}
