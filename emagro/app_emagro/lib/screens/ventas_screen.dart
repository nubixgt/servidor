import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/nota_envio.dart';
import '../models/item_carrito.dart';
import '../services/nota_envio_service.dart';
import '../services/pdf_service.dart';
import '../services/auth_service.dart';
import '../widgets/app_drawer.dart';

class VentasScreen extends StatefulWidget {
  const VentasScreen({Key? key}) : super(key: key);

  @override
  State<VentasScreen> createState() => _VentasScreenState();
}

class _VentasScreenState extends State<VentasScreen> {
  final _notaService = NotaEnvioService();
  final _pdfService = PdfService();
  final _searchController = TextEditingController();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  List<NotaEnvio> _notas = [];
  List<NotaEnvio> _notasFiltradas = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _cargarNotas();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _cargarNotas() async {
    setState(() => _isLoading = true);

    final result = await _notaService.listarNotas();

    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _notas = result['notas'];
          _notasFiltradas = _notas;
          _searchController.clear();
        }
      });
    }
  }

  void _filtrarNotas(String query) {
    setState(() {
      if (query.isEmpty) {
        _notasFiltradas = _notas;
      } else {
        _notasFiltradas = _notas.where((nota) {
          final clienteLower = nota.clienteNombre.toLowerCase();
          final vendedorLower = nota.vendedor.toLowerCase();
          final numeroLower = nota.numeroNota.toLowerCase();
          final searchLower = query.toLowerCase();

          return clienteLower.contains(searchLower) ||
              vendedorLower.contains(searchLower) ||
              numeroLower.contains(searchLower);
        }).toList();
      }
    });
  }

  Future<void> _regenerarPDF(NotaEnvio nota) async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: CircularProgressIndicator(color: Colors.white),
      ),
    );

    final result = await _pdfService.generarNotaEnvioPDF(
      numeroNota: nota.numeroNota,
      fecha: nota.fecha,
      vendedor: nota.vendedor,
      clienteNombre: nota.clienteNombre,
      nit: nota.nit,
      direccion: nota.direccion,
      productos: nota.productos.map((detalle) {
        return ItemCarrito(
          producto: detalle.producto,
          presentacion: detalle.presentacion,
          precioUnitario: detalle.precioUnitario,
          cantidad: detalle.cantidad,
          descuento: detalle.descuento,
        );
      }).toList(),
      subtotal: nota.subtotal,
      descuentoTotal: nota.descuentoTotal,
      total: nota.total,
    );

    if (!mounted) return;
    Navigator.pop(context); // Cerrar loading

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('PDF generado exitosamente'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _confirmarEliminar(NotaEnvio nota) async {
    final confirmar = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar Venta'),
        content: Text('¿Estás seguro de eliminar la nota #${nota.numeroNota}?\n\nEsta acción:\n1. Restaurará el inventario\n2. Eliminará los pagos asociados\n3. No se puede deshacer'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Eliminar', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirmar == true) {
      if (!mounted) return;
      
      // Mostrar loading
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => const Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
      );

      if (nota.id == null) return;
      final result = await _notaService.eliminarNota(nota.id!);

      if (!mounted) return;
      Navigator.pop(context); // Cerrar loading

      if (result['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.green,
          ),
        );
        _cargarNotas();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _mostrarDetalles(NotaEnvio nota) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.9,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (context, scrollController) {
          return Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
            ),
            child: Column(
              children: [
                // Handle Bar
                Center(
                  child: Container(
                    margin: const EdgeInsets.only(top: 12),
                    width: 40,
                    height: 5,
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(10),
                    ),
                  ),
                ),
                
                // Content
                Expanded(
                  child: SingleChildScrollView(
                    controller: scrollController,
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Nota #${nota.numeroNota}',
                                  style: const TextStyle(
                                    fontSize: 26,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.black87,
                                  ),
                                ),
                                Text(
                                  DateFormat('dd/MM/yyyy • HH:mm').format(DateTime.parse(nota.fecha)),
                                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                                ),
                              ],
                            ),
                             IconButton(
                                icon: const Icon(Icons.close),
                                onPressed: () => Navigator.pop(context),
                                style: IconButton.styleFrom(backgroundColor: Colors.grey.shade100),
                              ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // Main Info Card
                        Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.grey.shade50,
                            borderRadius: BorderRadius.circular(15),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Column(
                            children: [
                              _buildDetalleRow('Vendedor', nota.vendedor, Icons.person_outline),
                              const Divider(),
                              _buildDetalleRow('Cliente', nota.clienteNombre, Icons.group_outlined),
                              _buildDetalleRow('NIT', nota.nit, Icons.badge_outlined),
                              _buildDetalleRow('Dirección', nota.direccion, Icons.location_on_outlined),
                              const Divider(),
                              _buildDetalleRow('Tipo de Venta', nota.tipoVenta, Icons.payment_outlined),
                              if (nota.diasCredito != null)
                                _buildDetalleRow('Días Crédito', '${nota.diasCredito} días', Icons.calendar_today_outlined),
                            ],
                          ),
                        ),

                        const SizedBox(height: 24),
                        const Text(
                          'Productos',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                        const SizedBox(height: 12),

                        // Lista de Productos
                        ...nota.productos.map((producto) => Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Colors.grey.shade200),
                            boxShadow: [
                              BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 5, offset: const Offset(0, 2)),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      producto.producto,
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                                    ),
                                  ),
                                  Text(
                                    'Q${producto.total.toStringAsFixed(2)}',
                                    style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.green),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 4),
                              Text('${producto.presentacion} • x${producto.cantidad}', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                              if (producto.descuento > 0)
                                Padding(
                                  padding: const EdgeInsets.only(top: 4.0),
                                  child: Text('Desc: Q${producto.descuento.toStringAsFixed(2)}', style: const TextStyle(color: Colors.orange, fontSize: 12)),
                                ),
                            ],
                          ),
                        )),

                        const Divider(height: 30),

                        // Totales
                        _buildTotalRow('Subtotal', nota.subtotal),
                        _buildTotalRow('Descuento', nota.descuentoTotal, color: Colors.orange),
                        const SizedBox(height: 8),
                        _buildTotalRow('TOTAL', nota.total, isMain: true),

                        const SizedBox(height: 30),

                        // Botón PDF
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.pop(context);
                              _regenerarPDF(nota);
                            },
                            icon: const Icon(Icons.picture_as_pdf, color: Colors.white),
                            label: const Text('Descargar PDF', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.green.shade700,
                              padding: const EdgeInsets.symmetric(vertical: 16),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                            ),
                          ),
                        ),
                        const SizedBox(height: 20),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildDetalleRow(String label, String value, IconData icon) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        children: [
          Icon(icon, size: 16, color: Colors.grey),
          const SizedBox(width: 8),
          Expanded(
            child: RichText(
              text: TextSpan(
                style: const TextStyle(color: Colors.black87, fontSize: 14),
                children: [
                  TextSpan(text: '$label: ', style: TextStyle(color: Colors.grey.shade600)),
                  TextSpan(text: value, style: const TextStyle(fontWeight: FontWeight.w500)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTotalRow(String label, double value, {Color? color, bool isMain = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              fontWeight: isMain ? FontWeight.bold : FontWeight.normal,
              fontSize: isMain ? 18 : 14,
              color: isMain ? Colors.black : Colors.grey.shade700
            ),
          ),
          Text(
            'Q${value.toStringAsFixed(2)}',
            style: TextStyle(
              fontWeight: isMain ? FontWeight.bold : FontWeight.normal,
              fontSize: isMain ? 22 : 14,
              color: color ?? (isMain ? Colors.green.shade800 : Colors.black),
            ),
          ),
        ],
      ),
    );
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

          // 2. ScrollView
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
                            'Historial de Transacciones',
                            style: TextStyle(color: Colors.green.shade100, fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 1.2),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Notas de Envío',
                            style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold),
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
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.95),
                      borderRadius: BorderRadius.circular(15),
                      boxShadow: [
                         BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 5)),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      onChanged: _filtrarNotas,
                      decoration: InputDecoration(
                        hintText: 'Buscar por #, Cliente o Vendedor...',
                        prefixIcon: const Icon(Icons.search, color: Colors.green),
                        suffixIcon: _searchController.text.isNotEmpty
                           ? IconButton(icon: const Icon(Icons.clear, color: Colors.grey), onPressed: () { _searchController.clear(); _filtrarNotas(''); })
                           : null,
                        border: InputBorder.none,
                         contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 15),
                      ),
                    ),
                  ),
                ),
              ),

              // Lista
               _isLoading
                ? const SliverFillRemaining(child: Center(child: CircularProgressIndicator(color: Colors.white)))
                : _notasFiltradas.isEmpty
                    ? SliverFillRemaining(child: _buildEmptyState())
                    : SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (context, index) {
                            final nota = _notasFiltradas[index];
                            return Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
                              child: _buildNotaCard(nota),
                            );
                          },
                          childCount: _notasFiltradas.length,
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
        Icon(Icons.receipt_long_outlined, size: 80, color: Colors.white.withOpacity(0.6)),
        const SizedBox(height: 16),
        Text(
          'No hay notas registradas',
          style: TextStyle(fontSize: 18, color: Colors.white.withOpacity(0.8)),
        ),
      ],
    );
  }

  Widget _buildNotaCard(NotaEnvio nota) {
    return Container(
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
          onTap: () => _mostrarDetalles(nota),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.green.shade50,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.green.shade100),
                      ),
                      child: Text(
                        '#${nota.numeroNota}',
                        style: TextStyle(fontWeight: FontWeight.bold, color: Colors.green.shade800),
                      ),
                    ),
                     Row(
                      children: [
                        Icon(Icons.calendar_today_outlined, size: 14, color: Colors.grey.shade500),
                        const SizedBox(width: 4),
                        Text(
                          DateFormat('dd/MM/yyyy').format(DateTime.parse(nota.fecha)),
                          style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                        ),
                        const SizedBox(width: 8),
                        if (AuthService().usuarioActual?.isAdmin ?? false)
                          IconButton(
                            icon: const Icon(Icons.delete_outline, color: Colors.red, size: 22),
                            onPressed: () => _confirmarEliminar(nota),
                            tooltip: 'Eliminar Venta',
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                            style: IconButton.styleFrom(
                              tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                
                Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: Colors.grey.shade100,
                      radius: 20,
                      child: Text(nota.clienteNombre.substring(0,1).toUpperCase(), style: TextStyle(color: Colors.green.shade800, fontWeight: FontWeight.bold)),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(nota.clienteNombre, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                          Text(nota.vendedor, style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          'Q${nota.total.toStringAsFixed(2)}',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.green.shade700),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: _getTipoVentaColor(nota.tipoVenta).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            nota.tipoVenta,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                              color: _getTipoVentaColor(nota.tipoVenta),
                            ),
                          ),
                        ),
                      ],
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

  Color _getTipoVentaColor(String tipo) {
    switch (tipo) {
      case 'Contado': return Colors.green;
      case 'Crédito': return Colors.orange;
      case 'Pruebas': return Colors.blue;
      default: return Colors.grey;
    }
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
