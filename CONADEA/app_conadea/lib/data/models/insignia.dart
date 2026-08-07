import 'package:flutter/material.dart';

enum InsigniaColor { oro, verde, azul }

extension InsigniaColorGradient on InsigniaColor {
  List<Color> get gradiente {
    switch (this) {
      case InsigniaColor.oro:
        return const [Color(0xFFFDE68A), Color(0xFFD97706)];
      case InsigniaColor.verde:
        return const [Color(0xFF86EFAC), Color(0xFF15803D)];
      case InsigniaColor.azul:
        return const [Color(0xFF7DD3FC), Color(0xFF0369A1)];
    }
  }
}

/// Insignia — equivalente a INSIGNIAS en Frontend/src/data/local.js.
class Insignia {
  const Insignia({
    required this.id,
    required this.icono,
    required this.titulo,
    required this.descripcion,
    required this.color,
    this.obtenida = false,
  });

  final String id;
  final String icono;
  final String titulo;
  final String descripcion;
  final InsigniaColor color;
  final bool obtenida;
}
