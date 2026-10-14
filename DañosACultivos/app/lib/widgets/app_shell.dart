import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/usuario.dart';
import '../theme/app_theme.dart';
import 'common.dart';

class HeroInfo {
  final String title;
  final String subtitle;
  const HeroInfo(this.title, this.subtitle);
}

const kHeroes = {
  'dashboard': HeroInfo('Un campo mejor protegido\nempieza con información.', 'Registra, analiza y da seguimiento a las afectaciones en cultivos desde un solo lugar.'),
  'crear': HeroInfo('Cada detalle cuenta.\nRegistra lo que ocurre en campo.', 'Ubicación, cultivo, daños y evidencia: toda la información en un proceso claro.'),
  'registros': HeroInfo('La historia del campo,\nen cada registro.', 'Explora las afectaciones reportadas y encuentra la información que necesitas.'),
  'usuarios': HeroInfo('Un equipo conectado\ncon el campo.', 'Organiza a las personas que convierten las observaciones en información útil.'),
};

/// Contenedor de cada pantalla: barra superior, portada y contenido desplazable.
class PageScroll extends StatelessWidget {
  final String heroKey;
  final AuthUser user;
  final VoidCallback onLogout;
  final List<Widget> children;
  final Future<void> Function()? onRefresh;
  const PageScroll({super.key, required this.heroKey, required this.user, required this.onLogout, required this.children, this.onRefresh});

  @override
  Widget build(BuildContext context) {
    final hero = kHeroes[heroKey]!;
    final list = ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.fromLTRB(13, MediaQuery.of(context).padding.top + 10, 13, 110 + MediaQuery.of(context).padding.bottom),
      children: [
        _TopBar(onLogout: onLogout),
        const SizedBox(height: 12),
        _Hero(info: hero),
        const SizedBox(height: 18),
        ...children,
        const SizedBox(height: 22),
        const Text('Daños a Cultivos · Ministerio de Agricultura, Ganadería y Alimentación', style: TextStyle(fontSize: 8, color: Color(0xFFCFE3D5), height: 1.7)),
      ],
    );
    return onRefresh == null ? list : RefreshIndicator(color: AppColors.green, onRefresh: onRefresh!, child: list);
  }
}

class _TopBar extends StatelessWidget {
  final VoidCallback onLogout;
  const _TopBar({required this.onLogout});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 44,
      child: Row(children: [
        const Text('MAGA', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800, letterSpacing: -1, color: Colors.white)),
        Container(width: 1, height: 26, margin: const EdgeInsets.symmetric(horizontal: 8), color: const Color(0x66FFFFFF)),
        const Expanded(child: Text('Ministerio de Agricultura,\nGanadería y Alimentación', style: TextStyle(fontSize: 8, height: 1.4, color: Colors.white))),
        InkWell(
          onTap: onLogout,
          borderRadius: BorderRadius.circular(10),
          child: Container(
            padding: const EdgeInsets.all(9),
            decoration: BoxDecoration(color: const Color(0x21FFFFFF), borderRadius: BorderRadius.circular(10), border: Border.all(color: const Color(0x55FFFFFF))),
            child: const Icon(Icons.logout, size: 18, color: Colors.white),
          ),
        ),
      ]),
    );
  }
}

class _Hero extends StatelessWidget {
  final HeroInfo info;
  const _Hero({required this.info});

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(20),
      child: Container(
        constraints: const BoxConstraints(minHeight: 203),
        decoration: BoxDecoration(border: Border.all(color: const Color(0x66FFFFFF)), borderRadius: BorderRadius.circular(20), color: const Color(0xFF0B423B)),
        child: Stack(children: [
          Positioned.fill(child: Image.asset('assets/images/fondo.jpg', fit: BoxFit.cover, alignment: const Alignment(0, -.1))),
          Positioned.fill(
            child: DecoratedBox(
              decoration: const BoxDecoration(gradient: LinearGradient(begin: Alignment.centerLeft, end: Alignment.centerRight, colors: [Color(0xEB03272C), Color(0x8503272C), Color(0x0F03272C)], stops: [0, .57, 1])),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(23),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
              Row(children: [
                Container(width: 18, height: 1, color: const Color(0xFFC0E9AB)),
                const SizedBox(width: 8),
                const Text('SISTEMA DE INFORMACIÓN AGRÍCOLA', style: TextStyle(fontSize: 8, letterSpacing: 1.2, fontWeight: FontWeight.w600, color: Color(0xFFD0F1D9))),
              ]),
              const SizedBox(height: 11),
              Text(info.title, style: const TextStyle(fontSize: 28, height: 1.13, letterSpacing: -1, fontWeight: FontWeight.w600, color: Colors.white)),
              const SizedBox(height: 12),
              Text(info.subtitle, style: const TextStyle(fontSize: 11, height: 1.5, color: Color(0xFFE0EFE7))),
            ]),
          ),
        ]),
      ),
    );
  }
}

class NavItem {
  final String key;
  final String label;
  final IconData icon;
  const NavItem(this.key, this.label, this.icon);
}

/// Barra de navegación flotante inferior (equivale al .sidebar en móvil).
class BottomNav extends StatelessWidget {
  final List<NavItem> items;
  final int index;
  final ValueChanged<int> onTap;
  const BottomNav({super.key, required this.items, required this.index, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Positioned(
      left: 12,
      right: 12,
      bottom: 10 + MediaQuery.of(context).padding.bottom,
      child: Container(
        padding: const EdgeInsets.all(7),
        decoration: BoxDecoration(
          color: const Color(0xEB0B493E),
          borderRadius: BorderRadius.circular(20),
          boxShadow: const [BoxShadow(color: Color(0x44073027), blurRadius: 35, offset: Offset(0, 5))],
        ),
        child: Row(children: [
          for (var i = 0; i < items.length; i++)
            Expanded(
              child: InkWell(
                borderRadius: BorderRadius.circular(13),
                onTap: () => onTap(i),
                child: Container(
                  padding: const EdgeInsets.symmetric(vertical: 9),
                  decoration: i == index
                      ? BoxDecoration(
                          gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xFF01975B), Color(0xFF007A57)]),
                          borderRadius: BorderRadius.circular(13),
                          boxShadow: const [BoxShadow(color: Color(0x25002D29), blurRadius: 20, offset: Offset(0, 5))])
                      : null,
                  child: Column(mainAxisSize: MainAxisSize.min, children: [
                    Icon(items[i].icon, size: 20, color: i == index ? Colors.white : const Color(0xFFD2E7E0)),
                    const SizedBox(height: 4),
                    Text(items[i].label, maxLines: 1, overflow: TextOverflow.ellipsis, style: TextStyle(fontSize: 9, color: i == index ? Colors.white : const Color(0xFFD2E7E0))),
                  ]),
                ),
              ),
            ),
        ]),
      ),
    );
  }
}

String fechaLarga(String f) {
  try {
    return DateFormat('d MMM y', 'es').format(DateTime.parse(f));
  } catch (_) {
    return f;
  }
}

String fechaCorta(String f) {
  try {
    return DateFormat('dd MMM y', 'es').format(DateTime.parse(f));
  } catch (_) {
    return f;
  }
}

/// Aviso si la lista está vacía o hay error.
Widget loadingOrEmpty({required bool loading, required String emptyText}) =>
    loading ? const Padding(padding: EdgeInsets.all(36), child: Center(child: CircularProgressIndicator(color: AppColors.green))) : EmptyState(emptyText);
