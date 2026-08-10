class Departamento {
  const Departamento({required this.id, required this.nombre});

  final int id;
  final String nombre;

  factory Departamento.fromJson(Map<String, dynamic> json) {
    return Departamento(
      id: json['id'] as int,
      nombre: json['nombre'] as String,
    );
  }
}
