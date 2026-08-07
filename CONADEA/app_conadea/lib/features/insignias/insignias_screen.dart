import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/hex_badge.dart';
import '../../core/widgets/progress_track.dart';
import '../../core/widgets/section_header.dart';
import '../../core/widgets/top_header.dart';
import '../../data/mock/mock_data.dart';
import '../../data/models/insignia.dart';

/// Pantalla "Insignias" — equivalente a Insignias.vue, con el resumen
/// "Tu progreso" de la imagen de referencia.
class InsigniasScreen extends StatelessWidget {
  const InsigniasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final obtenidas = insignias.where((i) => i.obtenida).toList();
    final pendientes = insignias.where((i) => !i.obtenida).toList();
    final pct = insignias.isEmpty ? 0 : (obtenidas.length / insignias.length * 100).round();

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 110),
      children: [
        const TopHeader(title: 'Insignias'),
        const SizedBox(height: 12),
        GlassCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Tu progreso', style: AppTextStyles.subtitulo(size: 14)),
              const SizedBox(height: 12),
              Row(
                children: [
                  Text('${obtenidas.length}', style: AppTextStyles.titulo(size: 34)),
                  const SizedBox(width: 8),
                  Padding(
                    padding: const EdgeInsets.only(bottom: 6),
                    child: Text('Insignias\nobtenidas', style: AppTextStyles.cuerpo(size: 11.5)),
                  ),
                  const Spacer(),
                  Text('¡Sigue así,\nvas por buen camino!',
                      textAlign: TextAlign.right,
                      style: AppTextStyles.etiqueta(size: 11, color: AppColors.verde).copyWith(letterSpacing: 0)),
                ],
              ),
              const SizedBox(height: 10),
              ProgressTrack(pct: pct),
            ],
          ),
        ),
        const SizedBox(height: 22),
        SectionHeader(title: 'Insignias recientes'),
        Row(
          children: [
            for (final b in obtenidas.take(3))
              Padding(
                padding: const EdgeInsets.only(right: 14),
                child: Column(
                  children: [
                    HexBadge(emoji: b.icono, colors: b.color.gradiente, size: 56),
                    const SizedBox(height: 6),
                    SizedBox(
                      width: 68,
                      child: Text(
                        b.titulo,
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: AppTextStyles.cuerpo(size: 10.5, color: Colors.white),
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
        const SizedBox(height: 24),
        Text('Próximas insignias', style: AppTextStyles.titulo(size: 17)),
        const SizedBox(height: 10),
        GlassCard(
          child: Column(
            children: [
              for (final b in pendientes) _FilaBloqueada(insignia: b),
            ],
          ),
        ),
      ],
    );
  }
}

class _FilaBloqueada extends StatelessWidget {
  const _FilaBloqueada({required this.insignia});

  final Insignia insignia;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        children: [
          HexBadge(emoji: insignia.icono, colors: insignia.color.gradiente, size: 40, locked: true),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(insignia.titulo, style: AppTextStyles.subtitulo(size: 13)),
                Text(insignia.descripcion, style: AppTextStyles.cuerpo(size: 11.5)),
              ],
            ),
          ),
          Icon(Icons.lock_outline_rounded, size: 18, color: AppColors.textoSuave),
        ],
      ),
    );
  }
}
