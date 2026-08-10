import 'package:flutter/foundation.dart';
import '../models/curso.dart';
import '../models/ruta.dart';

/// Progreso de un curso individual — equivalente a `prog[moduloId]` en
/// Frontend/src/stores/app.js.
class ProgresoCurso {
  final Set<int> leccionesCompletadas = {};
  int? nota;
  bool aprobado = false;
  String? fecha;
}

/// Controlador de progreso del usuario — equivalente a useAppStore() en
/// Frontend/src/stores/app.js, pero en memoria (sin backend ni persistencia
/// todavía). Fuente única de verdad para el avance en Inicio, Cursos,
/// Catálogo, Detalle, Perfil, Certificados y Rutas.
///
/// Importante: no siembra progreso de ejemplo. Los cursos reales del
/// Backend usan ids autoincrementales que pueden coincidir con los ids de
/// los cursos mock (Rutas/Insignias siguen en mock por ahora) — si aquí se
/// "sembrara" progreso falso para el id 1, un curso real que también caiga
/// en el id 1 heredaría ese avance falso. Cada curso empieza en 0%.
class ProgresoController extends ChangeNotifier {
  ProgresoController._();
  static final ProgresoController instance = ProgresoController._();

  final Map<int, ProgresoCurso> _progreso = {};

  ProgresoCurso progresoDe(int cursoId) => _progreso.putIfAbsent(cursoId, () => ProgresoCurso());

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

  bool todasLeccionesHechas(Curso curso) =>
      progresoDe(curso.id).leccionesCompletadas.length == curso.lecciones.length;

  void completarLeccion(int cursoId, int index) {
    progresoDe(cursoId).leccionesCompletadas.add(index);
    notifyListeners();
  }

  void aprobarCurso(int cursoId, int nota) {
    final p = progresoDe(cursoId);
    p.nota = nota;
    if (nota >= 2 && !p.aprobado) {
      p.aprobado = true;
      p.fecha = _hoyLegible();
    }
    notifyListeners();
  }

  String _hoyLegible() {
    const meses = [
      'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    final hoy = DateTime.now();
    return '${hoy.day} de ${meses[hoy.month - 1]} de ${hoy.year}';
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
