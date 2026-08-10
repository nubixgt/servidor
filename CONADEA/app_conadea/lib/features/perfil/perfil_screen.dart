import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/progress_track.dart';
import '../../data/mock/mock_data.dart';
import '../../data/state/progreso_controller.dart';
import '../cursos/detalle_curso_screen.dart';

/// Mi perfil y avance — equivalente a Perfil.vue.
class PerfilScreen extends StatelessWidget {
  const PerfilScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = ProgresoController.instance;
    final r = resumenUsuario;

    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListenableBuilder(
            listenable: controller,
            builder: (context, _) {
              final leccionesHechas = controller.leccionesHechas(cursos);
              final totalLecciones = controller.totalLecciones(cursos);
              final certificados = controller.cursosCompletados(cursos);
              final pctGlobal = controller.pctGlobal(cursos);

              return ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
                children: [
                  const BackHeader(title: 'Mi perfil y avance'),
                  const SizedBox(height: 10),
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            Container(
                              width: 74,
                              height: 74,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: const LinearGradient(colors: [AppColors.verde, Color(0xFF16A34A)]),
                                border: Border.all(color: AppColors.oro, width: 3),
                              ),
                              alignment: Alignment.center,
                              child: Text(
                                r.nombre.isNotEmpty ? r.nombre[0].toUpperCase() : '?',
                                style: AppTextStyles.titulo(size: 26).copyWith(color: const Color(0xFF06281A)),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(r.nombre, style: AppTextStyles.titulo(size: 17)),
                                  const SizedBox(height: 6),
                                  Text(
                                    '🏢 Asociación pendiente de registro\n📍 Guatemala\n📅 En el programa desde junio 2026',
                                    style: AppTextStyles.cuerpo(size: 11.5),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 18),
                        GridView.count(
                          crossAxisCount: 2,
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          mainAxisSpacing: 12,
                          crossAxisSpacing: 12,
                          childAspectRatio: 1.5,
                          children: [
                            _StatCaja(valor: '$pctGlobal%', etiqueta: 'Avance'),
                            _StatCaja(valor: '$leccionesHechas/$totalLecciones', etiqueta: 'Lecciones'),
                            _StatCaja(valor: '$certificados', etiqueta: 'Certificados'),
                            _StatCaja(valor: '${r.rachaDias}', etiqueta: 'Días de racha'),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),
                  GlassCard(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Avance por curso', style: AppTextStyles.subtitulo(size: 15)),
                        const SizedBox(height: 10),
                        for (final m in cursos)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            child: InkWell(
                              onTap: () => Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => DetalleCursoScreen(curso: m)),
                              ),
                              child: Row(
                                children: [
                                  Text(m.icono, style: const TextStyle(fontSize: 20)),
                                  const SizedBox(width: 14),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(m.titulo, style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white)),
                                        const SizedBox(height: 5),
                                        ProgressTrack(pct: controller.pctCurso(m)),
                                      ],
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Text('${controller.pctCurso(m)}%', style: AppTextStyles.cuerpo(size: 11)),
                                ],
                              ),
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

class _StatCaja extends StatelessWidget {
  const _StatCaja({required this.valor, required this.etiqueta});

  final String valor;
  final String etiqueta;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.vidrio,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borde),
      ),
      alignment: Alignment.center,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(valor, style: AppTextStyles.titulo(size: 19).copyWith(color: AppColors.verde)),
          const SizedBox(height: 2),
          Text(etiqueta, style: AppTextStyles.etiqueta(size: 9.5)),
        ],
      ),
    );
  }
}
