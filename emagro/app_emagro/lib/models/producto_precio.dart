class ProductoPrecio {
  final int id;
  final String producto;
  final String presentacion;
  final double precio;
  final int cantidad;

  ProductoPrecio({
    required this.id,
    required this.producto,
    required this.presentacion,
    required this.precio,
    required this.cantidad,
  });

  // Crear ProductoPrecio desde JSON
  factory ProductoPrecio.fromJson(Map<String, dynamic> json) {
    return ProductoPrecio(
      id: json['id'] != null ? int.tryParse(json['id'].toString()) ?? 0 : 0,
      producto: json['producto']?.toString() ?? '',
      presentacion: json['presentacion']?.toString() ?? '',
      precio: json['precio'] != null ? double.tryParse(json['precio'].toString()) ?? 0.0 : 0.0,
      cantidad: json['cantidad'] != null ? int.tryParse(json['cantidad'].toString()) ?? 0 : 0,
    );
  }

  // Convertir ProductoPrecio a JSON
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'producto': producto,
      'presentacion': presentacion,
      'precio': precio,
      'cantidad': cantidad,
    };
  }

  @override
  String toString() {
    return 'ProductoPrecio{producto: $producto, presentacion: $presentacion, precio: Q$precio}';
  }
}

// Clase auxiliar para presentación con precio
class PresentacionPrecio {
  final String presentacion;
  final double precio;
  final int cantidad;

  PresentacionPrecio({
    required this.presentacion,
    required this.precio,
    required this.cantidad,
  });

  factory PresentacionPrecio.fromJson(Map<String, dynamic> json) {
    return PresentacionPrecio(
      presentacion: json['presentacion']?.toString() ?? '',
      precio: json['precio'] != null ? double.tryParse(json['precio'].toString()) ?? 0.0 : 0.0,
      cantidad: json['cantidad'] != null ? int.tryParse(json['cantidad'].toString()) ?? 0 : 0,
    );
  }

  @override
  String toString() {
    return '$presentacion - Q${precio.toStringAsFixed(2)}';
  }
}
