import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../models/pago.dart';
import '../services/pago_service.dart';
import '../services/auth_service.dart';

class RegistroPagoFormScreen extends StatefulWidget {
  const RegistroPagoFormScreen({Key? key}) : super(key: key);

  @override
  State<RegistroPagoFormScreen> createState() => _RegistroPagoFormScreenState();
}

class _RegistroPagoFormScreenState extends State<RegistroPagoFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _pagoService = PagoService();
  final _montoController = TextEditingController();
  final _referenciaController = TextEditingController();

  List<FacturaCredito> _facturas = [];
  FacturaCredito? _facturaSeleccionada;
  String? _bancoSeleccionado;
  DateTime _fechaPago = DateTime.now();
  bool _isLoadingFacturas = true;
  bool _isLoading = false;

  final List<String> _bancos = [
    'Banco G&T Continental',
    'Banco Industrial',
    'BAC Credomatic',
    'Banrural',
    'Bantrab',
  ];

  @override
  void initState() {
    super.initState();
    _cargarFacturas();
  }

  @override
  void dispose() {
    _montoController.dispose();
    _referenciaController.dispose();
    super.dispose();
  }

  Future<void> _cargarFacturas() async {
    print('🔍 DEBUG: Iniciando carga de facturas...');
    setState(() => _isLoadingFacturas = true);

    final result = await _pagoService.listarFacturasCredito();
    
    print('🔍 DEBUG: Resultado recibido: ${result['success']}');
    print('🔍 DEBUG: Mensaje: ${result['message']}');
    print('🔍 DEBUG: Facturas: ${result['facturas']}');

    if (mounted) {
      setState(() {
        _isLoadingFacturas = false;
        if (result['success']) {
          _facturas = result['facturas'];
          print('🔍 DEBUG: Total facturas cargadas: ${_facturas.length}');
        } else {
          print('❌ DEBUG: Error al cargar facturas');
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

  Future<void> _seleccionarFecha() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _fechaPago,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: ColorScheme.light(
              primary: Colors.green.shade700,
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Colors.grey.shade800,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _fechaPago) {
      setState(() => _fechaPago = picked);
    }
  }

  Future<void> _guardarPago() async {
    if (!_formKey.currentState!.validate()) return;

    if (_facturaSeleccionada == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debe seleccionar una factura'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    if (_bancoSeleccionado == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Debe seleccionar un banco'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    final usuarioId = AuthService().usuarioActual?.id ?? 0;
    final fechaPagoStr = DateFormat('yyyy-MM-dd').format(_fechaPago);

    final result = await _pagoService.crearPago(
      facturaId: _facturaSeleccionada!.id,
      montoPago: double.parse(_montoController.text),
      fechaPago: fechaPagoStr,
      banco: _bancoSeleccionado!,
      referenciaTransaccion: _referenciaController.text.trim(),
      usuarioId: usuarioId,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.green,
          behavior: SnackBarBehavior.floating,
        ),
      );
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final formatoMoneda = NumberFormat.currency(symbol: 'Q', decimalDigits: 2);
    final formatoFecha = DateFormat('dd/MM/yyyy');

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

          // 2. ScrollView Completo
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
                            'Registrar',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Nuevo Pago',
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

              // Formulario
              SliverToBoxAdapter(
                child: _isLoadingFacturas
                    ? const Padding(
                        padding: EdgeInsets.all(60),
                        child: Center(
                          child: CircularProgressIndicator(color: Colors.white),
                        ),
                      )
                    : _facturas.isEmpty
                        ? Padding(
                            padding: const EdgeInsets.all(40),
                            child: _buildNoFacturasState(),
                          )
                        : Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
                            child: Container(
                              padding: const EdgeInsets.all(24),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(25),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.1),
                                    blurRadius: 20,
                                    offset: const Offset(0, 10),
                                  ),
                                ],
                              ),
                              child: Form(
                                key: _formKey,
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.stretch,
                                  children: [
                                    _buildSectionTitle('Seleccionar Factura'),
                                    const SizedBox(height: 16),
                                    _buildFacturaDropdown(),
                                    const SizedBox(height: 16),
                                    _buildFechaPagoField(formatoFecha),
                                    const SizedBox(height: 30),

                                    _buildSectionTitle('Detalles del Pago'),
                                    const SizedBox(height: 16),
                                    _buildBancoDropdown(),
                                    const SizedBox(height: 16),
                                    _buildMontoField(formatoMoneda),
                                    const SizedBox(height: 16),
                                    _buildReferenciaField(),
                                    const SizedBox(height: 40),

                                    // Botones
                                    Row(
                                      children: [
                                        Expanded(
                                          child: OutlinedButton(
                                            onPressed: _isLoading ? null : () => Navigator.pop(context),
                                            style: OutlinedButton.styleFrom(
                                              padding: const EdgeInsets.symmetric(vertical: 16),
                                              side: BorderSide(color: Colors.grey.shade300),
                                              shape: RoundedRectangleBorder(
                                                borderRadius: BorderRadius.circular(15),
                                              ),
                                            ),
                                            child: const Text(
                                              'Cancelar',
                                              style: TextStyle(color: Colors.grey),
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 16),
                                        Expanded(
                                          child: ElevatedButton(
                                            onPressed: _isLoading ? null : _guardarPago,
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: Colors.green.shade600,
                                              padding: const EdgeInsets.symmetric(vertical: 16),
                                              shape: RoundedRectangleBorder(
                                                borderRadius: BorderRadius.circular(15),
                                              ),
                                              elevation: 5,
                                            ),
                                            child: _isLoading
                                                ? const SizedBox(
                                                    height: 20,
                                                    width: 20,
                                                    child: CircularProgressIndicator(
                                                      color: Colors.white,
                                                      strokeWidth: 2,
                                                    ),
                                                  )
                                                : const Text(
                                                    'Guardar',
                                                    style: TextStyle(
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 16,
                                                      color: Colors.white,
                                                    ),
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
              ),
              const SliverPadding(padding: EdgeInsets.only(bottom: 20)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.bold,
        color: Colors.grey.shade800,
      ),
    );
  }

  Widget _buildFacturaDropdown() {
    final formatoMoneda = NumberFormat.currency(symbol: 'Q', decimalDigits: 2);

    return DropdownButtonFormField<FacturaCredito>(
      value: _facturaSeleccionada,
      isExpanded: true,
      items: _facturas.map((factura) {
        return DropdownMenuItem(
          value: factura,
          child: Text(
            'Factura ${factura.numeroNota} - ${factura.clienteNombre} (${formatoMoneda.format(factura.saldoPendiente)})',
            style: const TextStyle(fontSize: 13),
            overflow: TextOverflow.ellipsis,
          ),
        );
      }).toList(),
      onChanged: (value) => setState(() => _facturaSeleccionada = value),
      decoration: InputDecoration(
        labelText: 'Factura de Referencia',
        prefixIcon: Icon(Icons.receipt_long, color: Colors.green.shade700),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      ),
      validator: (value) => value == null ? 'Seleccione una factura' : null,
    );
  }

  Widget _buildFechaPagoField(DateFormat formatoFecha) {
    return InkWell(
      onTap: _seleccionarFecha,
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: 'Fecha de Pago',
          prefixIcon: Icon(Icons.calendar_today, color: Colors.green.shade700),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(15),
            borderSide: BorderSide.none,
          ),
          filled: true,
          fillColor: Colors.grey.shade50,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        ),
        child: Text(
          formatoFecha.format(_fechaPago),
          style: const TextStyle(fontSize: 16),
        ),
      ),
    );
  }

  Widget _buildBancoDropdown() {
    return DropdownButtonFormField<String>(
      value: _bancoSeleccionado,
      items: _bancos.map((banco) {
        return DropdownMenuItem(
          value: banco,
          child: Text(banco),
        );
      }).toList(),
      onChanged: (value) => setState(() => _bancoSeleccionado = value),
      decoration: InputDecoration(
        labelText: 'Banco',
        prefixIcon: Icon(Icons.account_balance, color: Colors.green.shade700),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      ),
      validator: (value) => value == null ? 'Seleccione un banco' : null,
    );
  }

  Widget _buildMontoField(NumberFormat formatoMoneda) {
    return TextFormField(
      controller: _montoController,
      keyboardType: const TextInputType.numberWithOptions(decimal: true),
      decoration: InputDecoration(
        labelText: 'Monto del Pago',
        prefixIcon: Icon(Icons.attach_money, color: Colors.green.shade700),
        helperText: _facturaSeleccionada != null
            ? 'Saldo pendiente: ${formatoMoneda.format(_facturaSeleccionada!.saldoPendiente)}'
            : null,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      ),
      validator: (value) {
        if (value == null || value.isEmpty) {
          return 'Ingrese el monto';
        }
        final monto = double.tryParse(value);
        if (monto == null || monto <= 0) {
          return 'Monto inválido';
        }
        if (_facturaSeleccionada != null && monto > _facturaSeleccionada!.saldoPendiente) {
          return 'El monto excede el saldo pendiente';
        }
        return null;
      },
    );
  }

  Widget _buildReferenciaField() {
    return TextFormField(
      controller: _referenciaController,
      decoration: InputDecoration(
        labelText: 'Referencia / Número de Transacción',
        prefixIcon: Icon(Icons.confirmation_number, color: Colors.green.shade700),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      ),
      validator: (value) {
        if (value == null || value.isEmpty) {
          return 'Ingrese la referencia';
        }
        return null;
      },
    );
  }

  Widget _buildNoFacturasState() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20),
      padding: const EdgeInsets.all(40),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(25),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.receipt_long_outlined,
            size: 80,
            color: Colors.grey.shade400,
          ),
          const SizedBox(height: 16),
          Text(
            'No hay facturas pendientes',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey.shade800,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            'Todas las facturas están pagadas',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
            textAlign: TextAlign.center,
          ),
        ],
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
