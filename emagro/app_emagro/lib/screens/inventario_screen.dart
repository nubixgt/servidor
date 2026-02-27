import 'package:flutter/material.dart';
import '../models/producto_precio.dart';
import '../services/inventario_service.dart';
import '../widgets/app_drawer.dart';

class InventarioScreen extends StatefulWidget {
  const InventarioScreen({Key? key}) : super(key: key);

  @override
  State<InventarioScreen> createState() => _InventarioScreenState();
}

class _InventarioScreenState extends State<InventarioScreen> {
  final _inventarioService = InventarioService();
  final _searchController = TextEditingController();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  List<ProductoPrecio> _inventario = [];
  List<ProductoPrecio> _inventarioFiltrado = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _cargarInventario();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _cargarInventario() async {
    setState(() => _isLoading = true);

    final result = await _inventarioService.listarInventario();

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _inventario = result['data'];
          _inventarioFiltrado = _inventario;
          _searchController.clear();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message']),
              backgroundColor: Colors.red,
            ),
          );
        }
      });
    }
  }

  void _filtrarInventario(String query) {
    setState(() {
      if (query.isEmpty) {
        _inventarioFiltrado = _inventario;
      } else {
        _inventarioFiltrado = _inventario.where((producto) {
          final productoLower = producto.producto.toLowerCase();
          final presentacionLower = producto.presentacion.toLowerCase();
          final queryLower = query.toLowerCase();

          return productoLower.contains(queryLower) ||
              presentacionLower.contains(queryLower);
        }).toList();
      }
    });
  }

  Color _getStockColor(int cantidad) {
    if (cantidad == 0) return Colors.red;
    if (cantidad < 10) return Colors.orange;
    return Colors.green;
  }

  IconData _getStockIcon(int cantidad) {
    if (cantidad == 0) return Icons.error_outline;
    if (cantidad < 10) return Icons.warning_amber_rounded;
    return Icons.check_circle_outline;
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
                            'Control de Stock',
                            style: TextStyle(
                                color: Colors.green.shade100,
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 1.2),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Estado del Inventario',
                            style: TextStyle(
                                color: Colors.white,
                                fontSize: 28,
                                fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Buscador
              SliverToBoxAdapter(
                child: Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.95),
                      borderRadius: BorderRadius.circular(15),
                      boxShadow: [
                        BoxShadow(
                            color: Colors.black.withOpacity(0.1),
                            blurRadius: 10,
                            offset: const Offset(0, 5)),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      onChanged: _filtrarInventario,
                      decoration: InputDecoration(
                        hintText: 'Buscar producto...',
                        prefixIcon:
                            const Icon(Icons.search, color: Colors.green),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear,
                                    color: Colors.grey),
                                onPressed: () {
                                  _searchController.clear();
                                  _filtrarInventario('');
                                })
                            : null,
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 20, vertical: 15),
                      ),
                    ),
                  ),
                ),
              ),

              // Lista de Inventario
              _isLoading
                  ? const SliverFillRemaining(
                      child: Center(
                          child: CircularProgressIndicator(
                              color: Colors.white)))
                  : _inventarioFiltrado.isEmpty
                      ? SliverFillRemaining(child: _buildEmptyState())
                      : SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final producto = _inventarioFiltrado[index];
                              return _buildProductoCard(producto);
                            },
                            childCount: _inventarioFiltrado.length,
                          ),
                        ),
              const SliverPadding(padding: EdgeInsets.only(bottom: 20)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProductoCard(ProductoPrecio producto) {
    final stockColor = _getStockColor(producto.cantidad);
    final stockIcon = _getStockIcon(producto.cantidad);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: [
          // Top Stripe Color Indicator
          Container(
            height: 6,
            decoration: BoxDecoration(
              color: stockColor,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                // Icono Stock
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: stockColor.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.inventory_2_outlined,
                    color: stockColor,
                    size: 28,
                  ),
                ),
                const SizedBox(width: 16),
                
                // Info
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        producto.producto,
                        style: const TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 16),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        producto.presentacion,
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                      ),
                    ],
                  ),
                ),

                // Stock Count Pill
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: stockColor,
                    borderRadius: BorderRadius.circular(15),
                    boxShadow: [
                      BoxShadow(
                        color: stockColor.withOpacity(0.4),
                        blurRadius: 6,
                        offset: const Offset(0,2)
                      )
                    ]
                  ),
                  child: Row(
                    children: [
                      Icon(stockIcon, color: Colors.white, size: 16),
                      const SizedBox(width: 6),
                      Text(
                        '${producto.cantidad}',
                        style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 16),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

            // Divider and Price Row
            Padding(
              padding: const EdgeInsets.only(left: 16, right: 16, bottom: 16),
              child: Column(
                children: [
                  Divider(color: Colors.grey.shade200),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(6),
                            decoration: BoxDecoration(
                              color: Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: const Text('Q', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green)),
                          ),
                          const SizedBox(width: 8),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Precio', style: TextStyle(color: Colors.grey.shade500, fontSize: 11)),
                              Text(
                                'Q${producto.precio.toStringAsFixed(2)}',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                              ),
                            ],
                          ),
                        ],
                      ),
                       Row(
                        children: [
                          Icon(Icons.warehouse_outlined, size: 16, color: Colors.grey.shade400),
                          const SizedBox(width: 4),
                           Text(
                            producto.cantidad == 0 ? 'Agotado' : 'Disponible',
                            style: TextStyle(
                              color: stockColor, 
                              fontWeight: FontWeight.bold, 
                              fontSize: 12
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      );
    }

  Widget _buildEmptyState() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(Icons.inventory_2_outlined,
            size: 80, color: Colors.white.withOpacity(0.6)),
        const SizedBox(height: 16),
        Text(
          'No hay productos',
          style: TextStyle(
              fontSize: 18, color: Colors.white.withOpacity(0.8)),
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
        size.width / 2, size.height, size.width, size.height - 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
