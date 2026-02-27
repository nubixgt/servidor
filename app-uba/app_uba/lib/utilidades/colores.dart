import 'package:flutter/material.dart';

class AppColores {
  static const Color azulPrimario = Color(0xFF2563EB);
  static const Color verdePrimario = Color(0xFF16A34A);
  static const Color rojoPrimario = Color(0xFFDC2626);
  static const Color grisClaro = Color(0xFFF9FAFB);
  static const Color grisTexto = Color(0xFF6B7280);

  static const LinearGradient gradienteAzulVerde = LinearGradient(
    colors: [azulPrimario, verdePrimario],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
