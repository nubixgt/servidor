import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/app_drawer.dart';
import 'usuarios_screen.dart';
import 'clientes_screen.dart';
import 'nueva_venta_screen.dart';
import 'ventas_screen.dart';
import 'productos_screen.dart';
import 'pagos_screen.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final usuario = AuthService().usuarioActual;

    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      drawer: const AppDrawer(),
      body: Stack(
        children: [
          // 1. Fondo Gradiente Fijo
          Container(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  Colors.green.shade900,
                  Colors.green.shade50,
                ],
                stops: const [0.0, 0.6],
              ),
            ),
          ),

          // 2. Contenido Desplazable (Scroll Completo)
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              // Banner y Bienvenida
              SliverToBoxAdapter(
                child: Column(
                  children: [
                    Stack(
                      children: [
                        ClipPath(
                          clipper: _CurvedHeaderClipper(),
                          child: Container(
                            height: 280, // Altura del banner
                            decoration: const BoxDecoration(
                              image: DecorationImage(
                                image: AssetImage('assets/images/BannerEmagro.png'),
                                fit: BoxFit.cover,
                                alignment: Alignment.topCenter,
                              ),
                            ),
                            child: Container(
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  begin: Alignment.topCenter,
                                  end: Alignment.bottomCenter,
                                  colors: [
                                    Colors.black.withOpacity(0.4),
                                    Colors.green.shade900.withOpacity(0.8),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          bottom: 40,
                          left: 24,
                          right: 24,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '¡Bienvenido!',
                                style: TextStyle(
                                  fontSize: 28,
                                  color: Colors.white.withOpacity(0.9),
                                  fontWeight: FontWeight.w300,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                usuario?.nombre ?? 'Administrador',
                                style: const TextStyle(
                                  fontSize: 32,
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text(
                                  usuario?.rol.toUpperCase() ?? 'USUARIO',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 24.0),
                      child: Row(
                        children: [
                          Text(
                            'Menú Principal',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.white.withOpacity(0.9),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),

              // Grid de Opciones
              SliverPadding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 0),
                sliver: SliverGrid.count(
                  crossAxisCount: 2,
                  crossAxisSpacing: 20,
                  mainAxisSpacing: 20,
                  childAspectRatio: 1.1,
                  children: [
                    if (usuario?.isAdmin ?? false)
                      _AnimatedMenuCard(
                        index: 0,
                        title: 'Usuarios',
                        icon: Icons.group_outlined,
                        color: const Color(0xFF9C27B0),
                        onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const UsuariosScreen())),
                      ),
                    
                    _AnimatedMenuCard(
                      index: 1,
                      title: 'Clientes',
                      icon: Icons.people_alt_outlined,
                      color: const Color(0xFF2196F3),
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ClientesScreen())),
                    ),

                    _AnimatedMenuCard(
                      index: 2,
                      title: 'Nueva Venta',
                      icon: Icons.shopping_cart_outlined,
                      color: const Color(0xFF4CAF50),
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NuevaVentaScreen())),
                    ),

                    _AnimatedMenuCard(
                      index: 3,
                      title: 'Ventas',
                      icon: Icons.receipt_long_outlined,
                      color: const Color(0xFFFF9800),
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen())),
                    ),

                    _AnimatedMenuCard(
                      index: 4,
                      title: 'Productos',
                      icon: Icons.inventory_2_outlined,
                      color: const Color(0xFF009688),
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductosScreen())),
                    ),

                    _AnimatedMenuCard(
                      index: 5,
                      title: 'Pagos',
                      icon: Icons.payment_outlined,
                      color: const Color(0xFFE91E63),
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PagosScreen())),
                    ),
                  ],
                ),
              ),
              
              const SliverPadding(padding: EdgeInsets.only(bottom: 40)),
            ],
          ),
        ],
      ),
    );
  }
}

class _AnimatedMenuCard extends StatelessWidget {
  final int index;
  final String title;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _AnimatedMenuCard({
    required this.index,
    required this.title,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: const Duration(milliseconds: 600),
      curve: Curves.easeOutBack,
      builder: (context, value, child) {
        return Transform.translate(
          offset: Offset(0, 50 * (1 - value)),
          child: Opacity(
            opacity: value.clamp(0.0, 1.0),
            child: child,
          ),
        );
      },
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: color.withOpacity(0.2),
                blurRadius: 15,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(
                  icon,
                  size: 32,
                  color: color,
                ),
              ),
              const SizedBox(height: 16),
              Text(
                title,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CurvedHeaderClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    final path = Path();
    path.lineTo(0, size.height - 40);
    path.quadraticBezierTo(
      size.width / 2,
      size.height,
      size.width,
      size.height - 40,
    );
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
