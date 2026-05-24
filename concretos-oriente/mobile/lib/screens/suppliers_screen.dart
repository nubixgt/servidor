import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class SuppliersScreen extends StatelessWidget {
  const SuppliersScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> suppliers = [
      {'name': 'Cementos Progreso', 'category': 'Materia Prima', 'rating': 4.8, 'status': 'Activo', 'contact': 'ventas@cempro.com'},
      {'name': 'Aceros de Guatemala', 'category': 'Acero / Metal', 'rating': 4.5, 'status': 'Activo', 'contact': 'info@acerosgua.com'},
      {'name': 'Transportes Rápidos', 'category': 'Logística', 'rating': 3.2, 'status': 'En Revisión', 'contact': 'logistica@transrap.com'},
    ];

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: suppliers.length,
      separatorBuilder: (_, __) => const SizedBox(height: 16),
      itemBuilder: (context, index) {
        final supplier = suppliers[index];
        final bool isReview = supplier['status'] == 'En Revisión';

        return GlassCard(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      supplier['name'] as String,
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 18),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isReview ? Colors.orange.withOpacity(0.2) : Colors.green.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: isReview ? Colors.orange.withOpacity(0.3) : Colors.green.withOpacity(0.3)),
                    ),
                    child: Text(
                      (supplier['status'] as String).toUpperCase(),
                      style: TextStyle(
                        fontSize: 8,
                        fontWeight: FontWeight.w900,
                        color: isReview ? Colors.orange[400] : Colors.green[400],
                        letterSpacing: 1,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                (supplier['category'] as String).toUpperCase(),
                style: const TextStyle(
                  fontSize: 10,
                  color: AppTheme.primaryColor,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 1.5,
                ),
              ),
              const SizedBox(height: 16),
              const Divider(color: Colors.white12),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(Icons.email_outlined, size: 14, color: Colors.white54),
                  const SizedBox(width: 6),
                  Text(supplier['contact'] as String, style: const TextStyle(color: Colors.white70, fontSize: 12)),
                  const Spacer(),
                  const Icon(Icons.star, size: 14, color: Colors.amber),
                  const SizedBox(width: 4),
                  Text(supplier['rating'].toString(), style: const TextStyle(fontWeight: FontWeight.bold)),
                ],
              ),
            ],
          ),
        ).animate().slideY(begin: 0.1, end: 0, delay: (index * 150).ms).fadeIn();
      },
    );
  }
}
