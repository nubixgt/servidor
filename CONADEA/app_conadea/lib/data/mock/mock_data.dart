/// Datos locales temporales — MAGA AgroIA App Móvil.
///
/// NOTA: igual que Frontend/src/data/local.js, este archivo es TEMPORAL,
/// solo para el desarrollo del Frontend de la app. Cuando el Backend esté
/// listo se reemplaza por llamadas al API (ver services/ en el Frontend web
/// como referencia de esa migración futura). El catálogo de cursos vive en
/// mock_cursos.dart (es más extenso) y se reexporta aquí.
library;

import '../models/insignia.dart';
import '../models/novedad.dart';
import '../models/resumen_usuario.dart';
import '../models/ruta.dart';
import 'mock_cursos.dart';

export 'mock_cursos.dart';

const resumenUsuario = ResumenUsuario(
  nombre: 'Admin',
  progresoGlobalPct: 65,
  cursosCompletados: 12,
  enProgreso: 5,
  horasCapacitacion: 24,
  rachaDias: 7,
);

/// Rutas de aprendizaje — equivalente a RUTAS en Frontend/src/data/local.js.
final List<RutaAprendizaje> rutas = [
  RutaAprendizaje(
    id: 'ganaderia_sostenible',
    icono: '🐄',
    titulo: 'Ganadería Sostenible (Plan Piloto)',
    descripcion:
        'Malla curricular completa para la transición gradual hacia fincas más productivas y resilientes.',
    color: RutaColor.verde,
    cursos: [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21].map(cursoPorId).toList(),
  ),
  RutaAprendizaje(
    id: 'sostenible',
    icono: '🌱',
    titulo: 'Agricultura Sostenible',
    descripcion: 'Aprende técnicas sostenibles para mejorar la productividad cuidando el medio ambiente.',
    color: RutaColor.teal,
    cursos: [2, 3, 4, 6].map(cursoPorId).toList(),
  ),
  RutaAprendizaje(
    id: 'pecuaria',
    icono: '🐄',
    titulo: 'Producción Pecuaria',
    descripcion: 'Fortalece tus conocimientos en manejo, nutrición y producción animal.',
    color: RutaColor.azul,
    cursos: [5, 6].map(cursoPorId).toList(),
  ),
  RutaAprendizaje(
    id: 'gestion',
    icono: '📈',
    titulo: 'Gestión, Organización y Mercados',
    descripcion: 'Administra tu finca, fortalece tu organización y vende mejor tu producción.',
    color: RutaColor.oro,
    cursos: [7, 8, 9, 10].map(cursoPorId).toList(),
  ),
  RutaAprendizaje(
    id: 'digital',
    icono: '📱',
    titulo: 'Competencias Digitales AgroIA',
    descripcion: 'Domina el asistente AgroIA y las herramientas digitales del programa.',
    color: RutaColor.teal,
    cursos: [1].map(cursoPorId).toList(),
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
  Novedad(
    tipo: TipoNovedad.alerta,
    titulo: 'Pronóstico: inicio de canícula en julio',
    chip: '⛅ Clima',
    fecha: 'Hace 4 días',
  ),
  Novedad(
    tipo: TipoNovedad.novedad,
    titulo: 'Convocatoria: facilitadores digitales 2.ª cohorte',
    chip: '📣 Convocatoria',
    fecha: 'Hace 5 días',
  ),
];

/// Evento del calendario — equivalente a EVENTOS en Frontend/src/data/local.js.
class EventoCalendario {
  const EventoCalendario({
    required this.dia,
    required this.mes,
    required this.titulo,
    required this.hora,
    required this.icono,
  });

  final int dia;
  final String mes;
  final String titulo;
  final String hora;
  final String icono;
}

const List<EventoCalendario> eventos = [
  EventoCalendario(
    dia: 10,
    mes: 'JUN',
    titulo: 'Webinar en vivo: Innovaciones en riego tecnificado',
    hora: '10:00 a.m.',
    icono: '💻',
  ),
  EventoCalendario(
    dia: 12,
    mes: 'JUN',
    titulo: 'Cierre de inscripción: facilitadores digitales (2.ª cohorte)',
    hora: '5:00 p.m.',
    icono: '📝',
  ),
  EventoCalendario(
    dia: 17,
    mes: 'JUN',
    titulo: 'Jornada de campo: sistemas silvopastoriles · TNC Guatemala',
    hora: '8:00 a.m.',
    icono: '🌳',
  ),
  EventoCalendario(
    dia: 24,
    mes: 'JUN',
    titulo: 'Charla: precios y mercados de granos básicos',
    hora: '3:00 p.m.',
    icono: '📊',
  ),
  EventoCalendario(
    dia: 30,
    mes: 'JUN',
    titulo: 'Entrega de reportes territoriales a CONADEA',
    hora: 'Todo el día',
    icono: '📑',
  ),
];

/// Publicación de foro — equivalente a FOROS en Frontend/src/data/local.js.
class PublicacionForo {
  const PublicacionForo({
    required this.icono,
    required this.titulo,
    required this.autor,
    required this.respuestas,
  });

  final String icono;
  final String titulo;
  final String autor;
  final int respuestas;
}

const List<PublicacionForo> foros = [
  PublicacionForo(
    icono: '🌽',
    titulo: 'Gusano cogollero en maíz de primera, ¿qué están aplicando?',
    autor: 'Asoc. El Esfuerzo · hace 2 horas',
    respuestas: 14,
  ),
  PublicacionForo(
    icono: '🐄',
    titulo: 'Experiencias con bloques multinutricionales en verano',
    autor: 'Coop. Ganaderos del Norte · hace 5 horas',
    respuestas: 9,
  ),
  PublicacionForo(
    icono: '💧',
    titulo: 'Cosecha de agua de techo: medidas de toneles y canaletas',
    autor: 'Asoc. Mujeres Rurales · ayer',
    respuestas: 22,
  ),
  PublicacionForo(
    icono: '🤝',
    titulo: 'Modelo de acta para renovación de junta directiva',
    autor: 'Coop. La Unión · hace 2 días',
    respuestas: 6,
  ),
  PublicacionForo(
    icono: '🛒',
    titulo: 'Compradores de tomate en oriente, recomendaciones',
    autor: 'Asoc. Hortaliceros Unidos · hace 3 días',
    respuestas: 17,
  ),
];

/// Pregunta frecuente — equivalente a FAQS en Frontend/src/data/local.js.
class Faq {
  const Faq({required this.pregunta, required this.respuesta});

  final String pregunta;
  final String respuesta;
}

const List<Faq> faqs = [
  Faq(
    pregunta: '¿Cómo obtengo un certificado?',
    respuesta:
        'Completa todas las lecciones de un curso y aprueba su evaluación con al menos 2 de 3 respuestas correctas. El certificado se genera de inmediato y puedes imprimirlo o guardarlo como PDF.',
  ),
  Faq(
    pregunta: '¿Qué es la racha de aprendizaje?',
    respuesta:
        'Es el número de días seguidos en los que has completado al menos una lección o evaluación. Mantenerla activa te ayuda a avanzar de manera constante.',
  ),
  Faq(
    pregunta: '¿Cómo envío una consulta técnica de mi finca?',
    respuesta:
        'Las consultas con fotos y audios se envían por el WhatsApp AgroIA. Esta aula virtual complementa ese servicio con cursos y certificación. Revisa el Módulo 1 para aprender a usarlo.',
  ),
  Faq(
    pregunta: '¿La plataforma consume muchos datos?',
    respuesta:
        'No. El aula está diseñada para bajo consumo de datos y tu avance se guarda en tu propio dispositivo. El modo ahorro de datos está disponible en Configuración.',
  ),
  Faq(
    pregunta: '¿Quién valida los contenidos?',
    respuesta:
        'La red de especialistas del programa: CEFEP, TNC Guatemala, la Universidad Rafael Landívar, el Colegio de Ingenieros Agrónomos y técnicos de MAGA.',
  ),
];
