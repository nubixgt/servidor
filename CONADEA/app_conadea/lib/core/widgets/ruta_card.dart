import 'package:flutter/material.dart';
import '../../data/models/ruta.dart';
import '../theme/app_colors.dart';
import '../theme/app_text_styles.dart';
import 'progress_track.dart';

/// Tarjeta de ruta de aprendizaje — equivalente a `.ruta` en Dashboard.vue / Rutas.vue,
/// con una foto de fondo (como en la imagen de referencia) detrás del ícono.
class RutaCard extends StatelessWidget {
  const RutaCard({super.key, required this.ruta, this.imagenUrl, this.onTap});

  final RutaAprendizaje ruta;
  final String? imagenUrl;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final gradiente = ruta.color.gradiente;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        margin: const EdgeInsets.only(bottom: 14),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.vidrio,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: AppColors.borde),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _Icono(emoji: ruta.icono, gradiente: gradiente, imagenUrl: imagenUrl),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(ruta.titulo, style: AppTextStyles.subtitulo(size: 14.5)),
                  const SizedBox(height: 4),
                  Text(ruta.descripcion, style: AppTextStyles.cuerpo(size: 12)),
                  const SizedBox(height: 8),
                  Text(
                    '${ruta.pctCompletado}% completado · ${ruta.cursos.length} cursos',
                    style: AppTextStyles.etiqueta(size: 11, color: AppColors.lima)
                        .copyWith(letterSpacing: 0),
                  ),
                  const SizedBox(height: 6),
                  ProgressTrack(pct: ruta.pctCompletado),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Icono extends StatelessWidget {
  const _Icono({required this.emoji, required this.gradiente, this.imagenUrl});

  final String emoji;
  final List<Color> gradiente;
  final String? imagenUrl;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(14),
      child: Container(
        width: 60,
        height: 60,
        decoration: BoxDecoration(
          gradient: LinearGradient(colors: gradiente, begin: Alignment.topLeft, end: Alignment.bottomRight),
        ),
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (imagenUrl != null)
              Image.network(
                imagenUrl!,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) => const SizedBox.shrink(),
                color: Colors.black.withValues(alpha: 0.15),
                colorBlendMode: BlendMode.darken,
              ),
            Center(child: Text(emoji, style: const TextStyle(fontSize: 24))),
          ],
        ),
      ),
    );
  }
}
