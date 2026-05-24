import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../screens/dashboard_screen.dart';
import '../screens/users_screen.dart';
import '../screens/inventory_screen.dart';
import '../screens/projects_screen.dart';
import '../screens/machinery_screen.dart';
import '../screens/finance_screen.dart';
import '../screens/suppliers_screen.dart';

class MainLayout extends StatefulWidget {
  const MainLayout({Key? key}) : super(key: key);

  @override
  State<MainLayout> createState() => _MainLayoutState();
}

class _MainLayoutState extends State<MainLayout> {
  int _currentIndex = 0;

  final List<Widget> _screens = [
    const DashboardScreen(),
    const UsersScreen(),
    const InventoryScreen(),
    const ProjectsScreen(),
    const MachineryScreen(),
    const FinanceScreen(),
    const SuppliersScreen(),
  ];

  final List<String> _titles = [
    'Dashboard',
    'Usuarios',
    'Inventario',
    'Proyectos',
    'Maquinaria',
    'Finanzas',
    'Proveedores',
  ];

  @override
  Widget build(BuildContext context) {
    final role = Provider.of<AuthProvider>(context).userRole;

    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[_currentIndex]),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_none),
            onPressed: () {},
          ),
          const SizedBox(width: 8),
        ],
      ),
      drawer: Drawer(
        backgroundColor: AppTheme.backgroundColor,
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            DrawerHeader(
              decoration: BoxDecoration(
                color: AppTheme.primaryColor.withOpacity(0.2),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  const Icon(Icons.construction_rounded, size: 40, color: AppTheme.primaryColor),
                  const SizedBox(height: 10),
                  const Text('CONSTRUCTPRO', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 18, fontStyle: FontStyle.italic)),
                  Text('Rol: ${role?.toUpperCase()}', style: const TextStyle(color: Colors.white54, fontSize: 12, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
            _buildDrawerItem(0, Icons.dashboard_outlined, 'Dashboard'),
            _buildDrawerItem(1, Icons.people_outline, 'Gestión de Usuarios'),
            _buildDrawerItem(2, Icons.inventory_2_outlined, 'Inventario'),
            _buildDrawerItem(3, Icons.architecture_outlined, 'Proyectos'),
            _buildDrawerItem(4, Icons.fire_truck_outlined, 'Maquinaria'),
            _buildDrawerItem(5, Icons.account_balance_wallet_outlined, 'Finanzas'),
            _buildDrawerItem(6, Icons.storefront_outlined, 'Proveedores'),
            const Divider(color: Colors.white12),
            ListTile(
              leading: const Icon(Icons.logout, color: AppTheme.tertiaryColor),
              title: const Text('Cerrar Sesión', style: TextStyle(color: AppTheme.tertiaryColor, fontWeight: FontWeight.bold)),
              onTap: () {
                Provider.of<AuthProvider>(context, listen: false).logout();
              },
            ),
          ],
        ),
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
    );
  }

  Widget _buildDrawerItem(int index, IconData icon, String title) {
    final isSelected = _currentIndex == index;
    return ListTile(
      leading: Icon(icon, color: isSelected ? AppTheme.primaryColor : Colors.white54),
      title: Text(
        title,
        style: TextStyle(
          color: isSelected ? AppTheme.primaryColor : Colors.white,
          fontWeight: isSelected ? FontWeight.w900 : FontWeight.normal,
        ),
      ),
      selected: isSelected,
      selectedTileColor: AppTheme.primaryColor.withOpacity(0.1),
      onTap: () {
        setState(() {
          _currentIndex = index;
        });
        Navigator.pop(context); // Close drawer
      },
    );
  }
}
