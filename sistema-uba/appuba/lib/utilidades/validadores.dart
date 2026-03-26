class Validadores {
  static bool validarNombre(String? valor) {
    if (valor == null || valor.isEmpty) return false;
    return valor.trim().length >= 3;
  }

  static bool validarDPI(String? valor) {
    if (valor == null || valor.isEmpty) return false;
    final clean = obtenerSoloNumeros(valor);
    return clean.length == 13;
  }

  static bool validarEdad(String? valor) {
    if (valor == null || valor.isEmpty) return false;
    final edad = int.tryParse(valor);
    return edad != null && edad >= 18 && edad <= 120;
  }

  static bool validarCelular(String? valor) {
    if (valor == null || valor.isEmpty) return false;
    final clean = obtenerSoloNumeros(valor);
    return clean.length == 8;
  }

  static bool validarCorreo(String? valor) {
    if (valor == null || valor.isEmpty) return false;
    final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
    return emailRegex.hasMatch(valor);
  }

  static String obtenerSoloNumeros(String valor) {
    return valor.replaceAll(RegExp(r'\D'), '');
  }
}
