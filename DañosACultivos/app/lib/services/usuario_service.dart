import '../models/usuario.dart';
import 'api_client.dart';

class UsuarioService {
  final _api = ApiClient.instance;

  Future<List<Usuario>> list() async {
    final data = await _api.get('/usuarios');
    return ((data['usuarios'] as List?) ?? []).map((e) => Usuario.fromJson(Map<String, dynamic>.from(e))).toList();
  }

  Future<void> create(Map<String, dynamic> datos) async {
    await _api.post('/usuarios', datos);
  }

  Future<void> update(int id, Map<String, dynamic> datos) async {
    await _api.put('/usuarios/$id', datos);
  }

  Future<void> remove(int id) async {
    await _api.delete('/usuarios/$id');
  }
}
