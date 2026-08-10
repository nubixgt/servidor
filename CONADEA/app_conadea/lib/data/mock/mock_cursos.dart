/// Catálogo de ejemplo — portado 1:1 desde MODULOS en Frontend/src/data/local.js.
///
/// Los cursos reales ya salen del Backend (ver data/services/curso_service.dart
/// y Backend/src/Controllers/CursoController.php) en Mis cursos, Inicio,
/// Perfil y Catálogo. Este archivo solo sigue vivo porque Rutas/Insignias
/// todavía no tienen su propio Backend y `rutas` (en mock_data.dart) arma
/// sus grupos de cursos a partir de este catálogo de ejemplo.
library;

import '../models/curso.dart';

final List<Curso> cursos = [
  const Curso(
    id: 1,
    icono: '📱',
    titulo: 'Uso de WhatsApp AgroIA',
    descripcion: 'Aprende a usar el asistente AgroIA para resolver problemas de tu finca.',
    imagenUrl: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Cómo enviar consultas',
        contenido:
            'Escribe tu consulta al número oficial de AgroIA describiendo el cultivo o animal, el problema que observas y desde cuándo ocurre. Entre más clara sea tu consulta, mejor será la orientación que recibirás.',
      ),
      Leccion(
        titulo: 'Cómo tomar fotos útiles',
        contenido:
            'Toma las fotos con buena luz natural, sin sombra encima. Envía una foto de cerca del síntoma y otra de lejos para ver toda la planta o el animal. Limpia el lente antes de fotografiar.',
      ),
      Leccion(
        titulo: 'Cómo grabar audios claros',
        contenido:
            'Graba en un lugar sin mucho ruido, habla despacio y menciona: qué cultivo o animal es, qué problema observas, hace cuánto empezó y qué has aplicado. Un audio de 30 a 60 segundos es suficiente.',
      ),
      Leccion(
        titulo: 'Cómo compartir tu ubicación',
        contenido:
            'Compartir tu ubicación ayuda a que las recomendaciones tomen en cuenta tu zona y clima. En WhatsApp toca el clip 📎, elige «Ubicación» y luego «Enviar mi ubicación actual».',
      ),
      Leccion(
        titulo: 'Cómo interpretar las recomendaciones',
        contenido:
            'AgroIA da una orientación inicial, no un diagnóstico definitivo. Lee con atención las posibles causas y los pasos sugeridos. Si dice «este caso necesita revisión técnica», espera al técnico.',
      ),
      Leccion(
        titulo: 'Cuándo solicitar revisión técnica',
        contenido:
            'Pide revisión de un técnico cuando el problema avanza rápido, hay muchos animales o plantas afectadas, hay riesgo de perder la cosecha, o la orientación automática no resolvió el caso.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es la mejor manera de enviar una foto para diagnóstico?',
        opciones: [
          'Una sola foto de lejos y con sombra',
          'Una foto de cerca del síntoma y otra de toda la planta, con buena luz',
          'Una captura de pantalla de otra foto',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué debe incluir un buen audio de consulta?',
        opciones: [
          'Solo un saludo',
          'Música de fondo para que sea agradable',
          'Cultivo o animal, problema, tiempo del problema y qué se ha hecho',
        ],
        respuesta: 2,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuándo debes pedir revisión de un técnico humano?',
        opciones: [
          'Cuando el problema avanza rápido o hay riesgo de pérdida severa',
          'Nunca, la IA siempre es suficiente',
          'Solo en diciembre',
        ],
        respuesta: 0,
      ),
    ],
  ),
  const Curso(
    id: 2,
    icono: '🌿',
    titulo: 'Diagnóstico básico de cultivos',
    descripcion: 'Identifica síntomas comunes en hojas, frutos y plantas.',
    imagenUrl: 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Síntomas en hojas',
        contenido:
            'Las hojas hablan: el amarillamiento general puede indicar falta de nitrógeno; manchas con anillos suelen ser hongos; bordes quemados pueden ser exceso de sal o fertilizante; y hojas enrolladas pueden indicar virus.',
      ),
      Leccion(
        titulo: 'Síntomas en frutos',
        contenido:
            'Revisa pudriciones, perforaciones, deformaciones y manchas. Una perforación con excremento indica gusano; pudrición blanda con mal olor suele ser bacteria; manchas hundidas oscuras suelen ser antracnosis.',
      ),
      Leccion(
        titulo: 'Plagas comunes',
        contenido:
            'Aprende a reconocer gusano cogollero en maíz, mosca blanca en hortalizas, pulgones, trips y gallina ciega. Revisa el envés de las hojas: ahí se esconden la mayoría de plagas pequeñas.',
      ),
      Leccion(
        titulo: 'Enfermedades frecuentes',
        contenido:
            'Los hongos avanzan con humedad y se ven como manchas, polvillos o mohos. Las bacterias producen pudriciones acuosas y mal olor. Los virus deforman y amarillan la planta en patrones de mosaico.',
      ),
      Leccion(
        titulo: 'Deficiencias nutricionales',
        contenido:
            'Nitrógeno: hojas viejas amarillas. Fósforo: tonos morados y plantas pequeñas. Potasio: bordes quemados en hojas viejas. Las deficiencias aparecen parejas en el lote.',
      ),
      Leccion(
        titulo: 'Estrés por agua',
        contenido:
            'La falta de agua marchita la planta al mediodía. El exceso de agua amarilla la planta, pudre raíces y favorece hongos. Revisa el suelo a 10 cm de profundidad antes de regar.',
      ),
      Leccion(
        titulo: 'Prevención y monitoreo',
        contenido:
            'Camina tu parcela al menos dos veces por semana en forma de zigzag, revisa 5 plantas por punto y anota lo que ves. Detectar a tiempo un foco pequeño es más barato que controlar todo el lote.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Dónde se esconden la mayoría de plagas pequeñas?',
        opciones: [
          'En el envés (parte de abajo) de las hojas',
          'Solo en las flores',
          'En el tallo principal únicamente',
        ],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'Si las hojas viejas están amarillas de manera pareja en todo el lote, lo más probable es:',
        opciones: ['Un virus', 'Falta de nitrógeno', 'Daño por pájaros'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cada cuánto se recomienda monitorear la parcela?',
        opciones: ['Una vez al mes', 'Solo al sembrar', 'Al menos dos veces por semana'],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 3,
    icono: '🐛',
    titulo: 'Manejo integrado de plagas y enfermedades',
    descripcion: 'Previene y controla con métodos responsables y seguros.',
    imagenUrl: 'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Monitoreo constante',
        contenido:
            'El manejo integrado (MIP) empieza por observar. Usa trampas amarillas con pegamento, revisa el suelo y lleva un registro semanal. Solo se controla bien lo que se conoce.',
      ),
      Leccion(
        titulo: 'Prevención primero',
        contenido:
            'Usa semilla sana, variedades resistentes, rota cultivos, elimina rastrojos enfermos y desinfecta herramientas. La mayoría de problemas graves se evitan antes de sembrar.',
      ),
      Leccion(
        titulo: 'Control cultural',
        contenido:
            'Distancias de siembra adecuadas para ventilación, eliminación de malezas hospederas, podas sanitarias y manejo correcto del riego para no favorecer hongos.',
      ),
      Leccion(
        titulo: 'Control biológico',
        contenido:
            'Aprovecha a los aliados naturales: mariquitas y crisopas comen pulgones; avispas parasitan gusanos; hongos como Beauveria y Trichoderma controlan plagas. Protege a estos aliados.',
      ),
      Leccion(
        titulo: 'Uso responsable de productos',
        contenido:
            'Usa solo productos registrados, respeta la dosis de la etiqueta, usa equipo de protección y respeta los días de espera antes de cosechar. Nunca apliques sin diagnóstico confirmado.',
      ),
      Leccion(
        titulo: 'Registro de aplicaciones',
        contenido:
            'Anota fecha, producto, dosis, cultivo y resultado de cada aplicación. Este registro evita repetir productos que no funcionaron y previene resistencia de las plagas.',
      ),
      Leccion(
        titulo: 'Cuándo consultar a un técnico',
        contenido:
            'Consulta cuando la plaga no cede después de dos manejos, cuando no logres identificar el problema o cuando pienses usar productos de alta toxicidad.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es el primer paso del manejo integrado de plagas?',
        opciones: ['Aplicar el producto más fuerte', 'Monitorear y conocer el problema', 'Quemar el lote'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué insecto es un aliado natural contra los pulgones?',
        opciones: ['La mariquita', 'La mosca blanca', 'El gusano cogollero'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'Antes de aplicar un producto químico siempre debes:',
        opciones: [
          'Aplicar el doble de dosis para asegurar',
          'Confirmar el diagnóstico, leer la etiqueta y usar protección',
          'Aplicar al mediodía con viento',
        ],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 4,
    icono: '🪱',
    titulo: 'Nutrición y manejo de suelos',
    descripcion: 'Un suelo vivo y fértil es la base de toda buena cosecha.',
    imagenUrl: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Fertilidad del suelo',
        contenido:
            'Un suelo fértil tiene buena estructura, materia orgánica, vida y nutrientes disponibles. La fertilidad no es solo aplicar fertilizante: es manejar el suelo como un organismo vivo.',
      ),
      Leccion(
        titulo: 'Materia orgánica',
        contenido:
            'Incorpora rastrojos, abonos verdes, compost o lombricompost. La materia orgánica retiene humedad, alimenta los microorganismos y mejora la estructura del suelo.',
      ),
      Leccion(
        titulo: 'Conservación de suelos',
        contenido:
            'En laderas usa barreras vivas, curvas a nivel, terrazas y acequias. Evita la quema: destruye la materia orgánica y deja el suelo desnudo frente a la lluvia.',
      ),
      Leccion(
        titulo: 'Análisis de suelo',
        contenido:
            'Un análisis de laboratorio te dice qué nutrientes tiene tu suelo y cuáles faltan. Toma submuestras en zigzag a 20 cm de profundidad, mézclalas y envía 1 libra al laboratorio.',
      ),
      Leccion(
        titulo: 'Fertilización responsable',
        contenido:
            'Aplica el nutriente correcto, en la dosis correcta, en el momento correcto y en el lugar correcto (las 4 C). Fracciona el nitrógeno en 2 o 3 aplicaciones.',
      ),
      Leccion(
        titulo: 'Control de la erosión',
        contenido:
            'Mantén el suelo siempre cubierto con rastrojo o cultivos de cobertura. Un suelo desnudo pierde hasta 40 toneladas por hectárea al año en laderas.',
      ),
      Leccion(
        titulo: 'Manejo de humedad',
        contenido:
            'El acolchado (mulch), las coberturas y la materia orgánica guardan agua en el suelo. En época seca, cada práctica que conserve humedad puede ser la diferencia entre cosechar o perder.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Qué aporta la materia orgánica al suelo?',
        opciones: [
          'Solo color oscuro',
          'Retiene humedad, alimenta la vida del suelo y mejora la estructura',
          'Aumenta la erosión',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué no se recomienda la quema de rastrojos?',
        opciones: [
          'Porque destruye la materia orgánica y deja el suelo expuesto',
          'Porque el fuego enfría el suelo',
          'Porque atrae lombrices',
        ],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'Las «4 C» de la fertilización responsable son: nutriente correcto, dosis correcta...',
        opciones: [
          '...color correcto y costal correcto',
          '...momento correcto y lugar correcto',
          '...camión correcto y caja correcta',
        ],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 5,
    icono: '🐄',
    titulo: 'Ganadería sostenible',
    descripcion: 'Mejora la nutrición, sanidad y bienestar de tu hato.',
    imagenUrl: 'https://images.unsplash.com/photo-1570042225831-d9b085d44e20?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Condición corporal',
        contenido:
            'La condición corporal se evalúa de 1 (muy flaco) a 5 (obeso). Lo ideal es mantener el hato entre 3 y 3.5. Una vaca flaca produce menos leche, se preña menos y se enferma más.',
      ),
      Leccion(
        titulo: 'Nutrición animal',
        contenido:
            'El animal necesita energía, proteína, minerales y agua. Planifica ensilaje, heno, bloques minerales y suplementos para la época seca. La sal mineralizada debe estar disponible todo el año.',
      ),
      Leccion(
        titulo: 'Manejo de pasturas',
        contenido:
            'Divide el potrero en apartos y rota el ganado: deja descansar el pasto de 25 a 35 días según la especie. El sobrepastoreo degrada el pasto y compacta el suelo.',
      ),
      Leccion(
        titulo: 'Agua y sombra',
        contenido:
            'Una vaca lechera puede tomar de 40 a 80 litros de agua al día. Sin agua limpia y sombra, el animal come menos y produce menos. Los árboles en potreros dan sombra, forraje y mejoran el suelo.',
      ),
      Leccion(
        titulo: 'Bienestar animal',
        contenido:
            'Animales sin estrés producen más: evita gritos y golpes, mantén corrales sin lodo excesivo ni piedras, y maneja con calma en el embudo y la manga.',
      ),
      Leccion(
        titulo: 'Sanidad preventiva',
        contenido:
            'Cumple el calendario de vacunación y desparasitación, revisa parásitos externos y aísla animales enfermos. Prevenir cuesta menos que curar.',
      ),
      Leccion(
        titulo: 'Sistemas silvopastoriles',
        contenido:
            'Combinar pastos con árboles y arbustos forrajeros da más alimento por área, sombra, fija nitrógeno y hace la finca más resistente a la sequía.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es la condición corporal ideal para el hato?',
        opciones: ['Entre 1 y 2', 'Entre 3 y 3.5', '5 en todos los animales'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuántos días de descanso necesita un potrero en rotación?',
        opciones: ['De 25 a 35 días según la especie de pasto', '2 días', '1 año completo'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué beneficios dan los árboles forrajeros en un sistema silvopastoril?',
        opciones: ['Solo adornan la finca', 'Quitan espacio al pasto', 'Sombra, forraje, nitrógeno y resistencia a la sequía'],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 6,
    icono: '💧',
    titulo: 'Manejo de agua y adaptación climática',
    descripcion: 'Prepara tu finca para la sequía y el exceso de lluvia.',
    imagenUrl: 'https://images.unsplash.com/photo-1463123081488-729f60c1926d?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Cosecha de agua',
        contenido:
            'Aprovecha la lluvia: techos con canaletas hacia toneles o cisternas, reservorios y aljibes. Cada metro cuadrado de techo puede cosechar cerca de 1 litro por cada milímetro de lluvia.',
      ),
      Leccion(
        titulo: 'Riego eficiente',
        contenido:
            'El riego por goteo usa hasta 60% menos agua que el riego por gravedad. Riega temprano en la mañana o al atardecer para reducir la evaporación. Más agua no siempre es más cosecha.',
      ),
      Leccion(
        titulo: 'Manejo en sequía',
        contenido:
            'En años secos: siembra variedades de ciclo corto y tolerantes, reduce el área a la que sí puedes dar agua, usa acolchado y prioriza el agua para los cultivos y animales de mayor valor.',
      ),
      Leccion(
        titulo: 'Manejo del exceso de lluvia',
        contenido:
            'Construye drenajes y acequias antes del invierno, siembra en camellones altos en zonas que se inundan y refuerza el control preventivo de hongos.',
      ),
      Leccion(
        titulo: 'Conservación de humedad',
        contenido:
            'Acolchado, coberturas vivas, materia orgánica y barreras rompevientos reducen la pérdida de agua del suelo. Estas prácticas cuestan poco y alargan la humedad disponible varias semanas.',
      ),
      Leccion(
        titulo: 'Alertas climáticas',
        contenido:
            'Atiende los boletines del INSIVUMEH y las alertas de AgroIA: pronóstico de canícula, lluvias intensas o heladas. Planificar con el pronóstico evita sembrar en el momento equivocado.',
      ),
      Leccion(
        titulo: 'Planificación por temporada',
        contenido:
            'Haz un calendario de tu finca: qué siembras en primera, qué en postrera, cuándo vacunas, cuándo haces ensilaje. Una finca planificada por temporada sufre menos los golpes del clima.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuánta agua puede ahorrar el riego por goteo frente al riego por gravedad?',
        opciones: ['Hasta 60% menos', 'No ahorra nada', 'Solo 1%'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es el mejor momento del día para regar?',
        opciones: ['Al mediodía con pleno sol', 'Temprano en la mañana o al atardecer', 'A cualquier hora da igual'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: 'Ante pronóstico de lluvias intensas conviene:',
        opciones: ['No hacer nada', 'Quitar todos los drenajes', 'Preparar drenajes y reforzar la prevención de hongos'],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 7,
    icono: '🧮',
    titulo: 'Administración rural y costos',
    descripcion: 'Lleva las cuentas de tu finca y conoce tu rentabilidad.',
    imagenUrl: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Registro de ingresos y gastos',
        contenido:
            'Anota todo lo que entra y sale: ventas, compras de insumos, jornales, transporte. Sin registros, no se sabe si la finca gana o pierde; se administra a ciegas.',
      ),
      Leccion(
        titulo: 'Costos de producción',
        contenido:
            'Suma todo lo que cuesta producir: semilla, fertilizante, jornales, transporte y alquileres. Divide el total entre los quintales producidos: ese es tu costo por quintal.',
      ),
      Leccion(
        titulo: 'Precio de venta',
        contenido:
            'Vende conociendo tu costo: si producir un quintal cuesta Q150 y lo vendes a Q140, pierdes aunque parezca que entra dinero. Infórmate del precio de mercado antes de vender.',
      ),
      Leccion(
        titulo: 'Control de inventarios',
        contenido:
            'Lleva control de insumos, herramientas, cosecha almacenada y animales. Saber qué tienes evita compras dobles, pérdidas por vencimiento y robos que pasan desapercibidos.',
      ),
      Leccion(
        titulo: 'Rentabilidad',
        contenido:
            'Rentabilidad = ingresos menos costos. Calcula por cultivo o actividad: a veces un cultivo querido da pérdida y otro pequeño da la ganancia. Los números ayudan a decidir.',
      ),
      Leccion(
        titulo: 'Presupuesto',
        contenido:
            'Antes de cada ciclo, planifica cuánto vas a gastar y cuándo. Un presupuesto evita quedarse sin dinero a mitad del ciclo, justo cuando el cultivo más necesita inversión.',
      ),
      Leccion(
        titulo: 'Control financiero',
        contenido:
            'Compara cada mes lo planificado contra lo real, separa el dinero de la finca del dinero de la casa y guarda una reserva para emergencias.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cómo se calcula el costo por quintal?',
        opciones: [
          'Total de costos dividido entre los quintales producidos',
          'El precio que ponga el comprador',
          'El doble del precio de la semilla',
        ],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'Si producir un quintal cuesta Q150 y lo vendes a Q140:',
        opciones: ['Ganas Q10', 'Pierdes Q10 por quintal', 'No pasa nada'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: 'Una buena práctica financiera es:',
        opciones: [
          'Mezclar el dinero de la finca con el de la casa',
          'No anotar los gastos pequeños',
          'Separar las cuentas y guardar una reserva para emergencias',
        ],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 8,
    icono: '🤝',
    titulo: 'Organización asociativa y cooperativa',
    descripcion: 'Fortalece tu asociación con buenas prácticas de gestión.',
    imagenUrl: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Funciones de la organización',
        contenido:
            'Una asociación o cooperativa sirve para lograr juntos lo que no se logra solo: comprar insumos más baratos, vender en volumen, acceder a proyectos y asistencia técnica.',
      ),
      Leccion(
        titulo: 'Junta directiva',
        contenido:
            'La junta directiva administra por mandato de la asamblea, no es dueña de la organización. Debe rendir cuentas, convocar asambleas y renovar cargos en los plazos establecidos.',
      ),
      Leccion(
        titulo: 'Actas y documentos',
        contenido:
            'Toda decisión importante debe quedar en acta. Las actas al día y los libros autorizados son requisito para optar a proyectos y mantener la personería jurídica vigente.',
      ),
      Leccion(
        titulo: 'Participación de los socios',
        contenido:
            'Una organización es fuerte cuando los socios participan: asisten a asambleas, opinan, fiscalizan y asumen cargos. La apatía de los socios es la principal enfermedad de las organizaciones rurales.',
      ),
      Leccion(
        titulo: 'Transparencia',
        contenido:
            'Informes financieros claros y periódicos, compras con cotizaciones y cuentas bancarias a nombre de la organización generan confianza.',
      ),
      Leccion(
        titulo: 'Resolución de conflictos',
        contenido:
            'Los conflictos son normales; lo importante es manejarlos: escuchar a las partes, basarse en los estatutos, dejar acuerdos por escrito y buscar mediación cuando sea necesario.',
      ),
      Leccion(
        titulo: 'Plan de trabajo',
        contenido:
            'Cada año la organización debe definir metas, actividades, responsables y fechas. Un plan de trabajo realista permite evaluar a la directiva y demostrar resultados.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Quién manda en una cooperativa?',
        opciones: ['La asamblea de socios', 'Solo el presidente', 'El contador'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué son importantes las actas?',
        opciones: [
          'Para adornar la oficina',
          'Porque documentan decisiones y son requisito legal y para proyectos',
          'No son importantes',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es la principal «enfermedad» de las organizaciones rurales?',
        opciones: ['El exceso de asambleas', 'Tener plan de trabajo', 'La apatía y poca participación de los socios'],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 9,
    icono: '🛒',
    titulo: 'Comercialización y acceso a mercados',
    descripcion: 'Vende mejor: calidad, volumen, negociación y clientes.',
    imagenUrl: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Calidad del producto',
        contenido:
            'El mercado paga la calidad: clasifica tu producto por tamaño y estado, cosecha en el punto correcto, usa empaques limpios y maneja con cuidado.',
      ),
      Leccion(
        titulo: 'Volumen asociativo',
        contenido:
            'Vender juntos cambia la negociación: la asociación puede reunir volumen, cumplir contratos y acceder a compradores formales. El volumen también reduce el costo de transporte.',
      ),
      Leccion(
        titulo: 'Negociación',
        contenido:
            'Llega a negociar conociendo tu costo de producción, el precio del mercado y tu volumen disponible. Pide acuerdos por escrito: producto, calidad, cantidad, precio, fecha y forma de pago.',
      ),
      Leccion(
        titulo: 'Presentación comercial',
        contenido: 'Etiqueta, marca y empaque cuentan tu historia. Una marca de la cooperativa diferencia tu producto y fideliza clientes.',
      ),
      Leccion(
        titulo: 'Redes sociales para vender',
        contenido:
            'WhatsApp Business y Facebook permiten mostrar el producto, recibir pedidos y mantener clientes informados sin costo. Publica fotos reales, precios claros y responde rápido.',
      ),
      Leccion(
        titulo: 'Búsqueda de compradores',
        contenido:
            'Explora varios canales: mercado local, intermediarios, agroindustria, exportadores, programas de compras públicas y ferias. Depender de un solo comprador es un riesgo.',
      ),
      Leccion(
        titulo: 'Trazabilidad',
        contenido:
            'Registrar qué se produjo, dónde, cuándo y con qué prácticas permite responder ante reclamos y acceder a mercados formales y de exportación.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Qué información debes conocer antes de negociar?',
        opciones: ['Solo el clima', 'Tu costo de producción, el precio de mercado y tu volumen', 'El nombre del comprador y nada más'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué conviene vender en asociación?',
        opciones: [
          'Se reúne volumen, se accede a mejores compradores y baja el costo de transporte',
          'Porque es obligatorio',
          'Para vender más barato',
        ],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'La trazabilidad sirve para:',
        opciones: [
          'Hacer el producto más pesado',
          'Nada en especial',
          'Demostrar el origen y las prácticas, y acceder a mercados formales',
        ],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 10,
    icono: '📑',
    titulo: 'Formulación de proyectos y acceso a fondos',
    descripcion: 'Convierte las necesidades de tu organización en proyectos financiables.',
    imagenUrl: 'https://images.unsplash.com/photo-1450133064473-71024230f91b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Identificación del problema',
        contenido: 'Todo proyecto nace de un problema bien definido: ¿qué pasa, a quiénes afecta, desde cuándo y qué causa lo provoca?',
      ),
      Leccion(
        titulo: 'Objetivos',
        contenido: 'El objetivo general dice el cambio que se busca; los específicos, los pasos para lograrlo. Deben ser medibles y realistas.',
      ),
      Leccion(
        titulo: 'Actividades',
        contenido: 'Las actividades son las acciones concretas para cumplir cada objetivo. Cada actividad debe tener responsable, fecha y costo.',
      ),
      Leccion(
        titulo: 'Presupuesto',
        contenido: 'Detalla cada costo con cotizaciones reales: materiales, mano de obra, equipos, transporte, imprevistos (5-10%).',
      ),
      Leccion(
        titulo: 'Cronograma',
        contenido: 'Ordena las actividades en el tiempo, mes a mes. Considera la época de lluvia y los ciclos de cultivo al programar.',
      ),
      Leccion(
        titulo: 'Documentos legales',
        contenido: 'Ten al día: personería jurídica, representación legal vigente, RTU, libros autorizados, solvencias y cuenta bancaria.',
      ),
      Leccion(
        titulo: 'Rendición de cuentas',
        contenido:
            'Ejecutar bien incluye informar bien: guarda facturas, fotografías y listados de participantes, e informa a los socios y al donante.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál de estos es un objetivo bien formulado?',
        opciones: [
          '«Mejorar la situación de los socios»',
          '«Reducir las pérdidas postcosecha del 30% al 10% en un año»',
          '«Conseguir apoyo»',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué porcentaje de imprevistos se recomienda en el presupuesto?',
        opciones: ['Entre 5% y 10%', '50%', 'Nada de imprevistos'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué se pierden muchos proyectos?',
        opciones: ['Por exceso de cotizaciones', 'Por tener cronograma', 'Por documentos legales vencidos o incompletos'],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 11,
    icono: '🐄',
    titulo: 'Fundamentos de la ganadería',
    descripcion: 'Importancia de la ganadería en Guatemala, anatomía básica de rumiantes y razas ganaderas.',
    imagenUrl: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Importancia socioeconómica',
        contenido:
            'La ganadería bovina es una actividad clave en el medio rural de Guatemala. Aproximadamente el 62.1% de las fincas con menos de cinco animales producen leche para autoconsumo.',
      ),
      Leccion(
        titulo: 'Razas de carne y leche',
        contenido:
            'Las razas bovinas se clasifican según su especialidad. Las razas de leche (Holstein, Jersey) se caracterizan por alta producción láctea. Las de carne (Angus, Brahman) por gran musculatura.',
      ),
      Leccion(
        titulo: 'Fisiología y digestión del rumiante',
        contenido:
            'Los bovinos son rumiantes con un estómago de cuatro compartimentos: rumen, retículo, omaso y abomaso. El rumen actúa como una gran tina de fermentación.',
      ),
      Leccion(
        titulo: 'Transición a la ganadería sostenible',
        contenido:
            'La ganadería convencional provoca degradación de suelos y deforestación. La ganadería sostenible busca prácticas que eleven la rentabilidad y protejan los recursos naturales.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Qué porcentaje de fincas pequeñas con menos de 5 animales produce leche para autoconsumo?',
        opciones: ['Aproximadamente 25%', 'Aproximadamente 62.1%', 'Aproximadamente 85%'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es la función del rumen en el aparato digestivo del ganado?',
        opciones: [
          'Fermentación de la fibra mediante microorganismos',
          'Absorción directa de agua únicamente',
          'Masticación primaria de los alimentos',
        ],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es el principal objetivo de la transición a la ganadería sostenible?',
        opciones: [
          'Incrementar el uso de agroquímicos',
          'Aumentar la productividad y reducir el impacto ambiental',
          'Disminuir el tamaño del hato a la mitad',
        ],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 12,
    icono: '🌱',
    titulo: 'Conceptos de sostenibilidad',
    descripcion: 'Principios de ganadería sostenible, huella ambiental y la estrategia ganadera nacional.',
    imagenUrl: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Sostenibilidad y ganadería',
        contenido:
            'La ganadería sostenible satisface las necesidades de carne y leche actuales sin comprometer los recursos naturales ni el bienestar de las generaciones futuras.',
      ),
      Leccion(
        titulo: 'Impactos de la ganadería convencional',
        contenido:
            'La ganadería tradicional provoca degradación del suelo por pisoteo, deforestación, contaminación de fuentes de agua y contribuye al cambio climático.',
      ),
      Leccion(
        titulo: 'Estrategia nacional de ganadería',
        contenido:
            'Guatemala cuenta con la Estrategia de Desarrollo Sostenible de la Ganadería Bovina, que impulsa la adopción de tecnologías sostenibles.',
      ),
      Leccion(
        titulo: 'Principios de buenas prácticas',
        contenido:
            'Las Buenas Prácticas Ganaderas (BPG) abarcan desde el manejo humanitario de los animales hasta la protección del agua y restauración del suelo.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál de los siguientes es un impacto de la ganadería convencional?',
        opciones: ['Aumento de la biodiversidad de insectos', 'Restauración natural del suelo', 'Deforestación y degradación de suelos'],
        respuesta: 2,
      ),
      PreguntaQuiz(
        pregunta: '¿En qué estrategia nacional se inserta el desarrollo ganadero sostenible de Guatemala?',
        opciones: ['Estrategia de Desarrollo Sostenible de la Ganadería Bovina', 'Estatuto Forestal Municipal', 'Plan de Riego por Gravedad'],
        respuesta: 0,
      ),
      PreguntaQuiz(
        pregunta: 'El concepto de sostenibilidad en ganadería busca:',
        opciones: [
          'Producir sin importar el agotamiento de recursos',
          'Satisfacer necesidades actuales sin comprometer las futuras',
          'Exclusivamente eliminar el uso de pasturas',
        ],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 13,
    icono: '📊',
    titulo: 'Planificación y control productivo',
    descripcion: 'Planificación de fincas, zonificación de áreas y el control financiero mediante registros.',
    imagenUrl: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Zonificación y plano de la finca',
        contenido:
            'Elaborar un mapa o plano actual de la finca para identificar áreas productivas, zonas de conservación de bosques, fuentes de agua y accesos.',
      ),
      Leccion(
        titulo: 'Registros productivos esenciales',
        contenido:
            'Llevar registros sistemáticos de partos, pesaje de leche o carne, mortalidad, servicios de inseminación y sanidad animal permite evaluar el rendimiento real.',
      ),
      Leccion(
        titulo: 'Presupuesto y costos de producción',
        contenido:
            'Anotar detalladamente todos los gastos de insumos, mano de obra y servicios de la finca ayuda a determinar el costo de producción por litro de leche o kilogramo de carne.',
      ),
      Leccion(
        titulo: 'Elaboración de planes de finca',
        contenido: 'Un plan de finca establece metas productivas claras a corto, mediano y largo plazo, programando inversiones paso a paso.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es el primer paso recomendado para realizar la zonificación de la finca?',
        opciones: ['Comprar reproductores de raza pura', 'Elaborar un mapa o plano de la finca', 'Aplicar fertilizantes químicos a todo el lote'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué es importante llevar registros productivos en el hato?',
        opciones: [
          'Para cumplir con el pago de impuestos únicamente',
          'Permite medir el rendimiento real de los animales y tomar decisiones informadas',
          'Para evitar que el ganado se escape de los potreros',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué variable es fundamental para calcular la rentabilidad real de la leche?',
        opciones: ['El color del pelaje del animal', 'El costo de producción por litro de leche', 'La distancia a la capital de la República'],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 14,
    icono: '💧',
    titulo: 'Diseño de potreros y manejo del agua',
    descripcion: 'Uso de aguadas mejoradas, bebederos eficientes y diseño de cercas eléctricas.',
    imagenUrl: 'https://images.unsplash.com/photo-1463123081488-729f60c1926d?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Aguadas mejoradas y suministro',
        contenido:
            'Ubicar el agua cerca del ganado genera un incremento productivo sustancial: de 90 a 135 gramos de peso extra al día y entre 0.55 y 1.1 litros de leche diarios por animal.',
      ),
      Leccion(
        titulo: 'Estimación de consumo diario',
        contenido:
            'Las vacas de leche consumen 38-110 litros/día; los bovinos en engorde 26-66 litros/día. En general, se estima un consumo equivalente al 8-12% del peso corporal a 25°C.',
      ),
      Leccion(
        titulo: 'Diseño y subdivisión de potreros',
        contenido:
            'Subdividir la finca permite controlar mejor la alimentación. Limitar el tiempo de ocupación en cada potrero (máximo 3 días) evita que los animales dañen el rebrote.',
      ),
      Leccion(
        titulo: 'Cercas eléctricas de bajo costo',
        contenido: 'Las cercas eléctricas son una herramienta eficiente y de menor costo que las tradicionales. Permiten delimitar potreros pequeños con facilidad.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuánto peso diario adicional puede ganar un bovino si el agua se ubica cerca de él?',
        opciones: ['Entre 10 y 20 gramos', 'De 90 a 135 gramos', 'Alrededor de 1 kilogramo'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué porcentaje del peso corporal consume en agua un animal a 25°C?',
        opciones: ['Entre 1% y 2%', 'Entre 8% y 12%', 'Más del 50%'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es el tiempo de ocupación máximo recomendado por potrero?',
        opciones: ['15 días', '3 días', '1 mes'],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 15,
    icono: '🌿',
    titulo: 'Pastoreo rotacional y manejo de pasturas',
    descripcion: 'Principios del pastoreo rotacional, capacidad de carga y periodos de descanso del pasto.',
    imagenUrl: 'https://images.unsplash.com/photo-1589923188900-85dae440342b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Principios del pastoreo rotacional',
        contenido:
            'El pastoreo rotacional intensivo consiste en utilizar el pasto en su punto óptimo de madurez nutricional, dividiendo el área en potreros pequeños y rotando sistemáticamente.',
      ),
      Leccion(
        titulo: 'Beneficios productivos y económicos',
        contenido: 'El pastoreo rotacional elimina hasta el 50% del desperdicio de pasto por pisoteo y aumenta la producción de carne y leche hasta un 20% por hectárea.',
      ),
      Leccion(
        titulo: 'Beneficios ambientales de la rotación',
        contenido:
            'Favorece la fertilidad natural del suelo gracias a una mejor distribución del estiércol. Al concentrar a los animales se liberan áreas para la regeneración de bosques.',
      ),
      Leccion(
        titulo: 'Períodos de descanso y ocupación',
        contenido:
            'Los animales no deben permanecer más de 3 días en un potrero para evitar el sobrepastoreo del rebrote. Los períodos de descanso varían de 25 a 45 días.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿En cuánto puede incrementarse la producción de carne o leche por hectárea mediante el pastoreo rotacional?',
        opciones: ['Hasta 5%', 'Hasta 20%', 'No incrementa, solo ahorra agua'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué no se debe permitir que el ganado permanezca más de 3 días en el mismo potrero?',
        opciones: [
          'Porque se aburren del paisaje',
          'Para evitar que consuman el rebrote tierno del pasto debilitando la raíz',
          'Porque el agua se ensucia',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál de los siguientes es un beneficio ambiental del pastoreo rotacional?',
        opciones: [
          'Uso excesivo de herbicidas',
          'Compactación severa del suelo',
          'Distribución uniforme del estiércol e incremento de carbono en el suelo',
        ],
        respuesta: 2,
      ),
    ],
  ),
  const Curso(
    id: 16,
    icono: '🌳',
    titulo: 'Sistemas silvopastoriles: cercas vivas y árboles dispersos',
    descripcion: 'Establecimiento de cercas vivas multiestrato y árboles dispersos para confort animal.',
    imagenUrl: 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Cercas vivas multiestrato',
        contenido:
            'Las cercas vivas combinan árboles y arbustos plantados en línea. Reemplazan postes de madera muerta y producen forraje rico en proteína. Aportan de 3.5 a 6 toneladas de materia seca por kilómetro al año.',
      ),
      Leccion(
        titulo: 'Importancia de la sombra en potreros',
        contenido:
            'La sombra es vital para reducir el estrés térmico. Se estima que una vaca requiere unos 5 m² de sombra, lo que se logra con unos 50 árboles para un hato de 100 vacas.',
      ),
      Leccion(
        titulo: 'Árboles dispersos en potreros',
        contenido:
            'Mantener árboles dispersos protege el suelo contra la erosión y mejora su fertilidad. Se recomiendan entre 25 y 40 árboles adultos por hectárea.',
      ),
      Leccion(
        titulo: 'Remoción de carbono y biodiversidad',
        contenido:
            'Los sistemas silvopastoriles capturan de 22 a 55 toneladas de CO2 equivalente por hectárea. Sirven de corredores biológicos para aves e insectos benéficos.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuánta materia seca de forraje puede producir al año un kilómetro de cerca viva multiestrato?',
        opciones: ['Entre 100 y 200 kg', 'De 3.5 a 6 toneladas', 'Más de 50 toneladas'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuántos árboles adultos por hectárea se recomiendan?',
        opciones: ['De 5 a 10 árboles', 'De 25 a 40 árboles', '150 árboles'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuánta sombra en metros cuadrados requiere una vaca lechera?',
        opciones: ['1 metro cuadrado', '5 metros cuadrados', '50 metros cuadrados'],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 17,
    icono: '🌾',
    titulo: 'Bancos de forraje, silaje y nutrición',
    descripcion: 'Establecimiento de bancos proteicos y energéticos, y técnicas de ensilaje.',
    imagenUrl: 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Bancos forrajeros proteicos',
        contenido:
            'Áreas sembradas con alta densidad de arbustos leguminosos (Leucaena, Erythrina, Gliricidia, Morera) para suministrar follaje de alta calidad durante la escasez.',
      ),
      Leccion(
        titulo: 'Bancos forrajeros energéticos',
        contenido:
            'Áreas destinadas a forrajes perennes ricos en carbohidratos (caña de azúcar, Napier) que aportan la energía necesaria para la fermentación ruminal.',
      ),
      Leccion(
        titulo: 'Ensilaje: Principios básicos',
        contenido:
            'El ensilaje es un método de conservación de forrajes verdes por fermentación anaerobia. El éxito radica en cosechar en el punto óptimo, picar finamente, compactar y sellar herméticamente.',
      ),
      Leccion(
        titulo: 'Fases del silo y precauciones',
        contenido:
            'El proceso de ensilaje tiene cuatro fases: aeróbica, fermentación láctica, estabilización y deterioro. La exposición al aire acelera el crecimiento de mohos tóxicos.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Qué porcentaje mínimo de proteína cruda aportan los bancos forrajeros proteicos?',
        opciones: ['Menos del 5%', 'Al menos 15%', 'Exactamente 50%'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es el factor clave para evitar pérdidas de calidad en el ensilaje?',
        opciones: [
          'Agregar grandes cantidades de agua',
          'Exponer el silo al sol directo',
          'Lograr una compactación firme y un sellado 100% hermético sin oxígeno',
        ],
        respuesta: 2,
      ),
      PreguntaQuiz(
        pregunta: '¿En qué fase del proceso de ensilaje se estabiliza el alimento?',
        opciones: ['Durante la fase aeróbica inicial', 'En la fermentación láctica donde el pH baja', 'Solo después de abrirlo'],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 18,
    icono: '🧬',
    titulo: 'Mejoramiento genético y razas',
    descripcion: 'Criterios para selección de reproductores y evaluación de razas en Guatemala.',
    imagenUrl: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Selección de reproductores',
        contenido:
            'Selecciona toros y vacas basándote en registros de producción, conformación corporal y adaptación al clima local. Un buen reproductor mejora el hato en menos de 3 generaciones.',
      ),
      Leccion(
        titulo: 'Razas adaptadas al trópico',
        contenido:
            'En Guatemala predominan las razas Brahman (B. indicus) y sus cruces con Angus, Simmental o Holstein. La elección debe basarse en el sistema productivo y el mercado objetivo.',
      ),
      Leccion(
        titulo: 'Inseminación artificial',
        contenido:
            'La IA permite acceder a genética superior sin comprar el toro. Requiere buena detección de celo, semen de calidad certificada y un técnico capacitado. La tasa de preñez promedio es del 50-65%.',
      ),
      Leccion(
        titulo: 'Evaluación del progreso genético',
        contenido:
            'Lleva registros de producción por generación: producción de leche, ganancia diaria de peso, días al primer parto. El mejoramiento genético es lento pero acumulativo.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es la tasa de preñez promedio con inseminación artificial en ganado bovino?',
        opciones: ['10-20%', '50-65%', '90-95%'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué raza bovina de origen Bos indicus predomina en Guatemala?',
        opciones: ['Angus', 'Brahman', 'Holstein'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿En cuántas generaciones puede mejorar notablemente el hato con buenos reproductores?',
        opciones: ['Menos de 3 generaciones', '10 a 15 generaciones', 'Solo en la siguiente generación'],
        respuesta: 0,
      ),
    ],
  ),
  const Curso(
    id: 19,
    icono: '⏱️',
    titulo: 'Sanidad animal y calendario sanitario',
    descripcion: 'Vacunas, desparasitación, bioseguridad y reconocimiento de enfermedades comunes.',
    imagenUrl: 'https://images.unsplash.com/photo-1570042225831-d9b085d44e20?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Calendario de vacunación',
        contenido:
            'Las vacunas obligatorias en Guatemala incluyen la fiebre aftosa (donde aplica), brucelosis y carbunco sintomático. Coordina con MAGA el calendario oficial para tu región.',
      ),
      Leccion(
        titulo: 'Desparasitación estratégica',
        contenido:
            'Combina la desparasitación interna (antiparasitarios orales o inyectables) con la externa (acaricidas). Evita la resistencia rotando principios activos cada ciclo.',
      ),
      Leccion(
        titulo: 'Bioseguridad en la finca',
        contenido:
            'Aisla los animales nuevos por 21 días antes de integrarlos al hato. Desinfecta vehículos y equipos que entran a la finca. Mantén un registro de movimientos del ganado.',
      ),
      Leccion(
        titulo: 'Enfermedades comunes y señales de alerta',
        contenido:
            'Conoce las señales de brucelosis, mastitis, BVD y fiebre de transporte. Temperatura corporal normal del bovino: 38-39°C. Más de 40°C indica fiebre y urgencia veterinaria.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuántos días debe estar en cuarentena un animal nuevo antes de integrarse al hato?',
        opciones: ['1 día', '21 días', '60 días'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es la temperatura corporal normal del bovino?',
        opciones: ['36-37°C', '38-39°C', '41-42°C'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Por qué se recomienda rotar principios activos en la desparasitación?',
        opciones: ['Para ahorrar dinero', 'Para evitar el desarrollo de resistencia en los parásitos', 'Porque todos son iguales'],
        respuesta: 1,
      ),
    ],
  ),
  const Curso(
    id: 20,
    icono: '📈',
    titulo: 'Economía de la finca ganadera',
    descripcion: 'Indicadores de rentabilidad, costos de producción y acceso a financiamiento.',
    imagenUrl: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Costo de producción de leche',
        contenido:
            'Calcula el costo por litro sumando: alimentación, sanidad, mano de obra, depreciación de instalaciones y equipos. En Guatemala, costos menores a Q2.50/litro son competitivos.',
      ),
      Leccion(
        titulo: 'Indicadores clave de la finca',
        contenido: 'Monitorea: litros/vaca/día, días de lactancia, intervalo entre partos (óptimo: 12-14 meses), porcentaje de mortalidad y natalidad anual.',
      ),
      Leccion(
        titulo: 'Opciones de financiamiento',
        contenido:
            'Explora: crédito bancario (Banrural, BANECO), fideicomisos de MAGA, programas de AGEXPORT, cooperativas de ahorro y crédito, y fondos de la cooperación internacional.',
      ),
      Leccion(
        titulo: 'Análisis de rentabilidad',
        contenido:
            'La rentabilidad de la finca ganadera se mejora aumentando la producción, reduciendo costos o mejorando los precios de venta. Las tres palancas funcionan mejor en conjunto.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es el intervalo entre partos óptimo para una vaca lechera?',
        opciones: ['6-8 meses', '12-14 meses', '18-24 meses'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál de estos es un indicador clave de productividad de la finca lechera?',
        opciones: ['El color de la pintura del establo', 'Litros/vaca/día y días de lactancia', 'El número de caminos de acceso'],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué institución guatemalteca ofrece créditos especializados para el sector agropecuario?',
        opciones: ['Banrural', 'SAT', 'IGSS'],
        respuesta: 0,
      ),
    ],
  ),
  const Curso(
    id: 21,
    icono: '🏆',
    titulo: 'Evaluación final: Ganadería Sostenible',
    descripcion: 'Evaluación integral del programa piloto de Ganadería Sostenible CONADEA.',
    imagenUrl: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      Leccion(
        titulo: 'Repaso de conceptos clave',
        contenido:
            'Revisamos los conceptos fundamentales del programa: sostenibilidad, pastoreo rotacional, sistemas silvopastoriles, manejo del agua, nutrición animal y economía de la finca.',
      ),
      Leccion(
        titulo: 'Aplicación práctica en la finca',
        contenido:
            'Cada técnica aprendida tiene un impacto medible en tu finca. Establece metas concretas: reducir costos en X%, aumentar producción en Y litros/día, implementar X área de silvopastoril.',
      ),
      Leccion(
        titulo: 'Red de apoyo y seguimiento',
        contenido:
            'El programa CONADEA AgroIA incluye visitas de seguimiento, grupos de WhatsApp por zona y acceso a la red de técnicos validadores para resolver dudas en campo.',
      ),
      Leccion(
        titulo: 'Próximos pasos',
        contenido:
            'Comparte lo aprendido con tu asociación, aplica al menos 3 prácticas en tu finca este ciclo productivo y documenta los cambios. Tu experiencia ayudará a mejorar el programa.',
      ),
    ],
    quiz: [
      PreguntaQuiz(
        pregunta: '¿Cuál es el principal beneficio del pastoreo rotacional bien implementado?',
        opciones: [
          'Solo mejora el paisaje de la finca',
          'Aumenta la producción hasta un 20% por hectárea y reduce el sobrepastoreo',
          'Elimina la necesidad de agua en los potreros',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Qué elemento es clave para el éxito de los sistemas silvopastoriles?',
        opciones: [
          'Elegir solo árboles ornamentales',
          'Combinar árboles forrajeros con pastos y ganadería de manera planificada',
          'Eliminar todos los pastos del potrero',
        ],
        respuesta: 1,
      ),
      PreguntaQuiz(
        pregunta: '¿Cuál es la actitud correcta frente a los nuevos conocimientos del programa?',
        opciones: [
          'Guardarlos para uso personal exclusivamente',
          'Compartirlos con la asociación y aplicarlos documentando los cambios',
          'Esperar que el técnico los implemente por nosotros',
        ],
        respuesta: 1,
      ),
    ],
  ),
];

Curso cursoPorId(int id) => cursos.firstWhere((c) => c.id == id);
