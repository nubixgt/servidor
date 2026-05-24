import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../theme/app_theme.dart';

class ProjectsScreen extends StatelessWidget {
  const ProjectsScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final List<Map<String, dynamic>> projects = [
      {'name': 'Skyline Tower - Fase 2', 'progress': 78, 'client': 'Inmobiliaria Delta', 'status': 'En Progreso'},
      {'name': 'Restauración Gran Puente', 'progress': 45, 'client': 'Ministerio de Obras', 'status': 'Retrasado'},
      {'name': 'Cimentación Parque Eco-Tech', 'progress': 92, 'client': 'EcoTech Corp', 'status': 'En Progreso'},
      {'name': 'Condominios Riverside', 'progress': 12, 'client': 'Constructora Alfa', 'status': 'En Progreso'},
    ];

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: projects.length,
      separatorBuilder: (_, __) => const SizedBox(height: 16),
      itemBuilder: (context, index) {
        final project = projects[index];
        final progress = project['progress'] as int;
        final bool isDelayed = project['status'] == 'Retrasado';

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
                      project['name'] as String,
                      style: const TextStyle(fontWeight: FontWeight.w900, fontSize: 16),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: isDelayed ? Colors.red.withOpacity(0.2) : AppTheme.primaryColor.withOpacity(0.2),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      '${project['progress']}%',
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        color: isDelayed ? Colors.red[300] : AppTheme.primaryColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                'Cliente: ${project['client']}',
                style: const TextStyle(color: Colors.white54, fontSize: 12),
              ),
              const SizedBox(height: 16),
              Container(
                height: 8,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.05),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: Row(
                  children: [
                    Expanded(
                      flex: progress,
                      child: Container(
                        decoration: BoxDecoration(
                          color: isDelayed ? AppTheme.tertiaryColor : AppTheme.primaryColor,
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ).animate().scaleX(begin: 0, end: 1, duration: 1.seconds, curve: Curves.easeOutQuart, alignment: Alignment.centerLeft),
                    ),
                    Expanded(
                      flex: 100 - progress,
                      child: const SizedBox(),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ).animate().slideY(begin: 0.1, end: 0, delay: (index * 150).ms).fadeIn();
      },
    );
  }
}
