import 'package:flutter/material.dart';
import '../../core/theme/app_text_styles.dart';
import '../../data/mock/mock_data.dart';
import '../../data/models/curso.dart';

/// Modal de certificado de finalización — equivalente al `.certificado` de
/// DetalleCurso.vue / Certificados.vue (estilo diploma crema/dorado).
void mostrarCertificado(
  BuildContext context, {
  required Curso curso,
  required int nota,
  required String fecha,
}) {
  showDialog(
    context: context,
    barrierColor: const Color(0xD9051A1A),
    builder: (context) => Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(20),
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 480),
        child: Container(
          padding: const EdgeInsets.fromLTRB(28, 32, 28, 24),
          decoration: BoxDecoration(
            color: const Color(0xFFFFFDF4),
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFC8932B), width: 3),
          ),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: const Color(0xFFE9B44C)),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('🌽', style: TextStyle(fontSize: 40)),
                const SizedBox(height: 8),
                Text(
                  'MAGA · CONSEJO NACIONAL DE DESARROLLO\nAGROPECUARIO · PROGRAMA AGROIA',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 10,
                    letterSpacing: 1.4,
                    fontWeight: FontWeight.w700,
                    color: const Color(0xFF8C5E35),
                  ),
                ),
                const SizedBox(height: 14),
                Text(
                  'Certificado de finalización',
                  textAlign: TextAlign.center,
                  style: AppTextStyles.titulo(size: 22).copyWith(color: const Color(0xFF16382B)),
                ),
                const SizedBox(height: 10),
                const Text(
                  'Se otorga el presente certificado a',
                  style: TextStyle(fontSize: 13, color: Color(0xFF5C6B62)),
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.only(bottom: 8),
                  decoration: const BoxDecoration(
                    border: Border(bottom: BorderSide(color: Color(0xFFE9B44C), width: 1.5)),
                  ),
                  child: Text(
                    resumenUsuario.nombre,
                    style: AppTextStyles.titulo(size: 22).copyWith(color: const Color(0xFF2D6A4F)),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  'por haber completado satisfactoriamente el\n'
                  'Módulo ${curso.id}: ${curso.titulo}\n'
                  'del programa de capacitación digital AgroIA,\n'
                  'con evaluación aprobada ($nota/3) el $fecha.',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontSize: 13, color: Color(0xFF21302A), height: 1.6),
                ),
                const SizedBox(height: 22),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                  children: const [
                    Expanded(
                      child: Column(
                        children: [
                          Divider(color: Color(0xFF5C6B62)),
                          SizedBox(height: 4),
                          Text('Coordinación CONADEA\nPrograma AgroIA',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 10.5, color: Color(0xFF5C6B62))),
                        ],
                      ),
                    ),
                    SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        children: [
                          Divider(color: Color(0xFF5C6B62)),
                          SizedBox(height: 4),
                          Text('Red de técnicos validadores\nValidación académica',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontSize: 10.5, color: Color(0xFF5C6B62))),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Cerrar', style: TextStyle(color: Color(0xFF16382B))),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );
}
