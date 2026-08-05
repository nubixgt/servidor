/**
 * DATOS LOCALES TEMPORALES - MAGA AgroIA Aula Virtual
 *
 * ⚠️ NOTA: Este archivo es TEMPORAL y solo sirve para el desarrollo del Frontend.
 * Cuando el Backend esté listo, este archivo se elimina y los datos vendrán del API.
 * Los servicios en src/services/ serán los que hagan las llamadas al backend.
 */

// ============ MÓDULOS / CURSOS ============
export const MODULOS = [
  {
    id: 1, ic: '📱',
    t: 'Uso de WhatsApp AgroIA',
    d: 'Aprende a usar el asistente AgroIA para resolver problemas de tu finca.',
    img: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Cómo enviar consultas', c: 'Escribe tu consulta al número oficial de AgroIA describiendo el cultivo o animal, el problema que observas y desde cuándo ocurre. Entre más clara sea tu consulta, mejor será la orientación que recibirás.' },
      { t: 'Cómo tomar fotos útiles', c: 'Toma las fotos con buena luz natural, sin sombra encima. Envía una foto de cerca del síntoma y otra de lejos para ver toda la planta o el animal. Limpia el lente antes de fotografiar.' },
      { t: 'Cómo grabar audios claros', c: 'Graba en un lugar sin mucho ruido, habla despacio y menciona: qué cultivo o animal es, qué problema observas, hace cuánto empezó y qué has aplicado. Un audio de 30 a 60 segundos es suficiente.' },
      { t: 'Cómo compartir tu ubicación', c: 'Compartir tu ubicación ayuda a que las recomendaciones tomen en cuenta tu zona y clima. En WhatsApp toca el clip 📎, elige «Ubicación» y luego «Enviar mi ubicación actual».' },
      { t: 'Cómo interpretar las recomendaciones', c: 'AgroIA da una orientación inicial, no un diagnóstico definitivo. Lee con atención las posibles causas y los pasos sugeridos. Si dice «este caso necesita revisión técnica», espera al técnico.' },
      { t: 'Cuándo solicitar revisión técnica', c: 'Pide revisión de un técnico cuando el problema avanza rápido, hay muchos animales o plantas afectadas, hay riesgo de perder la cosecha, o la orientación automática no resolvió el caso.' }
    ],
    quiz: [
      { q: '¿Cuál es la mejor manera de enviar una foto para diagnóstico?', o: ['Una sola foto de lejos y con sombra', 'Una foto de cerca del síntoma y otra de toda la planta, con buena luz', 'Una captura de pantalla de otra foto'], r: 1 },
      { q: '¿Qué debe incluir un buen audio de consulta?', o: ['Solo un saludo', 'Música de fondo para que sea agradable', 'Cultivo o animal, problema, tiempo del problema y qué se ha hecho'], r: 2 },
      { q: '¿Cuándo debes pedir revisión de un técnico humano?', o: ['Cuando el problema avanza rápido o hay riesgo de pérdida severa', 'Nunca, la IA siempre es suficiente', 'Solo en diciembre'], r: 0 }
    ]
  },
  {
    id: 2, ic: '🌿',
    t: 'Diagnóstico básico de cultivos',
    d: 'Identifica síntomas comunes en hojas, frutos y plantas.',
    img: 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Síntomas en hojas', c: 'Las hojas hablan: el amarillamiento general puede indicar falta de nitrógeno; manchas con anillos suelen ser hongos; bordes quemados pueden ser exceso de sal o fertilizante; y hojas enrolladas pueden indicar virus.' },
      { t: 'Síntomas en frutos', c: 'Revisa pudriciones, perforaciones, deformaciones y manchas. Una perforación con excremento indica gusano; pudrición blanda con mal olor suele ser bacteria; manchas hundidas oscuras suelen ser antracnosis.' },
      { t: 'Plagas comunes', c: 'Aprende a reconocer gusano cogollero en maíz, mosca blanca en hortalizas, pulgones, trips y gallina ciega. Revisa el envés de las hojas: ahí se esconden la mayoría de plagas pequeñas.' },
      { t: 'Enfermedades frecuentes', c: 'Los hongos avanzan con humedad y se ven como manchas, polvillos o mohos. Las bacterias producen pudriciones acuosas y mal olor. Los virus deforman y amarillan la planta en patrones de mosaico.' },
      { t: 'Deficiencias nutricionales', c: 'Nitrógeno: hojas viejas amarillas. Fósforo: tonos morados y plantas pequeñas. Potasio: bordes quemados en hojas viejas. Las deficiencias aparecen parejas en el lote.' },
      { t: 'Estrés por agua', c: 'La falta de agua marchita la planta al mediodía. El exceso de agua amarilla la planta, pudre raíces y favorece hongos. Revisa el suelo a 10 cm de profundidad antes de regar.' },
      { t: 'Prevención y monitoreo', c: 'Camina tu parcela al menos dos veces por semana en forma de zigzag, revisa 5 plantas por punto y anota lo que ves. Detectar a tiempo un foco pequeño es más barato que controlar todo el lote.' }
    ],
    quiz: [
      { q: '¿Dónde se esconden la mayoría de plagas pequeñas?', o: ['En el envés (parte de abajo) de las hojas', 'Solo en las flores', 'En el tallo principal únicamente'], r: 0 },
      { q: 'Si las hojas viejas están amarillas de manera pareja en todo el lote, lo más probable es:', o: ['Un virus', 'Falta de nitrógeno', 'Daño por pájaros'], r: 1 },
      { q: '¿Cada cuánto se recomienda monitorear la parcela?', o: ['Una vez al mes', 'Solo al sembrar', 'Al menos dos veces por semana'], r: 2 }
    ]
  },
  {
    id: 3, ic: '🐛',
    t: 'Manejo integrado de plagas y enfermedades',
    d: 'Previene y controla con métodos responsables y seguros.',
    img: 'https://images.unsplash.com/photo-1600132806370-bf17e65e942f?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Monitoreo constante', c: 'El manejo integrado (MIP) empieza por observar. Usa trampas amarillas con pegamento, revisa el suelo y lleva un registro semanal. Solo se controla bien lo que se conoce.' },
      { t: 'Prevención primero', c: 'Usa semilla sana, variedades resistentes, rota cultivos, elimina rastrojos enfermos y desinfecta herramientas. La mayoría de problemas graves se evitan antes de sembrar.' },
      { t: 'Control cultural', c: 'Distancias de siembra adecuadas para ventilación, eliminación de malezas hospederas, podas sanitarias y manejo correcto del riego para no favorecer hongos.' },
      { t: 'Control biológico', c: 'Aprovecha a los aliados naturales: mariquitas y crisopas comen pulgones; avispas parasitan gusanos; hongos como Beauveria y Trichoderma controlan plagas. Protege a estos aliados.' },
      { t: 'Uso responsable de productos', c: 'Usa solo productos registrados, respeta la dosis de la etiqueta, usa equipo de protección y respeta los días de espera antes de cosechar. Nunca apliques sin diagnóstico confirmado.' },
      { t: 'Registro de aplicaciones', c: 'Anota fecha, producto, dosis, cultivo y resultado de cada aplicación. Este registro evita repetir productos que no funcionaron y previene resistencia de las plagas.' },
      { t: 'Cuándo consultar a un técnico', c: 'Consulta cuando la plaga no cede después de dos manejos, cuando no logres identificar el problema o cuando pienses usar productos de alta toxicidad.' }
    ],
    quiz: [
      { q: '¿Cuál es el primer paso del manejo integrado de plagas?', o: ['Aplicar el producto más fuerte', 'Monitorear y conocer el problema', 'Quemar el lote'], r: 1 },
      { q: '¿Qué insecto es un aliado natural contra los pulgones?', o: ['La mariquita', 'La mosca blanca', 'El gusano cogollero'], r: 0 },
      { q: 'Antes de aplicar un producto químico siempre debes:', o: ['Aplicar el doble de dosis para asegurar', 'Confirmar el diagnóstico, leer la etiqueta y usar protección', 'Aplicar al mediodía con viento'], r: 1 }
    ]
  },
  {
    id: 4, ic: '🪱',
    t: 'Nutrición y manejo de suelos',
    d: 'Un suelo vivo y fértil es la base de toda buena cosecha.',
    img: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Fertilidad del suelo', c: 'Un suelo fértil tiene buena estructura, materia orgánica, vida y nutrientes disponibles. La fertilidad no es solo aplicar fertilizante: es manejar el suelo como un organismo vivo.' },
      { t: 'Materia orgánica', c: 'Incorpora rastrojos, abonos verdes, compost o lombricompost. La materia orgánica retiene humedad, alimenta los microorganismos y mejora la estructura del suelo.' },
      { t: 'Conservación de suelos', c: 'En laderas usa barreras vivas, curvas a nivel, terrazas y acequias. Evita la quema: destruye la materia orgánica y deja el suelo desnudo frente a la lluvia.' },
      { t: 'Análisis de suelo', c: 'Un análisis de laboratorio te dice qué nutrientes tiene tu suelo y cuáles faltan. Toma submuestras en zigzag a 20 cm de profundidad, mézclalas y envía 1 libra al laboratorio.' },
      { t: 'Fertilización responsable', c: 'Aplica el nutriente correcto, en la dosis correcta, en el momento correcto y en el lugar correcto (las 4 C). Fracciona el nitrógeno en 2 o 3 aplicaciones.' },
      { t: 'Control de la erosión', c: 'Mantén el suelo siempre cubierto con rastrojo o cultivos de cobertura. Un suelo desnudo pierde hasta 40 toneladas por hectárea al año en laderas.' },
      { t: 'Manejo de humedad', c: 'El acolchado (mulch), las coberturas y la materia orgánica guardan agua en el suelo. En época seca, cada práctica que conserve humedad puede ser la diferencia entre cosechar o perder.' }
    ],
    quiz: [
      { q: '¿Qué aporta la materia orgánica al suelo?', o: ['Solo color oscuro', 'Retiene humedad, alimenta la vida del suelo y mejora la estructura', 'Aumenta la erosión'], r: 1 },
      { q: '¿Por qué no se recomienda la quema de rastrojos?', o: ['Porque destruye la materia orgánica y deja el suelo expuesto', 'Porque el fuego enfría el suelo', 'Porque atrae lombrices'], r: 0 },
      { q: 'Las «4 C» de la fertilización responsable son: nutriente correcto, dosis correcta...', o: ['...color correcto y costal correcto', '...momento correcto y lugar correcto', '...camión correcto y caja correcta'], r: 1 }
    ]
  },
  {
    id: 5, ic: '🐄',
    t: 'Ganadería sostenible',
    d: 'Mejora la nutrición, sanidad y bienestar de tu hato.',
    img: 'https://images.unsplash.com/photo-1570042225831-d9b085d44e20?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Condición corporal', c: 'La condición corporal se evalúa de 1 (muy flaco) a 5 (obeso). Lo ideal es mantener el hato entre 3 y 3.5. Una vaca flaca produce menos leche, se preña menos y se enferma más.' },
      { t: 'Nutrición animal', c: 'El animal necesita energía, proteína, minerales y agua. Planifica ensilaje, heno, bloques minerales y suplementos para la época seca. La sal mineralizada debe estar disponible todo el año.' },
      { t: 'Manejo de pasturas', c: 'Divide el potrero en apartos y rota el ganado: deja descansar el pasto de 25 a 35 días según la especie. El sobrepastoreo degrada el pasto y compacta el suelo.' },
      { t: 'Agua y sombra', c: 'Una vaca lechera puede tomar de 40 a 80 litros de agua al día. Sin agua limpia y sombra, el animal come menos y produce menos. Los árboles en potreros dan sombra, forraje y mejoran el suelo.' },
      { t: 'Bienestar animal', c: 'Animales sin estrés producen más: evita gritos y golpes, mantén corrales sin lodo excesivo ni piedras, y maneja con calma en el embudo y la manga.' },
      { t: 'Sanidad preventiva', c: 'Cumple el calendario de vacunación y desparasitación, revisa parásitos externos y aísla animales enfermos. Prevenir cuesta menos que curar.' },
      { t: 'Sistemas silvopastoriles', c: 'Combinar pastos con árboles y arbustos forrajeros da más alimento por área, sombra, fija nitrógeno y hace la finca más resistente a la sequía.' }
    ],
    quiz: [
      { q: '¿Cuál es la condición corporal ideal para el hato?', o: ['Entre 1 y 2', 'Entre 3 y 3.5', '5 en todos los animales'], r: 1 },
      { q: '¿Cuántos días de descanso necesita un potrero en rotación?', o: ['De 25 a 35 días según la especie de pasto', '2 días', '1 año completo'], r: 0 },
      { q: '¿Qué beneficios dan los árboles forrajeros en un sistema silvopastoril?', o: ['Solo adornan la finca', 'Quitan espacio al pasto', 'Sombra, forraje, nitrógeno y resistencia a la sequía'], r: 2 }
    ]
  },
  {
    id: 6, ic: '💧',
    t: 'Manejo de agua y adaptación climática',
    d: 'Prepara tu finca para la sequía y el exceso de lluvia.',
    img: 'https://images.unsplash.com/photo-1463123081488-729f60c1926d?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Cosecha de agua', c: 'Aprovecha la lluvia: techos con canaletas hacia toneles o cisternas, reservorios y aljibes. Cada metro cuadrado de techo puede cosechar cerca de 1 litro por cada milímetro de lluvia.' },
      { t: 'Riego eficiente', c: 'El riego por goteo usa hasta 60% menos agua que el riego por gravedad. Riega temprano en la mañana o al atardecer para reducir la evaporación. Más agua no siempre es más cosecha.' },
      { t: 'Manejo en sequía', c: 'En años secos: siembra variedades de ciclo corto y tolerantes, reduce el área a la que sí puedes dar agua, usa acolchado y prioriza el agua para los cultivos y animales de mayor valor.' },
      { t: 'Manejo del exceso de lluvia', c: 'Construye drenajes y acequias antes del invierno, siembra en camellones altos en zonas que se inundan y refuerza el control preventivo de hongos.' },
      { t: 'Conservación de humedad', c: 'Acolchado, coberturas vivas, materia orgánica y barreras rompevientos reducen la pérdida de agua del suelo. Estas prácticas cuestan poco y alargan la humedad disponible varias semanas.' },
      { t: 'Alertas climáticas', c: 'Atiende los boletines del INSIVUMEH y las alertas de AgroIA: pronóstico de canícula, lluvias intensas o heladas. Planificar con el pronóstico evita sembrar en el momento equivocado.' },
      { t: 'Planificación por temporada', c: 'Haz un calendario de tu finca: qué siembras en primera, qué en postrera, cuándo vacunas, cuándo haces ensilaje. Una finca planificada por temporada sufre menos los golpes del clima.' }
    ],
    quiz: [
      { q: '¿Cuánta agua puede ahorrar el riego por goteo frente al riego por gravedad?', o: ['Hasta 60% menos', 'No ahorra nada', 'Solo 1%'], r: 0 },
      { q: '¿Cuál es el mejor momento del día para regar?', o: ['Al mediodía con pleno sol', 'Temprano en la mañana o al atardecer', 'A cualquier hora da igual'], r: 1 },
      { q: 'Ante pronóstico de lluvias intensas conviene:', o: ['No hacer nada', 'Quitar todos los drenajes', 'Preparar drenajes y reforzar la prevención de hongos'], r: 2 }
    ]
  },
  {
    id: 7, ic: '🧮',
    t: 'Administración rural y costos',
    d: 'Lleva las cuentas de tu finca y conoce tu rentabilidad.',
    img: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Registro de ingresos y gastos', c: 'Anota todo lo que entra y sale: ventas, compras de insumos, jornales, transporte. Sin registros, no se sabe si la finca gana o pierde; se administra a ciegas.' },
      { t: 'Costos de producción', c: 'Suma todo lo que cuesta producir: semilla, fertilizante, jornales, transporte y alquileres. Divide el total entre los quintales producidos: ese es tu costo por quintal.' },
      { t: 'Precio de venta', c: 'Vende conociendo tu costo: si producir un quintal cuesta Q150 y lo vendes a Q140, pierdes aunque parezca que entra dinero. Infórmate del precio de mercado antes de vender.' },
      { t: 'Control de inventarios', c: 'Lleva control de insumos, herramientas, cosecha almacenada y animales. Saber qué tienes evita compras dobles, pérdidas por vencimiento y robos que pasan desapercibidos.' },
      { t: 'Rentabilidad', c: 'Rentabilidad = ingresos menos costos. Calcula por cultivo o actividad: a veces un cultivo querido da pérdida y otro pequeño da la ganancia. Los números ayudan a decidir.' },
      { t: 'Presupuesto', c: 'Antes de cada ciclo, planifica cuánto vas a gastar y cuándo. Un presupuesto evita quedarse sin dinero a mitad del ciclo, justo cuando el cultivo más necesita inversión.' },
      { t: 'Control financiero', c: 'Compara cada mes lo planificado contra lo real, separa el dinero de la finca del dinero de la casa y guarda una reserva para emergencias.' }
    ],
    quiz: [
      { q: '¿Cómo se calcula el costo por quintal?', o: ['Total de costos dividido entre los quintales producidos', 'El precio que ponga el comprador', 'El doble del precio de la semilla'], r: 0 },
      { q: 'Si producir un quintal cuesta Q150 y lo vendes a Q140:', o: ['Ganas Q10', 'Pierdes Q10 por quintal', 'No pasa nada'], r: 1 },
      { q: 'Una buena práctica financiera es:', o: ['Mezclar el dinero de la finca con el de la casa', 'No anotar los gastos pequeños', 'Separar las cuentas y guardar una reserva para emergencias'], r: 2 }
    ]
  },
  {
    id: 8, ic: '🤝',
    t: 'Organización asociativa y cooperativa',
    d: 'Fortalece tu asociación con buenas prácticas de gestión.',
    img: 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Funciones de la organización', c: 'Una asociación o cooperativa sirve para lograr juntos lo que no se logra solo: comprar insumos más baratos, vender en volumen, acceder a proyectos y asistencia técnica.' },
      { t: 'Junta directiva', c: 'La junta directiva administra por mandato de la asamblea, no es dueña de la organización. Debe rendir cuentas, convocar asambleas y renovar cargos en los plazos establecidos.' },
      { t: 'Actas y documentos', c: 'Toda decisión importante debe quedar en acta. Las actas al día y los libros autorizados son requisito para optar a proyectos y mantener la personería jurídica vigente.' },
      { t: 'Participación de los socios', c: 'Una organización es fuerte cuando los socios participan: asisten a asambleas, opinan, fiscalizan y asumen cargos. La apatía de los socios es la principal enfermedad de las organizaciones rurales.' },
      { t: 'Transparencia', c: 'Informes financieros claros y periódicos, compras con cotizaciones y cuentas bancarias a nombre de la organización generan confianza.' },
      { t: 'Resolución de conflictos', c: 'Los conflictos son normales; lo importante es manejarlos: escuchar a las partes, basarse en los estatutos, dejar acuerdos por escrito y buscar mediación cuando sea necesario.' },
      { t: 'Plan de trabajo', c: 'Cada año la organización debe definir metas, actividades, responsables y fechas. Un plan de trabajo realista permite evaluar a la directiva y demostrar resultados.' }
    ],
    quiz: [
      { q: '¿Quién manda en una cooperativa?', o: ['La asamblea de socios', 'Solo el presidente', 'El contador'], r: 0 },
      { q: '¿Por qué son importantes las actas?', o: ['Para adornar la oficina', 'Porque documentan decisiones y son requisito legal y para proyectos', 'No son importantes'], r: 1 },
      { q: '¿Cuál es la principal «enfermedad» de las organizaciones rurales?', o: ['El exceso de asambleas', 'Tener plan de trabajo', 'La apatía y poca participación de los socios'], r: 2 }
    ]
  },
  {
    id: 9, ic: '🛒',
    t: 'Comercialización y acceso a mercados',
    d: 'Vende mejor: calidad, volumen, negociación y clientes.',
    img: 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Calidad del producto', c: 'El mercado paga la calidad: clasifica tu producto por tamaño y estado, cosecha en el punto correcto, usa empaques limpios y maneja con cuidado.' },
      { t: 'Volumen asociativo', c: 'Vender juntos cambia la negociación: la asociación puede reunir volumen, cumplir contratos y acceder a compradores formales. El volumen también reduce el costo de transporte.' },
      { t: 'Negociación', c: 'Llega a negociar conociendo tu costo de producción, el precio del mercado y tu volumen disponible. Pide acuerdos por escrito: producto, calidad, cantidad, precio, fecha y forma de pago.' },
      { t: 'Presentación comercial', c: 'Etiqueta, marca y empaque cuentan tu historia. Una marca de la cooperativa diferencia tu producto y fideliza clientes.' },
      { t: 'Redes sociales para vender', c: 'WhatsApp Business y Facebook permiten mostrar el producto, recibir pedidos y mantener clientes informados sin costo. Publica fotos reales, precios claros y responde rápido.' },
      { t: 'Búsqueda de compradores', c: 'Explora varios canales: mercado local, intermediarios, agroindustria, exportadores, programas de compras públicas y ferias. Depender de un solo comprador es un riesgo.' },
      { t: 'Trazabilidad', c: 'Registrar qué se produjo, dónde, cuándo y con qué prácticas permite responder ante reclamos y acceder a mercados formales y de exportación.' }
    ],
    quiz: [
      { q: '¿Qué información debes conocer antes de negociar?', o: ['Solo el clima', 'Tu costo de producción, el precio de mercado y tu volumen', 'El nombre del comprador y nada más'], r: 1 },
      { q: '¿Por qué conviene vender en asociación?', o: ['Se reúne volumen, se accede a mejores compradores y baja el costo de transporte', 'Porque es obligatorio', 'Para vender más barato'], r: 0 },
      { q: 'La trazabilidad sirve para:', o: ['Hacer el producto más pesado', 'Nada en especial', 'Demostrar el origen y las prácticas, y acceder a mercados formales'], r: 2 }
    ]
  },
  {
    id: 10, ic: '📑',
    t: 'Formulación de proyectos y acceso a fondos',
    d: 'Convierte las necesidades de tu organización en proyectos financiables.',
    img: 'https://images.unsplash.com/photo-1450133064473-71024230f91b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Identificación del problema', c: 'Todo proyecto nace de un problema bien definido: ¿qué pasa, a quiénes afecta, desde cuándo y qué causa lo provoca?' },
      { t: 'Objetivos', c: 'El objetivo general dice el cambio que se busca; los específicos, los pasos para lograrlo. Deben ser medibles y realistas.' },
      { t: 'Actividades', c: 'Las actividades son las acciones concretas para cumplir cada objetivo. Cada actividad debe tener responsable, fecha y costo.' },
      { t: 'Presupuesto', c: 'Detalla cada costo con cotizaciones reales: materiales, mano de obra, equipos, transporte, imprevistos (5-10%).' },
      { t: 'Cronograma', c: 'Ordena las actividades en el tiempo, mes a mes. Considera la época de lluvia y los ciclos de cultivo al programar.' },
      { t: 'Documentos legales', c: 'Ten al día: personería jurídica, representación legal vigente, RTU, libros autorizados, solvencias y cuenta bancaria.' },
      { t: 'Rendición de cuentas', c: 'Ejecutar bien incluye informar bien: guarda facturas, fotografías y listados de participantes, e informa a los socios y al donante.' }
    ],
    quiz: [
      { q: '¿Cuál de estos es un objetivo bien formulado?', o: ['«Mejorar la situación de los socios»', '«Reducir las pérdidas postcosecha del 30% al 10% en un año»', '«Conseguir apoyo»'], r: 1 },
      { q: '¿Qué porcentaje de imprevistos se recomienda en el presupuesto?', o: ['Entre 5% y 10%', '50%', 'Nada de imprevistos'], r: 0 },
      { q: '¿Por qué se pierden muchos proyectos?', o: ['Por exceso de cotizaciones', 'Por tener cronograma', 'Por documentos legales vencidos o incompletos'], r: 2 }
    ]
  },
  // Módulos adicionales (11-21) del programa piloto de Ganadería
  {
    id: 11, ic: '🐄',
    t: 'Fundamentos de la ganadería',
    d: 'Importancia de la ganadería en Guatemala, anatomía básica de rumiantes y razas ganaderas.',
    img: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Importancia socioeconómica', c: 'La ganadería bovina es una actividad clave en el medio rural de Guatemala. Aproximadamente el 62.1% de las fincas con menos de cinco animales producen leche para autoconsumo.' },
      { t: 'Razas de carne y leche', c: 'Las razas bovinas se clasifican según su especialidad. Las razas de leche (Holstein, Jersey) se caracterizan por alta producción láctea. Las de carne (Angus, Brahman) por gran musculatura.' },
      { t: 'Fisiología y digestión del rumiante', c: 'Los bovinos son rumiantes con un estómago de cuatro compartimentos: rumen, retículo, omaso y abomaso. El rumen actúa como una gran tina de fermentación.' },
      { t: 'Transición a la ganadería sostenible', c: 'La ganadería convencional provoca degradación de suelos y deforestación. La ganadería sostenible busca prácticas que eleven la rentabilidad y protejan los recursos naturales.' }
    ],
    quiz: [
      { q: '¿Qué porcentaje de fincas pequeñas con menos de 5 animales produce leche para autoconsumo?', o: ['Aproximadamente 25%', 'Aproximadamente 62.1%', 'Aproximadamente 85%'], r: 1 },
      { q: '¿Cuál es la función del rumen en el aparato digestivo del ganado?', o: ['Fermentación de la fibra mediante microorganismos', 'Absorción directa de agua únicamente', 'Masticación primaria de los alimentos'], r: 0 },
      { q: '¿Cuál es el principal objetivo de la transición a la ganadería sostenible?', o: ['Incrementar el uso de agroquímicos', 'Aumentar la productividad y reducir el impacto ambiental', 'Disminuir el tamaño del hato a la mitad'], r: 1 }
    ]
  },
  {
    id: 12, ic: '🌱',
    t: 'Conceptos de sostenibilidad',
    d: 'Principios de ganadería sostenible, huella ambiental y la estrategia ganadera nacional.',
    img: 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Sostenibilidad y ganadería', c: 'La ganadería sostenible satisface las necesidades de carne y leche actuales sin comprometer los recursos naturales ni el bienestar de las generaciones futuras.' },
      { t: 'Impactos de la ganadería convencional', c: 'La ganadería tradicional provoca degradación del suelo por pisoteo, deforestación, contaminación de fuentes de agua y contribuye al cambio climático.' },
      { t: 'Estrategia nacional de ganadería', c: 'Guatemala cuenta con la Estrategia de Desarrollo Sostenible de la Ganadería Bovina, que impulsa la adopción de tecnologías sostenibles.' },
      { t: 'Principios de buenas prácticas', c: 'Las Buenas Prácticas Ganaderas (BPG) abarcan desde el manejo humanitario de los animales hasta la protección del agua y restauración del suelo.' }
    ],
    quiz: [
      { q: '¿Cuál de los siguientes es un impacto de la ganadería convencional?', o: ['Aumento de la biodiversidad de insectos', 'Restauración natural del suelo', 'Deforestación y degradación de suelos'], r: 2 },
      { q: '¿En qué estrategia nacional se inserta el desarrollo ganadero sostenible de Guatemala?', o: ['Estrategia de Desarrollo Sostenible de la Ganadería Bovina', 'Estatuto Forestal Municipal', 'Plan de Riego por Gravedad'], r: 0 },
      { q: 'El concepto de sostenibilidad en ganadería busca:', o: ['Producir sin importar el agotamiento de recursos', 'Satisfacer necesidades actuales sin comprometer las futuras', 'Exclusivamente eliminar el uso de pasturas'], r: 1 }
    ]
  },
  {
    id: 13, ic: '📊',
    t: 'Planificación y control productivo',
    d: 'Planificación de fincas, zonificación de áreas y el control financiero mediante registros.',
    img: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Zonificación y plano de la finca', c: 'Elaborar un mapa o plano actual de la finca para identificar áreas productivas, zonas de conservación de bosques, fuentes de agua y accesos.' },
      { t: 'Registros productivos esenciales', c: 'Llevar registros sistemáticos de partos, pesaje de leche o carne, mortalidad, servicios de inseminación y sanidad animal permite evaluar el rendimiento real.' },
      { t: 'Presupuesto y costos de producción', c: 'Anotar detalladamente todos los gastos de insumos, mano de obra y servicios de la finca ayuda a determinar el costo de producción por litro de leche o kilogramo de carne.' },
      { t: 'Elaboración de planes de finca', c: 'Un plan de finca establece metas productivas claras a corto, mediano y largo plazo, programando inversiones paso a paso.' }
    ],
    quiz: [
      { q: '¿Cuál es el primer paso recomendado para realizar la zonificación de la finca?', o: ['Comprar reproductores de raza pura', 'Elaborar un mapa o plano de la finca', 'Aplicar fertilizantes químicos a todo el lote'], r: 1 },
      { q: '¿Por qué es importante llevar registros productivos en el hato?', o: ['Para cumplir con el pago de impuestos únicamente', 'Permite medir el rendimiento real de los animales y tomar decisiones informadas', 'Para evitar que el ganado se escape de los potreros'], r: 1 },
      { q: '¿Qué variable es fundamental para calcular la rentabilidad real de la leche?', o: ['El color del pelaje del animal', 'El costo de producción por litro de leche', 'La distancia a la capital de la República'], r: 1 }
    ]
  },
  {
    id: 14, ic: '💧',
    t: 'Diseño de potreros y manejo del agua',
    d: 'Uso de aguadas mejoradas, bebederos eficientes y diseño de cercas eléctricas.',
    img: 'https://images.unsplash.com/photo-1463123081488-729f60c1926d?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Aguadas mejoradas y suministro', c: 'Ubicar el agua cerca del ganado genera un incremento productivo sustancial: de 90 a 135 gramos de peso extra al día y entre 0.55 y 1.1 litros de leche diarios por animal.' },
      { t: 'Estimación de consumo diario', c: 'Las vacas de leche consumen 38-110 litros/día; los bovinos en engorde 26-66 litros/día. En general, se estima un consumo equivalente al 8-12% del peso corporal a 25°C.' },
      { t: 'Diseño y subdivisión de potreros', c: 'Subdividir la finca permite controlar mejor la alimentación. Limitar el tiempo de ocupación en cada potrero (máximo 3 días) evita que los animales dañen el rebrote.' },
      { t: 'Cercas eléctricas de bajo costo', c: 'Las cercas eléctricas son una herramienta eficiente y de menor costo que las tradicionales. Permiten delimitar potreros pequeños con facilidad.' }
    ],
    quiz: [
      { q: '¿Cuánto peso diario adicional puede ganar un bovino si el agua se ubica cerca de él?', o: ['Entre 10 y 20 gramos', 'De 90 a 135 gramos', 'Alrededor de 1 kilogramo'], r: 1 },
      { q: '¿Qué porcentaje del peso corporal consume en agua un animal a 25°C?', o: ['Entre 1% y 2%', 'Entre 8% y 12%', 'Más del 50%'], r: 1 },
      { q: '¿Cuál es el tiempo de ocupación máximo recomendado por potrero?', o: ['15 días', '3 días', '1 mes'], r: 1 }
    ]
  },
  {
    id: 15, ic: '🌿',
    t: 'Pastoreo rotacional y manejo de pasturas',
    d: 'Principios del pastoreo rotacional, capacidad de carga y periodos de descanso del pasto.',
    img: 'https://images.unsplash.com/photo-1589923188900-85dae440342b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Principios del pastoreo rotacional', c: 'El pastoreo rotacional intensivo consiste en utilizar el pasto en su punto óptimo de madurez nutricional, dividiendo el área en potreros pequeños y rotando sistemáticamente.' },
      { t: 'Beneficios productivos y económicos', c: 'El pastoreo rotacional elimina hasta el 50% del desperdicio de pasto por pisoteo y aumenta la producción de carne y leche hasta un 20% por hectárea.' },
      { t: 'Beneficios ambientales de la rotación', c: 'Favorece la fertilidad natural del suelo gracias a una mejor distribución del estiércol. Al concentrar a los animales se liberan áreas para la regeneración de bosques.' },
      { t: 'Períodos de descanso y ocupación', c: 'Los animales no deben permanecer más de 3 días en un potrero para evitar el sobrepastoreo del rebrote. Los períodos de descanso varían de 25 a 45 días.' }
    ],
    quiz: [
      { q: '¿En cuánto puede incrementarse la producción de carne o leche por hectárea mediante el pastoreo rotacional?', o: ['Hasta 5%', 'Hasta 20%', 'No incrementa, solo ahorra agua'], r: 1 },
      { q: '¿Por qué no se debe permitir que el ganado permanezca más de 3 días en el mismo potrero?', o: ['Porque se aburren del paisaje', 'Para evitar que consuman el rebrote tierno del pasto debilitando la raíz', 'Porque el agua se ensucia'], r: 1 },
      { q: '¿Cuál de los siguientes es un beneficio ambiental del pastoreo rotacional?', o: ['Uso excesivo de herbicidas', 'Compactación severa del suelo', 'Distribución uniforme del estiércol e incremento de carbono en el suelo'], r: 2 }
    ]
  },
  {
    id: 16, ic: '🌳',
    t: 'Sistemas silvopastoriles: cercas vivas y árboles dispersos',
    d: 'Establecimiento de cercas vivas multiestrato y árboles dispersos para confort animal.',
    img: 'https://images.unsplash.com/photo-1448375240586-882707db888b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Cercas vivas multiestrato', c: 'Las cercas vivas combinan árboles y arbustos plantados en línea. Reemplazan postes de madera muerta y producen forraje rico en proteína. Aportan de 3.5 a 6 toneladas de materia seca por kilómetro al año.' },
      { t: 'Importancia de la sombra en potreros', c: 'La sombra es vital para reducir el estrés térmico. Se estima que una vaca requiere unos 5 m² de sombra, lo que se logra con unos 50 árboles para un hato de 100 vacas.' },
      { t: 'Árboles dispersos en potreros', c: 'Mantener árboles dispersos protege el suelo contra la erosión y mejora su fertilidad. Se recomiendan entre 25 y 40 árboles adultos por hectárea.' },
      { t: 'Remoción de carbono y biodiversidad', c: 'Los sistemas silvopastoriles capturan de 22 a 55 toneladas de CO2 equivalente por hectárea. Sirven de corredores biológicos para aves e insectos benéficos.' }
    ],
    quiz: [
      { q: '¿Cuánta materia seca de forraje puede producir al año un kilómetro de cerca viva multiestrato?', o: ['Entre 100 y 200 kg', 'De 3.5 a 6 toneladas', 'Más de 50 toneladas'], r: 1 },
      { q: '¿Cuántos árboles adultos por hectárea se recomiendan?', o: ['De 5 a 10 árboles', 'De 25 a 40 árboles', '150 árboles'], r: 1 },
      { q: '¿Cuánta sombra en metros cuadrados requiere una vaca lechera?', o: ['1 metro cuadrado', '5 metros cuadrados', '50 metros cuadrados'], r: 1 }
    ]
  },
  {
    id: 17, ic: '🌾',
    t: 'Bancos de forraje, silaje y nutrición',
    d: 'Establecimiento de bancos proteicos y energéticos, y técnicas de ensilaje.',
    img: 'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Bancos forrajeros proteicos', c: 'Áreas sembradas con alta densidad de arbustos leguminosos (Leucaena, Erythrina, Gliricidia, Morera) para suministrar follaje de alta calidad durante la escasez.' },
      { t: 'Bancos forrajeros energéticos', c: 'Áreas destinadas a forrajes perennes ricos en carbohidratos (caña de azúcar, Napier) que aportan la energía necesaria para la fermentación ruminal.' },
      { t: 'Ensilaje: Principios básicos', c: 'El ensilaje es un método de conservación de forrajes verdes por fermentación anaerobia. El éxito radica en cosechar en el punto óptimo, picar finamente, compactar y sellar herméticamente.' },
      { t: 'Fases del silo y precauciones', c: 'El proceso de ensilaje tiene cuatro fases: aeróbica, fermentación láctica, estabilización y deterioro. La exposición al aire acelera el crecimiento de mohos tóxicos.' }
    ],
    quiz: [
      { q: '¿Qué porcentaje mínimo de proteína cruda aportan los bancos forrajeros proteicos?', o: ['Menos del 5%', 'Al menos 15%', 'Exactamente 50%'], r: 1 },
      { q: '¿Cuál es el factor clave para evitar pérdidas de calidad en el ensilaje?', o: ['Agregar grandes cantidades de agua', 'Exponer el silo al sol directo', 'Lograr una compactación firme y un sellado 100% hermético sin oxígeno'], r: 2 },
      { q: '¿En qué fase del proceso de ensilaje se estabiliza el alimento?', o: ['Durante la fase aeróbica inicial', 'En la fermentación láctica donde el pH baja', 'Solo después de abrirlo'], r: 1 }
    ]
  },
  {
    id: 18, ic: '🧬',
    t: 'Mejoramiento genético y razas',
    d: 'Criterios para selección de reproductores y evaluación de razas en Guatemala.',
    img: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Selección de reproductores', c: 'Selecciona toros y vacas basándote en registros de producción, conformación corporal y adaptación al clima local. Un buen reproductor mejora el hato en menos de 3 generaciones.' },
      { t: 'Razas adaptadas al trópico', c: 'En Guatemala predominan las razas Brahman (B. indicus) y sus cruces con Angus, Simmental o Holstein. La elección debe basarse en el sistema productivo y el mercado objetivo.' },
      { t: 'Inseminación artificial', c: 'La IA permite acceder a genética superior sin comprar el toro. Requiere buena detección de celo, semen de calidad certificada y un técnico capacitado. La tasa de preñez promedio es del 50-65%.' },
      { t: 'Evaluación del progreso genético', c: 'Lleva registros de producción por generación: producción de leche, ganancia diaria de peso, días al primer parto. El mejoramiento genético es lento pero acumulativo.' }
    ],
    quiz: [
      { q: '¿Cuál es la tasa de preñez promedio con inseminación artificial en ganado bovino?', o: ['10-20%', '50-65%', '90-95%'], r: 1 },
      { q: '¿Qué raza bovina de origen Bos indicus predomina en Guatemala?', o: ['Angus', 'Brahman', 'Holstein'], r: 1 },
      { q: '¿En cuántas generaciones puede mejorar notablemente el hato con buenos reproductores?', o: ['Menos de 3 generaciones', '10 a 15 generaciones', 'Solo en la siguiente generación'], r: 0 }
    ]
  },
  {
    id: 19, ic: '⏱️',
    t: 'Sanidad animal y calendario sanitario',
    d: 'Vacunas, desparasitación, bioseguridad y reconocimiento de enfermedades comunes.',
    img: 'https://images.unsplash.com/photo-1570042225831-d9b085d44e20?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Calendario de vacunación', c: 'Las vacunas obligatorias en Guatemala incluyen la fiebre aftosa (donde aplica), brucelosis y carbunco sintomático. Coordina con MAGA el calendario oficial para tu región.' },
      { t: 'Desparasitación estratégica', c: 'Combina la desparasitación interna (antiparasitarios orales o inyectables) con la externa (acaricidas). Evita la resistencia rotando principios activos cada ciclo.' },
      { t: 'Bioseguridad en la finca', c: 'Aisla los animales nuevos por 21 días antes de integrarlos al hato. Desinfecta vehículos y equipos que entran a la finca. Mantén un registro de movimientos del ganado.' },
      { t: 'Enfermedades comunes y señales de alerta', c: 'Conoce las señales de brucelosis, mastitis, BVD y fiebre de transporte. Temperatura corporal normal del bovino: 38-39°C. Más de 40°C indica fiebre y urgencia veterinaria.' }
    ],
    quiz: [
      { q: '¿Cuántos días debe estar en cuarentena un animal nuevo antes de integrarse al hato?', o: ['1 día', '21 días', '60 días'], r: 1 },
      { q: '¿Cuál es la temperatura corporal normal del bovino?', o: ['36-37°C', '38-39°C', '41-42°C'], r: 1 },
      { q: '¿Por qué se recomienda rotar principios activos en la desparasitación?', o: ['Para ahorrar dinero', 'Para evitar el desarrollo de resistencia en los parásitos', 'Porque todos son iguales'], r: 1 }
    ]
  },
  {
    id: 20, ic: '📈',
    t: 'Economía de la finca ganadera',
    d: 'Indicadores de rentabilidad, costos de producción y acceso a financiamiento.',
    img: 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Costo de producción de leche', c: 'Calcula el costo por litro sumando: alimentación, sanidad, mano de obra, depreciación de instalaciones y equipos. En Guatemala, costos menores a Q2.50/litro son competitivos.' },
      { t: 'Indicadores clave de la finca', c: 'Monitorea: litros/vaca/día, días de lactancia, intervalo entre partos (óptimo: 12-14 meses), porcentaje de mortalidad y natalidad anual.' },
      { t: 'Opciones de financiamiento', c: 'Explora: crédito bancario (Banrural, BANECO), fideicomisos de MAGA, programas de AGEXPORT, cooperativas de ahorro y crédito, y fondos de la cooperación internacional.' },
      { t: 'Análisis de rentabilidad', c: 'La rentabilidad de la finca ganadera se mejora aumentando la producción, reduciendo costos o mejorando los precios de venta. Las tres palancas funcionan mejor en conjunto.' }
    ],
    quiz: [
      { q: '¿Cuál es el intervalo entre partos óptimo para una vaca lechera?', o: ['6-8 meses', '12-14 meses', '18-24 meses'], r: 1 },
      { q: '¿Cuál de estos es un indicador clave de productividad de la finca lechera?', o: ['El color de la pintura del establo', 'Litros/vaca/día y días de lactancia', 'El número de caminos de acceso'], r: 1 },
      { q: '¿Qué institución guatemalteca ofrece créditos especializados para el sector agropecuario?', o: ['Banrural', 'SAT', 'IGSS'], r: 0 }
    ]
  },
  {
    id: 21, ic: '🏆',
    t: 'Evaluación final: Ganadería Sostenible',
    d: 'Evaluación integral del programa piloto de Ganadería Sostenible CONADEA.',
    img: 'https://images.unsplash.com/photo-1543362906-acfc16c67564?w=320&auto=format&fit=crop&q=80',
    lecciones: [
      { t: 'Repaso de conceptos clave', c: 'Revisamos los conceptos fundamentales del programa: sostenibilidad, pastoreo rotacional, sistemas silvopastoriles, manejo del agua, nutrición animal y economía de la finca.' },
      { t: 'Aplicación práctica en la finca', c: 'Cada técnica aprendida tiene un impacto medible en tu finca. Establece metas concretas: reducir costos en X%, aumentar producción en Y litros/día, implementar X área de silvopastoril.' },
      { t: 'Red de apoyo y seguimiento', c: 'El programa CONADEA AgroIA incluye visitas de seguimiento, grupos de WhatsApp por zona y acceso a la red de técnicos validadores para resolver dudas en campo.' },
      { t: 'Próximos pasos', c: 'Comparte lo aprendido con tu asociación, aplica al menos 3 prácticas en tu finca este ciclo productivo y documenta los cambios. Tu experiencia ayudará a mejorar el programa.' }
    ],
    quiz: [
      { q: '¿Cuál es el principal beneficio del pastoreo rotacional bien implementado?', o: ['Solo mejora el paisaje de la finca', 'Aumenta la producción hasta un 20% por hectárea y reduce el sobrepastoreo', 'Elimina la necesidad de agua en los potreros'], r: 1 },
      { q: '¿Qué elemento es clave para el éxito de los sistemas silvopastoriles?', o: ['Elegir solo árboles ornamentales', 'Combinar árboles forrajeros con pastos y ganadería de manera planificada', 'Eliminar todos los pastos del potrero'], r: 1 },
      { q: '¿Cuál es la actitud correcta frente a los nuevos conocimientos del programa?', o: ['Guardarlos para uso personal exclusivamente', 'Compartirlos con la asociación y aplicarlos documentando los cambios', 'Esperar que el técnico los implemente por nosotros'], r: 1 }
    ]
  }
];

