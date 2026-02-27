import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';

/// Servicio mínimo para cámara y selección de archivos.
class ServiciosMultimedia {
  final ImagePicker _picker = ImagePicker();

  /// Toma una foto desde la cámara.
  Future<XFile?> tomarFotoCamara({int imageQuality = 90}) {
    return _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: imageQuality,
    );
  }

  /// Abre un selector del sistema para elegir archivos (cualquier tipo).
  /// Puedes limitar cantidad y tamaño por archivo (MB).
  Future<List<PlatformFile>> seleccionarArchivos({
    int? maxCantidad,
    int? sizeMaxMB,
  }) async {
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.any,
      withData: false, // preferimos path real en disco
    );
    if (result == null) return [];

    var archivos = result.files;

    if (sizeMaxMB != null) {
      final maxBytes = sizeMaxMB * 1024 * 1024;
      archivos = archivos.where((f) => f.size <= maxBytes).toList();
    }

    if (maxCantidad != null && archivos.length > maxCantidad) {
      archivos = archivos.take(maxCantidad).toList();
    }

    return archivos;
  }
}
