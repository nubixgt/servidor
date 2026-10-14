import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';
import 'crear_registro_screen.dart';
import 'dashboard_screen.dart';
import 'registros_screen.dart';
import 'usuarios_screen.dart';

/// Pantalla principal con navegación inferior. Los apartados dependen del rol:
/// Usuarios solo lo ve el admin (igual que en la web).
class MainScreen extends StatefulWidget {
  const MainScreen({super.key});

  @override
  State<MainScreen> createState() => _MainScreenState();
}

class _MainScreenState extends State<MainScreen> {
  int _index = 0;

  Future<void> _logout() async {
    if (!await confirmDialog(context, 'Se cerrará tu sesión actual.', title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión')) return;
    if (!mounted) return;
    await context.read<AuthService>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthService>().user;
    if (user == null) return const SizedBox();

    final items = <NavItem>[
      const NavItem('dashboard', 'Dashboard', Icons.grid_view_rounded),
      const NavItem('crear', 'Crear registro', Icons.edit_outlined),
      const NavItem('registros', 'Registros', Icons.description_outlined),
      if (user.esAdmin) const NavItem('usuarios', 'Usuarios', Icons.people_outline),
    ];
    if (_index >= items.length) _index = 0;
    final activo = items[_index].key;

    Widget page(String key) {
      switch (key) {
        case 'dashboard':
          return DashboardScreen(user: user, onLogout: _logout, active: activo == key, onCrear: () => setState(() => _index = 1));
        case 'crear':
          return CrearRegistroScreen(user: user, onLogout: _logout);
        case 'registros':
          return RegistrosScreen(user: user, onLogout: _logout, active: activo == key, onCrear: () => setState(() => _index = 1));
        default:
          return UsuariosScreen(user: user, onLogout: _logout, active: activo == key);
      }
    }

    return Scaffold(
      body: AppBackground(
        child: Stack(children: [
          // El formulario conserva su estado al cambiar de pestaña.
          IndexedStack(index: _index, children: [for (final i in items) page(i.key)]),
          BottomNav(items: items, index: _index, onTap: (i) => setState(() => _index = i)),
        ]),
      ),
    );
  }
}
