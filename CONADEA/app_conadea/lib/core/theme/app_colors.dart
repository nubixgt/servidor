import 'package:flutter/material.dart';

/// Paleta CONADEA · MAGA AgroIA — misma paleta que Frontend/src/style.css
/// (ver .claude/skills/conadea-design/SKILL.md para la fuente de verdad).
class AppColors {
  AppColors._();

  static const Color fondoBase = Color(0xFF0C2630);

  static const Color texto = Color(0xFFFFFFFF);
  static const Color textoSuave = Color(0xB3FFFFFF); // 70%

  static const Color verde = Color(0xFF4ADE80);
  static const Color verdeFuerte = Color(0xFF22C55E);
  static const Color verdeOscuro = Color(0xFF15803D);
  static const Color lima = Color(0xFFA3E635);
  static const Color oro = Color(0xFFF4C542);
  static const Color azul = Color(0xFF38BDF8);
  static const Color rojo = Color(0xFFF87171);

  static const Color vidrio = Color(0x730D2630); // rgba(13,38,48,.45)
  static const Color vidrio2 = Color(0x14FFFFFF); // rgba(255,255,255,.08)
  static const Color vidrio3 = Color(0x26FFFFFF); // rgba(255,255,255,.15)

  static const Color borde = Color(0x1FFFFFFF); // rgba(255,255,255,.12)
  static const Color bordeClaro = Color(0x38FFFFFF); // rgba(255,255,255,.22)

  static const double radio = 20;

  /// Colores por curso (paridad con COLORES_MOD en Frontend/src/data/local.js)
  static const Map<int, List<Color>> colorMod = {
    1: [Color(0xFF34D399), Color(0xFF0F766E)],
    2: [Color(0xFFA3E635), Color(0xFF3F6212)],
    3: [Color(0xFFFBBF24), Color(0xFFB45309)],
    4: [Color(0xFFFB923C), Color(0xFF9A3412)],
    5: [Color(0xFF60A5FA), Color(0xFF1E40AF)],
    6: [Color(0xFF22D3EE), Color(0xFF155E75)],
    7: [Color(0xFFC084FC), Color(0xFF6B21A8)],
    8: [Color(0xFFF472B6), Color(0xFF9D174D)],
    9: [Color(0xFFFACC15), Color(0xFF854D0E)],
    10: [Color(0xFF4ADE80), Color(0xFF166534)],
    11: [Color(0xFF86EFAC), Color(0xFF15803D)],
    12: [Color(0xFF6EE7B7), Color(0xFF047857)],
    13: [Color(0xFF93C5FD), Color(0xFF1D4ED8)],
    14: [Color(0xFF7DD3FC), Color(0xFF0369A1)],
    15: [Color(0xFFA7F3D0), Color(0xFF064E3B)],
    16: [Color(0xFFFDE047), Color(0xFFA16207)],
    17: [Color(0xFFF9A8D4), Color(0xFFBE185D)],
    18: [Color(0xFFC084FC), Color(0xFF581C87)],
    19: [Color(0xFFFDA4AF), Color(0xFFBE123C)],
    20: [Color(0xFFFDBA74), Color(0xFFC2410C)],
    21: [Color(0xFF818CF8), Color(0xFF4338CA)],
  };

  static List<Color> gradMod(int id) => colorMod[id] ?? [verde, verdeOscuro];
}
