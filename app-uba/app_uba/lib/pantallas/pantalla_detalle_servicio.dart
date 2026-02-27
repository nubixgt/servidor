import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../utilidades/colores.dart';
import '../modelos/clinica.dart';
import '../servicios/api/cliente.dart';

class PantallaDetalleServicio extends StatefulWidget {
  final ServicioAutorizado servicio;

  const PantallaDetalleServicio({
    super.key,
    required this.servicio,
  });

  @override
  State<PantallaDetalleServicio> createState() =>
      _PantallaDetalleServicioState();
}

class _PantallaDetalleServicioState extends State<PantallaDetalleServicio> {
  late double calificacionActual;
  late int totalCalificaciones;
  int calificacionUsuario = 0;
  bool enviandoCalificacion = false;

  @override
  void initState() {
    super.initState();
    calificacionActual = widget.servicio.calificacion;
    totalCalificaciones = widget.servicio.totalCalificaciones;
  }

  Future<void> _enviarCalificacion() async {
    if (calificacionUsuario == 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Por favor selecciona una calificación'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    setState(() {
      enviandoCalificacion = true;
    });

    try {
      final resultado = await calificarServicio(
        idServicio: widget.servicio.idServicio,
        calificacion: calificacionUsuario.toDouble(),
      );

      setState(() {
        calificacionActual = (resultado['nueva_calificacion'] as num).toDouble();
        totalCalificaciones = (resultado['total_calificaciones'] as num).toInt();
        calificacionUsuario = 0;
        enviandoCalificacion = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('¡Gracias por tu calificación!'),
            backgroundColor: Colors.green,
            duration: Duration(seconds: 2),
          ),
        );
      }
    } catch (e) {
      setState(() {
        enviandoCalificacion = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error al enviar calificación: $e'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  Future<void> _llamarTelefono() async {
    final uri = Uri.parse(widget.servicio.urlTelefono);
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

  Future<void> _abrirUbicacion() async {
    final url = widget.servicio.urlGoogleMaps;
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
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back, color: Colors.white),
                    onPressed: () => Navigator.pop(context),
                  ),
                  const SizedBox(width: 12),
                  const Expanded(
                    child: Text(
                      'Detalle del Servicio',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Contenido
            Expanded(
              child: SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Imagen
                    if (widget.servicio.imagenUrl != null)
                      Image.network(
                        widget.servicio.imagenUrl!,
                        width: double.infinity,
                        height: 240,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) {
                          return _buildImagenPlaceholder();
                        },
                      )
                    else
                      _buildImagenPlaceholder(),

                    // Información del servicio
                    Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Nombre
                          Text(
                            widget.servicio.nombreServicio,
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1F2937),
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Calificación actual
                          _buildCalificacionActual(),
                          const SizedBox(height: 20),

                          // Divider
                          Divider(
                            color: Colors.grey.shade300,
                            thickness: 1,
                          ),
                          const SizedBox(height: 20),

                          // Dirección
                          _buildInfoRow(
                            Icons.location_on,
                            'Dirección',
                            widget.servicio.direccion,
                          ),
                          const SizedBox(height: 16),

                          // Teléfono
                          _buildInfoRow(
                            Icons.phone,
                            'Teléfono',
                            widget.servicio.telefono,
                          ),
                          const SizedBox(height: 20),

                          // Servicios ofrecidos
                          const Text(
                            'Servicios Ofrecidos',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1F2937),
                            ),
                          ),
                          const SizedBox(height: 8),
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: Colors.green.shade50,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: Colors.green.shade200,
                                width: 1,
                              ),
                            ),
                            child: Text(
                              widget.servicio.serviciosOfrecidos,
                              style: TextStyle(
                                fontSize: 15,
                                color: Colors.grey.shade800,
                                height: 1.5,
                              ),
                            ),
                          ),
                          const SizedBox(height: 24),

                          // Botones de acción
                          Row(
                            children: [
                              Expanded(
                                child: ElevatedButton.icon(
                                  onPressed: _llamarTelefono,
                                  icon: const Icon(Icons.phone, size: 20),
                                  label: const Text('Llamar'),
                                  style: ElevatedButton.styleFrom(
                                    backgroundColor: AppColores.verdePrimario,
                                    foregroundColor: Colors.white,
                                    padding: const EdgeInsets.symmetric(
                                        vertical: 14),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: OutlinedButton.icon(
                                  onPressed: _abrirUbicacion,
                                  icon: const Icon(Icons.location_on, size: 20),
                                  label: const Text('Ubicación'),
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: AppColores.verdePrimario,
                                    side: BorderSide(
                                      color: AppColores.verdePrimario,
                                      width: 2,
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                        vertical: 14),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 32),

                          // Sección de calificación
                          _buildSeccionCalificacion(),
                          const SizedBox(height: 32),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildImagenPlaceholder() {
    return Container(
      width: double.infinity,
      height: 240,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [Colors.green.shade100, Colors.green.shade200],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Icon(
        Icons.local_hospital,
        size: 80,
        color: Colors.green.shade400,
      ),
    );
  }

  Widget _buildCalificacionActual() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.yellow.shade50,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: Colors.yellow.shade200,
          width: 1,
        ),
      ),
      child: Row(
        children: [
          // Estrellas
          Row(
            children: List.generate(5, (index) {
              return Icon(
                index < calificacionActual.round()
                    ? Icons.star
                    : Icons.star_border,
                color: Colors.amber.shade600,
                size: 28,
              );
            }),
          ),
          const SizedBox(width: 12),
          // Número
          Text(
            calificacionActual.toStringAsFixed(1),
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Colors.amber.shade800,
            ),
          ),
          const SizedBox(width: 8),
          // Total
          Text(
            '($totalCalificaciones)',
            style: TextStyle(
              fontSize: 14,
              color: Colors.grey.shade600,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 20, color: AppColores.verdePrimario),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: Colors.grey.shade600,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 16,
                  color: Color(0xFF1F2937),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildSeccionCalificacion() {
    return Container(
      padding: const EdgeInsets.all(20),
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
          const Text(
            '¿Cómo calificarías este servicio?',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Color(0xFF1F2937),
            ),
          ),
          const SizedBox(height: 16),
          
          // Estrellas interactivas
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(5, (index) {
              final estrella = index + 1;
              return GestureDetector(
                onTap: enviandoCalificacion
                    ? null
                    : () {
                        setState(() {
                          calificacionUsuario = estrella;
                        });
                      },
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: Icon(
                    estrella <= calificacionUsuario
                        ? Icons.star
                        : Icons.star_border,
                    color: estrella <= calificacionUsuario
                        ? Colors.amber.shade600
                        : Colors.grey.shade400,
                    size: 48,
                  ),
                ),
              );
            }),
          ),
          const SizedBox(height: 20),

          // Botón enviar
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: enviandoCalificacion ? null : _enviarCalificacion,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColores.verdePrimario,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                disabledBackgroundColor: Colors.grey.shade300,
              ),
              child: enviandoCalificacion
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                      ),
                    )
                  : const Text(
                      'Enviar Calificación',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}
