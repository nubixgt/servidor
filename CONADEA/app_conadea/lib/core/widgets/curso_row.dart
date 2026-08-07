import 'package:flutter/material.dart';
import '../../data/models/curso.dart';
import '../theme/app_colors.dart';
import '../theme/app_text_styles.dart';
import 'progress_track.dart';

/// Fila de curso con miniatura + progreso — equivalente a `.curso-fila` en
/// Dashboard.vue / MisCursos.vue. El progreso se pasa explícito (viene de
/// ProgresoController) para que este widget no dependa de dónde se use.
class CursoRow extends StatelessWidget {
  const CursoRow({
    super.key,
    required this.curso,
    required this.pct,
    required this.completado,
    this.onTap,
  });

  final Curso curso;
  final int pct;
  final bool completado;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.network(
              curso.imagenUrl,
              width: 60,
              height: 46,
              fit: BoxFit.cover,
              errorBuilder: (_, _, _) => Container(
                width: 60,
                height: 46,
                color: AppColors.vidrio2,
                alignment: Alignment.center,
                child: Text(curso.icono, style: const TextStyle(fontSize: 20)),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  curso.titulo,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: AppTextStyles.subtitulo(size: 13.5),
                ),
                const SizedBox(height: 6),
                ProgressTrack(pct: pct),
                const SizedBox(height: 4),
                Text(
                  completado ? 'Completado' : '$pct% completado',
                  style: AppTextStyles.cuerpo(size: 11.5),
                ),
              ],
            ),
          ),
          const SizedBox(width: 10),
          _BotonContinuar(completado: completado, onTap: onTap),
        ],
      ),
    );
  }
}

class _BotonContinuar extends StatelessWidget {
  const _BotonContinuar({required this.completado, this.onTap});

  final bool completado;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final color = completado ? AppColors.oro : AppColors.verde;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          border: Border(
            top: BorderSide(color: color, width: 1.2),
            left: BorderSide(color: color, width: 1.2),
            right: BorderSide(color: color, width: 1.2),
            bottom: BorderSide(color: color, width: 3),
          ),
        ),
        child: Text(
          completado ? 'Repasar' : 'Continuar',
          style: AppTextStyles.etiqueta(size: 11, color: color).copyWith(letterSpacing: 0),
        ),
      ),
    );
  }
}
