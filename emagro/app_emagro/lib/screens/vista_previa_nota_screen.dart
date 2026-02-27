import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/cliente.dart';
import '../models/item_carrito.dart';
import '../services/nota_envio_service.dart';
import '../services/auth_service.dart';
import '../services/pdf_service.dart';

class VistaPreviaNotaScreen extends StatefulWidget {
  final String fecha;
  final String vendedor;
  final Cliente cliente;
  final String tipoVenta;
  final int? diasCredito;
  final List<ItemCarrito> productos;

  const VistaPreviaNotaScreen({
    Key? key,
    required this.fecha,
    required this.vendedor,
    required this.cliente,
    required this.tipoVenta,
    this.diasCredito,
    required this.productos,
  }) : super(key: key);

  @override
  State<VistaPreviaNotaScreen> createState() => _VistaPreviaNotaScreenState();
}

class _VistaPreviaNotaScreenState extends State<VistaPreviaNotaScreen> {
  final _notaService = NotaEnvioService();
  final _pdfService = PdfService();
  
  String? _numeroNota;
  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _cargarNumeroNota();
  }

  Future<void> _cargarNumeroNota() async {
    setState(() => _isLoading = true);
    
    final result = await _notaService.obtenerSiguienteNumero();
    
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _numeroNota = result['numero_nota'];
        }
      });
    }
  }

  double _calcularSubtotal() {
    return widget.productos.fold(0.0, (sum, item) {
      if (item.esBonificacion) return sum;
      return sum + (item.precioUnitario * item.cantidad);
    });
  }

  double _calcularDescuentoTotal() {
    return widget.productos.fold(0.0, (sum, item) {
      if (item.esBonificacion) return sum;
      return sum + item.descuento;
    });
  }

  double _calcularTotal() {
    return _calcularSubtotal() - _calcularDescuentoTotal();
  }

  Future<void> _guardarYDescargarPDF() async {
    setState(() => _isSaving = true);

    try {
      final usuarioId = AuthService().usuarioActual?.id ?? 0;
      
      final productosJson = widget.productos.map((item) => item.toJson()).toList();
      
      final result = await _notaService.crearNota(
        fecha: widget.fecha,
        vendedor: widget.vendedor,
        clienteId: widget.cliente.id,
        nit: widget.cliente.nit,
        direccion: widget.cliente.direccion,
        tipoVenta: widget.tipoVenta,
        diasCredito: widget.diasCredito,
        productos: productosJson,
        subtotal: _calcularSubtotal(),
        descuentoTotal: _calcularDescuentoTotal(),
        total: _calcularTotal(),
        usuarioId: usuarioId,
      );

      if (!mounted) return;

      if (result['success']) {
        final pdfResult = await _pdfService.generarNotaEnvioPDF(
          numeroNota: result['numero_nota'],
          fecha: widget.fecha,
          vendedor: widget.vendedor,
          clienteNombre: widget.cliente.nombre,
          nit: widget.cliente.nit,
          direccion: widget.cliente.direccion,
          productos: widget.productos,
          subtotal: _calcularSubtotal(),
          descuentoTotal: _calcularDescuentoTotal(),
          total: _calcularTotal(),
          diasCredito: widget.diasCredito,
        );

        if (pdfResult['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Nota de envío ${result['numero_nota']} guardada y PDF descargado'),
              backgroundColor: Colors.green,
              duration: const Duration(seconds: 3),
            ),
          );
          Navigator.pop(context, true);
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(pdfResult['message']), backgroundColor: Colors.orange),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message']), backgroundColor: Colors.red),
        );
      }
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
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
            icon: const Icon(Icons.arrow_back, color: Colors.white, size: 20),
            onPressed: () => Navigator.pop(context),
            tooltip: 'Volver',
          ),
        ),
      ),
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

          // 2. Scroll Completo
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
                          const Text(
                            'Confirmar Documento',
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Vista Previa',
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

              // Contenido (Papel)
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
                  child: Column(
                    children: [
                      // Efecto de Papel Físico
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(5), // Bordes menos redondeados para simular papel
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.2),
                              blurRadius: 15,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(5),
                          child: Column(
                            children: [
                              // Barra superior decorativa
                              Container(
                                height: 8,
                                width: double.infinity,
                                color: Colors.green.shade700,
                              ),
                              _isLoading
                                ? const Padding(
                                    padding: EdgeInsets.all(50.0),
                                    child: Center(child: CircularProgressIndicator()),
                                  )
                                : _buildNotaEnvioContent(),
                            ],
                          ),
                        ),
                      ),
                      
                      const SizedBox(height: 30),
                      
                      // Botones
                      _buildBotones(),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // Se mantiene la lógica interna intacta, pero refactorizada en un método limpio
  Widget _buildNotaEnvioContent() {
    final fechaParsed = DateTime.parse(widget.fecha);
    final dia = fechaParsed.day.toString();
    final mes = DateFormat('MMMM', 'es').format(fechaParsed);
    final anio = fechaParsed.year.toString();

    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          // Header con logo y título
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Image.asset(
                'assets/images/logo_emagro.png',
                width: 100,
                height: 60,
                fit: BoxFit.contain,
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    const Text(
                      'NOTA DE ENVÍO',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF3949AB),
                      ),
                    ),
                    Text(
                      _numeroNota ?? '00000',
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.red,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: Colors.green.shade700,
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Text(
                    'EM',
                    style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
          
          const Divider(height: 30, thickness: 2),
          
          // Información del cliente
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                flex: 3,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildInfoRow('CLIENTE:', widget.cliente.nombre),
                    const SizedBox(height: 8),
                    _buildInfoRow('DIRECCIÓN:', widget.cliente.direccion),
                    const SizedBox(height: 8),
                    _buildInfoRow('Código/NIT:', widget.cliente.nit),
                  ],
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                flex: 2,
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    border: Border.all(color: Colors.grey.shade400),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'FECHA',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          _buildFechaBox(dia),
                          _buildFechaBox(mes.substring(0, 3)),
                          _buildFechaBox(anio),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 20),

          if (widget.diasCredito != null && widget.diasCredito! > 0) ...[
            Center(
              child: Text(
                '**Esta venta tiene ${widget.diasCredito} días de crédito',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
              ),
            ),
            const SizedBox(height: 20),
          ],
          
          // Tabla de productos (INTACTA)
          Table(
            border: TableBorder.all(color: Colors.grey.shade400, width: 1),
            columnWidths: const {
              0: FlexColumnWidth(0.8),
              1: FlexColumnWidth(2.5),
              2: FlexColumnWidth(1.5),
              3: FlexColumnWidth(1.2),
              4: FlexColumnWidth(1.2),
              5: FlexColumnWidth(1.2),
            },
            children: [
              TableRow(
                decoration: BoxDecoration(color: Colors.green.shade700),
                children: [
                  _buildTableHeader('CANT'),
                  _buildTableHeader('DESCRIPCIÓN'),
                  _buildTableHeader('PRESENT.'),
                  _buildTableHeader('PRECIO'),
                  _buildTableHeader('DESC.'),
                  _buildTableHeader('TOTAL'),
                ],
              ),
              ...widget.productos.map((item) => TableRow(
                children: [
                  _buildTableCell(item.cantidad.toString()),
                  _buildTableCell(item.producto, isBonificacion: item.esBonificacion),
                  _buildTableCell(item.presentacion),
                  _buildTableCell('Q${item.precioUnitario.toStringAsFixed(0)}'),
                  _buildTableCell(item.esBonificacion 
                      ? 'Q${(item.precioUnitario * item.cantidad).toStringAsFixed(0)}' 
                      : (item.descuento > 0 ? 'Q${item.descuento.toStringAsFixed(0)}' : '')),
                  _buildTableCell('Q${item.total.toStringAsFixed(0)}'),
                ],
              )),
              TableRow(
                decoration: BoxDecoration(color: Colors.grey.shade200),
                children: [
                  const SizedBox(),
                  const SizedBox(),
                  const SizedBox(),
                  _buildTableCell('TOTALES', bold: true),
                  _buildTableCell('Q${_calcularDescuentoTotal().toStringAsFixed(0)}', bold: true),
                  _buildTableCell('Q${_calcularTotal().toStringAsFixed(0)}', bold: true),
                ],
              ),
            ],
          ),
          
          const SizedBox(height: 50), // Espacio para firmas
          
          // Firmas
          Row(
            children: [
              Expanded(
                child: Column(
                  children: [
                    Container(height: 1, color: Colors.grey.shade400, margin: const EdgeInsets.only(bottom: 8)),
                    const Text('RECIBIDO POR', style: TextStyle(fontSize: 12)),
                    Text(
                      widget.cliente.nombre,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF3949AB)),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 40),
              Expanded(
                child: Column(
                  children: [
                    Container(height: 1, color: Colors.grey.shade400, margin: const EdgeInsets.only(bottom: 8)),
                    const Text('ENTREGADO POR', style: TextStyle(fontSize: 12)),
                    Text(
                      widget.vendedor,
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF3949AB)),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 100, child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold))),
        Expanded(child: Text(value)),
      ],
    );
  }

  Widget _buildFechaBox(String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 3),
      decoration: BoxDecoration(
        border: Border.all(color: Colors.grey.shade400),
        borderRadius: BorderRadius.circular(4),
      ),
      child: Text(text, style: const TextStyle(fontSize: 9)),
    );
  }

  Widget _buildTableHeader(String text) {
    return Padding(
      padding: const EdgeInsets.all(6.0),
      child: Text(
        text,
        textAlign: TextAlign.center,
        style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.white, fontSize: 10),
      ),
    );
  }

  Widget _buildTableCell(String text, {bool bold = false, bool isBonificacion = false}) {
    if (isBonificacion) {
      return Padding(
        padding: const EdgeInsets.all(6.0),
        child: Column(
          children: [
            Text(text, textAlign: TextAlign.center, style: TextStyle(fontWeight: bold ? FontWeight.bold : FontWeight.normal, fontSize: 10)),
            const SizedBox(height: 4),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(color: Colors.orange, borderRadius: BorderRadius.circular(4)),
              child: const Text('BONIFICACIÓN', style: TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      );
    }
    return Padding(
      padding: const EdgeInsets.all(6.0),
      child: Text(text, textAlign: TextAlign.center, style: TextStyle(fontWeight: bold ? FontWeight.bold : FontWeight.normal, fontSize: 10)),
    );
  }

  Widget _buildBotones() {
    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            onPressed: _isSaving ? null : () => Navigator.pop(context),
            icon: Icon(Icons.edit, color: Colors.green.shade800),
            label: Text('Editar', style: TextStyle(color: Colors.green.shade800)),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 16),
              side: BorderSide(color: Colors.green.shade800),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
            ),
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          flex: 2,
          child: ElevatedButton.icon(
             onPressed: _isSaving ? null : _guardarYDescargarPDF,
            icon: _isSaving
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.green, strokeWidth: 2))
                : const Icon(Icons.picture_as_pdf, color: Colors.green),
            label: Text(
              _isSaving ? 'Guardando...' : 'Confirmar y PDF',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.green.shade900),
            ),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.white,
              foregroundColor: Colors.green.shade900,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
              elevation: 5,
            ),
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
    path.quadraticBezierTo(size.width / 2, size.height, size.width, size.height - 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }
  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
