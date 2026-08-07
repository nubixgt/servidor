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
class ProgresoController extends ChangeNotifier {
  ProgresoController._();
  static final ProgresoController instance = ProgresoController._().._sembrarEjemplo();

  final Map<int, ProgresoCurso> _progreso = {};

  ProgresoCurso progresoDe(int cursoId) => _progreso.putIfAbsent(cursoId, () => ProgresoCurso());

  /// Datos de ejemplo iniciales (mismos cursos que mostraba la app antes de
  /// tener progreso dinámico), para que Inicio se vea igual al abrir la app.
  void _sembrarEjemplo() {
    _marcarLecciones(1, 3); // Uso de WhatsApp AgroIA ~45%
    _marcarLecciones(2, 2); // Diagnóstico básico de cultivos ~30%
    _marcarLecciones(3, 4); // Manejo integrado de plagas ~60%
    _marcarLecciones(6, 1); // Manejo de agua y adaptación climática ~20%
    _aprobarSemilla(5, 3); // Ganadería sostenible: completo y aprobado
    _aprobarSemilla(10, 3); // Formulación de proyectos: completo y aprobado
  }

  void _marcarLecciones(int cursoId, int cantidad) {
    final p = progresoDe(cursoId);
    for (var i = 0; i < cantidad; i++) {
      p.leccionesCompletadas.add(i);
    }
  }

  void _aprobarSemilla(int cursoId, int totalLecciones) {
    final p = progresoDe(cursoId);
    for (var i = 0; i < totalLecciones; i++) {
      p.leccionesCompletadas.add(i);
    }
    p.aprobado = true;
    p.nota = 3;
    p.fecha = '3 de junio de 2026';
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
