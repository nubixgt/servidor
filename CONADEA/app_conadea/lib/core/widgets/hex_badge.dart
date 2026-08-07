import 'package:flutter/material.dart';

/// Recorte hexagonal — equivalente al `clip-path: polygon(...)` de `.hexagono` en style.css.
class HexClipper extends CustomClipper<Path> {
  const HexClipper();

  @override
  Path getClip(Size size) {
    final w = size.width;
    final h = size.height;
    final path = Path()
      ..moveTo(w * 0.5, 0)
      ..lineTo(w, h * 0.25)
      ..lineTo(w, h * 0.75)
      ..lineTo(w * 0.5, h)
      ..lineTo(0, h * 0.75)
      ..lineTo(0, h * 0.25)
      ..close();
    return path;
  }

  @override
  bool shouldReclip(covariant CustomClipper<Path> oldClipper) => false;
}

/// Insignia hexagonal con emoji/ícono centrado.
class HexBadge extends StatelessWidget {
  const HexBadge({
    super.key,
    required this.emoji,
    required this.colors,
    this.size = 52,
    this.locked = false,
  });

  final String emoji;
  final List<Color> colors;
  final double size;
  final bool locked;

  @override
  Widget build(BuildContext context) {
    final child = ClipPath(
      clipper: const HexClipper(),
      child: Container(
        width: size,
        height: size * 1.12,
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: locked
                ? [Colors.white.withValues(alpha: 0.15), Colors.white.withValues(alpha: 0.05)]
                : colors,
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        alignment: Alignment.center,
        child: Text(
          locked ? '🔒' : emoji,
          style: TextStyle(fontSize: size * 0.36),
        ),
      ),
    );

    if (!locked) return child;
    return Opacity(opacity: 0.45, child: child);
  }
}
