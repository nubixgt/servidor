/// Error de negocio o de red al hablar con el Backend.
/// El mensaje ya viene listo para mostrarse tal cual al usuario.
class ApiException implements Exception {
  const ApiException(this.message);

  final String message;

  @override
  String toString() => message;
}
