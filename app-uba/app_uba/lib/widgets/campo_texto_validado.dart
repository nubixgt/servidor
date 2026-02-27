import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

class CampoTextoValidado extends StatelessWidget {
  final String etiqueta;
  final String placeholder;
  final TextEditingController controller;
  final TextInputType tipoTeclado;
  final List<TextInputFormatter>? formateadores;
  final bool mostrarValidacion;
  final bool esValido;
  final String? mensajeError;
  final int? maxLineas;

  const CampoTextoValidado({
    super.key,
    required this.etiqueta,
    required this.placeholder,
    required this.controller,
    this.tipoTeclado = TextInputType.text,
    this.formateadores,
    this.mostrarValidacion = false,
    this.esValido = false,
    this.mensajeError,
    this.maxLineas = 1,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          etiqueta,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
        ),
        const SizedBox(height: 8),
        TextField(
          controller: controller,
          keyboardType: tipoTeclado,
          inputFormatters: formateadores,
          maxLines: maxLineas,
          decoration: InputDecoration(
            hintText: placeholder,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: mostrarValidacion && !esValido
                    ? Colors.red.shade400
                    : Colors.grey.shade300,
                width: 1,
              ),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: mostrarValidacion && !esValido
                    ? Colors.red.shade400
                    : Colors.grey.shade300,
                width: 1,
              ),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: BorderSide(
                color: mostrarValidacion && esValido
                    ? Colors.green.shade500
                    : Colors.blue.shade500,
                width: 2,
              ),
            ),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 12,
            ),
            suffixIcon: mostrarValidacion
                ? Icon(
                    esValido ? Icons.check_circle : Icons.error,
                    color: esValido
                        ? Colors.green.shade500
                        : Colors.red.shade400,
                  )
                : null,
          ),
        ),
        if (mostrarValidacion && !esValido && mensajeError != null) ...[
          const SizedBox(height: 4),
          Text(
            mensajeError!,
            style: TextStyle(fontSize: 12, color: Colors.red.shade600),
          ),
        ],
      ],
    );
  }
}
