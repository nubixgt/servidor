import 'package:flutter/services.dart';

/// Formateador para DPI de Guatemala
/// Formato: 0000 00000 0000 (13 dígitos)
class FormateadorDPI extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    // Eliminar todo lo que no sea dígito
    String textoSinFormato = newValue.text.replaceAll(RegExp(r'\D'), '');

    // Limitar a 13 dígitos
    if (textoSinFormato.length > 13) {
      textoSinFormato = textoSinFormato.substring(0, 13);
    }

    // Aplicar formato
    String textoFormateado = '';

    for (int i = 0; i < textoSinFormato.length; i++) {
      if (i == 4 || i == 9) {
        textoFormateado += ' ';
      }
      textoFormateado += textoSinFormato[i];
    }

    return TextEditingValue(
      text: textoFormateado,
      selection: TextSelection.collapsed(offset: textoFormateado.length),
    );
  }
}

/// Formateador para Celular de Guatemala
/// Formato: 0000-0000 (8 dígitos)
class FormateadorCelular extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    // Eliminar todo lo que no sea dígito
    String textoSinFormato = newValue.text.replaceAll(RegExp(r'\D'), '');

    // Limitar a 8 dígitos ESTRICTAMENTE
    if (textoSinFormato.length > 8) {
      textoSinFormato = textoSinFormato.substring(0, 8);
    }

    // Si está vacío, retornar vacío
    if (textoSinFormato.isEmpty) {
      return const TextEditingValue(
        text: '',
        selection: TextSelection.collapsed(offset: 0),
      );
    }

    // Aplicar formato
    String textoFormateado = '';

    for (int i = 0; i < textoSinFormato.length; i++) {
      if (i == 4) {
        textoFormateado += '-';
      }
      textoFormateado += textoSinFormato[i];
    }

    return TextEditingValue(
      text: textoFormateado,
      selection: TextSelection.collapsed(offset: textoFormateado.length),
    );
  }
}
