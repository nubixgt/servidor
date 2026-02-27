import 'package:flutter/material.dart';
import '../models/producto_precio.dart';
import '../services/producto_service.dart';
import '../services/auth_service.dart';
import 'producto_form_screen.dart';
import '../widgets/app_drawer.dart'; // Ensure drawer is imported

class ProductosScreen extends StatefulWidget {
  const ProductosScreen({Key? key}) : super(key: key);

  @override
  State<ProductosScreen> createState() => _ProductosScreenState();
}

class _ProductosScreenState extends State<ProductosScreen> {
  final _productoService = ProductoService();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  List<ProductoPrecio> _productos = [];
  List<ProductoPrecio> _productosFiltrados = [];
  bool _isLoading = true;
  String _searchQuery = '';

  @override
  void initState() {
    super.initState();
    _cargarProductos();
  }

  Future<void> _cargarProductos() async {
    setState(() => _isLoading = true);
    final result = await _productoService.listarTodosProductosPrecios();

    if (mounted) {
      setState(() {
        if (result['success']) {
          _productos = result['data'] as List<ProductoPrecio>;
          _filtrarProductos();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message']),
              backgroundColor: Colors.red,
            ),
          );
        }
        _isLoading = false;
      });
    }
  }

  void _filtrarProductos() {
    setState(() {
      if (_searchQuery.isEmpty) {
        _productosFiltrados = _productos;
      } else {
        _productosFiltrados = _productos.where((producto) {
          final query = _searchQuery.toLowerCase();
          return producto.producto.toLowerCase().contains(query) ||
              producto.presentacion.toLowerCase().contains(query);
        }).toList();
      }
    });
  }

  Future<void> _navegarAFormulario({ProductoPrecio? producto}) async {
    final resultado = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ProductoFormScreen(producto: producto),
      ),
    );

    if (resultado == true) {
      _cargarProductos();
    }
  }

  Future<void> _confirmarEliminar(ProductoPrecio producto) async {
    final usuario = AuthService().usuarioActual;
    if (usuario?.rol != 'admin') {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Solo los administradores pueden eliminar productos'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Confirmar eliminación'),
        content: Text(
          '¿Está seguro de eliminar el producto "${producto.producto}" con presentación "${producto.presentacion}"?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Eliminar', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmar == true) {
      _eliminarProducto(producto);
    }
  }

  Future<void> _eliminarProducto(ProductoPrecio producto) async {
    final result = await _productoService.eliminarProducto(producto.id);

    if (!mounted) return;

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.green,
        ),
      );
      _cargarProductos();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    // Verificar rol para mostrar botón de eliminar
    final usuario = AuthService().usuarioActual;
    final esAdmin = usuario?.rol == 'admin';

    return Scaffold(
      key: _scaffoldKey,
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: Container(
          margin: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.black.withOpacity(0.2),
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

          // 2. Custom Scroll View
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              // Banner Title
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
                                Colors.black.withOpacity(0.5),
                                Colors.green.shade900.withOpacity(0.8),
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
                            'Gestión de Inventario',
                            style: TextStyle(color: Colors.green.shade100, fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 1.2),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Catálogo de Productos',
                            style: TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Buscador y Header
              SliverToBoxAdapter(
                 child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                   child: Row(
                     children: [
                       Expanded(
                         child: Container(
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.95),
                            borderRadius: BorderRadius.circular(15),
                            boxShadow: [
                               BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 5)),
                            ],
                          ),
                          child: TextField(
                            onChanged: (value) {
                              setState(() {
                                _searchQuery = value;
                                _filtrarProductos();
                              });
                            },
                            decoration: const InputDecoration(
                              hintText: 'Buscar producto...',
                              prefixIcon: Icon(Icons.search, color: Colors.green),
                              border: InputBorder.none,
                               contentPadding: EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                            ),
                          ),
                                             ),
                       ),
                        const SizedBox(width: 12),
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.green.shade700,
                            borderRadius: BorderRadius.circular(15),
                             boxShadow: [
                               BoxShadow(color: Colors.green.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 4)),
                            ],
                          ),
                          child: IconButton(
                            icon: const Icon(Icons.add, color: Colors.white),
                            onPressed: () => _navegarAFormulario(),
                            tooltip: 'Agregar Producto',
                          ),
                        ),
                     ],
                   ),
                 ),
              ),

              // Lista de Productos
               _isLoading
                ? const SliverFillRemaining(child: Center(child: CircularProgressIndicator()))
                : _productosFiltrados.isEmpty
                    ? SliverFillRemaining(child: _buildEmptyState())
                    : SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (context, index) {
                            final producto = _productosFiltrados[index];
                            return _buildProductoCard(producto, esAdmin);
                          },
                          childCount: _productosFiltrados.length,
                        ),
                      ),
               const SliverPadding(padding: EdgeInsets.only(bottom: 80)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProductoCard(ProductoPrecio producto, bool esAdmin) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => _navegarAFormulario(producto: producto),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 60,
                  height: 60,
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: Icon(Icons.inventory_2_outlined, color: Colors.green.shade700, size: 30),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        producto.producto,
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                       Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          producto.presentacion,
                          style: TextStyle(color: Colors.grey.shade700, fontSize: 12),
                        ),
                      ),
                       const SizedBox(height: 8),
                       Row(
                         children: [
                           Text(
                             'Q${producto.precio.toStringAsFixed(2)}',
                             style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.green.shade700),
                           ),
                           const Spacer(),
                            Text(
                             'Stock: ${producto.cantidad}',
                             style: TextStyle(color: producto.cantidad > 0 ? Colors.grey.shade600 : Colors.red, fontSize: 12, fontWeight: FontWeight.bold),
                           ),
                         ],
                       )
                    ],
                  ),
                ),
                 if (esAdmin)
                   IconButton(
                     icon: const Icon(Icons.delete_outline, color: Colors.red),
                     onPressed: () => _confirmarEliminar(producto),
                   ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.inventory_2_outlined, size: 80, color: Colors.white.withOpacity(0.6)),
        const SizedBox(height: 16),
        Text(
          'No hay productos',
          style: TextStyle(fontSize: 18, color: Colors.white.withOpacity(0.8)),
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
    path.quadraticBezierTo(size.width / 2, size.height, size.width, size.height - 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }
  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
