import 'package:flutter/material.dart';

import 'search_select.dart';

const kTiposRegistro = ['Cultivos', 'Pecuario', 'Infraestructura'];

/// Selector del tipo de registro (Cultivos / Pecuario / Infraestructura) que se muestra
/// arriba de "Crear registro" y de "Registros" para no saturar la barra inferior.
class TipoRegistroSelector extends StatelessWidget {
  final String value;
  final ValueChanged<String> onChanged;
  const TipoRegistroSelector({super.key, required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: ChoiceButtons(key: ValueKey('tipo-$value'), value: value, options: kTiposRegistro, required: false, onChanged: (v) {
        if (v.isNotEmpty) onChanged(v);
      }),
    );
  }
}
