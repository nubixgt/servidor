import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../screens/login_screen.dart';
import '../screens/usuarios_screen.dart';
import '../screens/home_screen.dart';
import '../screens/clientes_screen.dart';
import '../screens/nueva_venta_screen.dart';
import '../screens/ventas_screen.dart';
import '../screens/productos_screen.dart';
import '../screens/inventario_screen.dart';
import '../screens/pagos_screen.dart';

class AppDrawer extends StatelessWidget {
  const AppDrawer({Key? key}) : super(key: key);

  Future<void> _handleLogout(BuildContext context) async {
    final navigator = Navigator.of(context);
    
    final confirmed = await showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (BuildContext dialogContext) {
        return AlertDialog(
          title: const Text('Cerrar Sesión'),
          content: const Text('¿Está seguro que desea cerrar sesión?'),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(dialogContext).pop(true),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.redAccent,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Cerrar Sesión'),
            ),
          ],
        );
      },
    );

    if (confirmed == true) {
      await AuthService().logout();
      navigator.pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()),
        (route) => false,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final usuario = AuthService().usuarioActual;
    final isAdmin = usuario?.isAdmin ?? false;

    return Drawer(
      child: Container(
        color: Colors.white,
        child: Column(
          children: [
            // Minimalist Header
            UserAccountsDrawerHeader(
              decoration: BoxDecoration(
                color: Colors.green.shade700,
              ),
              currentAccountPicture: CircleAvatar(
                backgroundColor: Colors.white,
                child: Text(
                  usuario?.nombre.substring(0, 1).toUpperCase() ?? 'U',
                  style: TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.bold,
                    color: Colors.green.shade700,
                  ),
                ),
              ),
              accountName: Text(
                usuario?.nombre ?? 'Usuario',
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              accountEmail: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  usuario?.rol.toUpperCase() ?? '',
                  style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold),
                ),
              ),
            ),

            // Opciones del menú
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(vertical: 8),
                children: [
                  _buildDrawerItem(
                    context,
                    icon: Icons.home_outlined,
                    title: 'Inicio',
                    color: Colors.grey.shade700,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.pushAndRemoveUntil(
                        context,
                        MaterialPageRoute(builder: (_) => const HomeScreen()),
                        (route) => false,
                      );
                    },
                  ),

                  _buildDivider(),

                  // Sección de Admin
                  if (isAdmin) ...[
                    _buildSectionTitle('ADMINISTRACIÓN'),
                    _buildDrawerItem(
                      context,
                      icon: Icons.people_outline,
                      title: 'Usuarios',
                      color: Colors.purple,
                      onTap: () {
                        Navigator.pop(context);
                        Navigator.push(context, MaterialPageRoute(builder: (_) => const UsuariosScreen()));
                      },
                    ),
                  ],

                  _buildSectionTitle('OPERACIONES'),
                  _buildDrawerItem(
                    context,
                    icon: Icons.group_outlined,
                    title: 'Clientes',
                    color: Colors.blue,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ClientesScreen()));
                    },
                  ),
                  _buildDrawerItem(
                    context,
                    icon: Icons.add_shopping_cart,
                    title: 'Nueva Venta',
                    color: Colors.green,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const NuevaVentaScreen()));
                    },
                  ),
                  _buildDrawerItem(
                    context,
                    icon: Icons.receipt_long_outlined,
                    title: 'Ventas',
                    color: Colors.orange,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen()));
                    },
                  ),
                  _buildDrawerItem(
                    context,
                    icon: Icons.payment_outlined,
                    title: 'Pagos',
                    color: Colors.green,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const PagosScreen()));
                    },
                  ),

                  _buildSectionTitle('INVENTARIO'),
                  _buildDrawerItem(
                    context,
                    icon: Icons.inventory_2_outlined,
                    title: 'Productos',
                    color: Colors.teal,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductosScreen()));
                    },
                  ),
                  _buildDrawerItem(
                    context,
                    icon: Icons.warehouse_outlined,
                    title: 'Stock',
                    color: Colors.indigo,
                    onTap: () {
                      Navigator.pop(context);
                      Navigator.push(context, MaterialPageRoute(builder: (_) => const InventarioScreen()));
                    },
                  ),
                  
                  _buildDivider(),
                  
                  _buildDrawerItem(
                    context,
                    icon: Icons.logout,
                    title: 'Cerrar Sesión',
                    color: Colors.redAccent,
                    onTap: () {
                      Navigator.pop(context);
                      _handleLogout(context);
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: Text(
        title,
        style: const TextStyle(
          color: Colors.grey,
          fontSize: 11,
          fontWeight: FontWeight.bold,
          letterSpacing: 1.2,
        ),
      ),
    );
  }

  Widget _buildDivider() {
    return const Padding(
      padding: EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: Divider(height: 1),
    );
  }

  Widget _buildDrawerItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
    required Color color,
  }) {
    return ListTile(
      leading: Container(
        padding: const EdgeInsets.all(8),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          shape: BoxShape.circle,
        ),
        child: Icon(icon, color: color, size: 20),
      ),
      title: Text(
        title,
        style: const TextStyle(
          color: Colors.black87,
          fontSize: 15,
          fontWeight: FontWeight.w500,
        ),
      ),
      onTap: onTap,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 4),
      horizontalTitleGap: 16,
    );
  }
}
