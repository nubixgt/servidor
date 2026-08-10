import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
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
/// teléfono (para vincular WhatsApp) y departamento -> municipio en cascada,
/// con selector tipo buscador (ver [_CampoBuscador]).
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
    setState(() => _tabRegistro = registro);
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
      _alertaError(e.message);
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
      _alertaError(e.message);
    } finally {
      if (mounted) setState(() => _cargandoMunicipios = false);
    }
  }

  Future<void> _entrar() async {
    if (_cargando) return;

    final error = _tabRegistro ? _validarRegistro() : _validarLogin();
    if (error != null) {
      _alertaError(error);
      return;
    }

    setState(() => _cargando = true);

    try {
      if (_tabRegistro) {
        await _authService.registrar(
          nombreCompleto: _regNombreCtrl.text.trim(),
          usuario: _regUsuarioCtrl.text.trim(),
          password: _regPasswordCtrl.text,
          telefono: _regTelefonoCtrl.text.trim().replaceAll('-', ''),
          departamentoId: _departamentoSeleccionado!.id,
          municipioId: _municipioSeleccionado!.id,
        );
        // El registro no inicia sesión automáticamente: la persona debe
        // volver a escribir su usuario y contraseña en Iniciar Sesión.
        await _authService.cerrarSesion();
        if (!mounted) return;

        await _alerta(
          tipo: _TipoAlerta.exito,
          titulo: '¡Cuenta creada!',
          mensaje: 'Tu registro se guardó correctamente. Ahora inicia sesión con tu usuario y contraseña.',
        );
        if (!mounted) return;
        _limpiarFormularioRegistro();
        _cambiarTab(false);
      } else {
        await _authService.iniciarSesion(
          usuario: _loginUsuarioCtrl.text.trim(),
          password: _loginPasswordCtrl.text,
        );
        if (!mounted) return;

        await _alerta(
          tipo: _TipoAlerta.exito,
          titulo: '¡Bienvenido!',
          mensaje: 'Inicio de sesión correcto.',
          autoCerrar: const Duration(milliseconds: 900),
        );
        if (!mounted) return;

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (_) => const MainShell()),
        );
      }
    } on ApiException catch (e) {
      _alertaError(e.message);
    } finally {
      if (mounted) setState(() => _cargando = false);
    }
  }

  void _limpiarFormularioRegistro() {
    _regNombreCtrl.clear();
    _regUsuarioCtrl.clear();
    _regPasswordCtrl.clear();
    _regTelefonoCtrl.clear();
    setState(() {
      _departamentoSeleccionado = null;
      _municipioSeleccionado = null;
      _municipios = [];
    });
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
    if (!RegExp(r'^\d{4}-\d{4}$').hasMatch(_regTelefonoCtrl.text.trim())) {
      return 'Ingresa tu número de teléfono completo (formato 0000-0000).';
    }
    if (_departamentoSeleccionado == null) {
      return 'Selecciona tu departamento.';
    }
    if (_municipioSeleccionado == null) {
      return 'Selecciona tu municipio.';
    }
    return null;
  }

  void _alertaError(String mensaje) {
    _alerta(tipo: _TipoAlerta.error, titulo: 'Ocurrió un problema', mensaje: mensaje);
  }

  Future<void> _alerta({
    required _TipoAlerta tipo,
    required String titulo,
    required String mensaje,
    Duration? autoCerrar,
  }) async {
    final future = showDialog<void>(
      context: context,
      barrierDismissible: autoCerrar == null,
      builder: (_) => _DialogoAlerta(tipo: tipo, titulo: titulo, mensaje: mensaje),
    );

    if (autoCerrar != null) {
      Future.delayed(autoCerrar, () {
        if (mounted) Navigator.of(context).pop();
      });
    }

    await future;
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
        hint: 'Ej. 5512-3456',
        controller: _regTelefonoCtrl,
        tipoTeclado: TextInputType.phone,
        inputFormatters: [_TelefonoFormatter()],
      ),
      _CampoBuscador<Departamento>(
        label: 'Departamento',
        hint: _cargandoDepartamentos ? 'Cargando...' : 'Selecciona tu departamento',
        tituloBuscador: 'Elige tu departamento',
        valorTexto: _departamentoSeleccionado?.nombre,
        opciones: _departamentos,
        etiquetaDe: (d) => d.nombre,
        habilitado: !_cargandoDepartamentos,
        onSeleccionado: _seleccionarDepartamento,
      ),
      _CampoBuscador<Municipio>(
        label: 'Municipio',
        hint: _departamentoSeleccionado == null
            ? 'Primero elige un departamento'
            : (_cargandoMunicipios ? 'Cargando...' : 'Selecciona tu municipio'),
        tituloBuscador: 'Elige tu municipio',
        valorTexto: _municipioSeleccionado?.nombre,
        opciones: _municipios,
        etiquetaDe: (m) => m.nombre,
        habilitado: _departamentoSeleccionado != null && !_cargandoMunicipios,
        onSeleccionado: (m) => setState(() => _municipioSeleccionado = m),
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
    this.inputFormatters,
  });

  final String label;
  final String hint;
  final TextEditingController? controller;
  final bool esPassword;
  final TextInputType? tipoTeclado;
  final List<TextInputFormatter>? inputFormatters;

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
          inputFormatters: widget.inputFormatters,
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

