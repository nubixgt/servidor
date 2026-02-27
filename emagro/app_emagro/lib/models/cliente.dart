class Cliente {
  final int id;
  final String nombre;
  final String nit;
  final String telefono;
  final String departamento;
  final String municipio;
  final String direccion;
  final String? email;
  final String bloquearVentas;
  final int usuarioId;
  final String? usuarioCreador;
  final String? fechaCreacion;
  final String? fechaActualizacion;

  Cliente({
    required this.id,
    required this.nombre,
    required this.nit,
    required this.telefono,
    required this.departamento,
    required this.municipio,
    required this.direccion,
    this.email,
    required this.bloquearVentas,
    required this.usuarioId,
    this.usuarioCreador,
    this.fechaCreacion,
    this.fechaActualizacion,
  });

  // Crear Cliente desde JSON
  factory Cliente.fromJson(Map<String, dynamic> json) {
    return Cliente(
      id: json['id'] != null ? int.tryParse(json['id'].toString()) ?? 0 : 0,
      nombre: json['nombre']?.toString() ?? '',
      nit: json['nit']?.toString() ?? '',
      telefono: json['telefono']?.toString() ?? '',
      departamento: json['departamento']?.toString() ?? '',
      municipio: json['municipio']?.toString() ?? '',
      direccion: json['direccion']?.toString() ?? '',
      email: json['email']?.toString(),
      bloquearVentas: json['bloquear_ventas']?.toString() ?? 'no',
      usuarioId: json['usuario_id'] != null ? int.tryParse(json['usuario_id'].toString()) ?? 0 : 0,
      usuarioCreador: json['usuario_creador']?.toString(),
      fechaCreacion: json['fecha_creacion']?.toString(),
      fechaActualizacion: json['fecha_actualizacion']?.toString(),
    );
  }

  // Convertir Cliente a JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'nit': nit,
      'telefono': telefono,
      'departamento': departamento,
      'municipio': municipio,
      'direccion': direccion,
      'email': email,
      'bloquear_ventas': bloquearVentas,
      'usuario_id': usuarioId,
    };
  }

  // Verificar si las ventas están bloqueadas
  bool get ventasBloqueadas => bloquearVentas == 'si';

  @override
  String toString() {
    return 'Cliente{id: $id, nombre: $nombre, nit: $nit, telefono: $telefono}';
  }
}
