/// Modelo para representar un producto en el carrito temporal
class ItemCarrito {
  final String producto;
  final String presentacion;
  final double precioUnitario;
  int cantidad;
  double descuento;
  bool esBonificacion;

  ItemCarrito({
    required this.producto,
    required this.presentacion,
    required this.precioUnitario,
    required this.cantidad,
    this.descuento = 0.0,
    this.esBonificacion = false,
  });

  /// Calcula el total del item (precio * cantidad - descuento)
  /// Si es bonificación, el total es 0
  double get total => esBonificacion ? 0.0 : (precioUnitario * cantidad) - descuento;

  /// Convierte el item a JSON para enviar al backend
  Map<String, dynamic> toJson() {
    return {
      'producto': producto,
      'presentacion': presentacion,
      'precio_unitario': precioUnitario,
      'cantidad': cantidad,
      'descuento': descuento,
      'total': total,
      'es_bonificacion': esBonificacion,
    };
  }

  /// Crea una copia del item con valores modificados
  ItemCarrito copyWith({
    String? producto,
    String? presentacion,
    double? precioUnitario,
    int? cantidad,
    double? descuento,
    bool? esBonificacion,
  }) {
    return ItemCarrito(
      producto: producto ?? this.producto,
      presentacion: presentacion ?? this.presentacion,
      precioUnitario: precioUnitario ?? this.precioUnitario,
      cantidad: cantidad ?? this.cantidad,
      descuento: descuento ?? this.descuento,
      esBonificacion: esBonificacion ?? this.esBonificacion,
    );
  }

  @override
  String toString() {
    return '$producto - $presentacion';
  }
}
