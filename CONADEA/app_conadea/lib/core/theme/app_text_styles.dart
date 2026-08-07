import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'app_colors.dart';

/// Tipografía CONADEA: Outfit para títulos, Inter para cuerpo de texto.
class AppTextStyles {
  AppTextStyles._();

  static TextStyle titulo({double size = 22, FontWeight weight = FontWeight.w800}) =>
      GoogleFonts.outfit(
        fontSize: size,
        fontWeight: weight,
        color: AppColors.texto,
        letterSpacing: -0.3,
      );

  static TextStyle subtitulo({double size = 15, FontWeight weight = FontWeight.w700}) =>
      GoogleFonts.outfit(
        fontSize: size,
        fontWeight: weight,
        color: AppColors.texto,
      );

  static TextStyle cuerpo({double size = 13.5, Color? color, FontWeight weight = FontWeight.w400}) =>
      GoogleFonts.inter(
        fontSize: size,
        fontWeight: weight,
        color: color ?? AppColors.textoSuave,
        height: 1.45,
      );

  static TextStyle etiqueta({double size = 11, Color? color}) => GoogleFonts.inter(
        fontSize: size,
        fontWeight: FontWeight.w700,
        color: color ?? AppColors.textoSuave,
        letterSpacing: 0.6,
      );
}
