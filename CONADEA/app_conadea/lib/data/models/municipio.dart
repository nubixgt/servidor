class Municipio {
  const Municipio({required this.id, required this.departamentoId, required this.nombre});

  final int id;
  final int departamentoId;
  final String nombre;

  factory Municipio.fromJson(Map<String, dynamic> json) {
    return Municipio(
      id: json['id'] as int,
      departamentoId: json['departamento_id'] as int,
      nombre: json['nombre'] as String,
    );
  }
}
