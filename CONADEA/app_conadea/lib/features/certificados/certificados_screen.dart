import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../data/mock/mock_cursos.dart';
import '../../data/state/progreso_controller.dart';
import '../cursos/certificado_dialog.dart';

/// Mis certificados — equivalente a Certificados.vue.
class CertificadosScreen extends StatelessWidget {
  const CertificadosScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = ProgresoController.instance;

    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: controller,
            builder: (context, _) {
              final aprobados = cursos.where((c) => controller.aprobado(c.id)).toList();

              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
                children: [
                  BackHeader(title: 'Mis certificados', subtitle: '${aprobados.length} de ${cursos.length}'),
                  const SizedBox(height: 10),
                  if (aprobados.isEmpty)
                    GlassCard(
                      child: Text(
                        'Aún no tienes certificados. Completa las lecciones de un curso y aprueba su evaluación para obtener el primero. 🎓',
                        style: AppTextStyles.cuerpo(size: 13),
                      ),
                    )
                  else
                    GlassCard(
                      child: Column(
                        children: [
                          for (final c in aprobados)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 10),
                              child: Row(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(14),
                                      gradient: LinearGradient(colors: AppColors.gradMod(c.id)),
                                    ),
                                    alignment: Alignment.center,
                                    child: const Text('🎓', style: TextStyle(fontSize: 22)),
                                  ),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(c.titulo, style: AppTextStyles.subtitulo(size: 13)),
                                        const SizedBox(height: 3),
                                        Text(
                                          'Aprobado con ${controller.progresoDe(c.id).nota}/${c.quiz.length} · ${controller.progresoDe(c.id).fecha}',
                                          style: AppTextStyles.cuerpo(size: 11),
                                        ),
                                      ],
                                    ),
                                  ),
                                  OutlinedButton(
                                    onPressed: () => mostrarCertificado(
                                      context,
                                      curso: c,
                                      nota: controller.progresoDe(c.id).nota ?? 0,
                                      total: c.quiz.length,
                                      fecha: controller.progresoDe(c.id).fecha ?? '',
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppColors.oro,
                                      side: const BorderSide(color: AppColors.oro),
                                    ),
                                    child: const Text('Ver certificado', style: TextStyle(fontSize: 12)),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    ),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}