// ============ COLORES POR MÓDULO ============
export const COLORES_MOD = {
  1:  ['#34D399', '#0F766E'], 2:  ['#A3E635', '#3F6212'], 3:  ['#FBBF24', '#B45309'],
  4:  ['#FB923C', '#9A3412'], 5:  ['#60A5FA', '#1E40AF'], 6:  ['#22D3EE', '#155E75'],
  7:  ['#C084FC', '#6B21A8'], 8:  ['#F472B6', '#9D174D'], 9:  ['#FACC15', '#854D0E'],
  10: ['#4ADE80', '#166534'], 11: ['#86EFAC', '#15803D'], 12: ['#6EE7B7', '#047857'],
  13: ['#93C5FD', '#1D4ED8'], 14: ['#7DD3FC', '#0369A1'], 15: ['#A7F3D0', '#064E3B'],
  16: ['#FDE047', '#A16207'], 17: ['#F9A8D4', '#BE185D'], 18: ['#C084FC', '#581C87'],
  19: ['#FDA4AF', '#BE123C'], 20: ['#FDBA74', '#C2410C'], 21: ['#818CF8', '#4338CA']
};

export const gradMod = (id) => {
  const c = COLORES_MOD[id] || ['#4ADE80', '#166534'];
  return `background: linear-gradient(135deg, ${c[0]}, ${c[1]});`;
};

