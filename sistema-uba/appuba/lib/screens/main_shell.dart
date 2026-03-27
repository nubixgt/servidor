import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'home_screen.dart';
import 'report_screen.dart';
import 'news_screen.dart';
import 'denuncias_screen.dart';
import 'denuncia_form_screen.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _selectedIndex = 0;

  final List<Widget> _screens = [
    const HomeScreen(),
    const ReportScreen(),
    const NewsScreen(),
    const DenunciasScreen(), // Now labeled as Denuncias
  ];

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    bool isDesktop = MediaQuery.of(context).size.width > 900;

    return Scaffold(
      appBar: (_selectedIndex == 0 || _selectedIndex == 1 || _selectedIndex == 3)
          ? null
          : (isDesktop ? _buildDesktopAppBar() : _buildMobileAppBar()),
      body: _screens[_selectedIndex],
      bottomNavigationBar: isDesktop ? null : _buildBottomNav(),
    );
  }

  PreferredSizeWidget _buildMobileAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      centerTitle: false,
      title: Text(
        'AppUBA',
        style: TextStyle(
          fontFamily: 'Plus Jakarta Sans',
          fontWeight: FontWeight.w900,
          color: Colors.blue.shade900,
          letterSpacing: -1,
        ),
      ),
    );
  }

  PreferredSizeWidget _buildDesktopAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      title: Row(
        children: [
          const Icon(Icons.shield, color: AppColors.primary, size: 32),
          const SizedBox(width: 12),
          Text(
            'AppUBA',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.w900,
              color: Colors.blue.shade900,
            ),
          ),
          const Spacer(),
          _buildDesktopNavItem('Inicio', 0),
          _buildDesktopNavItem('Denuncia', 1),
          _buildDesktopNavItem('Noticias', 2),
          _buildDesktopNavItem('Informacion', 3),
          const Spacer(),
          const CircleAvatar(
            radius: 20,
            child: Icon(Icons.person),
          ),
        ],
      ),
    );
  }

  Widget _buildDesktopNavItem(String label, int index) {
    bool active = _selectedIndex == index;
    return TextButton(
      onPressed: () => _onItemTapped(index),
      child: Text(
        label,
        style: TextStyle(
          color: active ? Colors.blue.shade900 : const Color(0xFF64748B),
          fontWeight: active ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      padding: const EdgeInsets.only(bottom: 24, left: 16, right: 16, top: 12),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.7),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            offset: const Offset(0, -8),
            blurRadius: 32,
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildNavItem(Icons.dashboard, 'Inicio', 0),
          _buildNavItem(Icons.campaign, 'Denuncia', 1),
          _buildNavItem(Icons.newspaper, 'Noticias', 2),
          _buildNavItem(Icons.gavel, 'Informacion', 3),
        ],
      ),
    );
  }

  Widget _buildNavItem(IconData icon, String label, int index) {
    bool active = _selectedIndex == index;
    bool isDenuncia = index == 1;
    
    Color activeColor = isDenuncia ? const Color(0xFFC00000) : Colors.blue.shade800; // Solid Red for Denuncia
    Color inactiveColor = isDenuncia ? const Color(0xFFC00000).withOpacity(0.7) : const Color(0xFF94A3B8);
    Color shadowColor = isDenuncia ? Colors.red.shade200 : Colors.blue.shade200;

    return GestureDetector(
      onTap: () => _onItemTapped(index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 300),
        padding: EdgeInsets.symmetric(
            horizontal: active ? 20 : 12, vertical: active ? 8 : 8),
        decoration: BoxDecoration(
          color: active ? activeColor : Colors.transparent,
          borderRadius: BorderRadius.circular(16),
          boxShadow: active
              ? [
                  BoxShadow(
                      color: shadowColor,
                      offset: const Offset(0, 4),
                      blurRadius: 10)
                ]
              : [],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: active ? Colors.white : inactiveColor, size: 24),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: active ? Colors.white : inactiveColor,
                letterSpacing: 1.1,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

// Extension removed as it was incorrect and Icons.campaign is used directly
