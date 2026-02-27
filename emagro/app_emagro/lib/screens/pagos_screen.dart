import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/pago.dart';
import '../services/pago_service.dart';
import '../services/auth_service.dart';
import '../widgets/app_drawer.dart';
import 'registro_pago_form_screen.dart';

class PagosScreen extends StatefulWidget {
  const PagosScreen({Key? key}) : super(key: key);

  @override
  State<PagosScreen> createState() => _PagosScreenState();
}

class _PagosScreenState extends State<PagosScreen> with SingleTickerProviderStateMixin {
  final _pagoService = PagoService();
  List<Pago> _pagos = [];
  List<FacturaCredito> _facturasCredito = [];
  bool _isLoadingPagos = true;
  bool _isLoadingFacturas = true;
  late TabController _tabController;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      if (mounted) setState(() {}); // Rebuild cuando cambie de tab
    });
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    await Future.wait([
      _cargarPagos(),
      _cargarFacturasCredito(),
    ]);
  }

  Future<void> _cargarPagos() async {
    setState(() => _isLoadingPagos = true);

    final result = await _pagoService.listarPagos();

    if (mounted) {
      setState(() {
        _isLoadingPagos = false;
        if (result['success']) {
          _pagos = result['pagos'];
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

  Future<void> _cargarFacturasCredito() async {
    setState(() => _isLoadingFacturas = true);

    final result = await _pagoService.listarFacturasCredito();

    if (mounted) {
      setState(() {
        _isLoadingFacturas = false;
        if (result['success']) {
          _facturasCredito = result['facturas'];
        }
      });
    }
  }

  Future<void> _navigateToRegistroPago() async {
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => const RegistroPagoFormScreen(),
      ),
    );

    if (result == true) {
      _cargarDatos();
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
        leading: IconButton(
          icon: const Icon(Icons.menu, color: Colors.white),
          onPressed: () => _scaffoldKey.currentState?.openDrawer(),
          tooltip: 'Menú',
        ),
        title: const Text(
          'Pagos',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
        ),
      ),
      drawer: const AppDrawer(),
      body: Stack(
        children: [
          // Fondo Gradiente
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

          // Contenido con CustomScrollView
          CustomScrollView(
            physics: const BouncingScrollPhysics(),
            slivers: [
              // Banner con imagen
              SliverToBoxAdapter(
                child: Stack(
                  children: [
                    // Imagen de fondo con ClipPath curvo
                    ClipPath(
                      clipper: _HeaderClipper(),
                      child: Container(
                        height: 300,
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
                    // Contenido del header
                    Positioned(
                      left: 24,
                      right: 24,
                      bottom: 40,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Cartera de Pagos',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Gestión de Pagos',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 20),
                          // Tabs
                          Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.2),
                              borderRadius: BorderRadius.circular(25),
                            ),
                            child: TabBar(
                              controller: _tabController,
                              indicator: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(22),
                              ),
                              indicatorSize: TabBarIndicatorSize.tab,
                              dividerColor: Colors.transparent,
                              labelColor: Colors.green.shade700,
                              unselectedLabelColor: Colors.white,
                              labelStyle: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 14,
                              ),
                              labelPadding: const EdgeInsets.symmetric(vertical: 12),
                              tabs: const [
                                Tab(text: 'Historial'),
                                Tab(text: 'Pendientes'),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Contenido de tabs - usando slivers directamente para scroll unificado
              _tabController.index == 0
                  ? _buildHistorialSlivers()
                  : _buildPendientesSlivers(),
            ],
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(top: 80, right: 8),
        child: FloatingActionButton(
          onPressed: _navigateToRegistroPago,
          backgroundColor: Colors.green.shade600,
          child: const Icon(Icons.add, color: Colors.white, size: 28),
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.endTop,
    );
  }

  // Métodos que retornan slivers para scroll unificado
  Widget _buildHistorialSlivers() {
    if (_isLoadingPagos) {
      return SliverFillRemaining(
        child: const Center(
          child: CircularProgressIndicator(color: Colors.green),
        ),
      );
    }

    if (_pagos.isEmpty) {
      return SliverFillRemaining(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.9),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.1),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Icon(
                    Icons.receipt_long_outlined,
                    size: 60,
                    color: Colors.green.shade700,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  'No hay pagos registrados',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Los pagos que registres aparecerán aquí',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) {
            final pago = _pagos[index];
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _buildPagoCard(pago),
            );
          },
          childCount: _pagos.length,
        ),
      ),
    );
  }

  Widget _buildPendientesSlivers() {
    if (_isLoadingFacturas) {
      return SliverFillRemaining(
        child: const Center(
          child: CircularProgressIndicator(color: Colors.green),
        ),
      );
    }

    if (_facturasCredito.isEmpty) {
      return SliverFillRemaining(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.9),
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.1),
                        blurRadius: 20,
                        offset: const Offset(0, 10),
                      ),
                    ],
                  ),
                  child: Icon(
                    Icons.check_circle_outline,
                    size: 60,
                    color: Colors.green.shade700,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  '¡Todo al día!',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'No hay facturas pendientes de pago',
                  style: TextStyle(
                    fontSize: 14,
                    color: Colors.grey.shade600,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) {
            final factura = _facturasCredito[index];
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _buildFacturaCard(factura),
            );
          },
          childCount: _facturasCredito.length,
        ),
      ),
    );
  }

  // Métodos antiguos de tabs (ya no se usan pero los mantengo por si acaso)
  Widget _buildHistorialTab() {
    if (_isLoadingPagos) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    if (_pagos.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.9),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 20,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Icon(
                  Icons.receipt_long_outlined,
                  size: 60,
                  color: Colors.green.shade700,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'No hay pagos registrados',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey.shade800,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Los pagos que registres aparecerán aquí',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarPagos,
      color: Colors.green,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
        itemCount: _pagos.length,
        itemBuilder: (context, index) {
          final pago = _pagos[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _buildPagoCard(pago),
          );
        },
      ),
    );
  }

  Widget _buildPendientesTab() {
    if (_isLoadingFacturas) {
      return const Center(
        child: CircularProgressIndicator(color: Colors.green),
      );
    }

    if (_facturasCredito.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.9),
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 20,
                      offset: const Offset(0, 10),
                    ),
                  ],
                ),
                child: Icon(
                  Icons.check_circle_outline,
                  size: 60,
                  color: Colors.green.shade700,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'No hay facturas pendientes',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey.shade800,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                '¡Todas las facturas están al día!',
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarFacturasCredito,
      color: Colors.green,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
        itemCount: _facturasCredito.length,
        itemBuilder: (context, index) {
          final factura = _facturasCredito[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: _buildFacturaCard(factura),
          );
        },
      ),
    );
  }

  Widget _buildPagoCard(Pago pago) {
    final formatoFecha = DateFormat('dd/MM/yyyy');
    final formatoMoneda = NumberFormat.currency(symbol: 'Q', decimalDigits: 2);

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
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.green.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.payment,
                    color: Colors.green.shade700,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Factura ${pago.numeroNota ?? 'N/A'}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        pago.clienteNombre ?? 'Cliente',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  formatoMoneda.format(pago.montoPago),
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.green.shade700,
                  ),
                ),
              ],
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Divider(height: 1),
            ),
            Row(
              children: [
                Icon(Icons.calendar_today, size: 14, color: Colors.grey.shade600),
                const SizedBox(width: 6),
                Text(
                  formatoFecha.format(DateTime.parse(pago.fechaPago)),
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
                const SizedBox(width: 16),
                Icon(Icons.account_balance, size: 14, color: Colors.grey.shade600),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    pago.banco,
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.receipt, size: 14, color: Colors.grey.shade600),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    'Ref: ${pago.referenciaTransaccion}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFacturaCard(FacturaCredito factura) {
    final formatoFecha = DateFormat('dd/MM/yyyy');
    final formatoMoneda = NumberFormat.currency(symbol: 'Q', decimalDigits: 2);

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
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade50,
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.pending_actions,
                    color: Colors.orange.shade700,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Factura ${factura.numeroNota}',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        factura.clienteNombre,
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 12),
              child: Divider(height: 1),
            ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Total',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                    Text(
                      formatoMoneda.format(factura.total),
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.black87,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Pagado',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                    Text(
                      formatoMoneda.format(factura.totalPagado),
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.green.shade700,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Saldo',
                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                    ),
                    Text(
                      formatoMoneda.format(factura.saldoPendiente),
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: Colors.orange.shade700,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.calendar_today, size: 14, color: Colors.grey.shade600),
                const SizedBox(width: 6),
                Text(
                  formatoFecha.format(DateTime.parse(factura.fecha)),
                  style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                ),
              ],
            ),
          ],
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
