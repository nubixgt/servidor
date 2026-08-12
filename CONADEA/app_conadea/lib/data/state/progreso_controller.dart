import 'package:flutter/foundation.dart';
import '../models/curso.dart';
import '../models/ruta.dart';
import '../services/progreso_service.dart';

/// Progreso de un curso individual — equivalente a `prog[moduloId]` en
/// Frontend/src/stores/app.js. [leccionesCompletadas] guarda `Leccion.id`
/// (no el índice dentro del curso), porque así es como se persiste contra
/// el Backend y no depende del orden en que vengan las lecciones.
class ProgresoCurso {
  final Set<int> leccionesCompletadas = {};
  int? nota;
  bool aprobado = false;
  String? fecha;
}

/// Controlador de progreso del usuario — equivalente a useAppStore() en
/// Frontend/src/stores/app.js. Fuente única de verdad para el avance en
/// Inicio, Cursos, Catálogo, Detalle, Perfil, Certificados y Rutas.
///
/// El progreso ahora se persiste en el Backend
/// (Backend/src/Controllers/ProgresoController.php) — antes vivía solo en
/// memoria y no sobrevivía a cerrar la app. Este controller sigue siendo el
/// único punto de lectura para la UI (misma API pública de antes); por
/// dentro, [cargarProgreso] lo puebla una vez desde el Backend (se llama
/// desde MainShell al entrar) y cada mutación local dispara también la
/// llamada correspondiente en segundo plano, sin bloquear la UI.
///
/// Importante: no siembra progreso de ejemplo. Los cursos reales del
/// Backend usan ids autoincrementales que pueden coincidir con los ids de
/// los cursos mock (Rutas/Insignias siguen en mock por ahora) — si aquí se
/// "sembrara" progreso falso para el id 1, un curso real que también caiga
/// en el id 1 heredaría ese avance falso. Cada curso empieza en 0%.
class ProgresoController extends ChangeNotifier {
  ProgresoController._();
  static final ProgresoController instance = ProgresoController._();

  final ProgresoService _service = ProgresoService();

  final Map<int, ProgresoCurso> _progreso = {};
  final Map<int, int> _segundosVideo = {}; // Leccion.id -> segundos

  ProgresoCurso progresoDe(int cursoId) => _progreso.putIfAbsent(cursoId, () => ProgresoCurso());

  /// Trae todo el progreso del usuario desde el Backend y puebla el estado
  /// en memoria — se llama una vez al entrar a la app (MainShell.initState).
  /// Si falla (sin conexión, etc.) la app sigue con progreso vacío en vez
  /// de trabar el arranque; las mutaciones locales reintentan guardar
  /// contra el Backend cuando sí haya señal.
  Future<void> cargarProgreso() async {
    try {
      final remoto = await _service.obtenerTodo();
      for (final l in remoto.lecciones) {
        final p = progresoDe(l.cursoId);
        if (l.completada) p.leccionesCompletadas.add(l.leccionId);
        if (l.segundosVideo > 0) _segundosVideo[l.leccionId] = l.segundosVideo;
      }
      for (final c in remoto.cursos) {
        final p = progresoDe(c.cursoId);
        p.nota = c.nota;
        p.aprobado = c.aprobado;
        p.fecha = c.fechaAprobado != null ? _formatearFecha(DateTime.parse(c.fechaAprobado!)) : null;
      }
      notifyListeners();
    } catch (_) {
      // Sin conexión al abrir la app: no vale la pena trabar el arranque
      // por esto, se sigue con progreso vacío.
    }
  }

  /// Porcentaje de avance de un curso (lecciones + evaluación final).
  int pctCurso(Curso curso) {
    final p = progresoDe(curso.id);
    final total = curso.lecciones.length + 1;
    final hechas = p.leccionesCompletadas.length + (p.aprobado ? 1 : 0);
    return ((hechas / total) * 100).round();
  }

  bool aprobado(int cursoId) => progresoDe(cursoId).aprobado;

  bool enProgreso(Curso curso) {
    final p = progresoDe(curso.id);
    return !p.aprobado && p.leccionesCompletadas.isNotEmpty;
  }

  bool todasLeccionesHechas(Curso curso) {
    final completadas = progresoDe(curso.id).leccionesCompletadas;
    return curso.lecciones.every((l) => completadas.contains(l.id));
  }

  void completarLeccion(int cursoId, int leccionId) {
    progresoDe(cursoId).leccionesCompletadas.add(leccionId);
    notifyListeners();
    _service.guardarLeccion(leccionId, completada: true);
  }

  int segundosDe(int leccionId) => _segundosVideo[leccionId] ?? 0;

  /// Guarda el punto donde va el video de una lección — se llama
  /// periódicamente mientras reproduce y al pausar/salir/cerrar la app,
  /// para poder retomar en el mismo segundo la próxima vez que la abra.
  void guardarSegundos(int leccionId, int segundos) {
    _segundosVideo[leccionId] = segundos;
    _service.guardarLeccion(leccionId, segundosVideo: segundos);
  }

  /// Aprueba si acierta al menos el 60% de las preguntas del quiz — antes
  /// era un umbral fijo de "2 de 3", que ya no tiene sentido porque el
  /// Administrador puede crear quizzes con cualquier cantidad de preguntas.
  void aprobarCurso(int cursoId, int nota, int totalPreguntas) {
    final p = progresoDe(cursoId);
    p.nota = nota;
    if (totalPreguntas > 0 && nota / totalPreguntas >= 0.6 && !p.aprobado) {
      p.aprobado = true;
      p.fecha = _hoyLegible();
    }
    notifyListeners();
    _service.guardarEvaluacion(cursoId, nota: nota, total: totalPreguntas);
  }

  String _hoyLegible() => _formatearFecha(DateTime.now());

  String _formatearFecha(DateTime fecha) {
    const meses = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    return '${fecha.day} de ${meses[fecha.month - 1]} de ${fecha.year}';
  }

  int pctRuta(RutaAprendizaje ruta) {
    if (ruta.cursos.isEmpty) return 0;
    final suma = ruta.cursos.fold<int>(0, (s, c) => s + pctCurso(c));
    return (suma / ruta.cursos.length).round();
  }

  int cursosCompletados(List<Curso> cursos) => cursos.where((c) => aprobado(c.id)).length;

  int cursosEnProgreso(List<Curso> cursos) => cursos.where(enProgreso).length;

  int leccionesHechas(List<Curso> cursos) =>
      cursos.fold(0, (s, c) => s + progresoDe(c.id).leccionesCompletadas.length);

  int totalLecciones(List<Curso> cursos) => cursos.fold(0, (s, c) => s + c.lecciones.length);

  int pctGlobal(List<Curso> cursos) {
    if (cursos.isEmpty) return 0;
    final suma = cursos.fold<int>(0, (s, c) => s + pctCurso(c));
    return (suma / cursos.length).round();
  }

  int horasCapacitacion(List<Curso> cursos) =>
      (leccionesHechas(cursos) * 0.5 + cursosCompletados(cursos) * 0.5).round();
}
