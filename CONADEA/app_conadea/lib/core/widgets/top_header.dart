import 'package:flutter/material.dart';
import '../theme/app_colors.dart';
import '../theme/app_text_styles.dart';

/// Encabezado simple de pantalla — título + acción opcional (buscar),
/// como en las pantallas "Mis cursos / Rutas / Insignias" de la referencia.
class TopHeader extends StatelessWidget {
  const TopHeader({super.key, required this.title, this.onSearch});

  final String title;
  final VoidCallback? onSearch;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 6),
      child: Row(
        children: [
          Expanded(child: Text(title, style: AppTextStyles.titulo(size: 21))),
          if (onSearch != null)
            _CircleIconButton(icon: Icons.search_rounded, onTap: onSearch!),
        ],
      ),
    );
  }
}

class _CircleIconButton extends StatelessWidget {
  const _CircleIconButton({required this.icon, required this.onTap});

  final IconData icon;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkResponse(
      onTap: onTap,
      radius: 24,
      child: Container(
        width: 40,
        height: 40,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: AppColors.vidrio2,
          border: Border.all(color: AppColors.borde),
        ),
        child: Icon(icon, color: Colors.white, size: 20),
      ),
    );
  }
}
