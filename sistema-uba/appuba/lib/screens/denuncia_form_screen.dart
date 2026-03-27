import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'package:open_filex/open_filex.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../servicios/servicios_multimedia.dart';
import '../theme/app_theme.dart';
import '../utilidades/formateadores_texto.dart';
import '../utilidades/datos_guatemala.dart';
import '../utilidades/validadores.dart';
import '../widgets/selector_ubicacion.dart';
import '../servicios/api_service.dart';

enum TipoFoto { dpi, fachada, evidencia }

class DenunciaFormScreen extends StatefulWidget {
  const DenunciaFormScreen({super.key});

  @override
  State<DenunciaFormScreen> createState() => _DenunciaFormScreenState();
}

class _DenunciaFormScreenState extends State<DenunciaFormScreen> {
  int pasoActual = 1;
  final int totalPasos = 4;
  bool _enviando = false;
  final ApiService _apiService = ApiService();

  final _nombreController = TextEditingController();
  final _dpiController = TextEditingController();
  final _edadController = TextEditingController();
  final _celularController = TextEditingController();
  final _correoController = TextEditingController();

  String tipoPersona = 'Individual';
  String? generoSeleccionado;

  String? departamentoSeleccionado;
  String? municipioSeleccionado;
  List<String> municipiosDisponibles = [];

  double? _latitud;
  double? _longitud;

  final _responsableCtrl = TextEditingController();
  final _direccionCtrl = TextEditingController();
  final _colorCasaCtrl = TextEditingController();
  final _colorPuertaCtrl = TextEditingController();

  String _especieSeleccionada = 'Caninos';
  final TextEditingController _especieOtrosCtrl = TextEditingController();

  final _cantidadCtrl = TextEditingController();
  final _razaCtrl = TextEditingController();
  final _descripcionCtrl = TextEditingController();

  static const List<String> _catalogoInfracciones = [
    'Actos de Crueldad',
    'Abandono',
    'No garantizar condiciones de bienestar',
    'Maltrato físico',
    'Mutilaciones',
    'Envenenar o intoxicar a un animal',
    'Peleas de perros',
    'Técnicas de adiestramiento que causen sufrimiento',
    'Otros',
  ];
  final Set<String> _infraccionesSeleccionadas = {};
  final TextEditingController _otrosInfraccionController = TextEditingController();

  bool _aceptaDeclaracion = false;

  final ServiciosMultimedia _media = ServiciosMultimedia();

  final int _maxFotosDpi = 2;
  final int _maxFotosEvidencia = 5;
  final int _maxArchivosEvidencia = 5;
  final int _pesoMaxArchivoMB = 20;
  List<PlatformFile> _archivosEvidencia = [];

  List<XFile> _fotosDpi = [];
  XFile? _fotoFachada;
  List<XFile> _fotosEvidencia = [];

  @override
  void dispose() {
    _nombreController.dispose();
    _dpiController.dispose();
    _edadController.dispose();
    _celularController.dispose();
    _correoController.dispose();
    _responsableCtrl.dispose();
    _direccionCtrl.dispose();
    _colorCasaCtrl.dispose();
    _colorPuertaCtrl.dispose();
    _cantidadCtrl.dispose();
    _razaCtrl.dispose();
    _descripcionCtrl.dispose();
    _otrosInfraccionController.dispose();
    _especieOtrosCtrl.dispose();
    super.dispose();
  }


  Future<void> _subirImagen(TipoFoto destino) async {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (context) => Container(
        padding: const EdgeInsets.symmetric(vertical: 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Seleccionar Medio', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildActionOption(Icons.camera_alt_outlined, 'Cámara', () async {
                  Navigator.pop(context);
                  await _procesarCaptura(destino, source: ImageSource.camera);
                }),
                _buildActionOption(Icons.photo_library_outlined, 'Galería', () async {
                  Navigator.pop(context);
                  await _procesarCaptura(destino, source: ImageSource.gallery);
                }),
              ],
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildActionOption(IconData icon, String label, VoidCallback onTap) {
    return InkWell(
      onTap: onTap,
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: AppColors.primary.withOpacity(0.1), shape: BoxShape.circle),
            child: Icon(icon, color: AppColors.primary, size: 32),
          ),
          const SizedBox(height: 8),
          Text(label, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }

  Future<void> _procesarCaptura(TipoFoto destino, {required ImageSource source}) async {
    try {
      final XFile? foto = source == ImageSource.camera 
          ? await _media.tomarFotoCamara(imageQuality: 50) 
          : await _media.seleccionarDeGaleria(imageQuality: 50);

      if (foto == null) return;

      setState(() {
        switch (destino) {
          case TipoFoto.dpi:
            if (_fotosDpi.length < _maxFotosDpi) _fotosDpi.add(foto);
            break;
          case TipoFoto.fachada:
            _fotoFachada = foto;
            break;
          case TipoFoto.evidencia:
            if (_fotosEvidencia.length < _maxFotosEvidencia) _fotosEvidencia.add(foto);
            break;
        }
      });
    } catch (e) {
      _toast(source == ImageSource.camera && e.toString().contains('not available')
          ? 'Cámara no disponible en este dispositivo (Simulador)'
          : 'Error al capturar medio: $e');
    }
  }

  void _toast(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        backgroundColor: AppColors.primary,
      ),
    );
  }

  bool _validarPaso(int paso) {
    if (paso == 1) {
      if (_nombreController.text.isEmpty) { _toast('Ingresa tu nombre'); return false; }
      String dpiLimpio = _dpiController.text.replaceAll(' ', '');
      if (dpiLimpio.length != 13) { _toast('DPI debe tener 13 dígitos'); return false; }
      if (_fotosDpi.length < 2) { _toast('Captura ambos lados del DPI'); return false; }
    }
    if (paso == 2 && _fotoFachada == null) { _toast('Captura la foto de la fachada'); return false; }
    if (paso == 3 && _descripcionCtrl.text.length < 10) { _toast('Describe mejor el caso'); return false; }
    if (paso == 4 && !_aceptaDeclaracion) { _toast('Debes aceptar la declaración jurada'); return false; }
    return true;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.surface,
      appBar: _buildAppBar(),
      body: Column(
        children: [
          _buildProgressHeader(),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
              child: Column(
                children: [
                  _buildCurrentStep(),
                  const SizedBox(height: 100), // Espacio para botones
                ],
              ),
            ),
          ),
        ],
      ),
      bottomSheet: _buildBottomNav(),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      elevation: 0,
      title: Text(
        'Nueva Denuncia',
        style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800),
      ),
      leading: IconButton(
        icon: const Icon(Icons.close),
        onPressed: () => Navigator.pop(context),
      ),
    );
  }

  Widget _buildProgressHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      decoration: BoxDecoration(
        color: AppColors.primary,
        borderRadius: const BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: List.generate(totalPasos, (i) => _buildStepIndicator(i + 1)),
          ),
          const SizedBox(height: 16),
          Text(
            _getStepTitle(),
            style: GoogleFonts.plusJakartaSans(
              color: Colors.white,
              fontSize: 18,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int step) {
    bool isCompleted = pasoActual > step;
    bool isActive = pasoActual == step;

    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: isActive ? Colors.white : (isCompleted ? Colors.white24 : Colors.transparent),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white38),
      ),
      child: Center(
        child: isCompleted
            ? const Icon(Icons.check, color: Colors.white, size: 20)
            : Text(
                '$step',
                style: TextStyle(
                  color: isActive ? AppColors.primary : Colors.white60,
                  fontWeight: FontWeight.bold,
                ),
              ),
      ),
    );
  }

  String _getStepTitle() {
    switch (pasoActual) {
      case 1: return 'Datos del Denunciante';
      case 2: return 'Ubicación del Hecho';
      case 3: return 'Detalles del Caso';
      case 4: return 'Evidencias y Envío';
      default: return '';
    }
  }

  Widget _buildCurrentStep() {
    switch (pasoActual) {
      case 1: return _step1();
      case 2: return _step2();
      case 3: return _step3();
      case 4: return _step4();
      default: return const SizedBox.shrink();
    }
  }

  Widget _step1() {
    return Column(
      children: [
        _buildInputCard(
          title: 'Información Personal',
          icon: Icons.person_outline,
          children: [
            _buildField('Nombre Completo', _nombreController, Icons.badge_outlined),
            const SizedBox(height: 20),
            _buildField('Número de DPI', _dpiController, Icons.fingerprint, 
              formatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(13), FormateadorDPI()]),
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(child: _buildField('Edad', _edadController, Icons.cake_outlined, keyboardType: TextInputType.number)),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Género', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.onSurfaceVariant)),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String>(
                        value: generoSeleccionado,
                        decoration: _inputDecoration(null),
                        items: ['Masculino', 'Femenino'].map((g) => DropdownMenuItem(value: g, child: Text(g))).toList(),
                        onChanged: (v) => setState(() => generoSeleccionado = v),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ],
        ),
        const SizedBox(height: 24),
        _buildInputCard(
          title: 'Contacto',
          icon: Icons.contact_phone_outlined,
          children: [
            _buildField('Celular', _celularController, Icons.phone_android,
              formatters: [FilteringTextInputFormatter.digitsOnly, LengthLimitingTextInputFormatter(8), FormateadorCelular()]),
            const SizedBox(height: 20),
            _buildField('Correo Electrónico', _correoController, Icons.email_outlined),
          ],
        ),
        const SizedBox(height: 24),
        _buildMediaCaptureCard(
          title: 'Fotos de DPI',
          subtitle: 'Captura el frente y reverso de tu documento',
          icon: Icons.camera_front,
          files: _fotosDpi,
          onTap: () => _subirImagen(TipoFoto.dpi),
          onDelete: (i) => setState(() => _fotosDpi.removeAt(i)),
          max: 2,
        ),
      ],
    );
  }

  Widget _step2() {
    return Column(
      children: [
        _buildInputCard(
          title: 'Dirección del Suceso',
          icon: Icons.location_on_outlined,
          children: [
            _buildField('Dirección Exacta', _direccionCtrl, Icons.map_outlined),
            const SizedBox(height: 20),
            DropdownButtonFormField<String>(
              value: departamentoSeleccionado,
              decoration: _inputDecoration('Departamento'),
              items: DatosGuatemala.departamentos.map((d) => DropdownMenuItem(value: d, child: Text(d))).toList(),
              onChanged: (v) => setState(() {
                departamentoSeleccionado = v;
                municipioSeleccionado = null;
                municipiosDisponibles = DatosGuatemala.obtenerMunicipios(v!);
              }),
            ),
            if (departamentoSeleccionado != null) ...[
              const SizedBox(height: 20),
              DropdownButtonFormField<String>(
                value: municipioSeleccionado,
                decoration: _inputDecoration('Municipio'),
                items: municipiosDisponibles.map((m) => DropdownMenuItem(value: m, child: Text(m))).toList(),
                onChanged: (v) => setState(() => municipioSeleccionado = v),
              ),
            ],
          ],
        ),
        const SizedBox(height: 24),
        _buildMapSelectionCard(),
        const SizedBox(height: 24),
        _buildMediaCaptureCard(
          title: 'Foto de la Fachada',
          subtitle: 'Captura el lugar donde ocurrió el hecho',
          icon: Icons.home_outlined,
          files: _fotoFachada != null ? [_fotoFachada!] : [],
          onTap: () => _subirImagen(TipoFoto.fachada),
          onDelete: (_) => setState(() => _fotoFachada = null),
          max: 1,
        ),
      ],
    );
  }

  Widget _step3() {
    return Column(
      children: [
        _buildInputCard(
          title: 'Sobre el Animal',
          icon: Icons.pets_outlined,
          children: [
            const Text('Especie', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.onSurfaceVariant)),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              children: ['Caninos', 'Felinos', 'Equinos', 'Otros'].map((e) {
                bool selected = _especieSeleccionada == e;
                return ChoiceChip(
                  label: Text(e),
                  selected: selected,
                  selectedColor: AppColors.primary,
                  labelStyle: TextStyle(color: selected ? Colors.white : AppColors.primary, fontWeight: FontWeight.bold),
                  onSelected: (s) => setState(() => _especieSeleccionada = e),
                );
              }).toList(),
            ),
            const SizedBox(height: 20),
            _buildField('Cantidad de Animales', _cantidadCtrl, Icons.numbers, keyboardType: TextInputType.number),
            const SizedBox(height: 20),
            _buildField('Raza del Animal', _razaCtrl, Icons.pets_outlined),
            if (_especieSeleccionada == 'Otros') ...[
              const SizedBox(height: 16),
              _buildField('Especificar Otra Especie', _especieOtrosCtrl, Icons.edit_note),
            ],
            const SizedBox(height: 20),
            _buildField('Descripción de lo Ocurrido', _descripcionCtrl, Icons.description_outlined, maxLines: 4),
          ],
        ),
        const SizedBox(height: 24),
        _buildInputCard(
          title: 'Tipo de Infracción',
          icon: Icons.warning_amber_outlined,
          children: [
            ..._catalogoInfracciones.map((i) => CheckboxListTile(
              title: Text(i, style: const TextStyle(fontSize: 14)),
              value: _infraccionesSeleccionadas.contains(i),
              contentPadding: EdgeInsets.zero,
              activeColor: AppColors.primary,
              dense: true,
              onChanged: (v) => setState(() => v! ? _infraccionesSeleccionadas.add(i) : _infraccionesSeleccionadas.remove(i)),
            )),
            if (_infraccionesSeleccionadas.contains('Otros')) ...[
              const Divider(height: 24),
              _buildField('Especificar Otras Infracciones', _otrosInfraccionController, Icons.edit_note),
            ],
          ],
        ),
      ],
    );
  }

  Widget _step4() {
    return Column(
      children: [
        _buildMediaCaptureCard(
          title: 'Evidencias Fotográficas',
          subtitle: 'Agrega fotos del estado del animal o el entorno',
          icon: Icons.photo_library_outlined,
          files: _fotosEvidencia,
          onTap: () => _subirImagen(TipoFoto.evidencia),
          onDelete: (i) => setState(() => _fotosEvidencia.removeAt(i)),
          max: 5,
        ),
        const SizedBox(height: 24),
        _buildMediaCaptureCard(
          title: 'Documentos Adjuntos',
          subtitle: 'Agrega cualquier documento legal o médico relevante',
          icon: Icons.file_present_outlined,
          isFiles: true,
          platformFiles: _archivosEvidencia,
          onTap: () async {
            final f = await _media.seleccionarArchivos(maxCantidad: 5 - _archivosEvidencia.length);
            if (f.isNotEmpty) setState(() => _archivosEvidencia.addAll(f));
          },
          onDelete: (i) => setState(() => _archivosEvidencia.removeAt(i)),
          max: 5,
        ),
        const SizedBox(height: 32),
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            color: Colors.blue.shade50,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: Colors.blue.shade100),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Checkbox(
                value: _aceptaDeclaracion,
                activeColor: AppColors.primary,
                onChanged: (v) => setState(() => _aceptaDeclaracion = v!),
              ),
              const Expanded(
                child: Text(
                  'Declaro bajo juramento que toda la información proporcionada es verdadera y tengo conocimiento de las sanciones legales en caso de perjurio.',
                  style: TextStyle(fontSize: 12, height: 1.5, color: AppColors.primary),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildField(String label, TextEditingController ctrl, IconData icon, {TextInputType keyboardType = TextInputType.text, List<TextInputFormatter>? formatters, int maxLines = 1}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: AppColors.onSurfaceVariant)),
        const SizedBox(height: 8),
        TextField(
          controller: ctrl,
          keyboardType: keyboardType,
          inputFormatters: formatters,
          maxLines: maxLines,
          decoration: _inputDecoration(null, icon: icon),
        ),
      ],
    );
  }

  InputDecoration _inputDecoration(String? hint, {IconData? icon}) {
    return InputDecoration(
      hintText: hint,
      prefixIcon: icon != null ? Icon(icon, color: AppColors.primary.withOpacity(0.5), size: 20) : null,
      filled: true,
      fillColor: Colors.white,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: AppColors.outlineVariant.withOpacity(0.3))),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide(color: AppColors.outlineVariant.withOpacity(0.3))),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: AppColors.primary, width: 2)),
    );
  }

  Widget _buildInputCard({required String title, required IconData icon, required List<Widget> children}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 20, offset: const Offset(0, 10))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: AppColors.primary, size: 24),
              const SizedBox(width: 12),
              Text(title, style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800, fontSize: 16, color: AppColors.primary)),
            ],
          ),
          const Divider(height: 48, thickness: 1),
          ...children,
        ],
      ),
    );
  }

  Widget _buildMediaCaptureCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required VoidCallback onTap,
    required Function(int) onDelete,
    List<XFile>? files,
    List<PlatformFile>? platformFiles,
    bool isFiles = false,
    int max = 5,
  }) {
    int count = isFiles ? platformFiles!.length : files!.length;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 20, offset: const Offset(0, 10))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, color: AppColors.primary, size: 24),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800, fontSize: 16, color: AppColors.primary)),
                    Text(subtitle, style: const TextStyle(fontSize: 12, color: AppColors.onSurfaceVariant)),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          if (count < max)
            InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(16),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 32),
                decoration: BoxDecoration(
                  color: AppColors.primary.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.primary.withOpacity(0.1), style: BorderStyle.solid),
                ),
                child: Column(
                  children: [
                    Icon(Icons.add_a_photo_outlined, color: AppColors.primary, size: 32),
                    const SizedBox(height: 8),
                    Text('Subir evidencia ($count/$max)', style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary)),
                  ],
                ),
              ),
            ),
          if (count > 0) ...[
            const SizedBox(height: 16),
            if (!isFiles)
              SizedBox(
                height: 100,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  itemCount: count,
                  itemBuilder: (context, i) => _buildImageThumb(files![i], () => onDelete(i)),
                ),
              )
            else
              Column(
                children: platformFiles!.asMap().entries.map((e) => _buildFileItem(e.value, () => onDelete(e.key))).toList(),
              ),
          ],
        ],
      ),
    );
  }

  Widget _buildImageThumb(XFile file, VoidCallback onDelete) {
    return Container(
      margin: const EdgeInsets.only(right: 12),
      width: 100,
      height: 100,
      child: Stack(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Image.file(File(file.path), fit: BoxFit.cover, width: 100, height: 100),
          ),
          Positioned(
            top: 4,
            right: 4,
            child: GestureDetector(
              onTap: onDelete,
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: const BoxDecoration(color: Colors.red, shape: BoxShape.circle),
                child: const Icon(Icons.close, color: Colors.white, size: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFileItem(PlatformFile file, VoidCallback onDelete) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surfaceContainerLow,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          const Icon(Icons.insert_drive_file, color: AppColors.primary, size: 20),
          const SizedBox(width: 12),
          Expanded(child: Text(file.name, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold), overflow: TextOverflow.ellipsis)),
          IconButton(icon: const Icon(Icons.delete_outline, color: Colors.red, size: 20), onPressed: onDelete),
        ],
      ),
    );
  }

  Widget _buildMapSelectionCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.03), blurRadius: 20, offset: const Offset(0, 10))],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const Icon(Icons.map_outlined, color: AppColors.primary, size: 24),
              const SizedBox(width: 12),
              Text('Punto en el Mapa', style: GoogleFonts.plusJakartaSans(fontWeight: FontWeight.w800, fontSize: 16)),
            ],
          ),
          const SizedBox(height: 24),
          InkWell(
            onTap: _latitud == null ? () async {
              await Navigator.push(context, MaterialPageRoute(builder: (_) => SelectorUbicacion(onUbicacionSeleccionada: (lat, lng) => setState(() { _latitud = lat; _longitud = lng; }))));
            } : null,
            borderRadius: BorderRadius.circular(24),
            child: Container(
              height: 200,
              width: double.infinity,
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: Colors.blue.shade100),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(24),
                child: _latitud != null 
                  ? Stack(
                      children: [
                        GoogleMap(
                          initialCameraPosition: CameraPosition(target: LatLng(_latitud!, _longitud!), zoom: 15),
                          markers: {
                            Marker(markerId: const MarkerId('selected'), position: LatLng(_latitud!, _longitud!)),
                          },
                          zoomControlsEnabled: false,
                          myLocationButtonEnabled: false,
                          scrollGesturesEnabled: false,
                          rotateGesturesEnabled: false,
                          tiltGesturesEnabled: false,
                          zoomGesturesEnabled: false,
                        ),
                        Container(color: Colors.transparent), // Overlay para evitar interacciones accidentales
                        Positioned(
                          bottom: 12,
                          right: 12,
                          child: FilledButton.icon(
                            onPressed: () async {
                              await Navigator.push(context, MaterialPageRoute(builder: (_) => SelectorUbicacion(
                                latitudInicial: _latitud,
                                longitudInicial: _longitud,
                                onUbicacionSeleccionada: (lat, lng) => setState(() { _latitud = lat; _longitud = lng; })
                              )));
                            },
                            icon: const Icon(Icons.edit_location_alt_outlined, size: 18),
                            label: const Text('Cambiar Ubicación'),
                            style: FilledButton.styleFrom(
                              backgroundColor: Colors.white,
                              foregroundColor: AppColors.primary,
                              elevation: 4,
                            ),
                          ),
                        ),
                      ],
                    )
                  : Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.map_outlined, color: AppColors.primary, size: 48),
                          const SizedBox(height: 12),
                          const Text('Seleccionar ubicación GPS', style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary)),
                          const SizedBox(height: 4),
                          const Text('Toca para abrir el mapa', style: TextStyle(fontSize: 12, color: AppColors.onSurfaceVariant)),
                        ],
                      ),
                    ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 20, offset: const Offset(0, -10))],
      ),
      child: Row(
        children: [
          if (pasoActual > 1) ...[
            Expanded(
              child: OutlinedButton(
                onPressed: () => setState(() => pasoActual--),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: const Text('Anterior', style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ),
            const SizedBox(width: 16),
          ],
          Expanded(
            flex: 2,
            child: FilledButton(
              onPressed: _enviando ? null : () {
                if (_validarPaso(pasoActual)) {
                  if (pasoActual < totalPasos) {
                    setState(() => pasoActual++);
                  } else {
                    _enviarAlBackend();
                  }
                }
              },
              style: FilledButton.styleFrom(
                backgroundColor: AppColors.primary,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              child: _enviando 
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : Text(pasoActual == totalPasos ? 'Enviar Denuncia' : 'Siguiente', style: const TextStyle(fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }
  Future<void> _enviarAlBackend() async {
    setState(() => _enviando = true);

    try {
      final Map<String, String> data = {
        'nombre_denunciante': _nombreController.text,
        'dpi_denunciante': _dpiController.text.replaceAll(' ', ''),
        'edad_denunciante': _edadController.text,
        'genero_denunciante': generoSeleccionado ?? '',
        'celular_denunciante': _celularController.text.replaceAll(' ', ''),
        'correo_denunciante': _correoController.text,
        'direccion_hecho': _direccionCtrl.text,
        'departamento_hecho': departamentoSeleccionado ?? '',
        'municipio_hecho': municipioSeleccionado ?? '',
        'latitud': _latitud?.toString() ?? '',
        'longitud': _longitud?.toString() ?? '',
        'especie': _especieSeleccionada,
        'especie_otros': _especieSeleccionada == 'Otros' ? _especieOtrosCtrl.text : '',
        'cantidad_animales': _cantidadCtrl.text,
        'raza': _razaCtrl.text,
        'descripcion': _descripcionCtrl.text,
        'infracciones': _infraccionesSeleccionadas.join(','),
        'infracciones_otros': _infraccionesSeleccionadas.contains('Otros') ? _otrosInfraccionController.text : '',
        'acepto_declaracion': _aceptaDeclaracion ? '1' : '0',
      };

      final success = await _apiService.enviarDenuncia(
        data: data,
        fotosDpi: _fotosDpi,
        fotoFachada: _fotoFachada,
        fotosEvidencia: _fotosEvidencia,
        archivosEvidencia: _archivosEvidencia,
      );

      if (mounted) {
        if (success) {
          _toast('✅ Denuncia enviada exitosamente');
          Navigator.pop(context);
        } else {
          _toast('❌ Error al enviar la denuncia. Inténtalo de nuevo.');
        }
      }
    } catch (e) {
      _toast('❌ Error de conexión: $e');
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }
}

