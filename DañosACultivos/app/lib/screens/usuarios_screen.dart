import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../models/usuario.dart';
import '../services/api_client.dart';
import '../services/usuario_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_shell.dart';
import '../widgets/common.dart';

/// Administración de usuarios (solo rol admin).
class UsuariosScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  final bool active;
  const UsuariosScreen({super.key, required this.user, required this.onLogout, required this.active});

  @override
  State<UsuariosScreen> createState() => _UsuariosScreenState();
}

class _UsuariosScreenState extends State<UsuariosScreen> {
  final _service = UsuarioService();
  List<Usuario> _usuarios = [];
  bool _loading = false;
  String? _error;
  String _busqueda = '';
  String _rol = '';
  String _estado = '';

  static const _roles = {'admin': 'Administrador', 'supervisor': 'Supervisor', 'tecnico': 'Técnico'};

  @override
  void initState() {
    super.initState();
    _cargar();
  }

  @override
  void didUpdateWidget(covariant UsuariosScreen old) {
    super.didUpdateWidget(old);
    if (widget.active && !old.active) _cargar();
  }

  Future<void> _cargar() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final l = await _service.list();
      if (mounted) setState(() => _usuarios = l);
    } on ApiException {
      if (mounted) setState(() => _error = 'No se pudieron cargar los usuarios.');
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  List<Usuario> get _filtrados {
    final q = _busqueda.trim().toLowerCase();
    return _usuarios.where((u) => (q.isEmpty || u.usuario.toLowerCase().contains(q)) && (_rol.isEmpty || u.rol == _rol) && (_estado.isEmpty || u.activo.toString() == _estado)).toList();
  }

  Future<void> _eliminar(Usuario u) async {
    if (!await confirmDialog(context, 'Se eliminará al usuario "${u.usuario}". Esta acción no se puede deshacer.', title: '¿Eliminar usuario?', danger: true, confirmText: 'Eliminar')) return;
    try {
      await _service.remove(u.id);
      if (!mounted) return;
      setState(() => _usuarios = _usuarios.where((x) => x.id != u.id).toList());
      showMsg(context, 'Usuario eliminado');
    } on ApiException catch (e) {
      if (mounted) errorDialog(context, e.message, title: 'No se pudo eliminar');
    }
  }

  Future<void> _formulario([Usuario? u]) async {
    final ok = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _UsuarioForm(usuario: u, yo: widget.user.id, service: _service),
    );
    if (ok == true) {
      if (mounted) showMsg(context, u == null ? 'Usuario creado' : 'Usuario actualizado');
      _cargar();
    }
  }

  String _fecha(String f) {
    try {
      return DateFormat('dd MMM y', 'es').format(DateTime.parse(f.substring(0, 10)));
    } catch (_) {
      return '—';
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = _usuarios;
    final filtrados = _filtrados;
    return PageScroll(
      heroKey: 'usuarios',
      user: widget.user,
      onLogout: widget.onLogout,
      onRefresh: _cargar,
      children: [
        ContentHead(title: 'Equipo de trabajo', subtitle: 'Administra los usuarios y sus roles en el sistema.', action: AppButton('Nuevo', primary: true, icon: Icons.add, onPressed: () => _formulario())),
        StatsGrid(cards: [
          StatCard(index: 0, title: 'Integrantes del equipo', value: '${l.length}', icon: Icons.groups_outlined, foot: 'Usuarios registrados'),
          StatCard(index: 1, title: 'Usuarios activos', value: '${l.where((u) => u.activo).length}', icon: Icons.check_circle_outline, foot: 'Con acceso habilitado'),
          StatCard(index: 2, title: 'Supervisores', value: '${l.where((u) => u.rol == 'supervisor').length}', icon: Icons.shield_outlined, foot: 'Seguimiento del trabajo en campo'),
          StatCard(index: 3, title: 'Personal técnico', value: '${l.where((u) => u.rol == 'tecnico').length}', icon: Icons.eco_outlined, foot: 'Captura de información'),
        ]),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            const PanelHead(title: 'Personas y accesos', subtitle: 'Una vista clara del equipo y su estado.'),
            TextField(onChanged: (v) => setState(() => _busqueda = v), decoration: const InputDecoration(hintText: 'Buscar por nombre de usuario…', prefixIcon: Icon(Icons.search, size: 20, color: Color(0xFF81978D)))),
            const SizedBox(height: 10),
            Row(children: [
              Expanded(child: _FiltroMap(value: _rol, hint: 'Todos los roles', options: _roles, onChanged: (v) => setState(() => _rol = v))),
              const SizedBox(width: 8),
              Expanded(child: _FiltroMap(value: _estado, hint: 'Todos los estados', options: const {'true': 'Activo', 'false': 'Inactivo'}, onChanged: (v) => setState(() => _estado = v))),
            ]),
            const SizedBox(height: 18),
            if (_loading && l.isEmpty)
              loadingOrEmpty(loading: true, emptyText: '')
            else if (_error != null)
              Padding(padding: const EdgeInsets.all(30), child: Center(child: Text(_error!, style: const TextStyle(color: AppColors.red))))
            else if (filtrados.isEmpty)
              const EmptyState('No se encontraron usuarios.')
            else
              for (final u in filtrados) _UsuarioCard(u: u, esYo: u.id == widget.user.id, fecha: _fecha(u.creadoEn), onEditar: () => _formulario(u), onEliminar: () => _eliminar(u)),
          ]),
        ),
        const SizedBox(height: 16),
        Panel(
          padding: const EdgeInsets.all(20),
          child: const Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text('Roles del sistema', style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700)),
            SizedBox(height: 16),
            _RolInfo(Icons.shield_outlined, 'Administrador', 'Gestión del equipo, acceso a registros y configuración general.'),
            _RolInfo(Icons.grid_view_rounded, 'Supervisor', 'Consulta de indicadores, seguimiento y revisión de registros.'),
            _RolInfo(Icons.edit_outlined, 'Técnico', 'Captura de información, ubicación y evidencia de campo.'),
          ]),
        ),
      ],
    );
  }
}

