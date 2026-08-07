import 'package:flutter/material.dart';
import 'curso.dart';

enum RutaColor { verde, azul, oro, teal }

extension RutaColorGradient on RutaColor {
  List<Color> get gradiente {
    switch (this) {
      case RutaColor.verde:
        return const [Color(0xFF4ADE80), Color(0xFF15803D)];
      case RutaColor.azul:
        return const [Color(0xFF60A5FA), Color(0xFF1D4ED8)];
      case RutaColor.oro:
        return const [Color(0xFFFCD34D), Color(0xFFD97706)];
      case RutaColor.teal:
        return const [Color(0xFF34D399), Color(0xFF059669)];
    }
  }
}

/// Ruta de aprendizaje — equivalente a RUTAS en Frontend/src/data/local.js.
class RutaAprendizaje {
  const RutaAprendizaje({
    required this.id,
    required this.icono,
    required this.titulo,
    required this.descripcion,
    required this.color,
    required this.cursos,
  });

  final String id;
  final String icono;
  final String titulo;
  final String descripcion;
  final RutaColor color;
  final List<Curso> cursos;
}
