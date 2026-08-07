import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/ruta_card.dart';
import '../../core/widgets/top_header.dart';
import '../../data/mock/mock_data.dart';

/// Pantalla "Rutas de aprendizaje" — equivalente a Rutas.vue.
class RutasScreen extends StatelessWidget {
  const RutasScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 110),
      children: [
        const TopHeader(title: 'Rutas de aprendizaje'),
        Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: Text(
            'Rutas disponibles',
            style: AppTextStyles.etiqueta(size: 12, color: AppColors.textoSuave),
          ),
        ),
        for (final r in rutas) RutaCard(ruta: r, imagenUrl: r.cursos.first.imagenUrl),
      ],
    );
  }
}
