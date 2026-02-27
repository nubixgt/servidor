import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utilidades/colores.dart';
import '../modelos/clinica.dart';
import '../servicios/api/cliente.dart';
import 'pantalla_detalle_servicio.dart';

class PantallaServicios extends StatefulWidget {
  const PantallaServicios({super.key});

  @override
  State<PantallaServicios> createState() => _PantallaServiciosState();
}

class _PantallaServiciosState extends State<PantallaServicios> {
  List<ServicioAutorizado> servicios = [];
  List<ServicioAutorizado> serviciosFiltrados = [];
  bool isLoading = true;
  String? errorMessage;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _cargarServicios();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _cargarServicios() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      final serviciosObtenidos = await obtenerServicios();
      setState(() {
        servicios = serviciosObtenidos;
        serviciosFiltrados = serviciosObtenidos;
        isLoading = false;
      });
    } catch (e) {
      setState(() {
        errorMessage = 'Error al cargar servicios: $e';
        isLoading = false;
      });
    }
  }

  void _filtrarServicios(String query) {
    setState(() {
      if (query.isEmpty) {
        serviciosFiltrados = servicios;
      } else {
        serviciosFiltrados = servicios.where((servicio) {
          final nombreLower = servicio.nombreServicio.toLowerCase();
          final direccionLower = servicio.direccion.toLowerCase();
          final serviciosLower = servicio.serviciosOfrecidos.toLowerCase();
          final queryLower = query.toLowerCase();

          return nombreLower.contains(queryLower) ||
              direccionLower.contains(queryLower) ||
              serviciosLower.contains(queryLower);
        }).toList();
      }
    });
  }

  Future<void> _llamarTelefono(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('No se pudo abrir el marcador telefónico'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _abrirUbicacion(String? url) async {
    if (url == null) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Este servicio no tiene ubicación registrada'),
            backgroundColor: Colors.orange,
          ),
        );
      }
      return;
    }

    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('No se pudo abrir Google Maps'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      body: SafeArea(
        child: Column(
          children: [
            // Header
            Container(
              color: AppColores.verdePrimario,
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      IconButton(
                        icon: const Icon(Icons.arrow_back, color: Colors.white),
                        onPressed: () => Navigator.pop(context),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Servicios Autorizados',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                            Text(
                              'Clínicas veterinarias registradas',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 12,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Buscador
            Padding(
              padding: const EdgeInsets.all(16),
              child: TextField(
                controller: _searchController,
                onChanged: _filtrarServicios,
                decoration: InputDecoration(
                  hintText: 'Buscar clínica o veterinaria...',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchController.clear();
                            _filtrarServicios('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 12,
                  ),
                ),
              ),
            ),

            // Contenido
            Expanded(
              child: _buildContenido(),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContenido() {
    if (isLoading) {
      return const Center(
        child: CircularProgressIndicator(),
      );
    }

    if (errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.error_outline,
                size: 64,
                color: Colors.red,
              ),
              const SizedBox(height: 16),
              Text(
                errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 16),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _cargarServicios,
                icon: const Icon(Icons.refresh),
                label: const Text('Reintentar'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColores.verdePrimario,
                  foregroundColor: Colors.white,
                ),
              ),
            ],
          ),
        ),
      );
    }

    if (serviciosFiltrados.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(
                Icons.local_hospital_outlined,
                size: 64,
                color: Colors.grey,
              ),
              const SizedBox(height: 16),
              Text(
                _searchController.text.isNotEmpty
                    ? 'No se encontraron servicios'
                    : 'No hay servicios disponibles',
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 16, color: Colors.grey),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _cargarServicios,
      child: ListView.builder(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: serviciosFiltrados.length,
        itemBuilder: (context, index) {
          final servicio = serviciosFiltrados[index];
          return Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: _construirTarjetaClinica(servicio),
          );
        },
      ),
    );
  }

  Widget _construirTarjetaClinica(ServicioAutorizado servicio) {
    return InkWell(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => PantallaDetalleServicio(servicio: servicio),
          ),
        );
      },
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.05),
              blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Imagen si existe
          if (servicio.imagenUrl != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(
                  servicio.imagenUrl!,
                  width: double.infinity,
                  height: 150,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) {
                    return Container(
                      width: double.infinity,
                      height: 150,
                      decoration: BoxDecoration(
                        color: Colors.grey.shade200,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        Icons.local_hospital,
                        size: 48,
                        color: Colors.grey.shade400,
                      ),
                    );
                  },
                ),
              ),
            ),

          // Nombre y calificación
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Text(
                  servicio.nombreServicio,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1F2937),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.yellow.shade100,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Text(
                      '⭐',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.yellow.shade700,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      servicio.calificacionFormateada,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: Colors.yellow.shade800,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Dirección
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.location_on, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  servicio.direccion,
                  style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),

          // Teléfono
          Row(
            children: [
              Icon(Icons.phone, size: 16, color: Colors.grey.shade600),
              const SizedBox(width: 8),
              Text(
                servicio.telefono,
                style: TextStyle(fontSize: 14, color: Colors.grey.shade600),
              ),
            ],
          ),
          const SizedBox(height: 12),

          // Servicios
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: Colors.grey.shade50,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              servicio.serviciosOfrecidos,
              style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
            ),
          ),
          const SizedBox(height: 12),

          // Botones
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () => _llamarTelefono(servicio.urlTelefono),
                  icon: const Icon(Icons.phone, size: 16),
                  label: const Text('Llamar'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColores.verdePrimario,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () => _abrirUbicacion(servicio.urlGoogleMaps),
                  icon: const Icon(Icons.location_on, size: 16),
                  label: const Text('Ubicación'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColores.verdePrimario,
                    side: BorderSide(color: AppColores.verdePrimario, width: 2),
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                ),
              ),
            ],
          ),
          ],
        ),
      ),
    );
  }
}
