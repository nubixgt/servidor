import 'dart:ui';
import 'package:flutter/material.dart';
import '../theme/app_colors.dart';

/// Tarjeta de vidrio esmerilado — equivalente a `.vidrio` en Frontend/src/style.css.
class GlassCard extends StatelessWidget {
  const GlassCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(18),
    this.borderColor,
    this.margin,
  });

  final Widget child;
  final EdgeInsetsGeometry padding;
  final Color? borderColor;
  final EdgeInsetsGeometry? margin;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: margin,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(AppColors.radio),
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
          child: Container(
            padding: padding,
            decoration: BoxDecoration(
              color: AppColors.vidrio,
              borderRadius: BorderRadius.circular(AppColors.radio),
              border: Border.all(color: borderColor ?? AppColors.borde),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x38000000),
                  blurRadius: 32,
                  offset: Offset(0, 8),
                ),
              ],
            ),
            child: child,
          ),
        ),
      ),
    );
  }
}
