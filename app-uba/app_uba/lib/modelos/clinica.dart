// app_uba/lib/modelos/clinica.dart

class ServicioAutorizado {
  final int idServicio;
  final String nombreServicio;
  final String direccion;
  final double? latitud;
  final double? longitud;
  final String telefono;
  final String serviciosOfrecidos;
  final double calificacion;
  final int totalCalificaciones;
  final String? imagenUrl;

  ServicioAutorizado({
    required this.idServicio,
    required this.nombreServicio,
    required this.direccion,
    this.latitud,
    this.longitud,
    required this.telefono,
    required this.serviciosOfrecidos,
    required this.calificacion,
    required this.totalCalificaciones,
    this.imagenUrl,
  });

  /// Crea una instancia de ServicioAutorizado desde JSON
  factory ServicioAutorizado.fromJson(Map<String, dynamic> json) {
    return ServicioAutorizado(
      idServicio: json['id_servicio'] as int,
      nombreServicio: json['nombre_servicio'] as String,
      direccion: json['direccion'] as String,
      latitud: json['latitud'] != null ? (json['latitud'] as num).toDouble() : null,
      longitud: json['longitud'] != null ? (json['longitud'] as num).toDouble() : null,
      telefono: json['telefono'] as String,
      serviciosOfrecidos: json['servicios_ofrecidos'] as String,
      calificacion: (json['calificacion'] as num).toDouble(),
      totalCalificaciones: json['total_calificaciones'] as int,
      imagenUrl: json['imagen_url'] as String?,
    );
  }

  /// Convierte la instancia a JSON
  Map<String, dynamic> toJson() {
    return {
      'id_servicio': idServicio,
      'nombre_servicio': nombreServicio,
      'direccion': direccion,
      'latitud': latitud,
      'longitud': longitud,
      'telefono': telefono,
      'servicios_ofrecidos': serviciosOfrecidos,
      'calificacion': calificacion,
      'total_calificaciones': totalCalificaciones,
      'imagen_url': imagenUrl,
    };
  }

  /// Formatea la calificación para mostrar (ej: "4.8")
  String get calificacionFormateada {
    return calificacion.toStringAsFixed(1);
  }

  /// Verifica si tiene coordenadas GPS válidas
  bool get tieneUbicacion {
    return latitud != null && longitud != null;
  }

  /// Obtiene la URL de Google Maps con las coordenadas
  String? get urlGoogleMaps {
    if (!tieneUbicacion) return null;
    return 'https://www.google.com/maps/search/?api=1&query=$latitud,$longitud';
  }

  /// Obtiene la URL para llamar por teléfono
  String get urlTelefono {
    // Remover guiones y espacios del teléfono
    final telefonoLimpio = telefono.replaceAll(RegExp(r'[-\s]'), '');
    return 'tel:$telefonoLimpio';
  }
}