// ============ RUTAS DE APRENDIZAJE ============
export const RUTAS = [
  {
    id: 'ganaderia_sostenible', clase: 'verde', ic: '🐄',
    t: 'Ganadería Sostenible (Plan Piloto)',
    d: 'Malla curricular completa para la transición gradual hacia fincas más productivas y resilientes.',
    mods: [11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21]
  },
  {
    id: 'sostenible', clase: '', ic: '🌱',
    t: 'Agricultura Sostenible',
    d: 'Aprende técnicas sostenibles para mejorar la productividad cuidando el medio ambiente.',
    mods: [2, 3, 4, 6]
  },
  {
    id: 'pecuaria', clase: 'azul', ic: '🐄',
    t: 'Producción Pecuaria',
    d: 'Fortalece tus conocimientos en manejo, nutrición y producción animal.',
    mods: [5, 6]
  },
  {
    id: 'gestion', clase: 'oro', ic: '📈',
    t: 'Gestión, Organización y Mercados',
    d: 'Administra tu finca, fortalece tu organización y vende mejor tu producción.',
    mods: [7, 8, 9, 10]
  },
  {
    id: 'digital', clase: '', ic: '📱',
    t: 'Competencias Digitales AgroIA',
    d: 'Domina el asistente AgroIA y las herramientas digitales del programa.',
    mods: [1]
  }
];

