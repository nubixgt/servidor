import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';
import '../models/cliente.dart';
import '../models/producto_precio.dart';
import '../models/item_carrito.dart';
import '../services/cliente_service.dart';
import '../services/producto_service.dart';
import '../widgets/app_drawer.dart';
import 'vista_previa_nota_screen.dart';

class NuevaVentaScreen extends StatefulWidget {
  const NuevaVentaScreen({Key? key}) : super(key: key);

  @override
  State<NuevaVentaScreen> createState() => _NuevaVentaScreenState();
}

class _NuevaVentaScreenState extends State<NuevaVentaScreen> {
  final _formKey = GlobalKey<FormState>();
  final _clienteService = ClienteService();
  final _productoService = ProductoService();
  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  // Controllers
  late TextEditingController _fechaController;
  late TextEditingController _nitController;
  late TextEditingController _direccionController;
  late TextEditingController _diasCreditoController;
  late TextEditingController _precioUnitarioController;
  late TextEditingController _cantidadController;
  late TextEditingController _descuentoController;

  // Dropdowns
  String? _vendedorSeleccionado;
  Cliente? _clienteSeleccionado;
  String? _tipoVentaSeleccionado;
  String? _productoSeleccionado;
  PresentacionPrecio? _presentacionSeleccionada;

  // Listas
  List<Cliente> _clientes = [];
  List<String> _productos = [];
  List<PresentacionPrecio> _presentaciones = [];
  
  // CARRITO DE COMPRAS
  List<ItemCarrito> _carrito = [];

  // Estados
  bool _isLoadingClientes = true;
  bool _isLoadingProductos = true;
  bool _mostrarDiasCredito = false;
  // Estado para el producto actual que se está agregando
  bool _esBonificacionProducto = false;

  // Constantes
  final List<String> _vendedores = [
    'Felipe Machán',
    'Jurandir Terreaux',
    'Vinicio Arreaga',
  ];

  final List<String> _tiposVenta = [
    'Contado',
    'Crédito',
    'Pruebas',
  ];

