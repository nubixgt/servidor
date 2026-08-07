import 'package:flutter/material.dart';
import '../../core/theme/app_colors.dart';
import '../../core/theme/app_text_styles.dart';
import '../../core/widgets/app_background.dart';
import '../../core/widgets/back_header.dart';
import '../../core/widgets/glass_card.dart';
import '../../data/mock/mock_data.dart';

/// Ayuda y soporte — equivalente a Ayuda.vue (acordeón de FAQ).
class AyudaScreen extends StatefulWidget {
  const AyudaScreen({super.key});

  @override
  State<AyudaScreen> createState() => _AyudaScreenState();
}

class _AyudaScreenState extends State<AyudaScreen> {
  final Set<int> _abiertos = {};

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: AppBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
            children: [
              const BackHeader(title: 'Ayuda y soporte'),
              const SizedBox(height: 10),
              for (var i = 0; i < faqs.length; i++)
                _FaqTile(
                  faq: faqs[i],
                  abierta: _abiertos.contains(i),
                  onTap: () => setState(() {
                    if (!_abiertos.remove(i)) _abiertos.add(i);
                  }),
                ),
              const SizedBox(height: 6),
              GlassCard(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('¿Necesitas más ayuda?', style: AppTextStyles.subtitulo(size: 14.5)),
                    const SizedBox(height: 8),
                    Text(
                      'Contacta al facilitador digital de tu asociación o escribe al WhatsApp AgroIA. '
                      'En casos técnicos complejos, tu consulta será derivada a un especialista de la red de validadores.',
                      style: AppTextStyles.cuerpo(size: 12.5),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _FaqTile extends StatelessWidget {
  const _FaqTile({required this.faq, required this.abierta, required this.onTap});

  final Faq faq;
  final bool abierta;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: AppColors.vidrio,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borde),
      ),
      child: Column(
        children: [
          InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Row(
                children: [
                  const Text('❓', style: TextStyle(fontSize: 16)),
                  const SizedBox(width: 12),
                  Expanded(child: Text(faq.pregunta, style: AppTextStyles.subtitulo(size: 13.5))),
                  Icon(
                    abierta ? Icons.expand_more_rounded : Icons.chevron_right_rounded,
                    color: AppColors.textoSuave,
                  ),
                ],
              ),
            ),
          ),
          if (abierta)
            Padding(
              padding: const EdgeInsets.fromLTRB(46, 0, 16, 16),
              child: Text(faq.respuesta, style: AppTextStyles.cuerpo(size: 12.5)),
            ),
        ],
      ),
    );
  }
}
