import 'package:flutter/services.dart';

/// Formateador para teléfono guatemalteco
/// Formato: 1234-5678
/// El usuario solo escribe números y automáticamente se agrega el guion
class TelefonoInputFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    // Remover todo excepto números
    String digitsOnly = newValue.text.replaceAll(RegExp(r'[^0-9]'), '');

    // Limitar a 8 dígitos
    if (digitsOnly.length > 8) {
      digitsOnly = digitsOnly.substring(0, 8);
    }

    // Formatear: XXXX-XXXX
    String formatted = '';
    if (digitsOnly.isNotEmpty) {
      if (digitsOnly.length <= 4) {
        formatted = digitsOnly;
      } else {
        formatted = '${digitsOnly.substring(0, 4)}-${digitsOnly.substring(4)}';
      }
    }

    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}
