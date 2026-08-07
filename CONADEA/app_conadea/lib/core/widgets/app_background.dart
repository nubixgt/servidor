import 'package:flutter/material.dart';

/// Fondo fijo de paisaje verde con degradado oscuro encima,
/// igual que `.fondo-app` en Frontend/src/style.css.
class AppBackground extends StatelessWidget {
  const AppBackground({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Image.asset(
          'assets/images/fondoC.png',
          fit: BoxFit.cover,
        ),
        DecoratedBox(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                const Color(0xFF081920).withValues(alpha: 0.55),
                const Color(0xFF06141A).withValues(alpha: 0.78),
              ],
            ),
          ),
        ),
        child,
      ],
    );
  }
}
