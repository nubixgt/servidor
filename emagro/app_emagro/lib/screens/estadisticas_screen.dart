import 'package:flutter/material.dart';
import '../services/nota_envio_service.dart';
import '../services/pago_service.dart';
import '../services/producto_service.dart';
import '../services/cliente_service.dart';
import '../models/nota_envio.dart';
import '../models/pago.dart';
import '../widgets/app_drawer.dart';
import 'clientes_screen.dart';
import 'ventas_screen.dart';
import 'productos_screen.dart';
import 'pagos_screen.dart';
import '../models/cliente.dart';

enum FiltroPeriodo {
  historico,
  esteMes,
  mesAnterior,
  ultimos3Meses,
  ultimos6Meses,
  personalizado
}

String nombreMes(int mes) {
  const nombres = [
    'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
    'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'
  ];
  if (mes >= 1 && mes <= 12) {
    return nombres[mes - 1];
  }
  return '';
}

String formatFechaCorta(DateTime fecha) {
  return '${fecha.day.toString().padLeft(2, '0')}/${fecha.month.toString().padLeft(2, '0')}/${fecha.year}';
}

String formatMonedaCorta(double valor) {
  if (valor >= 1000000) {
    return 'Q ${(valor / 1000000).toStringAsFixed(1)}M';
  } else if (valor >= 1000) {
    return 'Q ${(valor / 1000).toStringAsFixed(1)}k';
  } else {
    return 'Q ${valor.toStringAsFixed(0)}';
  }
}

