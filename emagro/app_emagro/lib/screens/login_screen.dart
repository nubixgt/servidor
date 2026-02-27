import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _usuarioController = TextEditingController();
  final _contrasenaController = TextEditingController();
  final _authService = AuthService();
  bool _isLoading = false;
  bool _obscureText = true;

  @override
  void dispose() {
    _usuarioController.dispose();
    _contrasenaController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    final result = await _authService.login(
      _usuarioController.text.trim(),
      _contrasenaController.text,
    );

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success']) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
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
      body: Stack(
        children: [
          // 1. Imagen de Fondo
          Container(
            decoration: const BoxDecoration(
              image: DecorationImage(
                image: AssetImage('assets/images/Fondo.jpeg'),
                fit: BoxFit.cover,
              ),
            ),
          ),

          // 2. Filtro oscuro para mejorar contraste
          Container(
            color: Colors.black.withOpacity(0.4),
          ),

          // 3. Contenido Glassmorphism
          Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Logo
                  Hero(
                    tag: 'logo_emagro',
                    child: Container(
                      child: Image.asset(
                        'assets/images/logo_emagro.png',
                        width: 250,
                        height: 180,
                        fit: BoxFit.contain,
                      ),
                    ),
                  ),
                  const SizedBox(height: 40),

                  // Tarjeta Glass
                  ClipRRect(
                    borderRadius: BorderRadius.circular(25),
                    child: BackdropFilter(
                      filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
                      child: Container(
                        padding: const EdgeInsets.all(32),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(25),
                          border: Border.all(
                            color: Colors.white.withOpacity(0.2),
                            width: 1.5,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.1),
                              blurRadius: 20,
                              spreadRadius: -5,
                            ),
                          ],
                        ),
                        child: Form(
                          key: _formKey,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const Text(
                                'Bienvenido',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 28,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white,
                                  letterSpacing: 1.2,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                'Inicia sesión para continuar',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Colors.white.withOpacity(0.8),
                                ),
                              ),
                              const SizedBox(height: 40),

                              // Usuario
                              _buildGlassTextField(
                                controller: _usuarioController,
                                icon: Icons.person_outline,
                                hintText: 'Usuario',
                                validator: (value) => value == null || value.isEmpty
                                    ? 'Ingrese su usuario'
                                    : null,
                              ),
                              const SizedBox(height: 20),

                              // Contraseña
                              _buildGlassTextField(
                                controller: _contrasenaController,
                                icon: Icons.lock_outline,
                                hintText: 'Contraseña',
                                obscureText: _obscureText,
                                validator: (value) => value == null || value.isEmpty
                                    ? 'Ingrese su contraseña'
                                    : null,
                              ),
                              const SizedBox(height: 40),

                              // Botón
                              SizedBox(
                                height: 55,
                                child: ElevatedButton(
                                  onPressed: _isLoading ? null : _handleLogin,
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: const Color(0xFF4CAF50), // Verde Emagro
                                    overlayColor: Colors.white.withOpacity(0.2),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(15),
                                    ),
                                    elevation: 8,
                                    shadowColor: Colors.green.withOpacity(0.4),
                                  ),
                                  child: _isLoading
                                      ? const SizedBox(
                                          height: 24,
                                          width: 24,
                                          child: CircularProgressIndicator(
                                            color: Colors.white,
                                            strokeWidth: 2.5,
                                          ),
                                        )
                                      : const Text(
                                          'INGRESAR',
                                          style: TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.bold,
                                            color: Colors.white,
                                            letterSpacing: 1.5,
                                          ),
                                        ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: 20),
                  Text(
                    '© 2026 Emagro',
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.6),
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildGlassTextField({
    required TextEditingController controller,
    required IconData icon,
    required String hintText,
    bool obscureText = false,
    Widget? suffixIcon,
    String? Function(String?)? validator,
  }) {
    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      style: const TextStyle(color: Colors.white),
      cursorColor: Colors.greenAccent,
      decoration: InputDecoration(
        prefixIcon: Icon(icon, color: Colors.white70),
        suffixIcon: suffixIcon,
        hintText: hintText,
        hintStyle: TextStyle(color: Colors.white.withOpacity(0.6)),
        filled: true,
        fillColor: Colors.white.withOpacity(0.1),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide(color: Colors.white.withOpacity(0.3)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide(color: Colors.white.withOpacity(0.3)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: const BorderSide(color: Colors.greenAccent, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide(color: Colors.red.shade300, width: 1.5),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(15),
          borderSide: BorderSide(color: Colors.red.shade300, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
        errorStyle: TextStyle(color: Colors.red.shade100),
      ),
      validator: validator,
    );
  }
}
