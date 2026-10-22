import 'package:flutter/material.dart';

import '../models/usuario.dart';
import '../widgets/tipo_registro_selector.dart';
import 'crear_otros_screen.dart';
import 'crear_registro_screen.dart';

/// "Crear registro": elige entre Cultivos, Pecuario e Infraestructura.
/// Los tres formularios se conservan montados para no perder lo ya escrito al cambiar de tipo.
class CrearHubScreen extends StatefulWidget {
  final AuthUser user;
  final VoidCallback onLogout;
  const CrearHubScreen({super.key, required this.user, required this.onLogout});

  @override
  State<CrearHubScreen> createState() => _CrearHubScreenState();
}

class _CrearHubScreenState extends State<CrearHubScreen> {
  String _tipo = kTiposRegistro[0];

  @override
  Widget build(BuildContext context) {
    final selector = TipoRegistroSelector(value: _tipo, onChanged: (v) => setState(() => _tipo = v));
    return IndexedStack(index: kTiposRegistro.indexOf(_tipo), children: [
      CrearRegistroScreen(user: widget.user, onLogout: widget.onLogout, selector: selector),
      CrearPecuarioScreen(user: widget.user, onLogout: widget.onLogout, selector: selector),
      CrearInfraestructuraScreen(user: widget.user, onLogout: widget.onLogout, selector: selector),
    ]);
  }
}
