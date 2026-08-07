/// Datos locales temporales — MAGA AgroIA App Móvil.
///
/// NOTA: igual que Frontend/src/data/local.js, este archivo es TEMPORAL,
/// solo para el desarrollo del Frontend de la app. Cuando el Backend esté
/// listo se reemplaza por llamadas al API (ver services/ en el Frontend web
/// como referencia de esa migración futura).
library;

import '../models/curso.dart';
import '../models/insignia.dart';
import '../models/novedad.dart';
import '../models/resumen_usuario.dart';
import '../models/ruta.dart';

const resumenUsuario = ResumenUsuario(
  nombre: 'Admin',
  progresoGlobalPct: 65,
  cursosCompletados: 12,
  enProgreso: 5,
  horasCapacitacion: 24,
  rachaDias: 7,
);

final List<Curso> cursos = [
  const Curso(
    id: 1,
    icono: '📱',
    titulo: 'Uso de WhatsApp AgroIA',
    descripcion: 'Aprende a usar el asistente AgroIA para resolver problemas de tu finca.',
    imagenUrl:
        'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=320&auto=format&fit=crop&q=80',
    progresoPct: 45,
  ),
  const Curso(
    id: 2,
    icono: '🌿',
    titulo: 'Diagnóstico básico de cultivos',
    descripcion: 'Identifica síntomas comunes en hojas, frutos y plantas.',
    imagenUrl:
        'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=320&auto=format&fit=crop&q=80',
    progresoPct: 30,
  ),
  const Curso(
    id: 3,
    icono: '🐛',
    titulo: 'Manejo integrado de plagas y enfermedades',
    descripcion: 'Previene y controla con métodos responsables y seguros.',
    imagenUrl:
        'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=320&auto=format&fit=crop&q=80',
    progresoPct: 60,
  ),
  const Curso(
    id: 4,
    icono: '🪱',
    titulo: 'Nutrición y manejo de suelos',
    descripcion: 'Un suelo vivo y fértil es la base de toda buena cosecha.',
    imagenUrl:
        'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=320&auto=format&fit=crop&q=80',
    progresoPct: 0,
  ),
  const Curso(
    id: 5,
    icono: '🐄',
    titulo: 'Ganadería sostenible',
    descripcion: 'Mejora la nutrición, sanidad y bienestar de tu hato.',
    imagenUrl:
        'https://images.unsplash.com/photo-1570042225831-d9b085d44e20?w=320&auto=format&fit=crop&q=80',
    progresoPct: 100,
    completado: true,
  ),
  const Curso(
    id: 6,
    icono: '💧',
    titulo: 'Manejo de agua y adaptación climática',
    descripcion: 'Prepara tu finca para la sequía y el exceso de lluvia.',
    imagenUrl:
        'https://images.unsplash.com/photo-1463123081488-729f60c1926d?w=320&auto=format&fit=crop&q=80',
    progresoPct: 20,
  ),
  const Curso(
    id: 7,
    icono: '🧮',
    titulo: 'Administración rural y costos',
    descripcion: 'Lleva las cuentas de tu finca y conoce tu rentabilidad.',
    imagenUrl:
        'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    progresoPct: 0,
  ),
  const Curso(
    id: 8,
    icono: '🤝',
    titulo: 'Organización asociativa y cooperativa',
    descripcion: 'Fortalece tu asociación con buenas prácticas de gestión.',
    imagenUrl:
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=320&auto=format&fit=crop&q=80',
    progresoPct: 0,
  ),
  const Curso(
    id: 9,
    icono: '🛒',
    titulo: 'Comercialización y acceso a mercados',
    descripcion: 'Vende mejor: calidad, volumen, negociación y clientes.',
    imagenUrl:
        'https://images.unsplash.com/photo-1542838132-92c53300491e?w=320&auto=format&fit=crop&q=80',
    progresoPct: 0,
  ),
  const Curso(
    id: 10,
    icono: '📑',
    titulo: 'Formulación de proyectos y acceso a fondos',
    descripcion: 'Convierte las necesidades de tu organización en proyectos financiables.',
    imagenUrl:
        'https://images.unsplash.com/photo-1450133064473-71024230f91b?w=320&auto=format&fit=crop&q=80',
    progresoPct: 100,
    completado: true,
  ),
];

