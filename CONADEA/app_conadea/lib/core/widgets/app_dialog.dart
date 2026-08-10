import 'dart:async';

import 'package:flutter/material.dart';
import '../theme/app_colors.dart';
import '../theme/app_text_styles.dart';
import 'glass_card.dart';
import 'primary_button.dart';

enum AppDialogType { exito, error }

/// Diálogo estilo "alerta" (éxito/error) con la estética glass/verde de
/// CONADEA — Flutter no tiene un equivalente a SweetAlert, así que este
/// widget cumple ese rol en toda la app (login, crear curso, etc.).
Future<void> mostrarAlerta(
  BuildContext context, {
  required AppDialogType tipo,
  required String titulo,
  required String mensaje,
  Duration? autoCerrar,
}) async {
  final future = showDialog<void>(
    context: context,
    barrierDismissible: autoCerrar == null,
    builder: (_) => _AppAlertDialog(tipo: tipo, titulo: titulo, mensaje: mensaje),
  );

  // Si el usuario toca "Entendido" antes de que se cumpla [autoCerrar], hay
  // que cancelar este timer — si no, dispara un pop() de más ya sin diálogo
  // en pantalla, cerrando lo que sea que haya quedado arriba (ej. la
  // pantalla a la que se acaba de navegar) y rompe el Navigator.
  Timer? timer;
  if (autoCerrar != null) {
    timer = Timer(autoCerrar, () {
      if (context.mounted) Navigator.of(context).pop();
    });
  }

  await future;
  timer?.cancel();
}

/// Diálogo de confirmación (Sí/No) con la misma estética — para acciones
/// que conviene confirmar antes de hacerlas, como cerrar sesión. Devuelve
/// `true` solo si la persona toca el botón de confirmar.
Future<bool> mostrarConfirmacion(
  BuildContext context, {
  required String titulo,
  required String mensaje,
  String textoConfirmar = 'Sí',
  String textoCancelar = 'No',
}) async {
  final resultado = await showDialog<bool>(
    context: context,
    builder: (_) => _AppConfirmDialog(
      titulo: titulo,
      mensaje: mensaje,
      textoConfirmar: textoConfirmar,
      textoCancelar: textoCancelar,
    ),
  );
  return resultado ?? false;
}

class _AppConfirmDialog extends StatelessWidget {
  const _AppConfirmDialog({
    required this.titulo,
    required this.mensaje,
    required this.textoConfirmar,
    required this.textoCancelar,
  });

  final String titulo;
  final String mensaje;
  final String textoConfirmar;
  final String textoCancelar;

  @override
  Widget build(BuildContext context) {
    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 32),
      child: GlassCard(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: AppColors.rojo.withValues(alpha: 0.15),
                border: Border.all(color: AppColors.rojo.withValues(alpha: 0.4), width: 1.5),
              ),
              child: const Icon(Icons.help_outline_rounded, color: AppColors.rojo, size: 32),
            ),
            const SizedBox(height: 16),
            Text(titulo, textAlign: TextAlign.center, style: AppTextStyles.subtitulo(size: 17)),
            const SizedBox(height: 8),
            Text(mensaje, textAlign: TextAlign.center, style: AppTextStyles.cuerpo(size: 13)),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => Navigator.of(context).pop(false),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: Colors.white,
                      side: const BorderSide(color: AppColors.borde),
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: Text(textoCancelar),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => Navigator.of(context).pop(true),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.rojo,
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: Text(textoConfirmar, style: const TextStyle(fontWeight: FontWeight.w700)),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _AppAlertDialog extends StatelessWidget {
  const _AppAlertDialog({required this.tipo, required this.titulo, required this.mensaje});

  final AppDialogType tipo;
  final String titulo;
  final String mensaje;

  @override
  Widget build(BuildContext context) {
    final esExito = tipo == AppDialogType.exito;
    final color = esExito ? AppColors.verde : AppColors.rojo;

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 32),
      child: GlassCard(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withValues(alpha: 0.15),
                border: Border.all(color: color.withValues(alpha: 0.4), width: 1.5),
              ),
              child: Icon(
                esExito ? Icons.check_rounded : Icons.close_rounded,
                color: color,
                size: 34,
              ),
            ),
            const SizedBox(height: 16),
            Text(titulo, textAlign: TextAlign.center, style: AppTextStyles.subtitulo(size: 17)),
            const SizedBox(height: 8),
            Text(mensaje, textAlign: TextAlign.center, style: AppTextStyles.cuerpo(size: 13)),
            const SizedBox(height: 20),
            PrimaryButton(label: 'Entendido', onPressed: () => Navigator.of(context).pop()),
          ],
        ),
      ),
    );
  }
}
