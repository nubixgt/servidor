// app_uba/lib/modelos/noticia.dart

class Noticia {
  final int idNoticia;
  final String titulo;
  final String categoria;
  final String descripcionCorta;
  final String contenidoCompleto;
  final String? imagenUrl;
  final String fechaPublicacion;
  final String prioridad;

  Noticia({
    required this.idNoticia,
    required this.titulo,
    required this.categoria,
    required this.descripcionCorta,
    required this.contenidoCompleto,
    this.imagenUrl,
    required this.fechaPublicacion,
    required this.prioridad,
  });

  /// Crea una instancia de Noticia desde JSON
  factory Noticia.fromJson(Map<String, dynamic> json) {
    return Noticia(
      idNoticia: json['id_noticia'] as int,
      titulo: json['titulo'] as String,
      categoria: json['categoria'] as String,
      descripcionCorta: json['descripcion_corta'] as String,
      contenidoCompleto: json['contenido_completo'] as String,
      imagenUrl: json['imagen_url'] as String?,
      fechaPublicacion: json['fecha_publicacion'] as String,
      prioridad: json['prioridad'] as String,
    );
  }

  /// Convierte la instancia a JSON
  Map<String, dynamic> toJson() {
    return {
      'id_noticia': idNoticia,
      'titulo': titulo,
      'categoria': categoria,
      'descripcion_corta': descripcionCorta,
      'contenido_completo': contenidoCompleto,
      'imagen_url': imagenUrl,
      'fecha_publicacion': fechaPublicacion,
      'prioridad': prioridad,
    };
  }

  /// Obtiene el emoji según la categoría
  String get emoji {
    switch (categoria) {
      case 'Campaña':
        return '🏥';
      case 'Rescate':
        return '🐕';
      case 'Legislación':
        return '⚖️';
      case 'Alerta':
        return '⚠️';
      case 'Evento':
        return '📅';
      case 'Otro':
        return '📰';
      default:
        return '📰';
    }
  }

  /// Obtiene el color según la prioridad
  String get colorPrioridad {
    switch (prioridad) {
      case 'urgente':
        return 'rojo';
      case 'importante':
        return 'naranja';
      case 'normal':
      default:
        return 'azul';
    }
  }
}
