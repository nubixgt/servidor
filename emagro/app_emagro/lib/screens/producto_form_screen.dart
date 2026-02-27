import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/producto_precio.dart';
import '../services/producto_service.dart';

class ProductoFormScreen extends StatefulWidget {
  final ProductoPrecio? producto;

  const ProductoFormScreen({Key? key, this.producto}) : super(key: key);

  @override
  State<ProductoFormScreen> createState() => _ProductoFormScreenState();
}

class _ProductoFormScreenState extends State<ProductoFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _productoService = ProductoService();

  late TextEditingController _productoController;
  late TextEditingController _presentacionController;
  late TextEditingController _precioController;
  late TextEditingController _cantidadController;

  bool _isLoading = false;
  bool get _esEdicion => widget.producto != null;

  @override
  void initState() {
    super.initState();
    _productoController = TextEditingController(
      text: widget.producto?.producto ?? '',
    );
    _presentacionController = TextEditingController(
      text: widget.producto?.presentacion ?? '',
    );
    _precioController = TextEditingController(
      text: widget.producto?.precio.toStringAsFixed(2) ?? '',
    );
    _cantidadController = TextEditingController(
      text: widget.producto?.cantidad.toString() ?? '0',
    );
  }

  @override
  void dispose() {
    _productoController.dispose();
    _presentacionController.dispose();
    _precioController.dispose();
    _cantidadController.dispose();
    super.dispose();
  }

  Future<void> _guardarProducto() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final producto = _productoController.text.trim();
    final presentacion = _presentacionController.text.trim();
    final precio = double.parse(_precioController.text);
    final cantidad = int.parse(_cantidadController.text);

    final result = _esEdicion
        ? await _productoService.actualizarProducto(
            id: widget.producto!.id,
            producto: producto,
            presentacion: presentacion,
            precio: precio,
            cantidad: cantidad,
          )
        : await _productoService.crearProducto(
            producto: producto,
            presentacion: presentacion,
            precio: precio,
            cantidad: cantidad,
          );

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.green,
        ),
      );
      Navigator.pop(context, true);
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
                            _esEdicion ? 'Editar Registro' : 'Nuevo Registro',
                            style: TextStyle(color: Colors.green.shade100, fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 1.2),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _esEdicion ? 'Actualizar Producto' : 'Crear Producto',
                            style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // Formulario
              SliverToBoxAdapter(
                child: Container(
                  margin: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(25),
                     boxShadow: [
                      BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 20, offset: const Offset(0, 10)),
                    ],
                  ),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Información del Producto', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.green)),
                        const SizedBox(height: 20),

                        // Producto
                        TextFormField(
                          controller: _productoController,
                          decoration: InputDecoration(
                            labelText: 'Nombre del Producto',
                            prefixIcon: Icon(Icons.inventory_2_outlined, color: Colors.green.shade700),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                            filled: true,
                            fillColor: Colors.grey.shade50,
                          ),
                          textCapitalization: TextCapitalization.words,
                          validator: (value) => value == null || value.trim().isEmpty ? 'Requerido' : null,
                        ),
                        const SizedBox(height: 16),

                        // Presentación
                        TextFormField(
                          controller: _presentacionController,
                          decoration: InputDecoration(
                            labelText: 'Presentación',
                            hintText: 'Ej: 1 Litro, 5 Gal',
                            prefixIcon: Icon(Icons.category_outlined, color: Colors.green.shade700),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                            filled: true,
                            fillColor: Colors.grey.shade50,
                          ),
                          validator: (value) => value == null || value.trim().isEmpty ? 'Requerido' : null,
                        ),
                        const SizedBox(height: 16),

                        // Precio
                        TextFormField(
                          controller: _precioController,
                          decoration: InputDecoration(
                            labelText: 'Precio (Q)',
                             prefixIcon: const Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  'Q',
                                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.green),
                                ),
                              ],
                            ),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                            filled: true,
                            fillColor: Colors.grey.shade50,
                          ),
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}'))],
                          validator: (value) {
                            if (value == null || value.trim().isEmpty) return 'Requerido';
                            if (double.tryParse(value) == null) return 'Inválido';
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),

                        // Cantidad
                        TextFormField(
                          controller: _cantidadController,
                          decoration: InputDecoration(
                            labelText: 'Stock Inicial',
                            prefixIcon: Icon(Icons.warehouse_outlined, color: Colors.green.shade700),
                            border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
                            filled: true,
                            fillColor: Colors.grey.shade50,
                          ),
                          keyboardType: TextInputType.number,
                          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                          validator: (value) {
                             if (value == null || value.trim().isEmpty) return 'Requerido';
                             if (int.tryParse(value) == null) return 'Inválido';
                             return null;
                          },
                        ),
                        const SizedBox(height: 32),

                        // Botones
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton.icon(
                            onPressed: _isLoading ? null : _guardarProducto,
                            icon: _isLoading 
                              ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                              : Icon(_esEdicion ? Icons.save : Icons.check, color: Colors.white),
                            label: Text(
                              _isLoading ? 'Guardando...' : (_esEdicion ? 'Guardar Cambios' : 'Crear Producto'),
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
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
                  ),
                ),
              ),
            ],
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
    path.quadraticBezierTo(size.width / 2, size.height, size.width, size.height - 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }
  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
