import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// Paleta y estilos tomados de danos_a_cultivos_movil.html.
class AppColors {
  static const navy = Color(0xFF0B3446);
  static const muted = Color(0xFF607880);
  static const green = Color(0xFF00865D);
  static const greenDark = Color(0xFF0B6149);
  static const lime = Color(0xFFB7EF87);
  static const line = Color(0xFFD8E6E4);
  static const inputBorder = Color(0xFFD4E2DC);
  static const bg = Color(0xFF153C36);
  static const red = Color(0xFFB94444);
  static const label = Color(0xFF49665E);
}

class AppTheme {
  static ThemeData get light {
    final base = ThemeData(
      useMaterial3: true,
      colorScheme: ColorScheme.fromSeed(seedColor: AppColors.green, primary: AppColors.green, surface: Colors.white),
      scaffoldBackgroundColor: AppColors.bg,
    );
    return base.copyWith(
      textTheme: GoogleFonts.interTextTheme(base.textTheme).apply(bodyColor: AppColors.navy, displayColor: AppColors.navy),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: const Color(0xD9FFFFFF),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        hintStyle: const TextStyle(color: Color(0xFF98AAA4), fontSize: 14),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.inputBorder)),
        enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.inputBorder)),
        focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: Color(0xFF52BBA2), width: 2)),
        errorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.red)),
        focusedErrorBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: const BorderSide(color: AppColors.red, width: 2)),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: const Color(0xFF123F34),
        contentTextStyle: const TextStyle(color: Colors.white, fontSize: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13), side: const BorderSide(color: Color(0xFF83B496))),
      ),
    );
  }
}

/// Mensajes de aviso (equivalente a los toasts de la web).
void showMsg(BuildContext context, String message, {bool error = false}) {
  final m = ScaffoldMessenger.of(context);
  m.hideCurrentSnackBar();
  m.showSnackBar(SnackBar(
    content: Text(message),
    backgroundColor: error ? const Color(0xFF7A2A2A) : const Color(0xFF123F34),
    duration: Duration(seconds: error ? 4 : 3),
  ));
}

Future<bool> confirmDialog(BuildContext context, String message, {String title = '¿Estás seguro?', bool danger = false, String confirmText = 'Confirmar'}) async {
  final r = await showDialog<bool>(
    context: context,
    builder: (c) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: const Color(0xFFFAFDF9),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
      content: Text(message, style: const TextStyle(color: AppColors.muted)),
      actions: [
        TextButton(onPressed: () => Navigator.pop(c, false), child: const Text('Cancelar')),
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: danger ? AppColors.red : AppColors.green),
          onPressed: () => Navigator.pop(c, true),
          child: Text(confirmText),
        ),
      ],
    ),
  );
  return r ?? false;
}

Future<void> errorDialog(BuildContext context, String message, {String title = 'Ocurrió un error'}) {
  return showDialog<void>(
    context: context,
    builder: (c) => AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      backgroundColor: const Color(0xFFFAFDF9),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 18)),
      content: Text(message, style: const TextStyle(color: AppColors.muted)),
      actions: [FilledButton(style: FilledButton.styleFrom(backgroundColor: AppColors.green), onPressed: () => Navigator.pop(c), child: const Text('Aceptar'))],
    ),
  );
}