  @override
  void initState() {
    super.initState();
    _fechaController = TextEditingController(
      text: DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    _nitController = TextEditingController();
    _direccionController = TextEditingController();
    _diasCreditoController = TextEditingController();
    _precioUnitarioController = TextEditingController();
    _cantidadController = TextEditingController(text: '1');
    _descuentoController = TextEditingController(text: '0');

    _cargarClientes();
    _cargarProductos();
  }

  @override
  void dispose() {
    _fechaController.dispose();
    _nitController.dispose();
    _direccionController.dispose();
    _diasCreditoController.dispose();
    _precioUnitarioController.dispose();
    _cantidadController.dispose();
    _descuentoController.dispose();
    super.dispose();
  }

  Future<void> _cargarClientes() async {
    setState(() => _isLoadingClientes = true);
    final result = await _clienteService.listarClientes();
    
    if (mounted) {
      setState(() {
        _isLoadingClientes = false;
        if (result['success']) {
          _clientes = result['clientes'];
        }
      });
    }
  }

  Future<void> _cargarProductos() async {
    setState(() => _isLoadingProductos = true);
    final result = await _productoService.listarProductos();
    
    if (mounted) {
      setState(() {
        _isLoadingProductos = false;
        if (result['success']) {
          _productos = result['productos'];
        }
      });
    }
  }

  Future<void> _cargarPresentaciones(String producto) async {
    final result = await _productoService.obtenerPresentaciones(producto);
    
    if (mounted) {
      if (result['success']) {
        setState(() {
          _presentaciones = result['presentaciones'];
          _presentacionSeleccionada = null;
          _precioUnitarioController.clear();
        });
      }
    }
  }

  void _onClienteSeleccionado(Cliente? cliente) {
    if (cliente == null) return;

    if (cliente.ventasBloqueadas) {
      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: const Text('Cliente Bloqueado'),
          content: Text(
            'El cliente "${cliente.nombre}" tiene las ventas bloqueadas.\n\nNo se puede realizar una venta a este cliente.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Entendido'),
            ),
          ],
        ),
      );

      setState(() {
        _clienteSeleccionado = null;
        _nitController.clear();
        _direccionController.clear();
      });
      return;
    }

    setState(() {
      _clienteSeleccionado = cliente;
      _nitController.text = cliente.nit;
      _direccionController.text = cliente.direccion;
    });
  }

  void _onTipoVentaSeleccionado(String? tipo) {
    setState(() {
      _tipoVentaSeleccionado = tipo;
      _mostrarDiasCredito = tipo == 'Crédito';
      if (!_mostrarDiasCredito) {
        _diasCreditoController.clear();
      }
    });
  }

  void _onProductoSeleccionado(String? producto) {
    if (producto == null) return;
    
    setState(() {
      _productoSeleccionado = producto;
      _presentacionSeleccionada = null;
      _presentaciones = [];
    });

    _cargarPresentaciones(producto);
  }

  void _onPresentacionSeleccionada(PresentacionPrecio? presentacion) {
    if (presentacion == null) return;

    setState(() {
      _presentacionSeleccionada = presentacion;
      _precioUnitarioController.text = presentacion.precio.toStringAsFixed(2);
    });
  }

  Future<void> _seleccionarFecha() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
    );

    if (picked != null) {
      setState(() {
        _fechaController.text = DateFormat('yyyy-MM-dd').format(picked);
      });
    }
  }


  void _agregarAlCarrito() {
    if (_productoSeleccionado == null || _presentacionSeleccionada == null) {
      _mostrarError('Debe seleccionar un producto y presentación');
      return;
    }

    final cantidad = int.tryParse(_cantidadController.text) ?? 0;
    if (cantidad <= 0) {
      _mostrarError('La cantidad debe ser mayor a 0');
      return;
    }

    // Validar stock disponible
    final stockDisponible = _presentacionSeleccionada!.cantidad;
    if (cantidad > stockDisponible) {
      _mostrarError(
        'Stock insuficiente. Disponible: $stockDisponible unidades'
      );
      return;
    }

    final descuento = double.tryParse(_descuentoController.text) ?? 0.0;
    if (descuento < 0) {
      _mostrarError('El descuento no puede ser negativo');
      return;
    }

    // Determinar si es bonificación
    final esBonificacion = _esBonificacionProducto;

    final item = ItemCarrito(
      producto: _productoSeleccionado!,
      presentacion: _presentacionSeleccionada!.presentacion,
      precioUnitario: _presentacionSeleccionada!.precio,
      cantidad: cantidad,
      descuento: descuento,
      esBonificacion: esBonificacion,
    );

    setState(() {
      _carrito.add(item);
      // Limpiar campos de producto
      _productoSeleccionado = null;
      _presentacionSeleccionada = null;
      _presentaciones = [];
      _precioUnitarioController.clear();
      _cantidadController.text = '1';
      _descuentoController.text = '0';
      _esBonificacionProducto = false;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          esBonificacion 
            ? 'Producto agregado como BONIFICACIÓN' 
            : 'Producto agregado al carrito'
        ),
        backgroundColor: esBonificacion ? Colors.orange : Colors.green,
        duration: const Duration(seconds: 1),
      ),
    );
  }

  void _eliminarDelCarrito(int index) {
    setState(() {
      _carrito.removeAt(index);
    });
  }

  void _editarItemCarrito(int index) {
    final item = _carrito[index];
    
    showDialog(
      context: context,
      builder: (context) => _EditarItemDialog(
        item: item,
        onGuardar: (nuevoItem) {
          setState(() {
            _carrito[index] = nuevoItem;
          });
        },
      ),
    );
  }

  double _calcularSubtotal() {
    return _carrito.fold(0.0, (sum, item) {
      if (item.esBonificacion) return sum;
      return sum + (item.precioUnitario * item.cantidad);
    });
  }

  double _calcularDescuentoTotal() {
    return _carrito.fold(0.0, (sum, item) {
      if (item.esBonificacion) return sum;
      return sum + item.descuento;
    });
  }

  double _calcularTotal() {
    return _calcularSubtotal() - _calcularDescuentoTotal();
  }

  void _irAVistaPrevia() {
    if (_vendedorSeleccionado == null) {
      _mostrarError('Debe seleccionar un vendedor');
      return;
    }

    if (_clienteSeleccionado == null) {
      _mostrarError('Debe seleccionar un cliente');
      return;
    }

    if (_tipoVentaSeleccionado == null) {
      _mostrarError('Debe seleccionar un tipo de venta');
      return;
    }

    if (_carrito.isEmpty) {
      _mostrarError('Debe agregar al menos un producto al carrito');
      return;
    }

    if (_mostrarDiasCredito && _diasCreditoController.text.isEmpty) {
      _mostrarError('Debe ingresar los días de crédito');
      return;
    }

    // Navegar a vista previa
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => VistaPreviaNotaScreen(
          fecha: _fechaController.text,
          vendedor: _vendedorSeleccionado!,
          cliente: _clienteSeleccionado!,

          tipoVenta: _tipoVentaSeleccionado!,
          diasCredito: _mostrarDiasCredito ? int.tryParse(_diasCreditoController.text) : null,
          productos: _carrito,
        ),
      ),
    ).then((resultado) {
      // Si se guardó exitosamente, volver a la pantalla anterior
      if (resultado == true) {
        Navigator.pop(context, true);
      }
    });
  }

  void _mostrarError(String mensaje) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(mensaje),
        backgroundColor: Colors.red,
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
                            'Registro de Transacción',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Nueva Venta',
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

              // Contenido Principal
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      children: [
                         // Información General
                        _buildSeccionInformacionGeneral(),
                        const SizedBox(height: 20),
                        
                        // Agregar Producto
                        _buildSeccionAgregarProducto(),
                        const SizedBox(height: 20),
                        
                        // Carrito de Compras
                        if (_carrito.isNotEmpty) ...[
                          _buildSeccionCarrito(),
                          const SizedBox(height: 20),
                        ],

                        // Botón Vista Previa
                        if (_carrito.isNotEmpty)
                          _buildBotonVistaPrevia(),

                        const SizedBox(height: 40),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSeccionInformacionGeneral() {
    return Container(
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Información General',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.green,
            ),
          ),
          const SizedBox(height: 20),
          
          // Fecha
          TextFormField(
            controller: _fechaController,
            decoration: InputDecoration(
              labelText: 'Fecha',
              prefixIcon: const Icon(Icons.calendar_today, color: Colors.green),
              filled: true,
              fillColor: Colors.grey.shade50,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
            ),
            readOnly: true,
            onTap: _seleccionarFecha,
          ),
          const SizedBox(height: 16),

          // Vendedor
          _buildDropdown<String>(
            value: _vendedorSeleccionado,
            label: 'Vendedor',
            icon: Icons.person_outline,
            items: _vendedores.map((v) => DropdownMenuItem(value: v, child: Text(v))).toList(),
            onChanged: (v) => setState(() => _vendedorSeleccionado = v),
          ),
          const SizedBox(height: 16),

          // Cliente
           _buildDropdown<Cliente>(
            value: _clienteSeleccionado,
             label: 'Cliente',
            icon: Icons.group_outlined,
            items: _clientes.map((c) => DropdownMenuItem(value: c, child: Text(c.nombre))).toList(),
            onChanged: _onClienteSeleccionado,
          ),
          const SizedBox(height: 16),

          // NIT (solo lectura)
          TextFormField(
            controller: _nitController,
            decoration: InputDecoration(
              labelText: 'NIT',
              prefixIcon: const Icon(Icons.badge_outlined, color: Colors.grey),
              filled: true,
              fillColor: Colors.grey.shade100,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
            ),
            enabled: false,
          ),
          const SizedBox(height: 16),

          // Dirección (solo lectura)
          TextFormField(
            controller: _direccionController,
            decoration: InputDecoration(
              labelText: 'Dirección',
              prefixIcon: const Icon(Icons.home_outlined, color: Colors.grey),
              filled: true,
              fillColor: Colors.grey.shade100,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
            ),
            maxLines: 2,
            enabled: false,
          ),
          const SizedBox(height: 16),

          // Tipo de Venta
          _buildDropdown<String>(
            value: _tipoVentaSeleccionado,
            label: 'Tipo de Venta',
            icon: Icons.payment,
            items: _tiposVenta.map((t) => DropdownMenuItem(value: t, child: Text(t))).toList(),
            onChanged: _onTipoVentaSeleccionado,
          ),

          // Días de Crédito
          if (_mostrarDiasCredito) ...[ 
            const SizedBox(height: 16),
             TextFormField(
              controller: _diasCreditoController,
              decoration: InputDecoration(
                labelText: 'Días de Crédito',
                prefixIcon: const Icon(Icons.calendar_month, color: Colors.orange),
                filled: true,
                 fillColor: Colors.orange.shade50,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
              ),
              keyboardType: TextInputType.number,
              inputFormatters: [FilteringTextInputFormatter.digitsOnly],
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildSeccionAgregarProducto() {
    return Container(
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Agregar Producto',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.green,
            ),
          ),
          const SizedBox(height: 20),
          
          // Producto
          _buildDropdown<String>(
            value: _productoSeleccionado,
            label: 'Producto',
             icon: Icons.inventory_2_outlined,
            items: _productos.map((p) => DropdownMenuItem(value: p, child: Text(p))).toList(),
            onChanged: _onProductoSeleccionado,
          ),
          const SizedBox(height: 16),

          // Presentación
          _buildDropdown<PresentacionPrecio>(
            value: _presentacionSeleccionada,
            label: 'Presentación',
            icon: Icons.local_drink_outlined,
            items: _presentaciones.map((p) => DropdownMenuItem(value: p, child: Text(p.toString()))).toList(),
            onChanged: _productoSeleccionado == null 
              ? null // Si no hay producto seleccionado, el dropdown se deshabilita
              : (val) => _onPresentacionSeleccionada(val),
          ),
          const SizedBox(height: 16),

          // Precio Unitario
          TextFormField(
            controller: _precioUnitarioController,
             decoration: InputDecoration(
              labelText: 'Precio Unitario (Q)',
              prefixIcon: const Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'Q',
                    style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.green),
                  ),
                ],
              ),
              filled: true,
              fillColor: Colors.grey.shade100,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
            ),
            enabled: false,
          ),
          const SizedBox(height: 16),

          Row(
            children: [
               Expanded(
                 child: TextFormField(
                  controller: _cantidadController,
                  decoration: InputDecoration(
                    labelText: 'Cantidad',
                    prefixIcon: const Icon(Icons.shopping_cart_outlined, color: Colors.green),
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                  ),
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                           ),
               ),
              const SizedBox(width: 16),
              Expanded(
                child: TextFormField(
                  controller: _descuentoController,
                   decoration: InputDecoration(
                    labelText: 'Descuento (Q)',
                    prefixIcon: const Icon(Icons.discount_outlined, color: Colors.orange),
                    filled: true,
                    fillColor: Colors.grey.shade50,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                  ),
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}'))],
                  enabled: !_esBonificacionProducto,
                ),
              ),
            ],
          ),
          
          if (_esBonificacionProducto) ...[
             const Padding(
              padding: EdgeInsets.only(top: 8.0, left: 12.0),
              child: Text(
                'El descuento será del 100% porque es bonificación',
                style: TextStyle(color: Colors.orange, fontSize: 12),
              ),
            ),
          ],
          
          const SizedBox(height: 16),

          // Switch para Bonificación
          Container(
             padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.orange.shade50,
              borderRadius: BorderRadius.circular(15),
              border: Border.all(color: Colors.orange.shade200),
            ),
            child: SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Es Bonificación', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.black87)),
              subtitle: const Text('Marcar producto como regalo', style: TextStyle(fontSize: 12)),
              value: _esBonificacionProducto,
              activeColor: Colors.orange,
              secondary: const Icon(Icons.card_giftcard, color: Colors.orange),
              onChanged: (bool value) {
                setState(() {
                  _esBonificacionProducto = value;
                  if (_esBonificacionProducto) {
                    _descuentoController.text = '0';
                  }
                });
              },
            ),
          ),
          const SizedBox(height: 20),

          // Botón Agregar
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _agregarAlCarrito,
              icon: const Icon(Icons.add_shopping_cart, color: Colors.white),
              label: const Text(
                'Agregar al Carrito',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.green.shade700,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                elevation: 5,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown<T>({
    required T? value,
    required String label,
    required IconData icon,
    required List<DropdownMenuItem<T>> items,
    required Function(T?)? onChanged, // Ahora acepta nulo
  }) {
    return DropdownButtonFormField<T>(
      value: value,
      items: items,
      onChanged: onChanged,
      // ...
      isExpanded: true,
       decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: Colors.green.shade700),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 16),
      ),
    );
  }

  Widget _buildSeccionCarrito() {
    return Container(
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Carrito de Compras',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.green,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.green.shade100,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${_carrito.length} Items',
                  style: TextStyle(
                    color: Colors.green.shade800,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          
          // Lista de productos (Table Logic Preserved)
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: _carrito.length,
            separatorBuilder: (context, index) => const Divider(height: 20),
            itemBuilder: (context, index) {
              final item = _carrito[index];
              return _buildItemCarrito(item, index);
            },
          ),
          
          const Divider(height: 30, thickness: 2),
          
          // Totales
          _buildResumenTotales(),
        ],
      ),
    );
  }

  Widget _buildItemCarrito(ItemCarrito item, int index) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: item.esBonificacion ? Colors.orange.shade50 : Colors.grey.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: item.esBonificacion ? Colors.orange.shade300 : Colors.grey.shade300,
          width: item.esBonificacion ? 2 : 1,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            item.producto,
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                        ),
                        if (item.esBonificacion)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.orange,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Text(
                              'BONIFICACIÓN',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                      ],
                    ),
                    Text(
                      item.presentacion,
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 14,
                      ),
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.edit, color: Colors.blue),
                onPressed: () => _editarItemCarrito(index),
              ),
              IconButton(
                icon: const Icon(Icons.delete, color: Colors.red),
                onPressed: () => _eliminarDelCarrito(index),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Precio: Q${item.precioUnitario.toStringAsFixed(2)}'),
              Text('Cantidad: ${item.cantidad}'),
            ],
          ),
          if (item.descuento > 0)
            Text(
              'Descuento: Q${item.descuento.toStringAsFixed(2)}',
              style: const TextStyle(color: Colors.orange),
            ),
          const SizedBox(height: 4),
          Text(
            'Total: Q${item.total.toStringAsFixed(2)}',
            style: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 16,
              color: Colors.green,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildResumenTotales() {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Subtotal:', style: TextStyle(fontSize: 16)),
            Text(
              'Q${_calcularSubtotal().toStringAsFixed(2)}',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Descuento Total:', style: TextStyle(fontSize: 16)),
            Text(
              'Q${_calcularDescuentoTotal().toStringAsFixed(2)}',
              style: const TextStyle(fontSize: 16, color: Colors.orange),
            ),
          ],
        ),
        const Divider(height: 20),
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text(
              'TOTAL:',
              style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
            ),
            Text(
              'Q${_calcularTotal().toStringAsFixed(2)}',
              style: const TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: Colors.green,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildBotonVistaPrevia() {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: _irAVistaPrevia,
        icon: const Icon(Icons.visibility, color: Colors.white),
        label: const Text(
          'Generar Nota de Envío',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.green.shade800,
          padding: const EdgeInsets.symmetric(vertical: 18),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(15),
          ),
          elevation: 8,
          shadowColor: Colors.green.withOpacity(0.4),
        ),
      ),
    );
  }
}

