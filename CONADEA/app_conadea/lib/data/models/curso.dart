/// Lección de un curso — equivalente a los objetos de `lecciones` en MODULOS
/// (Frontend/src/data/local.js). [id] es 0 en los cursos de ejemplo
/// (mock_cursos.dart, que no vienen del Backend); los cursos reales siempre
/// traen su id real, que es la clave que usa ProgresoController para
/// guardar avance/segundos de video contra el Backend.
class Leccion {
  const Leccion({this.id = 0, required this.titulo, required this.contenido, this.videoUrl});

  final int id;
  final String titulo;
  final String contenido;
  final String? videoUrl;

  factory Leccion.fromJson(Map<String, dynamic> json) {
    return Leccion(
      id: json['id'] as int? ?? 0,
      titulo: json['titulo'] as String,
      contenido: json['contenido'] as String,
      videoUrl: json['video_url'] as String?,
    );
  }
}

/// Pregunta de la evaluación final de un curso — equivalente a los objetos
/// de `quiz` en MODULOS (Frontend/src/data/local.js). [respuesta] es el
/// índice (0-based) de la opción correcta.
class PreguntaQuiz {
  const PreguntaQuiz({required this.pregunta, required this.opciones, required this.respuesta});

  final String pregunta;
  final List<String> opciones;
  final int respuesta;

  /// El Backend devuelve las opciones como `[{texto, es_correcta}]`
  /// (ver Backend/src/Controllers/CursoController.php); aquí se aplana a
  /// la forma que ya usa toda la UI (lista de textos + índice correcto).
  factory PreguntaQuiz.fromJson(Map<String, dynamic> json) {
    final opciones = (json['opciones'] as List<dynamic>).cast<Map<String, dynamic>>();
    return PreguntaQuiz(
      pregunta: json['pregunta'] as String,
      opciones: opciones.map((o) => o['texto'] as String).toList(),
      respuesta: opciones.indexWhere((o) => o['es_correcta'] == true),
    );
  }
}

/// Modelo de curso/módulo — equivalente a los objetos de MODULOS en
/// Frontend/src/data/local.js. El progreso (lecciones hechas, nota,
/// aprobado) vive aparte en ProgresoController, no en este modelo.
///
/// Lo usan tanto los cursos de ejemplo (mock_cursos.dart) como los cursos
/// reales que vienen del Backend (ver CursoService) — misma forma para
/// ambos, así toda la UI de progreso/quiz/certificado funciona igual.
class Curso {
  const Curso({
    required this.id,
    required this.icono,
    required this.titulo,
    required this.descripcion,
    required this.imagenUrl,
    required this.lecciones,
    required this.quiz,
    this.totalLecciones,
  });

  final int id;
  final String icono;
  final String titulo;
  final String descripcion;
  final String imagenUrl;
  final List<Leccion> lecciones;
  final List<PreguntaQuiz> quiz;

  /// Cuántas lecciones tiene el curso en total — viene del Backend aparte
  /// porque GET /cursos (listado) no manda el arreglo completo de
  /// [lecciones] (para no cargar de más). Usar esto en vez de
  /// `lecciones.length` para calcular avance/porcentajes, que también debe
  /// funcionar con cursos que vienen del listado (Inicio, Perfil, Mis
  /// cursos, Catálogo) y no solo con el curso completo (Detalle).
  final int? totalLecciones;

  int get numeroLecciones => totalLecciones ?? lecciones.length;

  /// GET /cursos (listado) no manda lecciones/quiz, solo los datos
  /// generales; por eso son opcionales aquí y quedan vacíos en ese caso.
  factory Curso.fromJson(Map<String, dynamic> json) {
    return Curso(
      id: json['id'] as int,
      icono: json['icono'] as String,
      titulo: json['titulo'] as String,
      descripcion: json['descripcion'] as String,
      imagenUrl: json['imagen_url'] as String,
      totalLecciones: json['total_lecciones'] as int?,
      lecciones: (json['lecciones'] as List<dynamic>? ?? [])
          .map((l) => Leccion.fromJson(l as Map<String, dynamic>))
          .toList(),
      quiz: (json['quiz'] as List<dynamic>? ?? [])
          .map((q) => PreguntaQuiz.fromJson(q as Map<String, dynamic>))
          .toList(),
    );
  }
}