/// Campo de solo lectura que, al tocarlo, abre un buscador (bottom sheet)
/// en vez de desplegar una lista larga completa en pantalla.
class _CampoBuscador<T> extends StatelessWidget {
  const _CampoBuscador({
    required this.label,
    required this.hint,
    required this.tituloBuscador,
    required this.valorTexto,
    required this.opciones,
    required this.etiquetaDe,
    required this.onSeleccionado,
    required this.habilitado,
    super.key,
  });

  final String label;
  final String hint;
  final String tituloBuscador;
  final String? valorTexto;
  final List<T> opciones;
  final String Function(T) etiquetaDe;
  final ValueChanged<T> onSeleccionado;
  final bool habilitado;

  Future<void> _abrir(BuildContext context) async {
    final seleccion = await showModalBottomSheet<T>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _HojaBuscador<T>(titulo: tituloBuscador, opciones: opciones, etiquetaDe: etiquetaDe),
    );
    if (seleccion != null) onSeleccionado(seleccion);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(), style: AppTextStyles.etiqueta()),
        const SizedBox(height: 6),
        Opacity(
          opacity: habilitado ? 1 : 0.5,
          child: InkWell(
            borderRadius: BorderRadius.circular(12),
            onTap: habilitado ? () => _abrir(context) : null,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.07),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
              ),
              child: Row(
                children: [
                  Icon(Icons.search, size: 18, color: Colors.white.withValues(alpha: 0.45)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      valorTexto ?? hint,
                      overflow: TextOverflow.ellipsis,
                      style: valorTexto != null
                          ? AppTextStyles.cuerpo(size: 14, color: Colors.white)
                          : AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
                    ),
                  ),
                  Icon(Icons.expand_more, size: 20, color: Colors.white.withValues(alpha: 0.5)),
                ],
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
      ],
    );
  }
}

/// Hoja inferior con buscador: escribe y filtra en vivo, toca una fila
/// para seleccionarla.
class _HojaBuscador<T> extends StatefulWidget {
  const _HojaBuscador({required this.titulo, required this.opciones, required this.etiquetaDe, super.key});

  final String titulo;
  final List<T> opciones;
  final String Function(T) etiquetaDe;

  @override
  State<_HojaBuscador<T>> createState() => _HojaBuscadorState<T>();
}

class _HojaBuscadorState<T> extends State<_HojaBuscador<T>> {
  final _buscarCtrl = TextEditingController();
  late List<T> _filtrados = widget.opciones;

  void _filtrar(String texto) {
    final q = texto.trim().toLowerCase();
    setState(() {
      _filtrados = q.isEmpty
          ? widget.opciones
          : widget.opciones.where((o) => widget.etiquetaDe(o).toLowerCase().contains(q)).toList();
    });
  }

