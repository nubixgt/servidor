import 'package:flutter/material.dart';
import '../models/usuario.dart';
import '../services/usuario_service.dart';

class UsuarioFormScreen extends StatefulWidget {
  final Usuario? usuario;

  const UsuarioFormScreen({Key? key, this.usuario}) : super(key: key);

  @override
  State<UsuarioFormScreen> createState() => _UsuarioFormScreenState();
}

class _UsuarioFormScreenState extends State<UsuarioFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usuarioService = UsuarioService();

  late TextEditingController _nombreController;
  late TextEditingController _usuarioController;
  late TextEditingController _contrasenaController;
  String _rolSeleccionado = 'vendedor';
  String _estadoSeleccionado = 'activo';
  bool _isLoading = false;

  bool get isEditing => widget.usuario != null;

  @override
  void initState() {
    super.initState();
    _nombreController = TextEditingController(text: widget.usuario?.nombre ?? '');
    _usuarioController = TextEditingController(text: widget.usuario?.usuario ?? '');
    _contrasenaController = TextEditingController();
    
    if (widget.usuario != null) {
      _rolSeleccionado = widget.usuario!.rol;
      _estadoSeleccionado = widget.usuario!.estado;
    }

    _nombreController.addListener(() {
      setState(() {});
    });
  }

  @override
  void dispose() {
    _nombreController.dispose();
    _usuarioController.dispose();
    _contrasenaController.dispose();
    super.dispose();
  }

  Future<void> _guardarUsuario() async {
    if (!_formKey.currentState!.validate()) return;

    if (!isEditing && _contrasenaController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('La contraseña es requerida para crear un usuario'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    Map<String, dynamic> result;

    if (isEditing) {
      result = await _usuarioService.actualizarUsuario(
        id: widget.usuario!.id,
        nombre: _nombreController.text.trim(),
        usuario: _usuarioController.text.trim(),
        contrasena: _contrasenaController.text.isNotEmpty ? _contrasenaController.text : null,
        rol: _rolSeleccionado,
        estado: _estadoSeleccionado,
      );
    } else {
      result = await _usuarioService.crearUsuario(
        nombre: _nombreController.text.trim(),
        usuario: _usuarioController.text.trim(),
        contrasena: _contrasenaController.text,
        rol: _rolSeleccionado,
        estado: _estadoSeleccionado,
      );
    }

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
          // 1. Fondo Gradiente Fijo
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
              // Banner Superior con Título
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
                            isEditing ? 'Actualizar Datos' : 'Registrar',
                            style: TextStyle(
                              color: Colors.green.shade100,
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            isEditing ? 'Editar Usuario' : 'Nuevo Usuario',
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

              // Contenido del Formulario
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
                            isEditing ? 'Información del Usuario' : 'Complete los datos',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.grey.shade800,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 30),

                          _buildAvatarPreview(),
                          
                          const SizedBox(height: 30),

                          _buildModernField(
                            controller: _nombreController,
                            label: 'Nombre Completo',
                            icon: Icons.person_outline,
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _usuarioController,
                            label: 'Usuario',
                            icon: Icons.alternate_email,
                          ),
                          const SizedBox(height: 16),

                          _buildModernField(
                            controller: _contrasenaController,
                            label: isEditing ? 'Nueva Contraseña' : 'Contraseña',
                            icon: Icons.lock_outline,
                            isPassword: true,
                            helperText: isEditing ? 'Dejar vacío para mantener la actual' : null,
                          ),
                          const SizedBox(height: 16),

                          // Layout vertical para evitar overflow
                          _buildDropdown(
                            value: _rolSeleccionado,
                            label: 'Rol',
                            icon: Icons.admin_panel_settings_outlined,
                            items: const [
                              DropdownMenuItem(value: 'admin', child: Text('Admin')),
                              DropdownMenuItem(value: 'vendedor', child: Text('Vendedor')),
                            ],
                            onChanged: (v) => setState(() => _rolSeleccionado = v!),
                          ),
                          const SizedBox(height: 16),
                          
                          _buildDropdown(
                            value: _estadoSeleccionado,
                            label: 'Estado',
                            icon: Icons.toggle_on_outlined,
                            items: const [
                              DropdownMenuItem(value: 'activo', child: Text('Activo')),
                              DropdownMenuItem(value: 'De Baja', child: Text('Baja')),
                            ],
                            onChanged: (v) => setState(() => _estadoSeleccionado = v!),
                          ),

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
                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
                                  ),
                                  child: const Text('Cancelar', style: TextStyle(color: Colors.grey)),
                                ),
                              ),
                              const SizedBox(width: 16),
                              Expanded(
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _guardarUsuario,
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
    bool isPassword = false,
    String? helperText,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: isPassword,
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
        if (!isPassword && (value == null || value.isEmpty)) return 'Requerido';
        return null;
      },
    );
  }

  Widget _buildDropdown({
    required String value,
    required String label,
    required IconData icon,
    required List<DropdownMenuItem<String>> items,
    required Function(String?) onChanged,
  }) {
    return DropdownButtonFormField<String>(
      value: value,
      items: items,
      onChanged: onChanged,
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

  Widget _buildAvatarPreview() {
    final nombre = _nombreController.text.trim();
    final inicial = nombre.isNotEmpty ? nombre.substring(0, 1).toUpperCase() : 'U';
    final isAdmin = _rolSeleccionado == 'admin';
    
    return Center(
      child: Container(
        padding: const EdgeInsets.all(4),
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          border: Border.all(
            color: isAdmin ? Colors.purple.shade200 : Colors.blue.shade200,
            width: 3,
          ),
          boxShadow: [
            BoxShadow(
              color: (isAdmin ? Colors.purple : Colors.blue).withOpacity(0.2),
              blurRadius: 15,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: CircleAvatar(
          backgroundColor: isAdmin ? Colors.purple.shade50 : Colors.blue.shade50,
          radius: 40,
          child: Text(
            inicial,
            style: TextStyle(
              fontSize: 32,
              fontWeight: FontWeight.bold,
              color: isAdmin ? Colors.purple : Colors.blue,
            ),
          ),
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
    path.quadraticBezierTo(size.width / 2, size.height, size.width, size.height - 40);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }
  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