Curso cursoPorId(int id) => cursos.firstWhere((c) => c.id == id);

final List<RutaAprendizaje> rutas = [
  RutaAprendizaje(
    id: 'ganaderia_sostenible',
    icono: '🐄',
    titulo: 'Ganadería Sostenible (Plan Piloto)',
    descripcion:
        'Malla curricular completa para la transición gradual hacia fincas más productivas y resilientes.',
    color: RutaColor.verde,
    cursos: [cursoPorId(5), cursoPorId(6)],
  ),
  RutaAprendizaje(
    id: 'sostenible',
    icono: '🌱',
    titulo: 'Agricultura Sostenible',
    descripcion: 'Aprende técnicas sostenibles para mejorar la productividad cuidando el medio ambiente.',
    color: RutaColor.teal,
    cursos: [cursoPorId(2), cursoPorId(3), cursoPorId(4), cursoPorId(6)],
  ),
  RutaAprendizaje(
    id: 'gestion',
    icono: '📈',
    titulo: 'Gestión, Organización y Mercados',
    descripcion: 'Administra tu finca, fortalece tu organización y vende mejor tu producción.',
    color: RutaColor.oro,
    cursos: [cursoPorId(7), cursoPorId(8), cursoPorId(9), cursoPorId(10)],
  ),
  RutaAprendizaje(
    id: 'digital',
    icono: '📱',
    titulo: 'Competencias Digitales AgroIA',
    descripcion: 'Domina el asistente AgroIA y las herramientas digitales del programa.',
    color: RutaColor.azul,
    cursos: [cursoPorId(1)],
  ),
];

final List<Insignia> insignias = [
  const Insignia(
    id: 'semilla',
    icono: '🌱',
    titulo: 'Primera semilla',
    descripcion: 'Completaste tu primera lección.',
    color: InsigniaColor.verde,
    obtenida: true,
  ),
  const Insignia(
    id: 'modulo1',
    icono: '🎓',
    titulo: 'Primer módulo',
    descripcion: 'Aprobaste tu primer módulo.',
    color: InsigniaColor.verde,
    obtenida: true,
  ),
  const Insignia(
    id: 'tres',
    icono: '🏅',
    titulo: 'Tres módulos',
    descripcion: 'Completaste 3 módulos.',
    color: InsigniaColor.verde,
    obtenida: true,
  ),
  const Insignia(
    id: 'cinco',
    icono: '⭐',
    titulo: 'Cinco módulos',
    descripcion: 'Completaste 5 módulos.',
    color: InsigniaColor.oro,
    obtenida: true,
  ),
  const Insignia(
    id: 'perfecto',
    icono: '💎',
    titulo: 'Nota perfecta',
    descripcion: 'Respondiste las 3 preguntas correctamente.',
    color: InsigniaColor.oro,
    obtenida: true,
  ),
  const Insignia(
    id: 'explorador',
    icono: '🧭',
    titulo: 'Explorador',
    descripcion: 'Abriste 4 o más módulos distintos.',
    color: InsigniaColor.azul,
    obtenida: true,
  ),
  const Insignia(
    id: 'todas',
    icono: '🏆',
    titulo: 'Maestro AgroIA',
    descripcion: 'Completaste todos los módulos del programa.',
    color: InsigniaColor.oro,
  ),
];

const List<Novedad> novedades = [
  Novedad(
    tipo: TipoNovedad.alerta,
    titulo: 'Alerta fitosanitaria: roya en maíz',
    chip: '⚠️ Alerta',
    fecha: 'Hace 2 horas',
  ),
  Novedad(
    tipo: TipoNovedad.video,
    titulo: 'Nuevo video: Ensilaje de maíz paso a paso',
    chip: '🎬 Video',
    fecha: 'Ayer',
  ),
  Novedad(
    tipo: TipoNovedad.novedad,
    titulo: 'Nuevos módulos de Ganadería Sostenible disponibles',
    chip: '📗 Novedad',
    fecha: 'Hace 3 días',
  ),
];
