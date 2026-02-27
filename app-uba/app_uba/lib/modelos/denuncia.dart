// app_uba/lib/modelos/denuncia.dart

class Denuncia {
  final String tipoPersona;
  final String nombreCompleto;
  final String dpi;
  final int edad;
  final String genero;
  final String celular;
  final String fotoDpiFrontal;
  final String fotoDpiTrasera;
  final String? nombreResponsable;
  final String direccionInfraccion;
  final String departamento;
  final String municipio;
  final String? colorCasa;
  final String? colorPuerta;
  final String fotoFachada;
  final double? latitud;
  final double? longitud;
  final String especieAnimal;
  final String? especieOtro;
  final int cantidad;
  final String? raza;
  final String descripcionDetallada;
  final List<Map<String, dynamic>> infracciones;
  final List<Map<String, dynamic>> evidencias;

  Denuncia({
    required this.tipoPersona,
    required this.nombreCompleto,
    required this.dpi,
    required this.edad,
    required this.genero,
    required this.celular,
    required this.fotoDpiFrontal,
    required this.fotoDpiTrasera,
    this.nombreResponsable,
    required this.direccionInfraccion,
    required this.departamento,
    required this.municipio,
    this.colorCasa,
    this.colorPuerta,
    required this.fotoFachada,
    this.latitud,
    this.longitud,
    required this.especieAnimal,
    this.especieOtro,
    required this.cantidad,
    this.raza,
    required this.descripcionDetallada,
    required this.infracciones,
    required this.evidencias,
  });

  Map<String, dynamic> toJson() {
    return {
      'tipo_persona': tipoPersona,
      'nombre_completo': nombreCompleto,
      'dpi': dpi,
      'edad': edad,
      'genero': genero,
      'celular': celular,
      'foto_dpi_frontal': fotoDpiFrontal,
      'foto_dpi_trasera': fotoDpiTrasera,
      'nombre_responsable': nombreResponsable,
      'direccion_infraccion': direccionInfraccion,
      'departamento': departamento,
      'municipio': municipio,
      'color_casa': colorCasa,
      'color_puerta': colorPuerta,
      'foto_fachada': fotoFachada,
      'latitud': latitud,
      'longitud': longitud,
      'especie_animal': especieAnimal,
      'especie_otro': especieOtro,
      'cantidad': cantidad,
      'raza': raza,
      'descripcion_detallada': descripcionDetallada,
      'infracciones': infracciones,
      'evidencias': evidencias,
    };
  }
}