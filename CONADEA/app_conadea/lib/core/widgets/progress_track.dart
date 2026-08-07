import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

/// Barra de progreso — equivalente a `.pista` / `.pista-fill` en style.css.
class ProgressTrack extends StatelessWidget {
  const ProgressTrack({super.key, required this.pct, this.height = 8});

  /// 0-100
  final int pct;
  final double height;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(height),
      child: Container(
        height: height,
        color: Colors.white.withValues(alpha: 0.14),
        child: FractionallySizedBox(
          alignment: Alignment.centerLeft,
          widthFactor: (pct.clamp(0, 100)) / 100,
          child: Container(
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.lima, AppColors.verdeFuerte],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
