/// Modelo para representar un pago de factura a crédito
class Pago {
  final int? id;
  final int facturaId;
  final String fechaPago;
  final String banco;
  final double montoPago;
  final String referenciaTransaccion;
  final int usuarioId;
  final String? fechaCreacion;
  
  // Campos relacionados (de joins)
  final String? numeroNota;
  final String? clienteNombre;
  final String? nit;
  final double? totalFactura;
  final String? usuarioNombre;

  Pago({
    this.id,
    required this.facturaId,
    required this.fechaPago,
    required this.banco,
    required this.montoPago,
    required this.referenciaTransaccion,
    required this.usuarioId,
    this.fechaCreacion,
    this.numeroNota,
    this.clienteNombre,
    this.nit,
    this.totalFactura,
    this.usuarioNombre,
  });

  /// Crea un Pago desde JSON
  factory Pago.fromJson(Map<String, dynamic> json) {
    return Pago(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()),
      facturaId: json['factura_id'] is int 
          ? json['factura_id'] 
          : int.tryParse(json['factura_id'].toString()) ?? 0,
      fechaPago: json['fecha_pago'],
      banco: json['banco'],
      montoPago: double.tryParse(json['monto_pago'].toString()) ?? 0.0,
      referenciaTransaccion: json['referencia_transaccion'],
      usuarioId: json['usuario_id'] is int 
          ? json['usuario_id'] 
          : int.tryParse(json['usuario_id'].toString()) ?? 0,
      fechaCreacion: json['fecha_creacion'],
      numeroNota: json['numero_nota'],
      clienteNombre: json['cliente_nombre'],
      nit: json['nit'],
      totalFactura: json['total_factura'] != null 
          ? double.parse(json['total_factura'].toString()) 
          : null,
      usuarioNombre: json['usuario_nombre'],
    );
  }

  /// Convierte el Pago a JSON
  Map<String, dynamic> toJson() {
    return {
      'factura_id': facturaId,
      'fecha_pago': fechaPago,
      'banco': banco,
      'monto_pago': montoPago,
      'referencia_transaccion': referenciaTransaccion,
      'usuario_id': usuarioId,
    };
  }
}

/// Modelo para representar una factura a crédito con saldo
class FacturaCredito {
  final int id;
  final String numeroNota;
  final String fecha;
  final int clienteId;
  final String clienteNombre;
  final String nit;
  final double total;
  final int? diasCredito;
  final double totalPagado;
  final double saldoPendiente;

  FacturaCredito({
    required this.id,
    required this.numeroNota,
    required this.fecha,
    required this.clienteId,
    required this.clienteNombre,
    required this.nit,
    required this.total,
    this.diasCredito,
    required this.totalPagado,
    required this.saldoPendiente,
  });

  /// Crea una FacturaCredito desde JSON
  factory FacturaCredito.fromJson(Map<String, dynamic> json) {
    return FacturaCredito(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      numeroNota: json['numero_nota'],
      fecha: json['fecha'],
      clienteId: json['cliente_id'] is int 
          ? json['cliente_id'] 
          : int.tryParse(json['cliente_id'].toString()) ?? 0,
      clienteNombre: json['cliente_nombre'],
      nit: json['nit'],
      total: double.tryParse(json['total'].toString()) ?? 0.0,
      diasCredito: json['dias_credito'] != null
          ? (json['dias_credito'] is int 
              ? json['dias_credito'] 
              : int.tryParse(json['dias_credito'].toString()))
          : null,
      totalPagado: double.tryParse(json['total_pagado'].toString()) ?? 0.0,
      saldoPendiente: double.tryParse(json['saldo_pendiente'].toString()) ?? 0.0,
    );
  }
}
