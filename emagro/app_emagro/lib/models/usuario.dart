class Usuario {
  final int id;
  final String nombre;
  final String usuario;
  final String rol;
  final String estado;
  final String? fechaCreacion;
  final String? fechaActualizacion;

  Usuario({
    required this.id,
    required this.nombre,
    required this.usuario,
    required this.rol,
    required this.estado,
    this.fechaCreacion,
    this.fechaActualizacion,
  });

  // Crear Usuario desde JSON
  factory Usuario.fromJson(Map<String, dynamic> json) {
    return Usuario(
      id: int.parse(json['id'].toString()),
      nombre: json['nombre'] ?? '',
      usuario: json['usuario'] ?? '',
      rol: json['rol'] ?? '',
      estado: json['estado'] ?? '',
      fechaCreacion: json['fecha_creacion'],
      fechaActualizacion: json['fecha_actualizacion'],
    );
  }

  // Convertir Usuario a JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'usuario': usuario,
      'rol': rol,
      'estado': estado,
      'fecha_creacion': fechaCreacion,
      'fecha_actualizacion': fechaActualizacion,
    };
  }

  // Verificar si es administrador
  bool get isAdmin => rol == 'admin';

  // Verificar si está activo
  bool get isActivo => estado == 'activo';

  @override
  String toString() {
    return 'Usuario{id: $id, nombre: $nombre, usuario: $usuario, rol: $rol, estado: $estado}';
  }
}