class _RolInfo extends StatelessWidget {
  final IconData icon;
  final String titulo;
  final String texto;
  const _RolInfo(this.icon, this.titulo, this.texto);

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Icon(icon, size: 20, color: const Color(0xFF35866B)),
          const SizedBox(width: 14),
          Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Text(titulo, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
            const SizedBox(height: 5),
            Text(texto, style: const TextStyle(fontSize: 11, color: Color(0xFF718779))),
          ])),
        ]),
      );
}

class _FiltroMap extends StatelessWidget {
  final String value;
  final String hint;
  final Map<String, String> options;
  final ValueChanged<String> onChanged;
  const _FiltroMap({required this.value, required this.hint, required this.options, required this.onChanged});

  @override
  Widget build(BuildContext context) => DropdownButtonFormField<String>(
        key: ValueKey(value),
        initialValue: value,
        isExpanded: true,
        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 12)),
        items: [
          DropdownMenuItem(value: '', child: Text(hint, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12))),
          for (final e in options.entries) DropdownMenuItem(value: e.key, child: Text(e.value, style: const TextStyle(fontSize: 12))),
        ],
        onChanged: (v) => onChanged(v ?? ''),
      );
}

class _UsuarioCard extends StatelessWidget {
  final Usuario u;
  final bool esYo;
  final String fecha;
  final VoidCallback onEditar;
  final VoidCallback onEliminar;
  const _UsuarioCard({required this.u, required this.esYo, required this.fecha, required this.onEditar, required this.onEliminar});

  @override
  Widget build(BuildContext context) {
    final bg = switch (u.rol) { 'admin' => const Color(0xFFE7EAF5), 'supervisor' => const Color(0xFFE3EFF4), _ => const Color(0xFFE0EDDF) };
    final kind = switch (u.rol) { 'admin' => BadgeKind.purple, 'supervisor' => BadgeKind.blue, _ => BadgeKind.green };
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: const Color(0xD9FFFFFF), borderRadius: BorderRadius.circular(19), border: Border.all(color: const Color(0xFFE1EADE))),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(children: [
          Container(
            width: 49,
            height: 49,
            alignment: Alignment.center,
            decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white, width: 4), boxShadow: const [BoxShadow(color: Color(0xFFD8E4DA), spreadRadius: 1)]),
            child: Text(u.usuario.substring(0, u.usuario.length < 2 ? u.usuario.length : 2).toUpperCase(), style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: Color(0xFF245B48))),
          ),
          const Spacer(),
          AppBadge(u.activo ? 'Activo' : 'Inactivo', kind: u.activo ? BadgeKind.green : BadgeKind.gray),
        ]),
        const SizedBox(height: 14),
        Row(children: [
          Flexible(child: Text(u.usuario, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700), overflow: TextOverflow.ellipsis)),
          if (esYo) const Padding(padding: EdgeInsets.only(left: 8), child: Text('(tú)', style: TextStyle(fontSize: 11, color: AppColors.muted))),
        ]),
        const SizedBox(height: 8),
        AppBadge(UsuariosRoles.label(u.rol), kind: kind),
        const Divider(height: 30, color: Color(0xFFE2EAE1)),
        Row(children: [
          Expanded(child: Text('Creado el $fecha', style: const TextStyle(fontSize: 10, color: Color(0xFF7D9285)))),
          IconBtn(Icons.edit_outlined, tooltip: 'Editar', onPressed: onEditar),
          const SizedBox(width: 8),
          IconBtn(Icons.delete_outline, color: AppColors.red, tooltip: 'Eliminar', onPressed: esYo ? null : onEliminar),
        ]),
      ]),
    );
  }
}

