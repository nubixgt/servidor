import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/glass_card.dart';
import '../../core/widgets/primary_button.dart';
import '../../data/models/departamento.dart';
import '../../data/models/municipio.dart';
import '../../data/services/api_exception.dart';
import '../../data/services/auth_service.dart';
import '../../data/services/catalogo_service.dart';
import '../shell/main_shell.dart';

/// Pantalla de acceso — conectada al Backend (AuthController/LocationController).
/// Login: usuario + contraseña. Registro: nombre, usuario, contraseña,
/// teléfono (para vincular WhatsApp) y departamento -> municipio en cascada.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _authService = AuthService();
  final _catalogoService = CatalogoService();

  bool _tabRegistro = false;
  bool _cargando = false;
  String? _error;

  // Login
  final _loginUsuarioCtrl = TextEditingController();
  final _loginPasswordCtrl = TextEditingController();

  // Registro
  final _regNombreCtrl = TextEditingController();
  final _regUsuarioCtrl = TextEditingController();
  final _regPasswordCtrl = TextEditingController();
  final _regTelefonoCtrl = TextEditingController();

  bool _departamentosCargados = false;
  bool _cargandoDepartamentos = false;
  bool _cargandoMunicipios = false;
  List<Departamento> _departamentos = [];
  List<Municipio> _municipios = [];
  Departamento? _departamentoSeleccionado;
  Municipio? _municipioSeleccionado;

  void _cambiarTab(bool registro) {
    setState(() {
      _tabRegistro = registro;
      _error = null;
    });
    if (registro) _cargarDepartamentos();
  }

  Future<void> _cargarDepartamentos() async {
    if (_departamentosCargados || _cargandoDepartamentos) return;
    setState(() => _cargandoDepartamentos = true);
    try {
      final departamentos = await _catalogoService.listarDepartamentos();
      setState(() {
        _departamentos = departamentos;
        _departamentosCargados = true;
      });
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _cargandoDepartamentos = false);
    }
  }

  Future<void> _seleccionarDepartamento(Departamento? departamento) async {
    setState(() {
      _departamentoSeleccionado = departamento;
      _municipioSeleccionado = null;
      _municipios = [];
    });
    if (departamento == null) return;

    setState(() => _cargandoMunicipios = true);
    try {
      final municipios = await _catalogoService.listarMunicipios(departamento.id);
      setState(() => _municipios = municipios);
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _cargandoMunicipios = false);
    }
  }

  Future<void> _entrar() async {
    if (_cargando) return;

    final error = _tabRegistro ? _validarRegistro() : _validarLogin();
    if (error != null) {
      setState(() => _error = error);
      return;
    }

    setState(() {
      _cargando = true;
      _error = null;
    });

    try {
      if (_tabRegistro) {
        await _authService.registrar(
          nombreCompleto: _regNombreCtrl.text.trim(),
          usuario: _regUsuarioCtrl.text.trim(),
          password: _regPasswordCtrl.text,
          telefono: _regTelefonoCtrl.text.trim(),
          departamentoId: _departamentoSeleccionado!.id,
          municipioId: _municipioSeleccionado!.id,
        );
      } else {
        await _authService.iniciarSesion(
          usuario: _loginUsuarioCtrl.text.trim(),
          password: _loginPasswordCtrl.text,
        );
      }

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const MainShell()),
      );
    } on ApiException catch (e) {
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  String? _validarLogin() {
    if (_loginUsuarioCtrl.text.trim().isEmpty || _loginPasswordCtrl.text.isEmpty) {
      return 'Ingresa tu usuario y contraseña para continuar.';
    }
    return null;
  }

  String? _validarRegistro() {
    if (_regNombreCtrl.text.trim().length < 3) {
      return 'El nombre completo es obligatorio.';
    }
    if (_regUsuarioCtrl.text.trim().length < 3) {
      return 'El usuario debe tener al menos 3 caracteres.';
    }
    if (_regPasswordCtrl.text.length < 6) {
      return 'La contraseña debe tener al menos 6 caracteres.';
    }
    if (!RegExp(r'^\+?[0-9]{8,15}$').hasMatch(_regTelefonoCtrl.text.trim())) {
      return 'Ingresa un número de teléfono válido (el que usas en WhatsApp).';
    }
    if (_departamentoSeleccionado == null) {
      return 'Selecciona tu departamento.';
    }
    if (_municipioSeleccionado == null) {
      return 'Selecciona tu municipio.';
    }
    return null;
  }

  @override
  void dispose() {
    _loginUsuarioCtrl.dispose();
    _loginPasswordCtrl.dispose();
    _regNombreCtrl.dispose();
    _regUsuarioCtrl.dispose();
    _regPasswordCtrl.dispose();
    _regTelefonoCtrl.dispose();
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
                      _Tabs(registro: _tabRegistro, onChange: _cambiarTab),
                      const SizedBox(height: 20),
                      if (!_tabRegistro) ..._camposLogin() else ..._camposRegistro(),
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
                        label: _cargando
                            ? 'Cargando...'
                            : (_tabRegistro ? 'Registrarse y comenzar →' : 'Iniciar Sesión →'),
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

  List<Widget> _camposLogin() {
    return [
      _CampoTexto(label: 'Usuario', hint: 'Ej. ana.garcia', controller: _loginUsuarioCtrl),
      _CampoTexto(
        label: 'Contraseña',
        hint: 'Tu contraseña',
        controller: _loginPasswordCtrl,
        esPassword: true,
      ),
    ];
  }

  List<Widget> _camposRegistro() {
    return [
      _CampoTexto(label: 'Nombre completo', hint: 'Ej. Ana María García', controller: _regNombreCtrl),
      _CampoTexto(label: 'Usuario', hint: 'Ej. ana.garcia', controller: _regUsuarioCtrl),
      _CampoTexto(
        label: 'Contraseña',
        hint: 'Mínimo 6 caracteres',
        controller: _regPasswordCtrl,
        esPassword: true,
      ),
      _CampoTexto(
        label: 'Número de teléfono (WhatsApp)',
        hint: 'Ej. 55123456',
        controller: _regTelefonoCtrl,
        tipoTeclado: TextInputType.phone,
      ),
      _CampoDropdown<Departamento>(
        label: 'Departamento',
        hint: _cargandoDepartamentos ? 'Cargando...' : 'Selecciona tu departamento',
        valor: _departamentoSeleccionado,
        opciones: _departamentos,
        etiquetaDe: (d) => d.nombre,
        onChanged: _cargandoDepartamentos ? null : _seleccionarDepartamento,
      ),
      _CampoDropdown<Municipio>(
        label: 'Municipio',
        hint: _departamentoSeleccionado == null
            ? 'Primero elige un departamento'
            : (_cargandoMunicipios ? 'Cargando...' : 'Selecciona tu municipio'),
        valor: _municipioSeleccionado,
        opciones: _municipios,
        etiquetaDe: (m) => m.nombre,
        onChanged: (_departamentoSeleccionado == null || _cargandoMunicipios)
            ? null
            : (m) => setState(() => _municipioSeleccionado = m),
      ),
    ];
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

class _CampoTexto extends StatefulWidget {
  const _CampoTexto({
    required this.label,
    required this.hint,
    this.controller,
    this.esPassword = false,
    this.tipoTeclado,
  });

  final String label;
  final String hint;
  final TextEditingController? controller;
  final bool esPassword;
  final TextInputType? tipoTeclado;

  @override
  State<_CampoTexto> createState() => _CampoTextoState();
}

class _CampoTextoState extends State<_CampoTexto> {
  bool _oculto = true;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(widget.label.toUpperCase(), style: AppTextStyles.etiqueta()),
        const SizedBox(height: 6),
        TextField(
          controller: widget.controller,
          obscureText: widget.esPassword && _oculto,
          keyboardType: widget.tipoTeclado,
          style: AppTextStyles.cuerpo(size: 14, color: Colors.white),
          decoration: InputDecoration(
            hintText: widget.hint,
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
            suffixIcon: widget.esPassword
                ? IconButton(
                    icon: Icon(
                      _oculto ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                      size: 20,
                      color: Colors.white.withValues(alpha: 0.5),
                    ),
                    onPressed: () => setState(() => _oculto = !_oculto),
                  )
                : null,
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}

class _CampoDropdown<T> extends StatelessWidget {
  const _CampoDropdown({
    required this.label,
    required this.hint,
    required this.valor,
    required this.opciones,
    required this.etiquetaDe,
    required this.onChanged,
    super.key,
  });

  final String label;
  final String hint;
  final T? valor;
  final List<T> opciones;
  final String Function(T) etiquetaDe;
  final ValueChanged<T?>? onChanged;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: AppTextStyles.etiqueta()),
        const SizedBox(height: 6),
        DropdownButtonFormField<T>(
          initialValue: valor,
          isExpanded: true,
          dropdownColor: AppColors.fondoBase,
          icon: Icon(Icons.expand_more, color: Colors.white.withValues(alpha: 0.5)),
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
            disabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.08)),
            ),
          ),
          items: [
            for (final opcion in opciones)
              DropdownMenuItem(value: opcion, child: Text(etiquetaDe(opcion), overflow: TextOverflow.ellipsis)),
          ],
          onChanged: onChanged,
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}
