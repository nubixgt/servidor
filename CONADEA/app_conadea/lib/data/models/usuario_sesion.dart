/// Datos del usuario autenticado, tal como los devuelve el Backend en
/// /auth/login y /auth/register (dentro de `data.usuario`).
class UsuarioSesion {
  const UsuarioSesion({
    required this.id,
    required this.nombreCompleto,
    required this.usuario,
    required this.telefono,
    required this.departamentoId,
    required this.municipioId,
    required this.rol,
  });

  final int id;
  final String nombreCompleto;
  final String usuario;
  final String telefono;
  final int departamentoId;
  final int municipioId;
  final String rol;

  factory UsuarioSesion.fromJson(Map<String, dynamic> json) {
    return UsuarioSesion(
      id: json['id'] as int,
      nombreCompleto: json['nombre_completo'] as String,
      usuario: json['usuario'] as String,
      telefono: json['telefono'] as String,
      departamentoId: json['departamento_id'] as int,
      municipioId: json['municipio_id'] as int,
      rol: json['rol'] as String,
    );
  }
}