// ============ INSIGNIAS ============
export const INSIGNIAS = [
  { id: 'semilla',    ic: '🌱', t: 'Primera semilla',     d: 'Completaste tu primera lección.' },
  { id: 'modulo1',   ic: '🎓', t: 'Primer módulo',       d: 'Aprobaste tu primer módulo.' },
  { id: 'tres',      ic: '🏅', t: 'Tres módulos',         d: 'Completaste 3 módulos.' },
  { id: 'cinco',     ic: '⭐', t: 'Cinco módulos',        d: 'Completaste 5 módulos.' },
  { id: 'perfecto',  ic: '💎', t: 'Nota perfecta',        d: 'Respondiste las 3 preguntas correctamente.' },
  { id: 'explorador',ic: '🧭', t: 'Explorador',          d: 'Abriste 4 o más módulos distintos.' },
  { id: 'todas',     ic: '🏆', t: 'Maestro AgroIA',       d: 'Completaste todos los módulos del programa.' }
];

// ============ EVENTOS DEL CALENDARIO ============
export const EVENTOS = [
  { dia: 10, mes: 'JUN', t: 'Webinar en vivo: Innovaciones en riego tecnificado',                h: '10:00 a.m.', ic: '💻' },
  { dia: 12, mes: 'JUN', t: 'Cierre de inscripción: facilitadores digitales (2.ª cohorte)',      h: '5:00 p.m.',  ic: '📝' },
  { dia: 17, mes: 'JUN', t: 'Jornada de campo: sistemas silvopastoriles · TNC Guatemala',         h: '8:00 a.m.',  ic: '🌳' },
  { dia: 24, mes: 'JUN', t: 'Charla: precios y mercados de granos básicos',                       h: '3:00 p.m.',  ic: '📊' },
  { dia: 30, mes: 'JUN', t: 'Entrega de reportes territoriales a CONADEA',                        h: 'Todo el día', ic: '📑' }
];

