import 'package:flutter/material.dart';
import '../models/usuario.dart';
import '../services/usuario_service.dart';
import '../widgets/app_drawer.dart';
import 'usuario_form_screen.dart';

class UsuariosScreen extends StatefulWidget {
  const UsuariosScreen({Key? key}) : super(key: key);

  @override
  State<UsuariosScreen> createState() => _UsuariosScreenState();
}

class _UsuariosScreenState extends State<UsuariosScreen> {
  final _usuarioService = UsuarioService();
  final _searchController = TextEditingController();
  List<Usuario> _usuarios = [];
  List<Usuario> _usuariosFiltrados = [];
  bool _isLoading = true;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _filtrarUsuarios(String query) {
    setState(() {
      if (query.isEmpty) {
        _usuariosFiltrados = _usuarios;
      } else {
        _usuariosFiltrados = _usuarios.where((usuario) {
          final nombreLower = usuario.nombre.toLowerCase();
          final usuarioLower = usuario.usuario.toLowerCase();
          final idStr = usuario.id.toString();
          final rolLower = usuario.rol.toLowerCase();
          final queryLower = query.toLowerCase();

          return nombreLower.contains(queryLower) ||
              usuarioLower.contains(queryLower) ||
              idStr.contains(queryLower) ||
              rolLower.contains(queryLower);
        }).toList();
      }
    });
  }

  @override
  void initState() {
    super.initState();
    _cargarUsuarios();
  }