// Dialog para editar un item del carrito
class _EditarItemDialog extends StatefulWidget {
  final ItemCarrito item;
  final Function(ItemCarrito) onGuardar;

  const _EditarItemDialog({
    required this.item,
    required this.onGuardar,
  });

  @override
  State<_EditarItemDialog> createState() => _EditarItemDialogState();
}

class _EditarItemDialogState extends State<_EditarItemDialog> {
  late TextEditingController _cantidadController;
  late TextEditingController _descuentoController;

  @override
  void initState() {
    super.initState();
    _cantidadController = TextEditingController(text: widget.item.cantidad.toString());
    _descuentoController = TextEditingController(text: widget.item.descuento.toStringAsFixed(2));
  }

  @override
  void dispose() {
    _cantidadController.dispose();
    _descuentoController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Editar Producto', style: TextStyle(fontWeight: FontWeight.bold)),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            '${widget.item.producto} - ${widget.item.presentacion}',
            style: const TextStyle(fontWeight: FontWeight.w500, color: Colors.grey),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _cantidadController,
            decoration: InputDecoration(
              labelText: 'Cantidad',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
              filled: true,
              fillColor: Colors.grey.shade50,
            ),
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _descuentoController,
            decoration: InputDecoration(
              labelText: 'Descuento (Q)',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
              filled: true,
              fillColor: Colors.grey.shade50,
            ),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
            ],
             enabled: !widget.item.esBonificacion,
          ),
          if (widget.item.esBonificacion)
            const Padding(
              padding: EdgeInsets.only(top: 8),
              child: Text('Bonificación: Descuento fijo', style: TextStyle(color: Colors.orange, fontSize: 12)),
            ),
        ],
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
        ),
        ElevatedButton(
          onPressed: () {
            final cantidad = int.tryParse(_cantidadController.text) ?? 1;
            final descuento = double.tryParse(_descuentoController.text) ?? 0.0;
            
            final nuevoItem = widget.item.copyWith(
              cantidad: cantidad,
              descuento: descuento,
            );
            
            widget.onGuardar(nuevoItem);
            Navigator.pop(context);
          },
          style: ElevatedButton.styleFrom(
             backgroundColor: Colors.green,
             shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
          child: const Text('Guardar'),
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