class UsuariosRoles {
  static String label(String rol) => switch (rol) { 'admin' => 'Admin', 'supervisor' => 'Supervisor', 'tecnico' => 'Técnico', _ => rol };
}

class _UsuarioForm extends StatefulWidget {
  final Usuario? usuario;
  final int yo;
  final UsuarioService service;
  const _UsuarioForm({this.usuario, required this.yo, required this.service});

  @override
  State<_UsuarioForm> createState() => _UsuarioFormState();
}

class _UsuarioFormState extends State<_UsuarioForm> {
  late final TextEditingController _usuario = TextEditingController(text: widget.usuario?.usuario ?? '');
  final _password = TextEditingController();
  late String _rol = widget.usuario?.rol ?? 'tecnico';
  late bool _activo = widget.usuario?.activo ?? true;
  bool _guardando = false;

  bool get _esYo => widget.usuario?.id == widget.yo;

  @override
  void dispose() {
    _usuario.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _guardar() async {
    final usuario = _usuario.text.trim();
    final nuevo = widget.usuario == null;
    if (usuario.isEmpty) return errorDialog(context, 'Ingresa el nombre de usuario.', title: 'Datos incompletos');
    if (nuevo && _password.text.isEmpty) return errorDialog(context, 'Ingresa una contraseña para el nuevo usuario.', title: 'Datos incompletos');
    setState(() => _guardando = true);
    final datos = {'usuario': usuario, 'password': _password.text, 'rol': _rol, 'activo': _activo};
    try {
      if (nuevo) {
        await widget.service.create(datos);
      } else {
        await widget.service.update(widget.usuario!.id, datos);
      }
      if (mounted) Navigator.pop(context, true);
    } on ApiException catch (e) {
      if (mounted) errorDialog(context, e.message, title: 'No se pudo guardar');
    } finally {
      if (mounted) setState(() => _guardando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: Container(
        decoration: const BoxDecoration(color: Color(0xFFFAFDF9), borderRadius: BorderRadius.vertical(top: Radius.circular(23))),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(22),
            child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
              Row(children: [
                Expanded(child: Text(widget.usuario == null ? 'Nuevo usuario' : 'Editar usuario', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700))),
                IconBtn(Icons.close, color: AppColors.navy, onPressed: () => Navigator.pop(context)),
              ]),
              const SizedBox(height: 20),
              LabeledField(label: 'Usuario', child: TextField(controller: _usuario, autocorrect: false, enableSuggestions: false)),
              LabeledField(label: 'Contraseña', child: TextField(controller: _password, obscureText: true, autocorrect: false, decoration: InputDecoration(hintText: widget.usuario != null ? 'Dejar vacío para no cambiarla' : 'Mínimo 6 caracteres'))),
              LabeledField(
                label: 'Rol',
                child: AbsorbPointer(
                  absorbing: _esYo,
                  child: Opacity(
                    opacity: _esYo ? .5 : 1,
                    child: DropdownButtonFormField<String>(
                      initialValue: _rol,
                      isExpanded: true,
                      items: const [DropdownMenuItem(value: 'admin', child: Text('Admin')), DropdownMenuItem(value: 'supervisor', child: Text('Supervisor')), DropdownMenuItem(value: 'tecnico', child: Text('Técnico'))],
                      onChanged: (v) => setState(() => _rol = v ?? _rol),
                    ),
                  ),
                ),
              ),
              Row(children: [
                Checkbox(value: _activo, activeColor: AppColors.green, onChanged: _esYo ? null : (v) => setState(() => _activo = v ?? true)),
                const Text('Usuario activo', style: TextStyle(fontSize: 13)),
              ]),
              const SizedBox(height: 14),
              Row(mainAxisAlignment: MainAxisAlignment.end, children: [
                AppButton('Cancelar', onPressed: () => Navigator.pop(context)),
                const SizedBox(width: 10),
                AppButton(_guardando ? 'Guardando…' : 'Guardar', primary: true, loading: _guardando, onPressed: _guardar),
              ]),
            ]),
          ),
        ),
      ),
    );
  }
}