  Future<void> _cargarUsuarios() async {
    setState(() => _isLoading = true);

    final result = await _usuarioService.listarUsuarios();

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _usuarios = result['usuarios'];
          _usuariosFiltrados = _usuarios;
          _searchController.clear();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message']),
              backgroundColor: Colors.redAccent,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      });
    }
  }

  Future<void> _eliminarUsuario(Usuario usuario) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar Usuario', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('¿Está seguro que desea eliminar a "${usuario.nombre}"?\n\nEsta acción no se puede deshacer.'),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.redAccent,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final result = await _usuarioService.eliminarUsuario(usuario.id);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: result['success'] ? Colors.green : Colors.redAccent,
            behavior: SnackBarBehavior.floating,
          ),
        );

        if (result['success']) {
          _cargarUsuarios();
        }
      }
    }
  }

  Future<void> _navigateToAddUser() async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => const UsuarioFormScreen(),
      ),
    );
    
    if (result == true) {
      _cargarUsuarios();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: Container(
          margin: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.black.withOpacity(0.2), // Efecto Glass Oscuro
            shape: BoxShape.circle,
            border: Border.all(color: Colors.white.withOpacity(0.2), width: 1),
          ),
          child: IconButton(
            icon: const Icon(Icons.menu, color: Colors.white, size: 20),
            onPressed: () => _scaffoldKey.currentState?.openDrawer(),
            tooltip: 'Menú',
          ),
        ),
      ),
      drawer: const AppDrawer(),
      body: Stack(
        children: [
          // 1. Fondo Gradiente
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

          // 2. ScrollView Completo
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              // Header Banner con Título Integrado
              SliverToBoxAdapter(
                child: Stack(
                  children: [
                    ClipPath(
                      clipper: _HeaderClipper(),
                      child: Container(
                        height: 220,
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
                                Colors.green.shade900.withOpacity(0.9),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                    Positioned(
                      bottom: 50,
                      left: 24,
                      right: 24,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Administración',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Gestión de Usuarios',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Barra de Búsqueda y Botón Agregar
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  child: Row(
                    children: [
                      Expanded(
                        child: Container(
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.95),
                            borderRadius: BorderRadius.circular(15),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.1),
                                blurRadius: 10,
                                offset: const Offset(0, 5),
                              ),
                            ],
                          ),
                          child: TextField(
                            controller: _searchController,
                            onChanged: _filtrarUsuarios,
                            decoration: InputDecoration(
                              hintText: 'Buscar usuario...',
                              prefixIcon: const Icon(Icons.search, color: Colors.green),
                              suffixIcon: _searchController.text.isNotEmpty
                                  ? IconButton(
                                      icon: const Icon(Icons.clear, color: Colors.grey),
                                      onPressed: () {
                                        _searchController.clear();
                                        _filtrarUsuarios('');
                                      },
                                    )
                                  : null,
                              border: InputBorder.none,
                              contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Botón Agregar Usuario
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.green.shade600,
                          borderRadius: BorderRadius.circular(15),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.green.withOpacity(0.3),
                              blurRadius: 10,
                              offset: const Offset(0, 5),
                            ),
                          ],
                        ),
                        child: IconButton(
                          onPressed: _navigateToAddUser,
                          icon: const Icon(Icons.person_add, color: Colors.white),
                          tooltip: 'Nuevo Usuario',
                          padding: const EdgeInsets.all(12),
                          constraints: const BoxConstraints(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // Lista de Usuarios
              _isLoading
                  ? const SliverFillRemaining(
                      child: Center(child: CircularProgressIndicator(color: Colors.white)),
                    )
                  : _usuarios.isEmpty
                      ? SliverFillRemaining(child: _buildEmptyState())
                      : _usuariosFiltrados.isEmpty
                          ? SliverFillRemaining(child: _buildNoResultsState())
                          : SliverList(
                              delegate: SliverChildBuilderDelegate(
                                (context, index) {
                                  final usuario = _usuariosFiltrados[index];
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    child: _buildModernUserCard(usuario),
                                  );
                                },
                                childCount: _usuariosFiltrados.length,
                              ),
                            ),

              const SliverPadding(padding: EdgeInsets.only(bottom: 20)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.people_outline, size: 80, color: Colors.white.withOpacity(0.6)),
        const SizedBox(height: 16),
        Text(
          'No hay usuarios registrados',
          style: TextStyle(fontSize: 18, color: Colors.white.withOpacity(0.8)),
        ),
      ],
    );
  }

  Widget _buildNoResultsState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.search_off, size: 80, color: Colors.white.withOpacity(0.6)),
        const SizedBox(height: 16),
        Text(
          'Sin resultados',
          style: TextStyle(fontSize: 18, color: Colors.white.withOpacity(0.8)),
        ),
      ],
    );
  }

  Widget _buildModernUserCard(Usuario usuario) {
    final isActivo = usuario.isActivo;
    final isAdmin = usuario.isAdmin;

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () async {
             // Opcional: ver detalles o editar rápido
          },
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    // Avatar
                    Container(
                      padding: const EdgeInsets.all(2),
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: isAdmin ? Colors.purple.shade200 : Colors.blue.shade200,
                          width: 2,
                        ),
                      ),
                      child: CircleAvatar(
                        backgroundColor: isAdmin ? Colors.purple.shade50 : Colors.blue.shade50,
                        radius: 24,
                        child: Text(
                          usuario.nombre.substring(0, 1).toUpperCase(),
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: isAdmin ? Colors.purple : Colors.blue,
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 16),
                    // Info Principal
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            usuario.nombre,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(Icons.account_circle_outlined, size: 14, color: Colors.grey.shade600),
                              const SizedBox(width: 4),
                              Text(
                                usuario.usuario,
                                style: TextStyle(
                                  fontSize: 13,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    // Estado Badge
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: isActivo ? Colors.green.shade100 : Colors.red.shade100,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        usuario.estado.toUpperCase(),
                        style: TextStyle(
                          color: isActivo ? Colors.green.shade800 : Colors.red.shade800,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Divider(height: 1),
                ),
                // Footer con Info y Botones
                Row(
                  children: [
                    _buildMiniInfo(Icons.admin_panel_settings_outlined, usuario.rol.toUpperCase()),
                    const Spacer(),
                    // Botones de Acción
                    IconButton(
                      icon: const Icon(Icons.edit_outlined, color: Colors.blue),
                      onPressed: () async {
                        final result = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => UsuarioFormScreen(usuario: usuario),
                          ),
                        );
                        if (result == true) _cargarUsuarios();
                      },
                      tooltip: 'Editar',
                      constraints: const BoxConstraints(),
                      padding: const EdgeInsets.all(8),
                      style: IconButton.styleFrom(
                        backgroundColor: Colors.blue.shade50,
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      icon: const Icon(Icons.delete_outline, color: Colors.red),
                      onPressed: () => _eliminarUsuario(usuario),
                      tooltip: 'Eliminar',
                      constraints: const BoxConstraints(),
                      padding: const EdgeInsets.all(8),
                      style: IconButton.styleFrom(
                        backgroundColor: Colors.red.shade50,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildMiniInfo(IconData icon, String text) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey.shade500),
        const SizedBox(width: 6),
        Text(
          text,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }
}

class _HeaderClipper extends CustomClipper<Path> {
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
