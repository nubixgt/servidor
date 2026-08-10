import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/widgets/app_dialog.dart';
import '../../data/services/api_exception.dart';
import '../../data/services/curso_service.dart';
import 'detalle_curso_screen.dart';

/// GET /cursos (listado) no manda lecciones/quiz — antes de abrir el
/// detalle de un curso real hay que pedir GET /cursos/{id} completo.
/// Muestra un loader mientras carga y navega al terminar.
Future<void> abrirDetalleCurso(BuildContext context, CursoService service, int id) async {
  showDialog<void>(
    context: context,
    barrierDismissible: false,
    builder: (_) => const Center(child: CircularProgressIndicator(color: AppColors.verde)),
  );

  try {
    final completo = await service.obtenerCurso(id);
    if (!context.mounted) return;
    Navigator.of(context).pop(); // cierra el loader
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => DetalleCursoScreen(curso: completo)),
    );
  } on ApiException catch (e) {
    if (!context.mounted) return;
    Navigator.of(context).pop(); // cierra el loader
    await mostrarAlerta(context, tipo: AppDialogType.error, titulo: 'No se pudo abrir el curso', mensaje: e.message);
  }
}
