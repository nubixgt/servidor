import 'package:flutter/material.dart';
import '../models/cliente.dart';
import '../services/cliente_service.dart';
import '../services/auth_service.dart';
import '../widgets/app_drawer.dart';
import 'cliente_form_screen.dart';

class ClientesScreen extends StatefulWidget {
  const ClientesScreen({Key? key}) : super(key: key);

  @override
  State<ClientesScreen> createState() => _ClientesScreenState();
}

class _ClientesScreenState extends State<ClientesScreen> {
  final _clienteService = ClienteService();
  final _searchController = TextEditingController();
  List<Cliente> _clientes = [];
  List<Cliente> _clientesFiltrados = [];
  bool _isLoading = true;
  bool _isAdmin = false;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _isAdmin = AuthService().usuarioActual?.isAdmin ?? false;
    _cargarClientes();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _filtrarClientes(String query) {
    setState(() {
      if (query.isEmpty) {
        _clientesFiltrados = _clientes;
      } else {
        _clientesFiltrados = _clientes.where((cliente) {
          final nombreLower = cliente.nombre.toLowerCase();
          final nitLower = cliente.nit.toLowerCase();
          final telefonoLower = cliente.telefono.toLowerCase();
          final queryLower = query.toLowerCase();

          return nombreLower.contains(queryLower) ||
              nitLower.contains(queryLower) ||
              telefonoLower.contains(queryLower);
        }).toList();
      }
    });
  }

  Future<void> _cargarClientes() async {
    setState(() => _isLoading = true);

    final result = await _clienteService.listarClientes();

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _clientes = result['clientes'];
          _clientesFiltrados = _clientes;
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

  Future<void> _eliminarCliente(Cliente cliente) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar Cliente', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text(
            '¿Está seguro que desea eliminar a "${cliente.nombre}"?\n\nEsta acción no se puede deshacer.'),
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
      final result = await _clienteService.eliminarCliente(cliente.id);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: result['success'] ? Colors.green : Colors.redAccent,
            behavior: SnackBarBehavior.floating,
          ),
        );

        if (result['success']) {
          _cargarClientes();
        }
      }
    }
  }

  Future<void> _navigateToCreateClient() async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => const ClienteFormScreen(),
      ),
    );

    if (result == true) {
      _cargarClientes();
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
            color: Colors.black.withOpacity(0.2), // Efecto Glass
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
              // Header Banner
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
                            'Cartera de Clientes',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Gestión de Clientes',
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
                            onChanged: _filtrarClientes,
                            decoration: InputDecoration(
                              hintText: 'Buscar cliente...',
                              prefixIcon: const Icon(Icons.search, color: Colors.green),
                              suffixIcon: _searchController.text.isNotEmpty
                                  ? IconButton(
                                      icon: const Icon(Icons.clear, color: Colors.grey),
                                      onPressed: () {
                                        _searchController.clear();
                                        _filtrarClientes('');
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
                      // Botón Agregar Cliente
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
                          onPressed: _navigateToCreateClient,
                          icon: const Icon(Icons.person_add, color: Colors.white),
                          tooltip: 'Nuevo Cliente',
                          padding: const EdgeInsets.all(12),
                          constraints: const BoxConstraints(),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // Lista de Clientes
              _isLoading
                  ? const SliverFillRemaining(
                      child: Center(child: CircularProgressIndicator(color: Colors.white)),
                    )
                  : _clientes.isEmpty
                      ? SliverFillRemaining(child: _buildEmptyState())
                      : _clientesFiltrados.isEmpty
                          ? SliverFillRemaining(child: _buildNoResultsState())
                          : SliverList(
                              delegate: SliverChildBuilderDelegate(
                                (context, index) {
                                  final cliente = _clientesFiltrados[index];
                                  return Padding(
                                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                    child: _buildClienteCard(cliente),
                                  );
                                },
                                childCount: _clientesFiltrados.length,
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
          'No hay clientes registrados',
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

  Widget _buildClienteCard(Cliente cliente) {
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
            // Futuro: Ver detalles
          },
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  children: [
                    // Avatar/Icono
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        Icons.store_mall_directory_outlined,
                        color: Colors.green.shade700,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 16),
                    // Info Principal
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            cliente.nombre,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          const SizedBox(height: 4),
                          if (cliente.nit.isNotEmpty)
                          Row(
                            children: [
                              Icon(Icons.badge_outlined, size: 14, color: Colors.grey.shade600),
                              const SizedBox(width: 4),
                              Text(
                                cliente.nit,
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
                    // Bloqueado Badge
                    if (cliente.ventasBloqueadas)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.red.shade100,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          'BLOQUEADO',
                          style: TextStyle(
                            color: Colors.red.shade800,
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
                // Footer con Info y acciones
                Row(
                  children: [
                     Icon(Icons.location_on_outlined, size: 16, color: Colors.grey.shade500),
                     const SizedBox(width: 6),
                     Expanded(
                       child: Text(
                         '${cliente.municipio}, ${cliente.departamento}',
                         style: TextStyle(
                           fontSize: 12,
                           fontWeight: FontWeight.w600,
                           color: Colors.grey.shade600,
                         ),
                         overflow: TextOverflow.ellipsis,
                       ),
                     ),
                    // Acciones
                    IconButton(
                      icon: const Icon(Icons.edit_outlined, color: Colors.blue),
                      onPressed: () async {
                        final result = await Navigator.push(
                          context,
                          MaterialPageRoute(
                            builder: (_) => ClienteFormScreen(cliente: cliente),
                          ),
                        );
                        if (result == true) _cargarClientes();
                      },
                      tooltip: 'Editar',
                      constraints: const BoxConstraints(),
                      padding: const EdgeInsets.all(8),
                      style: IconButton.styleFrom(
                         backgroundColor: Colors.blue.shade50,
                      ),
                    ),
                    if (_isAdmin) ...[
                      const SizedBox(width: 8),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, color: Colors.red),
                        onPressed: () => _eliminarCliente(cliente),
                        tooltip: 'Eliminar',
                        constraints: const BoxConstraints(),
                        padding: const EdgeInsets.all(8),
                        style: IconButton.styleFrom(
                           backgroundColor: Colors.red.shade50,
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
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