// ============ NOVEDADES / NOTICIAS ============
export const NOVEDADES = [
  { tipo: 'alerta',  t: 'Alerta fitosanitaria: roya en maíz',                    p: 'Se reportan focos de roya en los departamentos de Petén y Alta Verapaz. Monitorea tus cultivos con hojas a nivel.', chip: '⚠️ Alerta',  fecha: 'Hace 2 horas' },
  { tipo: 'video',   t: 'Nuevo video: Ensilaje de maíz paso a paso',              p: 'Aprende la técnica correcta de ensilaje con el técnico regional de CONADEA en este video de 12 minutos.',            chip: '🎬 Video',    fecha: 'Ayer' },
  { tipo: 'novedad', t: 'Nuevos módulos de Ganadería Sostenible disponibles',     p: 'Ya puedes acceder a los módulos 11 al 21 del programa piloto de Ganadería Sostenible con TNC Guatemala.',          chip: '📗 Novedad',  fecha: 'Hace 3 días' },
  { tipo: 'alerta',  t: 'Pronóstico: inicio de canícula en julio',               p: 'El INSIVUMEH prevé inicio de canícula del 1 al 31 de julio. Planifica riegos y coberturas con anticipación.',       chip: '⛅ Clima',    fecha: 'Hace 4 días' },
  { tipo: 'novedad', t: 'Convocatoria: facilitadores digitales 2.ª cohorte',      p: 'CONADEA abre convocatoria para 50 facilitadores digitales en los 22 departamentos. Inscripción hasta el 12 de junio.',chip: '📣 Convocatoria', fecha: 'Hace 5 días' }
];