  @override
  void dispose() {
    _buscarCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final alturaMax = MediaQuery.of(context).size.height * 0.75;

    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: ConstrainedBox(
        constraints: BoxConstraints(maxHeight: alturaMax),
        child: ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          child: Container(
            color: AppColors.fondoBase,
            child: SafeArea(
              top: false,
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const SizedBox(height: 10),
                  Container(
                    width: 36,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.25),
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(widget.titulo, style: AppTextStyles.subtitulo(size: 16)),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _buscarCtrl,
                          autofocus: true,
                          onChanged: _filtrar,
                          style: AppTextStyles.cuerpo(size: 14, color: Colors.white),
                          decoration: InputDecoration(
                            hintText: 'Escribe para buscar...',
                            hintStyle: AppTextStyles.cuerpo(size: 13, color: Colors.white.withValues(alpha: 0.4)),
                            prefixIcon: Icon(Icons.search, size: 20, color: Colors.white.withValues(alpha: 0.5)),
                            filled: true,
                            fillColor: Colors.white.withValues(alpha: 0.07),
                            contentPadding: const EdgeInsets.symmetric(vertical: 12),
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
                      ],
                    ),
                  ),
                  Flexible(
                    child: _filtrados.isEmpty
                        ? Padding(
                            padding: const EdgeInsets.symmetric(vertical: 32),
                            child: Text(
                              'No se encontraron resultados.',
                              style: AppTextStyles.cuerpo(size: 13),
                            ),
                          )
                        : ListView.builder(
                            shrinkWrap: true,
                            padding: const EdgeInsets.only(bottom: 12),
                            itemCount: _filtrados.length,
                            itemBuilder: (context, i) {
                              final opcion = _filtrados[i];
                              return ListTile(
                                title: Text(
                                  widget.etiquetaDe(opcion),
                                  style: AppTextStyles.cuerpo(size: 14, color: Colors.white),
                                ),
                                onTap: () => Navigator.of(context).pop(opcion),
                              );
                            },
                          ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

enum _TipoAlerta { exito, error }

/// Diálogo estilo "sweetalert" con la estética glass/verde de CONADEA
/// (Flutter no tiene SweetAlert; este widget cumple el mismo rol).
class _DialogoAlerta extends StatelessWidget {
  const _DialogoAlerta({required this.tipo, required this.titulo, required this.mensaje});

  final _TipoAlerta tipo;
  final String titulo;
  final String mensaje;

  @override
  Widget build(BuildContext context) {
    final esExito = tipo == _TipoAlerta.exito;
    final color = esExito ? AppColors.verde : AppColors.rojo;

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 32),
      child: GlassCard(
        padding: const EdgeInsets.fromLTRB(24, 28, 24, 20),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: color.withValues(alpha: 0.15),
                border: Border.all(color: color.withValues(alpha: 0.4), width: 1.5),
              ),
              child: Icon(
                esExito ? Icons.check_rounded : Icons.close_rounded,
                color: color,
                size: 34,
              ),
            ),
            const SizedBox(height: 16),
            Text(titulo, textAlign: TextAlign.center, style: AppTextStyles.subtitulo(size: 17)),
            const SizedBox(height: 8),
            Text(mensaje, textAlign: TextAlign.center, style: AppTextStyles.cuerpo(size: 13)),
            const SizedBox(height: 20),
            PrimaryButton(label: 'Entendido', onPressed: () => Navigator.of(context).pop()),
          ],
        ),
      ),
    );
  }
}

/// Formatea el teléfono como 0000-0000 mientras se escribe y no deja
/// ingresar más de 8 dígitos.
class _TelefonoFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(TextEditingValue oldValue, TextEditingValue newValue) {
    final digitos = newValue.text.replaceAll(RegExp(r'[^0-9]'), '');
    final limitados = digitos.length > 8 ? digitos.substring(0, 8) : digitos;

    final buffer = StringBuffer();
    for (var i = 0; i < limitados.length; i++) {
      if (i == 4) buffer.write('-');
      buffer.write(limitados[i]);
    }

    return TextEditingValue(
      text: buffer.toString(),
      selection: TextSelection.collapsed(offset: buffer.length),
    );
  }
}
