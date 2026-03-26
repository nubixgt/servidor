import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  static const primary = Color(0xFF0f2a4a);
  static const primaryContainer = Color(0xFF1a457b);
  static const secondary = Color(0xFF904d00);
  static const secondaryContainer = Color(0xFFffa454);
  static const surface = Color(0xFFf8fafb);
  static const surfaceContainerLow = Color(0xFFf2f4f5);
  static const surfaceContainerHigh = Color(0xFFe6e8e9);
  static const surfaceContainerHighest = Color(0xFFe1e3e4);
  static const onSurface = Color(0xFF191c1d);
  static const onSurfaceVariant = Color(0xFF3d494a);
  static const outline = Color(0xFF6d797a);
  static const outlineVariant = Color(0xFFbdc9ca);
  static const white = Colors.white;

  static const LinearGradient primaryGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [primary, primaryContainer],
  );
}

class AppTheme {
  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.light(
        primary: AppColors.primary,
        onPrimary: Colors.white,
        primaryContainer: AppColors.primaryContainer,
        secondary: AppColors.secondary,
        onSecondary: Colors.white,
        secondaryContainer: AppColors.secondaryContainer,
        surface: AppColors.surface,
        onSurface: AppColors.onSurface,
        onSurfaceVariant: AppColors.onSurfaceVariant,
        outline: AppColors.outline,
        outlineVariant: AppColors.outlineVariant,
      ),
      scaffoldBackgroundColor: AppColors.surface,
      textTheme: GoogleFonts.plusJakartaSansTextTheme().apply(
        bodyColor: AppColors.onSurface,
        displayColor: AppColors.onSurface,
      ).copyWith(
        bodyMedium: GoogleFonts.inter(color: AppColors.onSurface),
        bodySmall: GoogleFonts.inter(color: AppColors.onSurfaceVariant),
        bodyLarge: GoogleFonts.inter(color: AppColors.onSurface),
      ),
    );
  }
}
