class Validadores {
  /// Valida que el DPI tenga exactamente 13 dígitos
  static bool validarDPI(String dpi) {
    String dpiSinFormato = dpi.replaceAll(RegExp(r'\D'), '');
    return dpiSinFormato.length == 13;
  }

  /// Valida que el celular tenga exactamente 8 dígitos
  static bool validarCelular(String celular) {
    String celularSinFormato = celular.replaceAll(RegExp(r'\D'), '');
    return celularSinFormato.length == 8;
  }

  /// Valida que el nombre no esté vacío y tenga al menos 3 caracteres
  static bool validarNombre(String nombre) {
    return nombre.trim().length >= 3;
  }

  /// Valida que el correo tenga formato válido
  static bool validarCorreo(String correo) {
    if (correo.isEmpty) return true; // El correo es opcional
    final RegExp emailRegex = RegExp(
      r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$',
    );
    return emailRegex.hasMatch(correo);
  }

  /// Valida que la edad esté entre 18 y 120
  static bool validarEdad(String edad) {
    final int? edadInt = int.tryParse(edad);
    if (edadInt == null) return false;
    return edadInt >= 18 && edadInt <= 120;
  }

  /// Obtiene el texto sin formato (solo números)
  static String obtenerSoloNumeros(String texto) {
    return texto.replaceAll(RegExp(r'\D'), '');
  }
}
