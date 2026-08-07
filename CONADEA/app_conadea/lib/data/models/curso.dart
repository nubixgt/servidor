/// Lección de un curso — equivalente a los objetos de `lecciones` en MODULOS
/// (Frontend/src/data/local.js).
class Leccion {
  const Leccion({required this.titulo, required this.contenido});

  final String titulo;
  final String contenido;
}

/// Pregunta de la evaluación final de un curso — equivalente a los objetos
/// de `quiz` en MODULOS (Frontend/src/data/local.js). [respuesta] es el
/// índice (0-based) de la opción correcta.
class PreguntaQuiz {
  const PreguntaQuiz({required this.pregunta, required this.opciones, required this.respuesta});

  final String pregunta;
  final List<String> opciones;
  final int respuesta;
}

/// Modelo de curso/módulo — equivalente a los objetos de MODULOS en
/// Frontend/src/data/local.js. El progreso (lecciones hechas, nota,
/// aprobado) vive aparte en ProgresoController, no en este modelo.
class Curso {
  const Curso({
    required this.id,
    required this.icono,
    required this.titulo,
    required this.descripcion,
    required this.imagenUrl,
    required this.lecciones,
    required this.quiz,
  });

  final int id;
  final String icono;
  final String titulo;
  final String descripcion;
  final String imagenUrl;
  final List<Leccion> lecciones;
  final List<PreguntaQuiz> quiz;
}
