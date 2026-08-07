enum TipoNovedad { alerta, video, novedad }

/// Novedad/noticia — equivalente a NOVEDADES en Frontend/src/data/local.js.
class Novedad {
  const Novedad({
    required this.tipo,
    required this.titulo,
    required this.chip,
    required this.fecha,
  });

  final TipoNovedad tipo;
  final String titulo;
  final String chip;
  final String fecha;

  String get emoji {
    switch (tipo) {
      case TipoNovedad.video:
        return '🎬';
      case TipoNovedad.alerta:
        return '📢';
      case TipoNovedad.novedad:
        return '📗';
    }
  }
}
