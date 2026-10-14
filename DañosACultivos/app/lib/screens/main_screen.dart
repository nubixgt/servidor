import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../services/auth_service.dart';
import '../services/offline_queue.dart';
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

class _MainScreenState extends State<MainScreen> with WidgetsBindingObserver {
  int _index = 0;
  final _queue = OfflineQueue.instance;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    final user = context.read<AuthService>().user;
    if (user != null) _queue.iniciar(user.id);
    _queue.addListener(_onQueue);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _queue.removeListener(_onQueue);
    super.dispose();
  }

  // Al volver a abrir la app se intenta enviar lo pendiente.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed && _queue.pendientes > 0) _queue.sincronizar();
  }

  void _onQueue() {
    final aviso = _queue.aviso;
    if (aviso != null && mounted) {
      _queue.aviso = null;
      showMsg(context, aviso);
    }
  }

  Future<void> _logout() async {
    final pend = _queue.pendientes;
    final msg = pend > 0
        ? 'Tienes $pend registro(s) sin enviar. Se conservarán en este teléfono y se enviarán cuando vuelvas a iniciar sesión con este usuario.'
        : 'Se cerrará tu sesión actual.';
    if (!await confirmDialog(context, msg, title: '¿Cerrar sesión?', confirmText: 'Cerrar sesión')) return;
    if (!mounted) return;
    _queue.detener();
    await context.read<AuthService>().logout();
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthService>().user;
    if (user == null) return const SizedBox();
    final pendientes = context.watch<OfflineQueue>().pendientes;

    final items = <NavItem>[
      const NavItem('dashboard', 'Dashboard', Icons.grid_view_rounded),
      const NavItem('crear', 'Crear registro', Icons.edit_outlined),
      NavItem('registros', 'Registros', Icons.description_outlined, badge: pendientes),
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
