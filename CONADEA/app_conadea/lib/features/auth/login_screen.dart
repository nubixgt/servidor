import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../shell/main_shell.dart';

/// Pantalla de acceso — solo interfaz (sin backend), inspirada en
/// Frontend/src/views/auth/Login.vue. Cualquier nombre ingresado entra a la app.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  bool _tabRegistro = false;
  final _nombreCtrl = TextEditingController();
  String? _error;

  void _entrar() {
    if (_nombreCtrl.text.trim().isEmpty) {
      setState(() => _error = 'Ingresa tu nombre completo para continuar.');
      return;
    }
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => const MainShell()),
    );
  }

  @override
  void dispose() {
    _nombreCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: GlassCard(
                  padding: const EdgeInsets.all(28),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Container(
                        width: 96,
                        height: 96,
                        decoration: const BoxDecoration(
                          image: DecorationImage(
                            image: AssetImage('assets/images/logo_agroia.png'),
                            fit: BoxFit.contain,
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text('Aula Virtual AgroIA', style: AppTextStyles.titulo(size: 22)),
                      const SizedBox(height: 8),
                      Text(
                        'Ministerio de Agricultura, Ganadería y Alimentación · CONADEA\n'
                        'Capacitación digital para productores agropecuarios',
                        textAlign: TextAlign.center,
                        style: AppTextStyles.cuerpo(size: 12.5),
                      ),
                      const SizedBox(height: 24),
                      _Tabs(
                        registro: _tabRegistro,
                        onChange: (v) => setState(() => _tabRegistro = v),
                      ),
                      const SizedBox(height: 20),
                      _CampoTexto(
                        label: 'Nombre completo',
                        hint: 'Ej. Ana María García',
                        controller: _nombreCtrl,
                      ),
                      if (_tabRegistro) ...[
                        const SizedBox(height: 14),
                        const _CampoTexto(label: 'Asociación o cooperativa', hint: 'Ej. Cooperativa El Porvenir'),
                        const SizedBox(height: 14),
                        const _CampoTexto(label: 'Municipio y departamento', hint: 'Ej. Sanarate, El Progreso'),
                      ],
                      if (_error != null) ...[
                        const SizedBox(height: 4),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          margin: const EdgeInsets.only(bottom: 8),
                          decoration: BoxDecoration(
                            color: AppColors.rojo.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: AppColors.rojo.withValues(alpha: 0.3)),
                          ),
                          child: Text(_error!, style: AppTextStyles.cuerpo(size: 12, color: AppColors.rojo)),
                        ),
                      ],
                      const SizedBox(height: 8),
                      PrimaryButton(
                        label: _tabRegistro ? 'Registrarse y comenzar →' : 'Iniciar Sesión →',
                        onPressed: _entrar,
                      ),
                      const SizedBox(height: 18),
                      Text(
                        'Juntos para alimentar Guatemala\nPrograma MAGA · CONADEA AgroIA',
                        textAlign: TextAlign.center,
                        style: AppTextStyles.cuerpo(size: 11),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _Tabs extends StatelessWidget {
  const _Tabs({required this.registro, required this.onChange});

  final bool registro;
  final ValueChanged<bool> onChange;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.fondoBase.withValues(alpha: 0.45),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.borde),
      ),
      child: Row(
        children: [
          Expanded(child: _TabBtn(label: 'Iniciar Sesión', active: !registro, onTap: () => onChange(false))),
          const SizedBox(width: 4),
          Expanded(child: _TabBtn(label: 'Registrarse', active: registro, onTap: () => onChange(true))),
        ],
      ),
    );
  }
}

class _TabBtn extends StatelessWidget {
  const _TabBtn({required this.label, required this.active, required this.onTap});

  final String label;
  final bool active;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(vertical: 10),
        alignment: Alignment.center,
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(10),
          gradient: active
              ? LinearGradient(
                  colors: [AppColors.verde.withValues(alpha: 0.25), AppColors.verdeFuerte.withValues(alpha: 0.1)],
                )
              : null,
          border: active ? Border.all(color: AppColors.verde.withValues(alpha: 0.3)) : null,
        ),
        child: Text(
          label,
          style: AppTextStyles.etiqueta(size: 12.5, color: active ? Colors.white : AppColors.textoSuave)
              .copyWith(letterSpacing: 0, fontWeight: FontWeight.w700),
        ),
      ),
    );
  }
}

class _CampoTexto extends StatelessWidget {
  const _CampoTexto({required this.label, required this.hint, this.controller});

  final String label;
  final String hint;
  final TextEditingController? controller;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: AppTextStyles.etiqueta()),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          style: AppTextStyles.cuerpo(size: 14, color: Colors.white),
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
            filled: true,
            fillColor: Colors.white.withValues(alpha: 0.07),
            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.15)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: AppColors.verde, width: 1.5),
            ),
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}
