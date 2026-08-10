import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/app_bottom_nav.dart';
import '../../core/widgets/app_dialog.dart';
import '../../data/models/usuario_sesion.dart';
import '../../data/services/auth_service.dart';
import '../admin/crear_curso_screen.dart';
import '../asistente/asistente_screen.dart';
import '../auth/login_screen.dart';
import '../ayuda/ayuda_screen.dart';
import '../calendario/calendario_screen.dart';
import '../certificados/certificados_screen.dart';
import '../configuracion/configuracion_screen.dart';
import '../cursos/cursos_screen.dart';
import '../foros/foros_screen.dart';
import '../home/home_screen.dart';
import '../insignias/insignias_screen.dart';
import '../perfil/perfil_screen.dart';
import '../rutas/rutas_screen.dart';

/// Shell principal de la app: 4 pestañas (Inicio, Cursos, Rutas, Insignias)
/// + botón central elevado que abre el acceso rápido a AgroIA/WhatsApp,
/// y un menú lateral (equivalente a Sidebar.vue) para el resto de opciones.
class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _index = 0;

  static const _items = [
    NavItem(icon: Icons.home_outlined, activeIcon: Icons.home_rounded, label: 'Inicio'),
    NavItem(icon: Icons.menu_book_outlined, activeIcon: Icons.menu_book_rounded, label: 'Cursos'),
    NavItem(icon: Icons.route_outlined, activeIcon: Icons.route_rounded, label: 'Rutas'),
    NavItem(icon: Icons.military_tech_outlined, activeIcon: Icons.military_tech_rounded, label: 'Insignias'),
  ];

  void _irA(int i) => setState(() => _index = i);

  void _abrirAsistente() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const AsistenteScreen(),
    );
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      HomeScreen(
        onVerCursos: () => _irA(1),
        onVerRutas: () => _irA(2),
        onVerInsignias: () => _irA(3),
      ),
      const CursosScreen(),
      const RutasScreen(),
      const InsigniasScreen(),
    ];

    return Scaffold(
      extendBody: true,
      drawer: const _AppDrawer(),
      body: AppBackground(
        child: SafeArea(
          bottom: false,
          child: IndexedStack(index: _index, children: screens),
        ),
      ),
      bottomNavigationBar: AppBottomNav(
        items: _items,
        currentIndex: _index,
        onTap: _irA,
        onCenterTap: _abrirAsistente,
      ),
    );
  }
}

class _AppDrawer extends StatefulWidget {
  const _AppDrawer();

  @override
  State<_AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<_AppDrawer> {
  final _authService = AuthService();
  UsuarioSesion? _sesion;

  @override
  void initState() {
    super.initState();
    _authService.obtenerSesionGuardada().then((sesion) {
      if (mounted) setState(() => _sesion = sesion);
    });
  }

  List<(IconData, String, WidgetBuilder?)> get _opciones => [
        if (_sesion?.rol == 'Administrador')
          (Icons.add_box_outlined, 'Crear curso', (_) => const CrearCursoScreen()),
        (Icons.person_outline_rounded, 'Mi perfil', (_) => const PerfilScreen()),
        (Icons.event_note_outlined, 'Calendario', (_) => const CalendarioScreen()),
        (Icons.campaign_outlined, 'Novedades', null),
        (Icons.workspace_premium_outlined, 'Certificados', (_) => const CertificadosScreen()),
        (Icons.forum_outlined, 'Foros', (_) => const ForosScreen()),
        (Icons.settings_outlined, 'Configuración', (_) => const ConfiguracionScreen()),
        (Icons.help_outline_rounded, 'Ayuda y soporte', (_) => const AyudaScreen()),
      ];

  @override
  Widget build(BuildContext context) {
    return Drawer(
      backgroundColor: AppColors.fondoBase,
      child: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const SizedBox(height: 18),
            Center(
              child: Column(
                children: [
                  Image.asset('assets/images/logo_agroia.png', width: 76, height: 76),
                  const SizedBox(height: 10),
                  Text(
                    'MINISTERIO DE AGRICULTURA,\nGANADERÍA Y ALIMENTACIÓN',
                    textAlign: TextAlign.center,
                    style: AppTextStyles.etiqueta(size: 9.5),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'JUNTOS PARA ALIMENTAR GUATEMALA',
                    textAlign: TextAlign.center,
                    style: AppTextStyles.etiqueta(size: 8.5, color: AppColors.textoSuave.withValues(alpha: 0.6)),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 8),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Divider(color: AppColors.borde),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                children: [
                  for (final o in _opciones)
                    ListTile(
                      leading: Icon(o.$1, color: AppColors.textoSuave, size: 20),
                      title: Text(o.$2, style: AppTextStyles.cuerpo(size: 13.5, color: Colors.white)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      onTap: () {
                        Navigator.of(context).pop();
                        final builder = o.$3;
                        if (builder == null) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Próximamente'), behavior: SnackBarBehavior.floating),
                          );
                          return;
                        }
                        Navigator.of(context).push(MaterialPageRoute(builder: builder));
                      },
                    ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              child: ListTile(
                leading: const Icon(Icons.logout_rounded, color: AppColors.rojo, size: 20),
                title: Text('Cerrar sesión', style: AppTextStyles.cuerpo(size: 13.5, color: AppColors.rojo)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                onTap: () async {
                  final confirmar = await mostrarConfirmacion(
                    context,
                    titulo: '¿Cerrar sesión?',
                    mensaje: '¿Seguro que quieres salir de tu cuenta?',
                    textoConfirmar: 'Sí, salir',
                    textoCancelar: 'No',
                  );
                  if (!confirmar) return;

                  await _authService.cerrarSesion();
                  if (!context.mounted) return;
                  Navigator.of(context).pushAndRemoveUntil(
                    MaterialPageRoute(builder: (_) => const LoginScreen()),
                    (route) => false,
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
