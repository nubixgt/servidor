import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../models/cliente.dart';
import '../services/cliente_service.dart';
import '../services/auth_service.dart';
import '../data/guatemala_data.dart';
import '../utils/input_formatters.dart';

class ClienteFormScreen extends StatefulWidget {
  final Cliente? cliente;

  const ClienteFormScreen({Key? key, this.cliente}) : super(key: key);

  @override
  State<ClienteFormScreen> createState() => _ClienteFormScreenState();
}

class _ClienteFormScreenState extends State<ClienteFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _clienteService = ClienteService();

  late TextEditingController _nombreController;
  late TextEditingController _nitController;
  late TextEditingController _telefonoController;
  late TextEditingController _direccionController;
  late TextEditingController _emailController;

  String? _departamentoSeleccionado;
  String? _municipioSeleccionado;
  List<String> _municipiosDisponibles = [];
  bool _bloquearVentas = false;
  bool _isLoading = false;

  bool get isEditing => widget.cliente != null;

  @override
  void initState() {
    super.initState();
    _nombreController = TextEditingController(text: widget.cliente?.nombre ?? '');
    _nitController = TextEditingController(text: widget.cliente?.nit ?? '');
    _telefonoController = TextEditingController(text: widget.cliente?.telefono ?? '');
    _direccionController = TextEditingController(text: widget.cliente?.direccion ?? '');
    _emailController = TextEditingController(text: widget.cliente?.email ?? '');

    if (widget.cliente != null) {
      _departamentoSeleccionado = widget.cliente!.departamento;
      _municipioSeleccionado = widget.cliente!.municipio;
      _municipiosDisponibles = GuatemalaData.getMunicipios(_departamentoSeleccionado!);
      _bloquearVentas = widget.cliente!.ventasBloqueadas;
    }
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _nitController.dispose();
    _telefonoController.dispose();
    _direccionController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  String? _validarNIT(String? value) {
    if (value == null || value.isEmpty) {
      return 'El NIT es requerido';
    }
    return null;
  }

  String? _validarTelefono(String? value) {
    if (value == null || value.isEmpty) {
      return 'El teléfono es requerido';
    }
    if (!RegExp(r'^\d{4}-\d{4}$').hasMatch(value)) {
      return 'Formato inválido. Use XXXX-XXXX';
    }
    return null;
  }

  String? _validarEmail(String? value) {
    if (value == null || value.isEmpty) return null;
    if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
      return 'Formato de email inválido';
    }
    return null;
  }

  Future<void> _guardarCliente() async {
    if (!_formKey.currentState!.validate()) return;
    if (_departamentoSeleccionado == null || _municipioSeleccionado == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Seleccione departamento y municipio'), backgroundColor: Colors.redAccent),
      );
      return;
    }

    setState(() => _isLoading = true);

    Map<String, dynamic> result;

    if (isEditing) {
      result = await _clienteService.actualizarCliente(
        id: widget.cliente!.id,
        nombre: _nombreController.text.trim(),
        nit: _nitController.text.trim().toUpperCase(),
        telefono: _telefonoController.text.trim(),
        departamento: _departamentoSeleccionado!,
        municipio: _municipioSeleccionado!,
        direccion: _direccionController.text.trim(),
        email: _emailController.text.trim(),
        bloquearVentas: _bloquearVentas ? 'si' : 'no',
      );
    } else {
      final usuarioId = AuthService().usuarioActual?.id ?? 0;
      result = await _clienteService.crearCliente(
        nombre: _nombreController.text.trim(),
        nit: _nitController.text.trim().toUpperCase(),
        telefono: _telefonoController.text.trim(),
        departamento: _departamentoSeleccionado!,
        municipio: _municipioSeleccionado!,
        direccion: _direccionController.text.trim(),
        email: _emailController.text.trim(),
        usuarioId: usuarioId,
      );
    }

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message']), backgroundColor: Colors.green),
      );
      Navigator.pop(context, true);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message']), backgroundColor: Colors.redAccent),
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
                            isEditing ? 'Editar Información' : 'Nuevo Registro',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            isEditing ? 'Cliente Existente' : 'Registrar Cliente',
                            style: const TextStyle(
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
                child: Padding(
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
                          Text(
                            'Datos del Cliente',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.grey.shade800,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 30),

                          _buildModernField(
                            controller: _nombreController,
                            label: 'Nombre Completo',
                            icon: Icons.person_outline,
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _nitController,
                            label: 'NIT',
                            icon: Icons.badge_outlined,
                            helperText: 'C/F o número',
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _telefonoController,
                            label: 'Teléfono',
                            icon: Icons.phone_android_outlined,
                            isPhone: true,
                          ),
                          const SizedBox(height: 16),

                          // Ubicación
                          Row(
                            children: [
                              Expanded(
                                child: _buildDropdown(
                                  value: _departamentoSeleccionado,
                                  label: 'Departamento',
                                  icon: Icons.map_outlined,
                                  items: GuatemalaData.getDepartamentos()
                                    .map((d) => DropdownMenuItem(value: d, child: Text(d)))
                                    .toList(),
                                  onChanged: (v) {
                                    setState(() {
                                      _departamentoSeleccionado = v;
                                      _municipioSeleccionado = null;
                                      _municipiosDisponibles = GuatemalaData.getMunicipios(v!);
                                    });
                                  },
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: _buildDropdown(
                                  value: _municipioSeleccionado,
                                  label: 'Municipio',
                                  icon: Icons.location_on_outlined,
                                  items: _municipiosDisponibles
                                    .map((m) => DropdownMenuItem(value: m, child: Text(m)))
                                    .toList(),
                                  onChanged: (v) => setState(() => _municipioSeleccionado = v),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _direccionController,
                            label: 'Dirección Exacta',
                            icon: Icons.home_outlined,
                            maxLines: 2,
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _emailController,
                            label: 'Email (Opcional)',
                            icon: Icons.email_outlined,
                          ),

                          if (isEditing) ...[
                             const SizedBox(height: 20),
                            Container(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                decoration: BoxDecoration(
                                  color: Colors.orange.shade50,
                                  borderRadius: BorderRadius.circular(15),
                                  border: Border.all(color: Colors.orange.shade200),
                                ),
                                child: SwitchListTile(
                                  contentPadding: EdgeInsets.zero,
                                  title: Text('Bloquear Ventas', style: TextStyle(color: Colors.orange.shade900, fontWeight: FontWeight.bold)),
                                  subtitle: Text('El cliente no podrá comprar', style: TextStyle(color: Colors.orange.shade700, fontSize: 12)),
                                  value: _bloquearVentas,
                                  onChanged: (v) => setState(() => _bloquearVentas = v),
                                  activeColor: Colors.red,
                                ),
                              ),
                          ],

                          const SizedBox(height: 40),

                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: _isLoading ? null : () => Navigator.pop(context),
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(vertical: 16),
                                    side: BorderSide(color: Colors.grey.shade300),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                                  ),
                                  child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _guardarCliente,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: Colors.green.shade600,
                                    padding: const EdgeInsets.symmetric(vertical: 16),
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                                    elevation: 5,
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          height: 20,
                                          width: 20,
                                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                        )
                                      : Text(
                                          isEditing ? 'Actualizar' : 'Guardar',
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
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

  Widget _buildModernField({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool isPhone = false,
    String? helperText,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: isPhone ? TextInputType.phone : TextInputType.text,
      inputFormatters: isPhone ? [TelefonoInputFormatter()] : [],
      decoration: InputDecoration(
        labelText: label,
        helperText: helperText,
        prefixIcon: Icon(icon, color: Colors.green.shade700),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide(color: Colors.green.shade200, width: 2),
        ),
      ),
      validator: (value) {
        if (value == null || value.isEmpty) {
           if (label.contains('Opcional')) return null;
           return 'Requerido';
        }
        return null;
      },
    );
  }

  Widget _buildDropdown({
    required String? value,
    required String label,
    required IconData icon,
    required List<DropdownMenuItem<String>> items,
    required Function(String?) onChanged,
  }) {
    return DropdownButtonFormField<String>(
      value: value,
      items: items,
      onChanged: onChanged,
      isExpanded: true,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, color: Colors.green.shade700, size: 20),
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(15), borderSide: BorderSide.none),
        filled: true,
        fillColor: Colors.grey.shade50,
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 16),
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
