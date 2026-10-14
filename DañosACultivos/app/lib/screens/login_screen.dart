import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../services/api_client.dart';
import '../services/auth_service.dart';
import '../theme/app_theme.dart';
import '../widgets/common.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _usuario = TextEditingController();
  final _password = TextEditingController();
  bool _ver = false;
  bool _cargando = false;

  @override
  void dispose() {
    _usuario.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _ingresar() async {
    final usuario = _usuario.text.trim();
    final password = _password.text;
    if (usuario.isEmpty || password.isEmpty) {
      errorDialog(context, 'Ingresa tu usuario y contraseña.', title: 'Datos incompletos');
      return;
    }
    FocusScope.of(context).unfocus();
    setState(() => _cargando = true);
    final auth = context.read<AuthService>();
    try {
      await auth.login(usuario, password);
      if (!mounted) return;
      showMsg(context, '¡Bienvenido! Sesión iniciada como $usuario.');
    } on ApiException catch (e) {
      if (!mounted) return;
      _password.clear();
      errorDialog(context, e.message, title: e.status == null ? 'Sin conexión' : 'No se pudo iniciar sesión');
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    const shadow = [Shadow(color: Color(0x66000000), blurRadius: 10)];
    return Scaffold(
      body: AppBackground(
        login: true,
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 14, 20, 25),
            keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                const Text('MAGA', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: -1, color: Colors.white)),
                Container(width: 1, height: 28, margin: const EdgeInsets.symmetric(horizontal: 10), color: const Color(0x66FFFFFF)),
                const Expanded(child: Text('Ministerio de Agricultura,\nGanadería y Alimentación', style: TextStyle(fontSize: 8, height: 1.4, color: Colors.white))),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                  decoration: BoxDecoration(color: const Color(0x0DFFFFFF), borderRadius: BorderRadius.circular(30), border: Border.all(color: const Color(0x3DE4F4E4))),
                  child: const Row(mainAxisSize: MainAxisSize.min, children: [
                    Icon(Icons.eco_outlined, size: 12, color: Color(0xFFC6EFB4)),
                    SizedBox(width: 5),
                    Text('Gestión de campo', style: TextStyle(fontSize: 8, color: Color(0xFFE8F5E8))),
                  ]),
                ),
              ]),
              const SizedBox(height: 34),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 7),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Row(children: [
                    Container(width: 18, height: 1, color: const Color(0xFFC0E9AB)),
                    const SizedBox(width: 8),
                    const Text('INFORMACIÓN QUE PROTEGE', style: TextStyle(fontSize: 8, letterSpacing: 1.2, fontWeight: FontWeight.w600, color: Color(0xFFD0F1D9))),
                  ]),
                  const SizedBox(height: 12),
                  const Text('Cuidamos el campo.\nEmpezamos con', style: TextStyle(fontSize: 34, height: 1.12, letterSpacing: -1, fontWeight: FontWeight.w600, color: Colors.white, shadows: shadow)),
                  const Text('información.', style: TextStyle(fontSize: 34, height: 1.12, letterSpacing: -1, fontWeight: FontWeight.w600, color: Color(0xFFC8EE9D), shadows: shadow)),
                  const SizedBox(height: 14),
                  const Text('Cada reporte acerca una respuesta. Registra las afectaciones y acompaña el trabajo de quienes producen nuestros alimentos.',
                      style: TextStyle(fontSize: 11, height: 1.8, color: Color(0xFFE0ECDF))),
                ]),
              ),
              const SizedBox(height: 26),
              Panel(
                radius: 24,
                padding: const EdgeInsets.fromLTRB(26, 24, 26, 26),
                gradient: const LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [Color(0xF7FFFFFF), Color(0xE8EBF7EB)]),
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Center(child: Image.asset('assets/images/logo_login.png', width: 200)),
                  const SizedBox(height: 14),
                  Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                    Container(
                      width: 41,
                      height: 41,
                      decoration: BoxDecoration(color: const Color(0xFFE1F0E4), borderRadius: BorderRadius.circular(14), border: Border.all(color: const Color(0xFFC3DFCC))),
                      child: const Icon(Icons.lock_outline, size: 20, color: Color(0xFF138358)),
                    ),
                    const Text('ACCESO AL SISTEMA', style: TextStyle(fontSize: 9, letterSpacing: 1.3, fontWeight: FontWeight.w600, color: Color(0xFF638171))),
                  ]),
                  const SizedBox(height: 18),
                  const Text('Bienvenido de nuevo.', style: TextStyle(fontSize: 27, fontWeight: FontWeight.w700, letterSpacing: -1, color: Color(0xFF123F3B))),
                  const SizedBox(height: 8),
                  const Text('Inicia sesión para continuar con el registro y seguimiento de daños a cultivos.', style: TextStyle(fontSize: 12, height: 1.6, color: Color(0xFF688275))),
                  const SizedBox(height: 22),
                  const FieldLabel('Usuario'),
                  TextField(
                    controller: _usuario,
                    autocorrect: false,
                    enableSuggestions: false,
                    textInputAction: TextInputAction.next,
                    autofillHints: const [AutofillHints.username],
                    decoration: const InputDecoration(hintText: 'Tu usuario', prefixIcon: Icon(Icons.person_outline, size: 20, color: Color(0xFF7E9A86))),
                  ),
                  const SizedBox(height: 18),
                  const FieldLabel('Contraseña'),
                  TextField(
                    controller: _password,
                    obscureText: !_ver,
                    autocorrect: false,
                    enableSuggestions: false,
                    textInputAction: TextInputAction.done,
                    autofillHints: const [AutofillHints.password],
                    onSubmitted: (_) => _ingresar(),
                    decoration: InputDecoration(
                      hintText: 'Tu contraseña',
                      prefixIcon: const Icon(Icons.lock_outline, size: 20, color: Color(0xFF7E9A86)),
                      suffixIcon: IconButton(
                        icon: Icon(_ver ? Icons.visibility_off_outlined : Icons.visibility_outlined, size: 20, color: const Color(0xFF708D79)),
                        onPressed: () => setState(() => _ver = !_ver),
                      ),
                    ),
                  ),
                  const SizedBox(height: 26),
                  SizedBox(
                    width: double.infinity,
                    child: AppButton(_cargando ? 'Ingresando…' : 'Ingresar al sistema', primary: true, expand: true, loading: _cargando, trailingIcon: _cargando ? null : Icons.arrow_forward, onPressed: _ingresar),
                  ),
                ]),
              ),
              const SizedBox(height: 20),
              const Center(child: Text('Ministerio de Agricultura, Ganadería y Alimentación\nDaños a Cultivos', textAlign: TextAlign.center, style: TextStyle(fontSize: 8, height: 1.8, color: Color(0xFFD1E5CF)))),
            ]),
          ),
        ),
      ),
    );
  }
}
