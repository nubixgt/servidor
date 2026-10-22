import '../models/dano_otros.dart';
import 'api_client.dart';

/// Servicio genérico de los módulos Pecuario e Infraestructura (misma API que cultivos, sin fotos).
class DanoModuloService<T extends DanoModulo> {
  final String path;
  final T Function(Map<String, dynamic>) _fromJson;
  DanoModuloService(this.path, this._fromJson);

  final _api = ApiClient.instance;

  Future<List<T>> list() async {
    final data = await _api.get(path);
    return ((data['danos'] as List?) ?? []).map((e) => _fromJson(Map<String, dynamic>.from(e))).toList();
  }

  Future<T> create(Map<String, dynamic> registro) async {
    final data = await _api.post(path, registro);
    return _fromJson(Map<String, dynamic>.from(data['dano']));
  }

  Future<void> remove(int id) async {
    await _api.delete('$path/$id');
  }
}

final pecuarioService = DanoModuloService<DanoPecuario>('/danos-pecuarios', DanoPecuario.fromJson);
final infraestructuraService = DanoModuloService<DanoInfraestructura>('/danos-infraestructura', DanoInfraestructura.fromJson);
