/// Modelo para representar una nota de envío completa
class NotaEnvio {
  final int? id;
  final String numeroNota;
  final String fecha;
  final String vendedor;
  final int clienteId;
  final String clienteNombre;
  final String nit;
  final String direccion;
  final String tipoVenta; // 'Contado', 'Crédito', 'Pruebas', 'Bonificación'
  final int? diasCredito;
  final double subtotal;
  final double descuentoTotal;
  final double total;
  final int usuarioId;
  final String? fechaCreacion;
  final List<DetalleNotaEnvio> productos;

  NotaEnvio({
    this.id,
    required this.numeroNota,
    required this.fecha,
    required this.vendedor,
    required this.clienteId,
    required this.clienteNombre,
    required this.nit,
    required this.direccion,
    required this.tipoVenta,
    this.diasCredito,
    required this.subtotal,
    required this.descuentoTotal,
    required this.total,
    required this.usuarioId,
    this.fechaCreacion,
    required this.productos,
  });

  /// Crea una NotaEnvio desde JSON
  factory NotaEnvio.fromJson(Map<String, dynamic> json) {
    return NotaEnvio(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()),
      numeroNota: json['numero_nota'],
      fecha: json['fecha'],
      vendedor: json['vendedor'],
      clienteId: json['cliente_id'] is int ? json['cliente_id'] : int.parse(json['cliente_id'].toString()),
      clienteNombre: json['cliente_nombre'],
      nit: json['nit'],
      direccion: json['direccion'],
      tipoVenta: json['tipo_venta'],
      diasCredito: json['dias_credito'] != null 
          ? (json['dias_credito'] is int ? json['dias_credito'] : int.tryParse(json['dias_credito'].toString()))
          : null,
      subtotal: double.parse(json['subtotal'].toString()),
      descuentoTotal: double.parse(json['descuento_total'].toString()),
      total: double.parse(json['total'].toString()),
      usuarioId: json['usuario_id'] is int ? json['usuario_id'] : int.parse(json['usuario_id'].toString()),
      fechaCreacion: json['fecha_creacion'],
      productos: (json['productos'] as List?)
              ?.map((p) => DetalleNotaEnvio.fromJson(p))
              .toList() ??
          [],
    );
  }

  /// Convierte la NotaEnvio a JSON
  Map<String, dynamic> toJson() {
    return {
      'numero_nota': numeroNota,
      'fecha': fecha,
      'vendedor': vendedor,
      'cliente_id': clienteId,
      'cliente_nombre': clienteNombre,
      'nit': nit,
      'direccion': direccion,
      'tipo_venta': tipoVenta,
      'dias_credito': diasCredito,
      'subtotal': subtotal,
      'descuento_total': descuentoTotal,
      'total': total,
      'usuario_id': usuarioId,
      'productos': productos.map((p) => p.toJson()).toList(),
    };
  }
}

/// Modelo para representar el detalle (producto) de una nota de envío
class DetalleNotaEnvio {
  final int? id;
  final int? notaEnvioId;
  final String producto;
  final String presentacion;
  final double precioUnitario;
  final int cantidad;
  final bool esBonificacion;
  final double descuento;
  final double total;

  DetalleNotaEnvio({
    this.id,
    this.notaEnvioId,
    required this.producto,
    required this.presentacion,
    required this.precioUnitario,
    required this.cantidad,
    this.esBonificacion = false,
    required this.descuento,
    required this.total,
  });

  /// Crea un DetalleNotaEnvio desde JSON
  factory DetalleNotaEnvio.fromJson(Map<String, dynamic> json) {
    return DetalleNotaEnvio(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()),
      notaEnvioId: json['nota_envio_id'] is int ? json['nota_envio_id'] : int.tryParse(json['nota_envio_id'].toString()),
      producto: json['producto'],
      presentacion: json['presentacion'],
      precioUnitario: double.parse(json['precio_unitario'].toString()),
      cantidad: json['cantidad'] is int ? json['cantidad'] : int.parse(json['cantidad'].toString()),
      esBonificacion: json['es_bonificacion'] == 'si' || json['es_bonificacion'] == true,
      descuento: double.parse(json['descuento'].toString()),
      total: double.parse(json['total'].toString()),
    );
  }

  /// Convierte el DetalleNotaEnvio a JSON
  Map<String, dynamic> toJson() {
    return {
      'producto': producto,
      'presentacion': presentacion,
      'precio_unitario': precioUnitario,
      'cantidad': cantidad,
      'es_bonificacion': esBonificacion,
      'descuento': descuento,
      'total': total,
    };
  }
}