Map<String, String> obtenerFechasPeriodoTexto(FiltroPeriodo filtro, DateTimeRange? rangoPers) {
  DateTime ahora = DateTime.now();
  DateTime inicio;
  DateTime fin;

  switch (filtro) {
    case FiltroPeriodo.historico:
      return {'titulo': 'Histórico', 'fechas': 'Todos los registros desde el inicio'};
    case FiltroPeriodo.esteMes:
      inicio = DateTime(ahora.year, ahora.month, 1);
      fin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
      return {
        'titulo': 'Este Mes',
        'fechas': '${formatFechaCorta(inicio)} al ${formatFechaCorta(fin)}'
      };
    case FiltroPeriodo.mesAnterior:
      inicio = DateTime(ahora.year, ahora.month - 1, 1);
      fin = DateTime(ahora.year, ahora.month, 1).subtract(const Duration(days: 1));
      return {
        'titulo': 'Mes Anterior',
        'fechas': '${formatFechaCorta(inicio)} al ${formatFechaCorta(fin)}'
      };
    case FiltroPeriodo.ultimos3Meses:
      inicio = DateTime(ahora.year, ahora.month - 2, 1);
      fin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
      return {
        'titulo': 'Últimos 3 Meses',
        'fechas': '${formatFechaCorta(inicio)} al ${formatFechaCorta(fin)}'
      };
    case FiltroPeriodo.ultimos6Meses:
      inicio = DateTime(ahora.year, ahora.month - 5, 1);
      fin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
      return {
        'titulo': 'Últimos 6 Meses',
        'fechas': '${formatFechaCorta(inicio)} al ${formatFechaCorta(fin)}'
      };
    case FiltroPeriodo.personalizado:
      if (rangoPers != null) {
        return {
          'titulo': 'Rango Personalizado',
          'fechas': '${formatFechaCorta(rangoPers.start)} al ${formatFechaCorta(rangoPers.end)}'
        };
      }
      return {'titulo': 'Rango Personalizado', 'fechas': 'Seleccionar rango...'};
  }
}

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

  // Nuevas métricas para gráfica y resumen
  int _ventasCount = 0;
  int _productosCount = 0;
  int _clientesTotalesCount = 0;

  List<MapEntry<String, double>> _topProductos = [];
  List<MapEntry<String, double>> _topClientes = [];
  List<MapEntry<String, double>> _topVendedores = [];

  // Datos históricos guardados
  List<NotaEnvio> _allNotas = [];
  List<Pago> _allPagos = [];
  List<String> _allProductos = [];
  List<Cliente> _allClientes = [];
  int _allClientesTotalesCount = 0;

  // Variables de filtrado
  FiltroPeriodo _filtroSeleccionado = FiltroPeriodo.historico;
  DateTimeRange? _rangoPersonalizado;

  // Variables de tendencia por producto
  String? _productoSeleccionadoTendencia;
  List<MapEntry<String, double>> _tendenciaProducto = [];
  int _clientesCreadosCount = 0;

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
      final resultados = await Future.wait([
        NotaEnvioService().listarNotas(),
        PagoService().listarPagos(),
        ProductoService().listarProductos(),
        ClienteService().listarClientes(),
      ]);

      final notasResult = resultados[0];
      final pagosResult = resultados[1];
      final productosResult = resultados[2];
      final clientesResult = resultados[3];

      if (!notasResult['success']) {
        throw Exception(notasResult['message'] ?? 'Error al cargar notas');
      }
      if (!pagosResult['success']) {
        throw Exception(pagosResult['message'] ?? 'Error al cargar pagos');
      }
      if (!productosResult['success']) {
        throw Exception(productosResult['message'] ?? 'Error al cargar productos');
      }
      if (!clientesResult['success']) {
        throw Exception(clientesResult['message'] ?? 'Error al cargar clientes');
      }

      final List<NotaEnvio> notas = List<NotaEnvio>.from(notasResult['notas'] ?? []);
      final List<Pago> pagos = List<Pago>.from(pagosResult['pagos'] ?? []);
      final List<String> productos = List<String>.from(productosResult['productos'] ?? []);
      final List<Cliente> clientes = List<Cliente>.from(clientesResult['clientes'] ?? []);
      final int clientesTotalesCount = clientesResult['total'] ?? clientes.length;

      _allNotas = notas;
      _allPagos = pagos;
      _allProductos = productos;
      _allClientes = clientes;
      _allClientesTotalesCount = clientesTotalesCount;

      _filtrarYProcesarDatos();
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
          _isLoading = false;
        });
      }
    }
  }

  void _filtrarYProcesarDatos() {
    DateTime ahora = DateTime.now();
    DateTime? fechaInicio;
    DateTime? fechaFin;

    switch (_filtroSeleccionado) {
      case FiltroPeriodo.historico:
        break;
      case FiltroPeriodo.esteMes:
        fechaInicio = DateTime(ahora.year, ahora.month, 1);
        fechaFin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
        break;
      case FiltroPeriodo.mesAnterior:
        fechaInicio = DateTime(ahora.year, ahora.month - 1, 1);
        fechaFin = DateTime(ahora.year, ahora.month, 1).subtract(const Duration(days: 1));
        break;
      case FiltroPeriodo.ultimos3Meses:
        fechaInicio = DateTime(ahora.year, ahora.month - 2, 1);
        fechaFin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
        break;
      case FiltroPeriodo.ultimos6Meses:
        fechaInicio = DateTime(ahora.year, ahora.month - 5, 1);
        fechaFin = DateTime(ahora.year, ahora.month + 1, 1).subtract(const Duration(days: 1));
        break;
      case FiltroPeriodo.personalizado:
        if (_rangoPersonalizado != null) {
          fechaInicio = _rangoPersonalizado!.start;
          fechaFin = _rangoPersonalizado!.end;
        }
        break;
    }

    List<NotaEnvio> notasFiltradas = [];
    for (var nota in _allNotas) {
      DateTime? fechaNota = DateTime.tryParse(nota.fecha);
      if (fechaNota == null && nota.fecha.length >= 10) {
        fechaNota = DateTime.tryParse(nota.fecha.substring(0, 10));
      }
      if (fechaNota != null) {
        if (fechaInicio != null && fechaNota.isBefore(fechaInicio)) continue;
        if (fechaFin != null && fechaNota.isAfter(fechaFin.add(const Duration(hours: 23, minutes: 59, seconds: 59)))) continue;
      }
      notasFiltradas.add(nota);
    }

    List<Pago> pagosFiltrados = [];
    for (var pago in _allPagos) {
      DateTime? fechaPago = DateTime.tryParse(pago.fechaPago);
      if (fechaPago == null && pago.fechaPago.length >= 10) {
        fechaPago = DateTime.tryParse(pago.fechaPago.substring(0, 10));
      }
      if (fechaPago != null) {
        if (fechaInicio != null && fechaPago.isBefore(fechaInicio)) continue;
        if (fechaFin != null && fechaPago.isAfter(fechaFin.add(const Duration(hours: 23, minutes: 59, seconds: 59)))) continue;
      }
      pagosFiltrados.add(pago);
    }

    double ventasTotales = 0.0;
    double totalContado = 0.0;
    double totalCredito = 0.0;
    Set<int> clientesUnicos = {};

    Map<String, double> productosContador = {};
    Map<String, double> clientesCompras = {};
    Map<String, double> vendedoresVentas = {};

    for (var nota in notasFiltradas) {
      ventasTotales += nota.total;
      clientesUnicos.add(nota.clienteId);

      if (nota.tipoVenta == 'Contado') {
        totalContado += nota.total;
      } else if (nota.tipoVenta == 'Crédito') {
        totalCredito += nota.total;
      }

      String vendedor = nota.vendedor.trim();
      if (vendedor.isNotEmpty) {
        vendedoresVentas[vendedor] = (vendedoresVentas[vendedor] ?? 0.0) + nota.total;
      }

      String cliente = nota.clienteNombre.trim();
      if (cliente.isNotEmpty) {
        clientesCompras[cliente] = (clientesCompras[cliente] ?? 0.0) + nota.total;
      }

      for (var prod in nota.productos) {
        String prodKey = '${prod.producto} (${prod.presentacion})';
        productosContador[prodKey] = (productosContador[prodKey] ?? 0.0) + prod.cantidad.toDouble();
      }
    }

    double totalAbonos = 0.0;
    for (var pago in pagosFiltrados) {
      totalAbonos += pago.montoPago;
    }

    double montoRecaudado = totalContado + totalAbonos;
    double cuentasPorCobrar = totalCredito - totalAbonos;
    if (cuentasPorCobrar < 0) cuentasPorCobrar = 0.0;

    var topProdsSorted = productosContador.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));
    var topClientesSorted = clientesCompras.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));
    var topVendedoresSorted = vendedoresVentas.entries.toList()
      ..sort((a, b) => b.value.compareTo(a.value));

    // Calcular clientes creados en período
    int clientesCreadosEnPeriodo = 0;
    for (var cliente in _allClientes) {
      if (cliente.fechaCreacion != null) {
        DateTime? fecha = DateTime.tryParse(cliente.fechaCreacion!);
        if (fecha == null && cliente.fechaCreacion!.length >= 10) {
          fecha = DateTime.tryParse(cliente.fechaCreacion!.substring(0, 10));
        }
        if (fecha != null) {
          if (fechaInicio != null && fecha.isBefore(fechaInicio)) continue;
          if (fechaFin != null && fecha.isAfter(fechaFin.add(const Duration(hours: 23, minutes: 59, seconds: 59)))) continue;
          clientesCreadosEnPeriodo++;
        }
      }
    }

    _procesarTendenciaProducto();

    if (mounted) {
      setState(() {
        _ventasTotales = ventasTotales;
        _montoRecaudado = montoRecaudado;
        _cuentasPorCobrar = cuentasPorCobrar;
        _clientesActivosCount = clientesUnicos.length;

        _ventasCount = notasFiltradas.length;
        _productosCount = _filtroSeleccionado == FiltroPeriodo.historico 
            ? _allProductos.length 
            : productosContador.length;
        _clientesTotalesCount = _allClientesTotalesCount;
        _clientesCreadosCount = clientesCreadosEnPeriodo;

        _topProductos = topProdsSorted.take(5).toList();
        _topClientes = topClientesSorted.take(5).toList();
        _topVendedores = topVendedoresSorted.take(5).toList();
        _isLoading = false;
      });
    }
  }

  void _procesarTendenciaProducto() {
    if (_productoSeleccionadoTendencia == null && _allNotas.isNotEmpty) {
      Set<String> prods = {};
      for (var n in _allNotas) {
        for (var p in n.productos) {
          prods.add(p.producto);
        }
      }
      if (prods.isNotEmpty) {
        _productoSeleccionadoTendencia = (prods.toList()..sort()).first;
      }
    }

    if (_productoSeleccionadoTendencia == null) {
      _tendenciaProducto = [];
      return;
    }

    DateTime ahora = DateTime.now();
    List<DateTime> meses = [];
    for (int i = 5; i >= 0; i--) {
      meses.add(DateTime(ahora.year, ahora.month - i, 1));
    }

    Map<String, double> agrupado = {};
    for (var m in meses) {
      String key = '${m.year}-${m.month.toString().padLeft(2, '0')}';
      agrupado[key] = 0.0;
    }

    for (var nota in _allNotas) {
      DateTime? fecha = DateTime.tryParse(nota.fecha);
      if (fecha == null && nota.fecha.length >= 10) {
        fecha = DateTime.tryParse(nota.fecha.substring(0, 10));
      }
      if (fecha != null) {
        String key = '${fecha.year}-${fecha.month.toString().padLeft(2, '0')}';
        if (agrupado.containsKey(key)) {
          for (var prod in nota.productos) {
            if (prod.producto == _productoSeleccionadoTendencia) {
              agrupado[key] = agrupado[key]! + prod.cantidad.toDouble();
            }
          }
        }
      }
    }

    _tendenciaProducto = agrupado.entries.map((entry) {
      List<String> partes = entry.key.split('-');
      int anio = int.parse(partes[0]);
      int mes = int.parse(partes[1]);
      String nombre = '${nombreMes(mes)} ${anio.toString().substring(2)}';
      return MapEntry(nombre, entry.value);
    }).toList();
  }

  List<MapEntry<String, double>> _calcularVentasMensualesGenerales() {
    DateTime ahora = DateTime.now();
    List<DateTime> meses = [];
    for (int i = 5; i >= 0; i--) {
      meses.add(DateTime(ahora.year, ahora.month - i, 1));
    }

    Map<String, double> agrupado = {};
    for (var m in meses) {
      String key = '${m.year}-${m.month.toString().padLeft(2, '0')}';
      agrupado[key] = 0.0;
    }

    for (var nota in _allNotas) {
      DateTime? fecha = DateTime.tryParse(nota.fecha);
      if (fecha == null && nota.fecha.length >= 10) {
        fecha = DateTime.tryParse(nota.fecha.substring(0, 10));
      }
      if (fecha != null) {
        String key = '${fecha.year}-${fecha.month.toString().padLeft(2, '0')}';
        if (agrupado.containsKey(key)) {
          agrupado[key] = agrupado[key]! + nota.total;
        }
      }
    }

    return agrupado.entries.map((entry) {
      List<String> partes = entry.key.split('-');
      int anio = int.parse(partes[0]);
      int mes = int.parse(partes[1]);
      String nombre = '${nombreMes(mes)} ${anio.toString().substring(2)}';
      return MapEntry(nombre, entry.value);
    }).toList();
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
        _buildFilterBar(),

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

  Widget _buildFilterBar() {
    final info = obtenerFechasPeriodoTexto(_filtroSeleccionado, _rangoPersonalizado);
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.15),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: Colors.white.withOpacity(0.25)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.yellow.shade700,
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.date_range, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Período: ${info['titulo']}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    info['fechas']!,
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.85),
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
            ElevatedButton(
              onPressed: _mostrarSeleccionadorPeriodo,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.white,
                foregroundColor: Colors.green.shade900,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('Filtrar', style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold)),
                  SizedBox(width: 4),
                  Icon(Icons.keyboard_arrow_down, size: 16),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _mostrarSeleccionadorPeriodo() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) {
        return Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: const BorderRadius.only(
              topLeft: Radius.circular(24),
              topRight: Radius.circular(24),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.15),
                blurRadius: 10,
                offset: const Offset(0, -5),
              ),
            ],
          ),
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Período de Análisis',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              _buildPeriodOption(FiltroPeriodo.historico, 'Histórico (Todo)'),
              _buildPeriodOption(FiltroPeriodo.esteMes, 'Este Mes'),
              _buildPeriodOption(FiltroPeriodo.mesAnterior, 'Mes Anterior'),
              _buildPeriodOption(FiltroPeriodo.ultimos3Meses, 'Últimos 3 Meses'),
              _buildPeriodOption(FiltroPeriodo.ultimos6Meses, 'Últimos 6 Meses'),
              _buildPeriodOption(FiltroPeriodo.personalizado, 'Rango Personalizado...'),
              const SizedBox(height: 16),
            ],
          ),
        );
      },
    );
  }

  Widget _buildPeriodOption(FiltroPeriodo filtro, String titulo) {
    final info = obtenerFechasPeriodoTexto(filtro, _rangoPersonalizado);
    final esSeleccionado = _filtroSeleccionado == filtro;

    return ListTile(
      contentPadding: EdgeInsets.zero,
      title: Text(
        titulo,
        style: TextStyle(
          fontWeight: esSeleccionado ? FontWeight.bold : FontWeight.normal,
          color: esSeleccionado ? Colors.green.shade900 : Colors.black87,
        ),
      ),
      subtitle: Text(
        info['fechas']!,
        style: TextStyle(
          color: esSeleccionado ? Colors.green.shade700 : Colors.grey.shade600,
          fontSize: 12,
        ),
      ),
      trailing: esSeleccionado
          ? Icon(Icons.check_circle, color: Colors.green.shade700)
          : null,
      onTap: () async {
        Navigator.pop(context);
        if (filtro == FiltroPeriodo.personalizado) {
          final rango = await showDateRangePicker(
            context: context,
            initialDateRange: _rangoPersonalizado ??
                DateTimeRange(
                  start: DateTime.now().subtract(const Duration(days: 30)),
                  end: DateTime.now(),
                ),
            firstDate: DateTime(2020),
            lastDate: DateTime(2030),
            builder: (context, child) {
              return Theme(
                data: Theme.of(context).copyWith(
                  colorScheme: ColorScheme.light(
                    primary: Colors.green.shade900,
                    onPrimary: Colors.white,
                    onSurface: Colors.black87,
                  ),
                ),
                child: child!,
              );
            },
          );
          if (rango != null) {
            setState(() {
              _filtroSeleccionado = FiltroPeriodo.personalizado;
              _rangoPersonalizado = rango;
            });
            _filtrarYProcesarDatos();
          }
        } else {
          setState(() {
            _filtroSeleccionado = filtro;
          });
          _filtrarYProcesarDatos();
        }
      },
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
          const SizedBox(height: 32),
          _buildGeneralChart(),
          const SizedBox(height: 32),
          _buildVentasMensualesChart(),
          const SizedBox(height: 32),
          _buildStatisticalSummary(),
        ],
      ),
    );
  }

  Widget _buildVentasMensualesChart() {
    final datos = _calcularVentasMensualesGenerales();
    double maxVal = datos.map((e) => e.value).fold(0.0, (max, val) => val > max ? val : max);
    if (maxVal == 0) maxVal = 1.0;

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
              Icon(Icons.analytics, color: Colors.blue),
              SizedBox(width: 8),
              Text(
                'Facturación Mensual (Últimos 6 Meses)',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Comparativa de facturación histórica por meses',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade500,
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 170,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: datos.map((entry) {
                double pct = entry.value / maxVal;
                return Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        child: Text(
                          formatMonedaCorta(entry.value),
                          style: const TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                            color: Colors.black87,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Expanded(
                        child: FractionallySizedBox(
                          heightFactor: pct.clamp(0.02, 1.0),
                          child: TweenAnimationBuilder<double>(
                            tween: Tween<double>(begin: 0.0, end: pct),
                            duration: const Duration(milliseconds: 800),
                            curve: Curves.easeOutCubic,
                            builder: (context, value, child) {
                              return Container(
                                width: 22,
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                    colors: [
                                      Colors.green.shade700,
                                      Colors.green.shade400,
                                    ],
                                  ),
                                  borderRadius: const BorderRadius.only(
                                    topLeft: Radius.circular(6),
                                    topRight: Radius.circular(6),
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.green.shade700.withOpacity(0.15),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        entry.key,
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey.shade600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
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
    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      child: Column(
        children: [
          _buildRankingList(
            title: 'Top 5 Productos más Vendidos',
            subtitle: 'Cantidad de unidades despachadas en el período (Toca para ver)',
            items: _topProductos,
            formatValue: (val) => '${val.toInt()} uds.',
            icon: Icons.inventory_2_outlined,
            barColor: Colors.teal.shade500,
            onItemTap: (key) {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductosScreen()));
            },
            insideScroll: true,
          ),
          const SizedBox(height: 24),
          _buildProductTrendSection(),
        ],
      ),
    );
  }

  Widget _buildProductTrendSection() {
    Set<String> uniqueProductsSet = {};
    for (var nota in _allNotas) {
      for (var prod in nota.productos) {
        uniqueProductsSet.add(prod.producto);
      }
    }
    List<String> listProductos = uniqueProductsSet.toList()..sort();

    if (listProductos.isEmpty) {
      return Container(
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
        child: const Center(
          child: Text(
            'No hay productos vendidos registrados para mostrar tendencias.',
            style: TextStyle(color: Colors.black54),
          ),
        ),
      );
    }

    if (_productoSeleccionadoTendencia == null || !listProductos.contains(_productoSeleccionadoTendencia)) {
      _productoSeleccionadoTendencia = listProductos.first;
      _procesarTendenciaProducto();
    }

    double maxVal = _tendenciaProducto.map((e) => e.value).fold(0.0, (max, val) => val > max ? val : max);
    if (maxVal == 0) maxVal = 1.0;

    return Container(
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
          const Row(
            children: [
              Icon(Icons.trending_up, color: Colors.teal),
              SizedBox(width: 8),
              Text(
                'Estacionalidad del Producto',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Analiza las cantidades vendidas mes a mes en el último semestre',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade500,
            ),
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(15),
              border: Border.all(color: Colors.grey.shade200),
            ),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<String>(
                value: _productoSeleccionadoTendencia,
                isExpanded: true,
                icon: const Icon(Icons.arrow_drop_down, color: Colors.teal),
                style: const TextStyle(
                  color: Colors.black87,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
                items: listProductos.map((prod) {
                  return DropdownMenuItem<String>(
                    value: prod,
                    child: Text(prod, overflow: TextOverflow.ellipsis),
                  );
                }).toList(),
                onChanged: (val) {
                  if (val != null) {
                    setState(() {
                      _productoSeleccionadoTendencia = val;
                      _procesarTendenciaProducto();
                    });
                  }
                },
              ),
            ),
          ),
          const SizedBox(height: 24),
          SizedBox(
            height: 150,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              crossAxisAlignment: CrossAxisAlignment.end,
              children: _tendenciaProducto.map((entry) {
                double pct = entry.value / maxVal;
                return Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      FittedBox(
                        fit: BoxFit.scaleDown,
                        child: Text(
                          '${entry.value.toInt()} uds',
                          style: const TextStyle(
                            fontSize: 9,
                            fontWeight: FontWeight.bold,
                            color: Colors.black87,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Expanded(
                        child: FractionallySizedBox(
                          heightFactor: pct.clamp(0.02, 1.0),
                          child: TweenAnimationBuilder<double>(
                            tween: Tween<double>(begin: 0.0, end: pct),
                            duration: const Duration(milliseconds: 800),
                            curve: Curves.easeOutCubic,
                            builder: (context, value, child) {
                              return Container(
                                width: 22,
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                    colors: [
                                      Colors.teal.shade700,
                                      Colors.teal.shade400,
                                    ],
                                  ),
                                  borderRadius: const BorderRadius.only(
                                    topLeft: Radius.circular(6),
                                    topRight: Radius.circular(6),
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.teal.shade700.withOpacity(0.15),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        entry.key,
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey.shade600,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ],
                  ),
                );
              }).toList(),
            ),
          ),
        ],
      ),
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
    bool insideScroll = false,
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

    final cardContent = Container(
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
    );

    if (insideScroll) {
      return cardContent;
    }

    return SingleChildScrollView(
      physics: const BouncingScrollPhysics(),
      padding: const EdgeInsets.all(16.0),
      child: cardContent,
    );
  }

  Widget _buildGeneralChart() {
    double maxVentasTarget = _ventasTotales > 30000 ? _ventasTotales : 30000.0;
    double maxPagosTarget = _ventasTotales > 0 ? _ventasTotales : 1.0;
    double maxCountTarget = 50.0;

    List<Map<String, dynamic>> metricas = [
      {
        'title': 'Ventas Totales',
        'value': 'Q. ${_ventasTotales.toStringAsFixed(2)}',
        'ratio': _ventasTotales / maxVentasTarget,
        'color': Colors.amber.shade600,
        'icon': Icons.monetization_on_outlined,
        'onTap': () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen())),
      },
      {
        'title': 'Monto Recaudado',
        'value': 'Q. ${_montoRecaudado.toStringAsFixed(2)}',
        'ratio': _montoRecaudado / maxPagosTarget,
        'color': Colors.green.shade600,
        'icon': Icons.account_balance_wallet_outlined,
        'onTap': () => Navigator.push(context, MaterialPageRoute(builder: (_) => const PagosScreen())),
      },
      {
        'title': 'Número de Ventas',
        'value': '$_ventasCount Notas',
        'ratio': _ventasCount / maxCountTarget,
        'color': Colors.purple.shade600,
        'icon': Icons.receipt_long_outlined,
        'onTap': () => Navigator.push(context, MaterialPageRoute(builder: (_) => const VentasScreen())),
      },
      {
        'title': 'Productos en Catálogo',
        'value': '$_productosCount Productos',
        'ratio': _productosCount / maxCountTarget,
        'color': Colors.teal.shade600,
        'icon': Icons.inventory_2_outlined,
        'onTap': () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ProductosScreen())),
      },
      {
        'title': 'Clientes Registrados',
        'value': '$_clientesTotalesCount Clientes',
        'ratio': _clientesTotalesCount / maxCountTarget,
        'color': Colors.blue.shade600,
        'icon': Icons.people_alt_outlined,
        'onTap': () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ClientesScreen())),
      },
    ];

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
              Icon(Icons.bar_chart_outlined, color: Colors.blueAccent),
              SizedBox(width: 8),
              Text(
                'Gráfica Comparativa de Métricas',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Resumen visual de volumen y conteos operativos (Toca para ir)',
            style: TextStyle(
              fontSize: 12,
              color: Colors.grey.shade500,
            ),
          ),
          const SizedBox(height: 24),
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: metricas.length,
            separatorBuilder: (context, index) => const SizedBox(height: 16),
            itemBuilder: (context, index) {
              final metrica = metricas[index];
              return InkWell(
                onTap: metrica['onTap'],
                borderRadius: BorderRadius.circular(8),
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4.0, horizontal: 4.0),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: (metrica['color'] as Color).withOpacity(0.1),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(metrica['icon'] as IconData, color: metrica['color'] as Color, size: 20),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  metrica['title'] as String,
                                  style: const TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.black87,
                                  ),
                                ),
                                Text(
                                  metrica['value'] as String,
                                  style: TextStyle(
                                    fontSize: 13,
                                    fontWeight: FontWeight.bold,
                                    color: metrica['color'] as Color,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 6),
                            TweenAnimationBuilder<double>(
                              tween: Tween<double>(begin: 0.0, end: metrica['ratio'] as double),
                              duration: const Duration(milliseconds: 1000),
                              curve: Curves.easeOutCubic,
                              builder: (context, val, child) {
                                return Container(
                                  height: 8,
                                  decoration: BoxDecoration(
                                    color: Colors.grey.shade100,
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                  child: FractionallySizedBox(
                                    alignment: Alignment.centerLeft,
                                    widthFactor: val.clamp(0.0, 1.0),
                                    child: Container(
                                      decoration: BoxDecoration(
                                        color: metrica['color'] as Color,
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                    ),
                                  ),
                                );
                              },
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildStatisticalSummary() {
    double ticketMedio = _ventasCount > 0 ? (_ventasTotales / _ventasCount) : 0.0;
    double efectividadRecaudacion = _ventasTotales > 0 ? (_montoRecaudado / _ventasTotales * 100) : 0.0;

    final info = obtenerFechasPeriodoTexto(_filtroSeleccionado, _rangoPersonalizado);
    final isHistorico = _filtroSeleccionado == FiltroPeriodo.historico;
    final periodoLabel = isHistorico ? 'histórica' : 'para el período seleccionado (${info['titulo']})';
    final acumuladoLabel = isHistorico ? 'acumulando' : 'registrando';

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Colors.green.shade900.withOpacity(0.05),
            Colors.green.shade50.withOpacity(0.1),
          ],
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: Colors.green.shade700.withOpacity(0.2),
          width: 1.5,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: Colors.amber.shade600.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.analytics_outlined, color: Colors.amber.shade700, size: 22),
              ),
              const SizedBox(width: 10),
              const Text(
                'Resumen Estadístico del Sistema',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  color: Colors.black87,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'El sistema agrícola Emagro reporta una facturación total $periodoLabel de Q. ${_ventasTotales.toStringAsFixed(2)} distribuida en un total de $_ventasCount ventas concretadas, lo que representa un ticket promedio de Q. ${ticketMedio.toStringAsFixed(2)} por cada nota de envío generada.',
            style: const TextStyle(
              fontSize: 13,
              color: Colors.black87,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Actualmente, se ha logrado una efectividad de caja del ${efectividadRecaudacion.toStringAsFixed(1)}%, $acumuladoLabel Q. ${_montoRecaudado.toStringAsFixed(2)} en efectivo real y pagos cobrados. El saldo activo por cobrar en cuentas de crédito generado en el período es de Q. ${_cuentasPorCobrar.toStringAsFixed(2)}.',
            style: const TextStyle(
              fontSize: 13,
              color: Colors.black87,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            isHistorico
                ? 'En cuanto a cobertura de mercado, la app cuenta con $_clientesTotalesCount clientes registrados en base de datos, de los cuales $_clientesActivosCount ya presentan compras activas registradas. El catálogo operativo cuenta con $_productosCount productos activos listos para su distribución.'
                : 'En cuanto a cobertura de mercado, en el período seleccionado se registraron $_clientesCreadosCount nuevos clientes y $_clientesActivosCount clientes realizaron compras. Asimismo, se movilizaron $_productosCount productos distintos del catálogo operativo.',
            style: const TextStyle(
              fontSize: 13,
              color: Colors.black87,
              height: 1.5,
            ),
          ),
        ],
      ),
    );
  }
}
