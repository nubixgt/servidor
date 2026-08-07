import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../data/mock/mock_data.dart';

/// Calendario · Junio 2026 — equivalente a Calendario.vue.
class CalendarioScreen extends StatelessWidget {
  const CalendarioScreen({super.key});

  static const _diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

  @override
  Widget build(BuildContext context) {
    final eventosDias = eventos.map((e) => e.dia).toSet();

    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              const BackHeader(title: 'Calendario · Junio 2026'),
              const SizedBox(height: 10),
              GlassCard(
                child: Column(
                  children: [
                    GridView.count(
                      crossAxisCount: 7,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 6,
                      crossAxisSpacing: 6,
                      children: [
                        for (final d in _diasSemana)
                          Center(child: Text(d, style: AppTextStyles.etiqueta(size: 10))),
                        for (var dia = 1; dia <= 30; dia++)
                          _DiaCelda(
                            dia: dia,
                            evento: eventosDias.contains(dia),
                            hoy: dia == 9,
                          ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        '🟩 Días con actividades del programa · Borde azul: hoy',
                        style: AppTextStyles.cuerpo(size: 10.5),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 16),
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Próximas actividades', style: AppTextStyles.subtitulo(size: 15)),
                    const SizedBox(height: 10),
                    for (final e in eventos)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              width: 50,
                              padding: const EdgeInsets.symmetric(vertical: 8),
                              decoration: BoxDecoration(
                                color: AppColors.vidrio2,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.borde),
                              ),
                              child: Column(
                                children: [
                                  Text('${e.dia}', style: AppTextStyles.titulo(size: 16)),
                                  Text(e.mes, style: AppTextStyles.etiqueta(size: 8)),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('${e.icono} ${e.titulo}', style: AppTextStyles.cuerpo(size: 12.5, color: Colors.white)),
                                  const SizedBox(height: 3),
                                  Text(e.hora, style: AppTextStyles.cuerpo(size: 11)),
                                ],
                              ),
                            ),
                          ],
                        ),
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

class _DiaCelda extends StatelessWidget {
  const _DiaCelda({required this.dia, required this.evento, required this.hoy});

  final int dia;
  final bool evento;
  final bool hoy;

  @override
  Widget build(BuildContext context) {
    return Container(
      alignment: Alignment.center,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
        color: evento ? AppColors.verde.withValues(alpha: 0.15) : AppColors.vidrio,
        border: Border.all(
          color: hoy ? AppColors.azul : (evento ? AppColors.verde : AppColors.borde),
          width: hoy ? 2 : 1,
        ),
      ),
      child: Text(
        '$dia',
        style: AppTextStyles.cuerpo(
          size: 12,
          color: Colors.white,
          weight: evento || hoy ? FontWeight.w800 : FontWeight.w400,
        ),
      ),
    );
  }
}
