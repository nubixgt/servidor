class AuthUser {
  final int id;
  final String usuario;
  final String rol; // admin | supervisor | tecnico

  AuthUser({required this.id, required this.usuario, required this.rol});

  factory AuthUser.fromJson(Map<String, dynamic> j) =>
      AuthUser(id: int.tryParse('${j['id']}') ?? 0, usuario: (j['usuario'] ?? '').toString(), rol: (j['rol'] ?? '').toString());

  Map<String, dynamic> toJson() => {'id': id, 'usuario': usuario, 'rol': rol};

  bool get esAdmin => rol == 'admin';
  bool get esTecnico => rol == 'tecnico';

  /// Solo admin y supervisor eliminan registros.
  bool get puedeEliminarRegistros => rol == 'admin' || rol == 'supervisor';
}

class Usuario {
  final int id;
  final String usuario;
  final String rol;
  final bool activo;
  final String creadoEn;

  Usuario({required this.id, required this.usuario, required this.rol, required this.activo, required this.creadoEn});

  factory Usuario.fromJson(Map<String, dynamic> j) => Usuario(
        id: int.tryParse('${j['id']}') ?? 0,
        usuario: (j['usuario'] ?? '').toString(),
        rol: (j['rol'] ?? '').toString(),
        activo: j['activo'] == true || j['activo'] == 1 || j['activo'] == '1',
        creadoEn: (j['creadoEn'] ?? j['created_at'] ?? '').toString(),
      );
}
