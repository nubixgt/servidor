/// Modelo de curso/módulo — equivalente a los objetos de MODULOS en
/// Frontend/src/data/local.js (aquí solo los campos que la app necesita).
class Curso {
  const Curso({
    required this.id,
    required this.icono,
    required this.titulo,
    required this.descripcion,
    required this.imagenUrl,
    required this.progresoPct,
    this.completado = false,
  });

  final int id;
  final String icono;
  final String titulo;
  final String descripcion;
  final String imagenUrl;
  final int progresoPct;
  final bool completado;

  bool get enProgreso => !completado && progresoPct > 0;
}
