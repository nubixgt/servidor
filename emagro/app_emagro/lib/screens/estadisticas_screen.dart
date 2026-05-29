import 'package:flutter/material.dart';
import '../services/nota_envio_service.dart';
import '../services/pago_service.dart';
import '../models/nota_envio.dart';
import '../models/pago.dart';
import '../widgets/app_drawer.dart';
import 'clientes_screen.dart';
import 'ventas_screen.dart';
import 'productos_screen.dart';
import 'pagos_screen.dart';

class EstadisticasScreen extends StatefulWidget {
  const EstadisticasScreen({Key? key}) : super(key: key);

  @override
  State<EstadisticasScreen> createState() => _EstadisticasScreenState();
}

class _EstadisticasScreenState extends State<EstadisticasScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String _errorMessage = '';

  // Datos calculados
  double _ventasTotales = 0.0;
  double _montoRecaudado = 0.0;
  double _cuentasPorCobrar = 0.0;
  int _clientesActivosCount = 0;

  List<MapEntry<String, double>> _topProductos = [];
  List<MapEntry<String, double>> _topClientes = [];
  List<MapEntry<String, double>> _topVendedores = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _cargarDatos();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _cargarDatos() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      // 1. Obtener notas de envío y pagos de forma paralela
      final resultados = await Future.wait([
        NotaEnvioService().listarNotas(),
        PagoService().listarPagos(),
      ]);

      final notasResult = resultados[0];
      final pagosResult = resultados[1];

      if (!notasResult['success']) {
        throw Exception(notasResult['message'] ?? 'Error al cargar notas');
      }
      if (!pagosResult['success']) {
        throw Exception(pagosResult['message'] ?? 'Error al cargar pagos');
      }

      final List<NotaEnvio> notas = List<NotaEnvio>.from(notasResult['notas'] ?? []);
      final List<Pago> pagos = List<Pago>.from(pagosResult['pagos'] ?? []);

      // 2. Procesar estadísticas básicas
      double ventasTotales = 0.0;
      double totalContado = 0.0;
      double totalCredito = 0.0;
      Set<int> clientesUnicos = {};

      Map<String, double> productosContador = {};
      Map<String, double> clientesCompras = {};
      Map<String, double> vendedoresVentas = {};

      for (var nota in notas) {
        ventasTotales += nota.total;
        clientesUnicos.add(nota.clienteId);

        if (nota.tipoVenta == 'Contado') {
          totalContado += nota.total;
        } else if (nota.tipoVenta == 'Crédito') {
          totalCredito += nota.total;
        }

        // Top Vendedores (por monto de venta)
        String vendedor = nota.vendedor.trim();
        if (vendedor.isNotEmpty) {
          vendedoresVentas[vendedor] = (vendedoresVentas[vendedor] ?? 0.0) + nota.total;
        }

        // Top Clientes (por monto total comprado)
        String cliente = nota.clienteNombre.trim();
        if (cliente.isNotEmpty) {
          clientesCompras[cliente] = (clientesCompras[cliente] ?? 0.0) + nota.total;
        }

        // Top Productos (por cantidad total vendida)
        for (var prod in nota.productos) {
          String prodKey = '${prod.producto} (${prod.presentacion})';
          productosContador[prodKey] = (productosContador[prodKey] ?? 0.0) + prod.cantidad.toDouble();
        }
      }

      // Sumar todos los abonos a facturas a crédito
      double totalAbonos = 0.0;
      for (var pago in pagos) {
        totalAbonos += pago.montoPago;
      }

      // Monto Recaudado = Ventas de contado + todos los abonos registrados
      double montoRecaudado = totalContado + totalAbonos;
      // Cuentas por Cobrar = Ventas a crédito - abonos registrados
      double cuentasPorCobrar = totalCredito - totalAbonos;
      if (cuentasPorCobrar < 0) cuentasPorCobrar = 0.0;

      // Ordenar y tomar los top 5
      var topProdsSorted = productosContador.entries.toList()
        ..sort((a, b) => b.value.compareTo(a.value));
      var topClientesSorted = clientesCompras.entries.toList()
        ..sort((a, b) => b.value.compareTo(a.value));
      var topVendedoresSorted = vendedoresVentas.entries.toList()
        ..sort((a, b) => b.value.compareTo(a.value));

      if (mounted) {
        setState(() {
          _ventasTotales = ventasTotales;
          _montoRecaudado = montoRecaudado;
          _cuentasPorCobrar = cuentasPorCobrar;
          _clientesActivosCount = clientesUnicos.length;

          _topProductos = topProdsSorted.take(5).toList();
          _topClientes = topClientesSorted.take(5).toList();
          _topVendedores = topVendedoresSorted.take(5).toList();
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
          _isLoading = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: const Text(
          'Estadísticas',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: 22,
          ),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white),
            onPressed: _cargarDatos,
          ),
        ],
      ),
      drawer: const AppDrawer(),
      body: Stack(
        children: [
          // Fondo Gradiente Fijo a tono con Emagro
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

          SafeArea(
            child: _isLoading
                ? _buildLoadingState()
                : _errorMessage.isNotEmpty
                    ? _buildErrorState()
                    : _buildContentState(),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(
            color: Colors.yellow.shade700,
            strokeWidth: 4,
          ),
          const SizedBox(height: 16),
          const Text(
            'Cargando métricas de producción...',
            style: TextStyle(color: Colors.white70, fontSize: 16),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 64, color: Colors.redAccent),
            const SizedBox(height: 16),
            const Text(
              'Ocurrió un error al cargar los datos',
              style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Text(
              _errorMessage,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.white70, fontSize: 14),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _cargarDatos,
              icon: const Icon(Icons.refresh),
              label: const Text('Reintentar'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: Colors.green.shade900,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContentState() {
    return Column(
      children: [
        // TabBar con diseño curado
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 8.0),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.1),
              borderRadius: BorderRadius.circular(25),
              border: Border.all(color: Colors.white.withOpacity(0.2)),
            ),
            child: TabBar(
              controller: _tabController,
              indicator: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(25),
              ),
              labelColor: Colors.green.shade900,
              unselectedLabelColor: Colors.white,
              labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
              tabs: const [
                Tab(text: 'General'),
                Tab(text: 'Productos'),
                Tab(text: 'Clientes'),
                Tab(text: 'Ventas'),
              ],
            ),
          ),
        ),

        // Contenedor principal de pestañas
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              _buildGeneralTab(),
              _buildProductosTab(),
              _buildClientesTab(),
              _buildVendedoresTab(),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildGeneralTab() {
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Resumen Financiero',
            style: TextStyle(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 16),

          // Grid de Tarjetas de KPI Premium con Glassmorphism
          GridView.count(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisCount: 2,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
            childAspectRatio: 1.1,
            children: [
              _buildKPICard(
                title: 'Ventas Totales',
                value: 'Q. ${_ventasTotales.toStringAsFixed(2)}',
                icon: Icons.monetization_on_outlined,
                gradientColors: [Colors.amber.shade700, Colors.orange.shade800],
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen())),
              ),
              _buildKPICard(
                title: 'Total Cobrado',
                value: 'Q. ${_montoRecaudado.toStringAsFixed(2)}',
                icon: Icons.account_balance_wallet_outlined,
                gradientColors: [Colors.green.shade700, Colors.teal.shade800],
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PagosScreen())),
              ),
              _buildKPICard(
                title: 'Por Cobrar',
                value: 'Q. ${_cuentasPorCobrar.toStringAsFixed(2)}',
                icon: Icons.pending_actions_outlined,
                gradientColors: [Colors.deepOrange.shade600, Colors.red.shade800],
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PagosScreen())),
              ),
              _buildKPICard(
                title: 'Clientes Activos',
                value: '$_clientesActivosCount',
                icon: Icons.people_alt_outlined,
                gradientColors: [Colors.blue.shade700, Colors.indigo.shade800],
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ClientesScreen())),
              ),
            ],
          ),

          const SizedBox(height: 32),
          _buildHealthIndicator(),
        ],
      ),
    );
  }

  Widget _buildKPICard({
    required String title,
    required String value,
    required IconData icon,
    required List<Color> gradientColors,
    required VoidCallback onTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: gradientColors,
        ),
        boxShadow: [
          BoxShadow(
            color: gradientColors[1].withOpacity(0.4),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(20),
          splashColor: Colors.white24,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Icon(icon, color: Colors.white, size: 28),
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Colors.white54,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ],
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 12,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                    const SizedBox(height: 4),
                    FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(
                        value,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                        ),
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

  Widget _buildHealthIndicator() {
    double ratio = _ventasTotales > 0 ? (_montoRecaudado / _ventasTotales) : 0.0;
    String percentage = (ratio * 100).toStringAsFixed(1);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.insights, color: Colors.green),
              SizedBox(width: 8),
              Text(
                'Efectividad de Recaudación',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          LinearProgressIndicator(
            value: ratio,
            backgroundColor: Colors.grey.shade200,
            color: Colors.green.shade600,
            minHeight: 12,
            borderRadius: BorderRadius.circular(6),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '$percentage% Cobrado de Facturación',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey.shade700,
                ),
              ),
              Text(
                'Meta: 90%',
                style: TextStyle(
                  fontSize: 13,
                  color: Colors.grey.shade500,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildProductosTab() {
    return _buildRankingList(
      title: 'Top 5 Productos más Vendidos',
      subtitle: 'Cantidad de unidades despachadas (Toca para ver)',
      items: _topProductos,
      formatValue: (val) => '${val.toInt()} uds.',
      icon: Icons.inventory_2_outlined,
      barColor: Colors.teal.shade500,
      onItemTap: (key) {
        Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductosScreen()));
      },
    );
  }

  Widget _buildClientesTab() {
    return _buildRankingList(
      title: 'Top 5 Clientes Principales',
      subtitle: 'Volumen total de facturación (Toca para ver)',
      items: _topClientes,
      formatValue: (val) => 'Q. ${val.toStringAsFixed(2)}',
      icon: Icons.people_alt_outlined,
      barColor: Colors.blue.shade500,
      onItemTap: (key) {
        Navigator.push(context, MaterialPageRoute(builder: (_) => const ClientesScreen()));
      },
    );
  }

  Widget _buildVendedoresTab() {
    return _buildRankingList(
      title: 'Ventas por Asesor Comercial',
      subtitle: 'Monto total colocado por vendedor (Toca para ver)',
      items: _topVendedores,
      formatValue: (val) => 'Q. ${val.toStringAsFixed(2)}',
      icon: Icons.person_outline,
      barColor: Colors.orange.shade500,
      onItemTap: (key) {
        Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen()));
      },
    );
  }

  Widget _buildRankingList({
    required String title,
    required String subtitle,
    required List<MapEntry<String, double>> items,
    required String Function(double) formatValue,
    required IconData icon,
    required Color barColor,
    void Function(String)? onItemTap,
  }) {
    if (items.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.hourglass_empty, size: 48, color: Colors.grey.shade400),
            const SizedBox(height: 12),
            const Text(
              'No hay suficientes registros para generar este ranking.',
              style: TextStyle(color: Colors.black54),
            ),
          ],
        ),
      );
    }

    double maxValue = items.first.value;
    if (maxValue == 0) maxValue = 1.0;

    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 15,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon, color: barColor),
                const SizedBox(width: 8),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              subtitle,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade500,
              ),
            ),
            const SizedBox(height: 24),
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: items.length,
              separatorBuilder: (context, index) => const SizedBox(height: 20),
              itemBuilder: (context, index) {
                final entry = items[index];
                double percentage = entry.value / maxValue;

                return InkWell(
                  onTap: onItemTap != null ? () => onItemTap(entry.key) : null,
                  borderRadius: BorderRadius.circular(8),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 4.0, horizontal: 8.0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Text(
                                '${index + 1}. ${entry.key}',
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.black87,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            Text(
                              formatValue(entry.value),
                              style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  color: barColor,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),
                        TweenAnimationBuilder<double>(
                          tween: Tween<double>(begin: 0, end: percentage),
                          duration: const Duration(milliseconds: 800),
                          curve: Curves.easeOutCubic,
                          builder: (context, val, child) {
                            return Container(
                              height: 10,
                              width: double.infinity,
                              decoration: BoxDecoration(
                                color: Colors.grey.shade100,
                                borderRadius: BorderRadius.circular(5),
                              ),
                              child: FractionallySizedBox(
                                alignment: Alignment.centerLeft,
                                widthFactor: val.clamp(0.0, 1.0),
                                child: Container(
                                  decoration: BoxDecoration(
                                    color: barColor,
                                    borderRadius: BorderRadius.circular(5),
                                  ),
                                ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
