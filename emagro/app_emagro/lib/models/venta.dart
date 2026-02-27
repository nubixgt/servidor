class Venta {
  final int id;
  final String fecha;
  final String vendedor;
  final int clienteId;
  final String nit;
  final String direccion;
  final String tipoVenta;
  final int? diasCredito;
  final String producto;
  final String presentacion;
  final double precioUnitario;
  final int cantidad;
  final double descuento;
  final double total;
  final int usuarioId;
  final String? clienteNombre;
  final String? usuarioNombre;
  final String? fechaCreacion;

  Venta({
    required this.id,
    required this.fecha,
    required this.vendedor,
    required this.clienteId,
    required this.nit,
    required this.direccion,
    required this.tipoVenta,
    this.diasCredito,
    required this.producto,
    required this.presentacion,
    required this.precioUnitario,
    required this.cantidad,
    required this.descuento,
    required this.total,
    required this.usuarioId,
    this.clienteNombre,
    this.usuarioNombre,
    this.fechaCreacion,
  });

  // Crear Venta desde JSON
  factory Venta.fromJson(Map<String, dynamic> json) {
    return Venta(
      id: json['id'] != null ? int.tryParse(json['id'].toString()) ?? 0 : 0,
      fecha: json['fecha']?.toString() ?? '',
      vendedor: json['vendedor']?.toString() ?? '',
      clienteId: json['cliente_id'] != null ? int.tryParse(json['cliente_id'].toString()) ?? 0 : 0,
      nit: json['nit']?.toString() ?? '',
      direccion: json['direccion']?.toString() ?? '',
      tipoVenta: json['tipo_venta']?.toString() ?? '',
      diasCredito: json['dias_credito'] != null ? int.tryParse(json['dias_credito'].toString()) : null,
      producto: json['producto']?.toString() ?? '',
      presentacion: json['presentacion']?.toString() ?? '',
      precioUnitario: json['precio_unitario'] != null ? double.tryParse(json['precio_unitario'].toString()) ?? 0.0 : 0.0,
      cantidad: json['cantidad'] != null ? int.tryParse(json['cantidad'].toString()) ?? 0 : 0,
      descuento: json['descuento'] != null ? double.tryParse(json['descuento'].toString()) ?? 0.0 : 0.0,
      total: json['total'] != null ? double.tryParse(json['total'].toString()) ?? 0.0 : 0.0,
      usuarioId: json['usuario_id'] != null ? int.tryParse(json['usuario_id'].toString()) ?? 0 : 0,
      clienteNombre: json['cliente_nombre']?.toString(),
      usuarioNombre: json['usuario_nombre']?.toString(),
      fechaCreacion: json['fecha_creacion']?.toString(),
    );
  }

  // Convertir Venta a JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'fecha': fecha,
      'vendedor': vendedor,
      'cliente_id': clienteId,
      'nit': nit,
      'direccion': direccion,
      'tipo_venta': tipoVenta,
      'dias_credito': diasCredito,
      'producto': producto,
      'presentacion': presentacion,
      'precio_unitario': precioUnitario,
      'cantidad': cantidad,
      'descuento': descuento,
      'total': total,
      'usuario_id': usuarioId,
    };
  }

  @override
  String toString() {
    return 'Venta{id: $id, fecha: $fecha, producto: $producto, total: Q$total}';
  }
}
