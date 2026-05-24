import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  bool _obscureText = true;

  void _autofill(String user, String pass) {
    _usernameController.text = user;
    _passwordController.text = pass;
  }

  void _login() {
    final user = _usernameController.text.toLowerCase();
    String role = 'admin';

    if (user.contains('supervisor')) {
      role = 'supervisor';
    } else if (user.contains('tecnico')) {
      role = 'tecnico';
    }

    Provider.of<AuthProvider>(context, listen: false).login(role);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        children: [
          // Background Glow
          Positioned(
            top: -100,
            left: -100,
            child: Container(
              width: 300,
              height: 300,
              decoration: BoxDecoration(
                color: AppTheme.primaryColor.withOpacity(0.2),
                shape: BoxShape.circle,
              ),
            ).animate(onPlay: (controller) => controller.repeat(reverse: true))
             .scale(duration: 2.seconds, begin: const Offset(1, 1), end: const Offset(1.2, 1.2))
             .blur(xy: 60),
          ),
          Positioned(
            bottom: -50,
            right: -50,
            child: Container(
              width: 200,
              height: 200,
              decoration: BoxDecoration(
                color: AppTheme.tertiaryColor.withOpacity(0.15),
                shape: BoxShape.circle,
              ),
            ).animate(onPlay: (controller) => controller.repeat(reverse: true))
             .scale(duration: 3.seconds, begin: const Offset(1, 1), end: const Offset(1.3, 1.3))
             .blur(xy: 50),
          ),
          
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: GlassCard(
                  padding: const EdgeInsets.all(32),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.construction_rounded,
                        size: 60,
                        color: AppTheme.primaryColor,
                      ).animate().scale(duration: 800.ms, curve: Curves.easeOutBack),
                      const SizedBox(height: 16),
                      const Text(
                        'CONSTRUCTPRO',
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 2,
                          fontStyle: FontStyle.italic,
                        ),
                      ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.2, end: 0),
                      const Text(
                        'GESTIÓN EMPRESARIAL',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 4,
                          color: Colors.white54,
                        ),
                      ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.2, end: 0),
                      const SizedBox(height: 40),
                      
                      TextField(
                        controller: _usernameController,
                        decoration: const InputDecoration(
                          labelText: 'USUARIO',
                          prefixIcon: Icon(Icons.person_outline, color: Colors.white54),
                        ),
                      ).animate().fadeIn(delay: 600.ms).slideX(begin: 0.1, end: 0),
                      const SizedBox(height: 20),
                      
                      TextField(
                        controller: _passwordController,
                        obscureText: _obscureText,
                        decoration: InputDecoration(
                          labelText: 'CONTRASEÑA',
                          prefixIcon: const Icon(Icons.lock_outline, color: Colors.white54),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscureText ? Icons.visibility_off : Icons.visibility,
                              color: Colors.white54,
                            ),
                            onPressed: () => setState(() => _obscureText = !_obscureText),
                          ),
                        ),
                      ).animate().fadeIn(delay: 800.ms).slideX(begin: 0.1, end: 0),
                      const SizedBox(height: 40),
                      
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _login,
                          child: const Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text('SINCRONIZAR ACCESO'),
                              SizedBox(width: 10),
                              Icon(Icons.arrow_forward_rounded, size: 20),
                            ],
                          ),
                        ),
                      ).animate().fadeIn(delay: 1000.ms).scale(duration: 400.ms, curve: Curves.easeOutBack),
                      
                      const SizedBox(height: 30),
                      const Divider(color: Colors.white12),
                      const SizedBox(height: 20),
                      
                      const Text(
                        'CREDENCIALES POR ROL',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w900,
                          letterSpacing: 2,
                          color: Colors.white54,
                        ),
                      ).animate().fadeIn(delay: 1200.ms),
                      const SizedBox(height: 16),
                      
                      _buildRoleShortcut(
                        icon: Icons.shield_outlined,
                        roleName: 'Administrador',
                        desc: 'admin_pro',
                        color: AppTheme.primaryColor,
                        onTap: () => _autofill('admin_pro', 'admin123'),
                      ).animate().fadeIn(delay: 1400.ms).slideY(begin: 0.2, end: 0),
                      
                      const SizedBox(height: 12),
                      
                      _buildRoleShortcut(
                        icon: Icons.group_outlined,
                        roleName: 'Supervisor',
                        desc: 'supervisor_site',
                        color: Colors.orange,
                        onTap: () => _autofill('supervisor_site', 'super456'),
                      ).animate().fadeIn(delay: 1600.ms).slideY(begin: 0.2, end: 0),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRoleShortcut({
    required IconData icon,
    required String roleName,
    required String desc,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.05),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: Colors.white.withOpacity(0.05)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withOpacity(0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  roleName.toUpperCase(),
                  style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    letterSpacing: 2,
                  ),
                ),
                Text(
                  desc,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Colors.white54,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const Spacer(),
            const Icon(Icons.add, color: Colors.white24, size: 20),
          ],
        ),
      ),
    );
  }
}
