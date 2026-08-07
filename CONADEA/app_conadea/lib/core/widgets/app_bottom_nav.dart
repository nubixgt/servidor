import 'dart:ui';
import 'package:flutter/material.dart';
import '../theme/app_colors.dart';
import '../theme/app_text_styles.dart';

class NavItem {
  const NavItem({required this.icon, required this.activeIcon, required this.label});
  final IconData icon;
  final IconData activeIcon;
  final String label;
}

/// Barra inferior de 5 apartados: 4 destinos + botón central elevado
/// (acceso rápido al asistente AgroIA / WhatsApp). Estructura tomada de la
/// imagen de referencia de la app, con el estilo glass del Frontend web.
class AppBottomNav extends StatelessWidget {
  const AppBottomNav({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onTap,
    required this.onCenterTap,
  });

  final List<NavItem> items;
  final int currentIndex;
  final ValueChanged<int> onTap;
  final VoidCallback onCenterTap;

  @override
  Widget build(BuildContext context) {
    final left = items.sublist(0, 2);
    final right = items.sublist(2, 4);

    return SafeArea(
      top: false,
      child: SizedBox(
        height: 78,
        child: Stack(
          clipBehavior: Clip.none,
          alignment: Alignment.bottomCenter,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(26),
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
                child: Container(
                  height: 66,
                  margin: const EdgeInsets.symmetric(horizontal: 14),
                  decoration: BoxDecoration(
                    color: AppColors.vidrio,
                    borderRadius: BorderRadius.circular(26),
                    border: Border.all(color: AppColors.borde),
                    boxShadow: const [
                      BoxShadow(color: Color(0x4D000000), blurRadius: 24, offset: Offset(0, 8)),
                    ],
                  ),
                  child: Row(
                    children: [
                      for (final entry in left.asMap().entries)
                        _NavButton(
                          item: entry.value,
                          active: currentIndex == entry.key,
                          onTap: () => onTap(entry.key),
                        ),
                      const SizedBox(width: 66),
                      for (final entry in right.asMap().entries)
                        _NavButton(
                          item: entry.value,
                          active: currentIndex == entry.key + 2,
                          onTap: () => onTap(entry.key + 2),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              bottom: 30,
              child: GestureDetector(
                onTap: onCenterTap,
                child: Container(
                  width: 58,
                  height: 58,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(
                      colors: [AppColors.verde, AppColors.verdeFuerte],
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                    ),
                    border: Border.all(color: AppColors.fondoBase, width: 4),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.verdeFuerte.withValues(alpha: 0.55),
                        blurRadius: 18,
                        offset: const Offset(0, 6),
                      ),
                    ],
                  ),
                  child: const Icon(Icons.eco_rounded, color: Color(0xFF06281A), size: 28),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({required this.item, required this.active, required this.onTap});

  final NavItem item;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              active ? item.activeIcon : item.icon,
              size: 22,
              color: active ? AppColors.verde : AppColors.textoSuave,
            ),
            const SizedBox(height: 3),
            Text(
              item.label,
              style: AppTextStyles.etiqueta(
                size: 10.5,
                color: active ? AppColors.verde : AppColors.textoSuave,
              ).copyWith(letterSpacing: 0, fontWeight: active ? FontWeight.w800 : FontWeight.w600),
            ),
          ],
        ),
      ),
    );
  }
}
