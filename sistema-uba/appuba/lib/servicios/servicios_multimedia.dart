import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';

class ServiciosMultimedia {
  final ImagePicker _picker = ImagePicker();

  Future<XFile?> tomarFotoCamara({int imageQuality = 80}) async {
    return await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: imageQuality,
    );
  }

  Future<List<PlatformFile>> seleccionarArchivos({
    int maxCantidad = 1,
    int sizeMaxMB = 20,
  }) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      allowMultiple: maxCantidad > 1,
      type: FileType.any,
    );

    if (result != null) {
      return result.files.where((f) => (f.size / (1024 * 1024)) <= sizeMaxMB).toList();
    }
    return [];
  }
}