// ============ FOROS ============
export const FOROS = [
  { ic: '🌽', t: 'Gusano cogollero en maíz de primera, ¿qué están aplicando?',     a: 'Asoc. El Esfuerzo · hace 2 horas',        r: 14 },
  { ic: '🐄', t: 'Experiencias con bloques multinutricionales en verano',           a: 'Coop. Ganaderos del Norte · hace 5 horas', r: 9  },
  { ic: '💧', t: 'Cosecha de agua de techo: medidas de toneles y canaletas',        a: 'Asoc. Mujeres Rurales · ayer',             r: 22 },
  { ic: '🤝', t: 'Modelo de acta para renovación de junta directiva',              a: 'Coop. La Unión · hace 2 días',             r: 6  },
  { ic: '🛒', t: 'Compradores de tomate en oriente, recomendaciones',              a: 'Asoc. Hortaliceros Unidos · hace 3 días',  r: 17 }
];

// ============ PREGUNTAS FRECUENTES (FAQ) ============
export const FAQS = [
  {
    q: '¿Cómo obtengo un certificado?',
    a: 'Completa todas las lecciones de un curso y aprueba su evaluación con al menos 2 de 3 respuestas correctas. El certificado se genera de inmediato y puedes imprimirlo o guardarlo como PDF.'
  },
  {
    q: '¿Qué es la racha de aprendizaje?',
    a: 'Es el número de días seguidos en los que has completado al menos una lección o evaluación. Mantenerla activa te ayuda a avanzar de manera constante.'
  },
  {
    q: '¿Cómo envío una consulta técnica de mi finca?',
    a: 'Las consultas con fotos y audios se envían por el WhatsApp AgroIA. Esta aula virtual complementa ese servicio con cursos y certificación. Revisa el Módulo 1 para aprender a usarlo.'
  },
  {
    q: '¿La plataforma consume muchos datos?',
    a: 'No. El aula está diseñada para bajo consumo de datos y tu avance se guarda en tu propio dispositivo. El modo ahorro de datos está disponible en Configuración.'
  },
  {
    q: '¿Quién valida los contenidos?',
    a: 'La red de especialistas del programa: CEFEP, TNC Guatemala, la Universidad Rafael Landívar, el Colegio de Ingenieros Agrónomos y técnicos de MAGA.'
  }
];
