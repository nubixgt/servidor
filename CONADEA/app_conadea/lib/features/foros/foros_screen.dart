import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../data/mock/mock_data.dart';

/// Foros de productores — equivalente a Foros.vue.
class ForosScreen extends StatelessWidget {
  const ForosScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              const BackHeader(title: 'Foros de productores'),
              const SizedBox(height: 4),
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Text(
                  'Espacio de intercambio entre asociaciones y cooperativas participantes.',
                  style: AppTextStyles.cuerpo(size: 12.5),
                ),
              ),
              GlassCard(
                child: Column(
                  children: [
                    for (final f in foros)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: Row(
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: AppColors.vidrio2,
                                border: Border.all(color: AppColors.borde),
                              ),
                              alignment: Alignment.center,
                              child: Text(f.icono, style: const TextStyle(fontSize: 18)),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(f.titulo, style: AppTextStyles.subtitulo(size: 12.5)),
                                  const SizedBox(height: 3),
                                  Text(f.autor, style: AppTextStyles.cuerpo(size: 11)),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.04),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: AppColors.borde),
                              ),
                              child: Text.rich(
                                TextSpan(
                                  children: [
                                    TextSpan(
                                      text: '${f.respuestas} ',
                                      style: AppTextStyles.subtitulo(size: 12).copyWith(color: AppColors.verde),
                                    ),
                                    TextSpan(text: 'respuestas', style: AppTextStyles.cuerpo(size: 10)),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    const SizedBox(height: 8),
                    PrimaryButton(
                      label: '+ Nueva publicación',
                      onPressed: () {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Los foros estarán disponibles en la fase piloto del programa.'),
                            behavior: SnackBarBehavior.floating,
                          ),
                        );
                      },
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
