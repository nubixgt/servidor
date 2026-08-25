import { ServiceItem, PortfolioItem, RepairOrder } from '../types';

export const SERVICES_DATA: ServiceItem[] = [
  {
    id: 'pc-laptops',
    title: 'PC & Laptops',
    category: 'hardware',
    iconName: 'laptop',
    shortDescription: 'Diagnóstico, reparación de hardware, ampliación de memoria y soluciones a problemas de rendimiento.',
    fullDescription: 'Reparación integral de ordenadores de sobremesa, estaciones de trabajo y portátiles (Windows, macOS y Linux). Diagnósticos con osciloscopio y cámaras térmicas para detección de cortocircuitos a nivel de componente.',
    features: [
      'Ampliación de memoria RAM y discos SSD NVMe de alta velocidad',
      'Reparación de placas base, circuitos de carga y chips gráficos',
      'Reemplazo de teclados, pantallas, bisagras y carcasas rotas',
      'Optimización de rendimiento y eliminación de cuello de botella',
      'Soldadura BGA y reballing de procesadores y tarjetas gráficas'
    ],
    estimatedTime: '24 a 48 horas (Express: 4 horas)',
    startingPrice: '$25.000',
    warranty: '6 meses de garantía escrita',
    commonIssues: ['No enciende o se apaga solo', 'Pantalla azul o reinicios constantes', 'Lentitud extrema al abrir programas', 'Bisagra rota o plástico quebrado']
  },
  {
    id: 'smartphones',
    title: 'Smartphones',
    category: 'mobile',
    iconName: 'smartphone',
    shortDescription: 'Cambio de pantallas, baterías, conectores de carga y recuperación de placas base mojadas.',
    fullDescription: 'Servicio técnico especializado para iPhone, Samsung Galaxy, Xiaomi, Motorola y Google Pixel. Contamos con repuestos originales y máquinas de calibración TrueTone, Face ID y sellado estanco.',
    features: [
      'Cambio de pantalla OLED / AMOLED original en menos de 60 minutos',
      'Reemplazo de baterías de alta capacidad con estado de salud al 100%',
      'Microsoldadura en placa: Face ID, IC de audio, circuito Touch y chip baseband',
      'Limpieza ultrasónica y desoxidación de equipos caídos al agua',
      'Reparación de puertos de carga USB-C y Lightning con conector original'
    ],
    estimatedTime: '45 minutos a 24 horas',
    startingPrice: '$18.000',
    warranty: '90 días a 6 meses según repuesto',
    commonIssues: ['Vidrio roto o líneas verdes en pantalla', 'Batería dura pocas horas o se apaga al 30%', 'No carga a menos que mueva el cable', 'Cayó en agua o piscina']
  },
  {
    id: 'televisores',
    title: 'Televisores',
    category: 'displays',
    iconName: 'tv',
    shortDescription: 'Reparación de paneles LED/OLED, fuentes de alimentación y problemas de placa principal.',
    fullDescription: 'Especialistas en Smart TVs de 32" a 85" (Samsung, LG, Sony, Philips, TCL, Hisense). Solución a fallas de retroiluminación (tiras LED), fuentes conmutadas quemadas por picos de tensión y actualización de firmware emmc.',
    features: [
      'Cambio completo de tiras de retroiluminación LED con disipación de aluminio',
      'Reparación y reconstrucción de fuentes de alimentación (fuente power)',
      'Reballing o reprogramación de memorias NAND/eMMC en placas main',
      'Solución a problemas de conexión WiFi, reinicios en el logo o sin imagen con sonido',
      'Calibración de color y diagnóstico en banco de pruebas'
    ],
    estimatedTime: '48 a 72 horas',
    startingPrice: '$35.000',
    warranty: '6 meses de garantía escrita',
    commonIssues: ['Se escucha la voz pero la pantalla se ve negra', 'La luz de standby parpadea y no enciende', 'Se queda pegado en el logo de la marca', 'Líneas verticales u horizontales']
  },
  {
    id: 'consolas',
    title: 'Consolas',
    category: 'gaming',
    iconName: 'gamepad',
    shortDescription: 'Mantenimiento de PS5, PS4, Nintendo Switch. Problemas de lectura, puertos HDMI y sobrecalentamiento.',
    fullDescription: 'Taller especializado en consolas de última generación: PlayStation 5 (cambio de metal líquido, sellado APU), PS4, Xbox Series X/S, Xbox One y Nintendo Switch (fallas de carga M92T36, joy-con drift, pantalla).',
    features: [
      'Reemplazo de puerto HDMI roto o sin señal de video (microsoldadura)',
      'Cambio y respreading de metal líquido original en PS5 y pasta Kryonaut en PS4/Xbox',
      'Reparación de fuentes internas quemadas y módulos WiFi/Bluetooth',
      'Mantenimiento y reparación de lectoras de disco Blu-Ray (lentes y mecanismos)',
      'Solución a drift en sticks de controles con sensores magnéticos Hall Effect'
    ],
    estimatedTime: '24 a 48 horas',
    startingPrice: '$28.000',
    warranty: '90 días a 6 meses',
    commonIssues: ['Luz blanca/azul fija sin señal en TV (HDMI roto)', 'Ventilador suena como turbina de avión', 'Apagón repentino jugando a juegos exigentes', 'No lee los discos o los expulsa solos']
  },
  {
    id: 'sistemas-os',
    title: 'Sistemas OS',
    category: 'software',
    iconName: 'terminal',
    shortDescription: 'Instalación limpia, actualización y optimización de Windows, macOS y distribuciones Linux.',
    fullDescription: 'Puesta a punto completa de entornos operativos. Instalación de Windows 11/10 sin bloatware ni telemetría invasiva, macOS Sonoma/Sequoia con recuperación de Time Machine y distribuciones Linux para servidores o desarrolladores.',
    features: [
      'Formateo e instalación en limpio con controladores actualizados y optimización de inicio',
      'Migración completa de sistema de HDD antiguo a SSD ultrarrápido sin perder datos ni licencias',
      'Configuración de arranque dual (Dual Boot Windows + Ubuntu/Fedora)',
      'Solución de errores de registro, DLLs faltantes y pantallas azules BSOD',
      'Activación y licenciamiento digital genuino'
    ],
    estimatedTime: '2 a 6 horas',
    startingPrice: '$15.000',
    warranty: 'Garantía de soporte 30 días',
    commonIssues: ['Windows tarda varios minutos en iniciar', 'Error de arranque "No bootable device found"', 'Actualización fallida que bloqueó el equipo', 'Incompatibilidad de programas o drivers']
  },
  {
    id: 'software-office',
    title: 'Software & Office',
    category: 'software',
    iconName: 'grid',
    shortDescription: 'Configuración de suites de productividad, eliminación de malware y recuperación de datos.',
    fullDescription: 'Soporte informático corporativo y particular. Limpieza exhaustiva de virus, troyanos, spyware y secuestradores de navegador. Recuperación forense de fotos, documentos y bases de datos borradas accidentalmente.',
    features: [
      'Eliminación avanzada de virus, malware, adware y ransomware residente',
      'Instalación y configuración de Office 365, software de diseño (Adobe, AutoCAD) y utilitarios',
      'Recuperación de datos de discos duros dañados, pendrives y memorias SD',
      'Configuración de respaldos automáticos en la nube (Google Drive, OneDrive, NAS)',
      'Optimización de antivirus y configuración de cortafuegos de red'
    ],
    estimatedTime: '3 a 12 horas',
    startingPrice: '$18.000',
    warranty: 'Garantía de satisfacción y soporte',
    commonIssues: ['Ventanas de publicidad que se abren solas', 'Archivos cifrados o inaccesibles', 'Archivos borrados por error en papelera', 'Office muestra cartel de producto no licenciado']
  },
  {
    id: 'mantenimiento-preventivo',
    title: 'Mantenimiento Preventivo',
    category: 'maintenance',
    iconName: 'wrench',
    shortDescription: 'Servicio integral de limpieza interna, cambio de pasta térmica de alto rendimiento y tests de estrés para prolongar la vida útil de tus equipos de alto rendimiento y estaciones de trabajo.',
    fullDescription: 'El servicio más recomendado para evitar fallas catastróficas por temperatura. Desarmado minucioso de computadoras gamer, workstations y laptops. Despeje de pelusas y polvo con aire ionizado, lubricación de rodamientos de ventiladores, aplicación de pasta térmica de alta conductividad (Thermal Grizzly / Arctic MX-6) y thermal pads de grado industrial.',
    features: [
      'Limpieza profunda por ultrasonido y aire comprimido antiestático',
      'Cambio de pasta térmica de grado extremo (disminuye de 15°C a 25°C la temperatura)',
      'Reemplazo de thermal pads en memorias VRAM y VRMs de tarjetas de video',
      'Limpieza y reacondicionamiento de ventiladores y filtros antipolvo',
      'Pruebas de estrés térmico con FurMark y Cinebench antes y después del servicio con reporte gráfico'
    ],
    estimatedTime: '3 a 5 horas',
    startingPrice: '$22.000',
    warranty: 'Certificado de temperatura y 90 días',
    commonIssues: ['El equipo calienta excesivamente al tacto', 'Apagados térmicos en medio de partidas o renders', 'Ruido insoportable de ventiladores al 100%', 'Más de 1 año sin servicio de limpieza']
  }
];

export const PORTFOLIO_ITEMS: PortfolioItem[] = [
  {
    id: 'ps5-hdmi-repair',
    title: 'PlayStation 5 con Puerto HDMI Destruido',
    device: 'Sony PlayStation 5 Digital Edition',
    category: 'Consolas',
    problem: 'El cliente reportó que tras un tirón del cable, la consola encendía con luz blanca pero el televisor marcaba "Sin Señal". Los pines internos del puerto estaban doblados y en cortocircuito.',
    solution: 'Desoldado mediante estación de aire caliente a 380°C con tobera focalizada, reconstrucción de 2 pistas cortadas con micro-alambre esmaltado de 0.1mm, montaje de puerto HDMI original reforzado y testeo a 4K 120Hz.',
    turnaround: '24 horas',
    warranty: '6 meses',
    beforeImage: 'https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80',
    afterImage: 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?auto=format&fit=crop&w=800&q=80',
    beforeLabel: 'Antes: Pines HDMI rotos',
    afterLabel: 'Después: Salida 4K 120Hz perfecta',
    details: [
      'Puerto HDMI con pines desalineados y 2 pistas de señal arrancadas',
      'Microscopio estereoscópico para micro-puentes con máscara UV',
      'Reemplazo por puerto original de aleación de níquel endurecido',
      'Prueba de rendimiento continuo por 2 horas en God of War Ragnarök'
    ]
  },
  {
    id: 'laptop-gamer-thermal',
    title: 'Overheating en Laptop Asus ROG Strix (98°C)',
    device: 'Asus ROG Strix G15 (Ryzen 7 + RTX 3070)',
    category: 'PC & Laptops',
    problem: 'Caída drástica de FPS (Thermal Throttling) y apagados automáticos durante partidas de juego. La pasta térmica de fábrica estaba completamente cristalizada y seca.',
    solution: 'Desensamble completo del disipador, limpieza con alcohol isopropílico de alta pureza, aplicación de pasta térmica de alta conductividad Arctic MX-6 y reemplazo de thermal pads en memorias VRAM.',
    turnaround: '3 horas',
    warranty: 'Certificado de Mantenimiento',
    beforeImage: 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=800&q=80',
    afterImage: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?auto=format&fit=crop&w=800&q=80',
    beforeLabel: 'Antes: Pasta seca y 98°C en carga',
    afterLabel: 'Después: 71°C estables en stress test',
    details: [
      'Reducción de temperatura de 98°C a 71°C en CPU bajo carga máxima',
      'Recuperación de 45% de FPS perdidos por Thermal Throttling',
      'Limpieza ultrasónica de turbinas con lubricación de rodamientos',
      'Reporte térmico detallado entregado al cliente'
    ]
  },
  {
    id: 'iphone-oled-repair',
    title: 'iPhone 14 Pro Max con Pantalla Destruida y Sin Táctil',
    device: 'Apple iPhone 14 Pro Max 256GB',
    category: 'Smartphones',
    problem: 'Caída de 2 metros sobre concreto. Cristal pulverizado, display OLED con manchas negras y tinta derramada, sin respuesta táctil en el 80% de la superficie.',
    solution: 'Instalación de módulo de pantalla Super Retina XDR original, reprogramación de código EEPROM para preservar la tecnología TrueTone y calibración del sensor de proximidad.',
    turnaround: '40 minutos',
    warranty: '6 meses',
    beforeImage: 'https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?auto=format&fit=crop&w=800&q=80',
    afterImage: 'https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?auto=format&fit=crop&w=800&q=80',
    beforeLabel: 'Antes: Cristal y OLED destrozados',
    afterLabel: 'Después: TrueTone & 120Hz ProMotion activos',
    details: [
      'Repuesto Display OLED Original de 120Hz ProMotion',
      'Copia de seriales con programadora QianLi iCopy Plus para TrueTone',
      'Instalación de junta adhesiva estanca contra polvo y salpicaduras IP68',
      'Vidrio templado 9H de regalo instalado sin burbujas'
    ]
  },
  {
    id: 'smart-tv-backlight',
    title: 'Smart TV Samsung 55" 4K Sin Imagen (Audio Funciona)',
    device: 'Samsung 55" Crystal UHD 4K',
    category: 'Televisores',
    problem: 'El televisor encendía, respondía al control remoto y emitía sonido perfecto, pero la imagen se mantenía completamente a oscuras (se veía tenue alumbrando con linterna).',
    solution: 'Apertura del panel de cristal líquido en cámara limpia, cambio del juego completo de tiras LED por repuestos de aluminio de disipación mejorada y ajuste de corriente en la placa fuente para mayor durabilidad.',
    turnaround: '48 horas',
    warranty: '6 meses',
    beforeImage: 'https://images.unsplash.com/photo-1593784991095-a205069470b6?auto=format&fit=crop&w=800&q=80',
    afterImage: 'https://images.unsplash.com/photo-1461151304267-38535e780c79?auto=format&fit=crop&w=800&q=80',
    beforeLabel: 'Antes: Pantalla negra por LEDs quemados',
    afterLabel: 'Después: Brillo uniforme y colores vivos',
    details: [
      'Juego nuevo de barras de LED con base de cobre y aluminio',
      'Modificación de resistencia de muestreo en placa fuente para evitar sobrecalentamiento futuro',
      'Limpieza de difusores ópticos sin marcas de huellas',
      'Prueba de 24 horas continuas de encendido sin variaciones'
    ]
  }
];

export const MOCK_ORDERS: Record<string, RepairOrder> = {
  'TF-8841': {
    id: 'ord-1',
    ticketCode: 'TF-8841',
    clientName: 'Carlos Mendonça',
    device: 'Notebook Lenovo Legion 5 (Ryzen 7 / RTX 3060)',
    serialOrModel: 'SN: PF28JK99',
    reportedIssue: 'Apagados repentinos al jugar y teclado con teclas que no responden (espacio y enter).',
    technician: 'Ing. Lucas Varela',
    intakeDate: '14/08/2026 10:30',
    estimatedCompletion: '16/08/2026 17:00',
    costEstimate: '$42.000',
    depositPaid: '$15.000',
    remainingBalance: '$27.000',
    currentStatus: 'repairing',
    diagnosticReport: 'Se detectó obstrucción severa en turbina izquierda por acumulación de pelusa y teclado con líquido seco residual bajo las membranas. Placa base sin cortos.',
    partsUsed: ['Teclado retroiluminado RGB Lenovo original', 'Pasta térmica Thermal Grizzly Kryonaut', 'Pads térmicos 1.5mm'],
    timeline: [
      {
        stage: 'received',
        label: 'Equipo Recepcionado',
        date: '14/08 - 10:30',
        completed: true,
        current: false,
        notes: 'Ingreso en sucursal central con cargador original y funda.'
      },
      {
        stage: 'diagnosing',
        label: 'Diagnóstico en Banco',
        date: '14/08 - 14:15',
        completed: true,
        current: false,
        notes: 'Desarme preliminar y test de voltajes con osciloscopio.'
      },
      {
        stage: 'waiting_parts',
        label: 'Repuestos Asignados',
        date: '15/08 - 09:00',
        completed: true,
        current: false,
        notes: 'Teclado OEM original retirado de depósito central.'
      },
      {
        stage: 'repairing',
        label: 'En Proceso de Reparación',
        date: '15/08 - 11:30',
        completed: true,
        current: true,
        notes: 'Instalación de teclado y mantenimiento térmico integral en curso.'
      },
      {
        stage: 'qa_testing',
        label: 'Control de Calidad & Stress Test',
        date: 'Pendiente',
        completed: false,
        current: false,
        notes: 'Test de 40 min en FurMark + Prime95.'
      },
      {
        stage: 'ready',
        label: 'Listo para Retiro',
        date: 'Pendiente',
        completed: false,
        current: false,
        notes: 'Se notificará al cliente por WhatsApp y correo.'
      }
    ]
  },
  'TF-9022': {
    id: 'ord-2',
    ticketCode: 'TF-9022',
    clientName: 'Valeria Rossi',
    device: 'iPhone 13 128GB Midnight',
    serialOrModel: 'IMEI: 354891102948123',
    reportedIssue: 'Batería inflada que levantó la pantalla lateral y conector de carga flojo.',
    technician: 'Matías Benítez',
    intakeDate: '15/08/2026 09:15',
    estimatedCompletion: '15/08/2026 13:00',
    costEstimate: '$34.000',
    depositPaid: '$34.000 (Pagado)',
    remainingBalance: '$0',
    currentStatus: 'ready',
    diagnosticReport: 'Batería degradada con celdas hinchadas (peligro de perforación). Flex de carga con acumulación de fibras compactas.',
    partsUsed: ['Batería de alta capacidad Grado A+', 'Sello estanco adhesivo original'],
    timeline: [
      { stage: 'received', label: 'Equipo Recepcionado', date: '15/08 - 09:15', completed: true, current: false },
      { stage: 'diagnosing', label: 'Diagnóstico en Banco', date: '15/08 - 09:40', completed: true, current: false },
      { stage: 'repairing', label: 'Reparación Realizada', date: '15/08 - 10:30', completed: true, current: false },
      { stage: 'qa_testing', label: 'Control de Calidad', date: '15/08 - 11:15', completed: true, current: false, notes: 'Salud 100%, ciclos 0, carga rápida 20W verificada.' },
      { stage: 'ready', label: 'Listo para Retiro', date: '15/08 - 11:30', completed: true, current: true, notes: 'Disponible para retirar en Mostrador 1.' }
    ]
  },
  'TF-4115': {
    id: 'ord-3',
    ticketCode: 'TF-4115',
    clientName: 'Gonzalo Fernández',
    device: 'PlayStation 5 Edición Disco',
    serialOrModel: 'SN: 03-27489201',
    reportedIssue: 'Sobrecalentamiento con mensaje de advertencia en pantalla a los 10 min de juego.',
    technician: 'Ing. Lucas Varela',
    intakeDate: '13/08/2026 16:00',
    estimatedCompletion: '14/08/2026 18:00',
    costEstimate: '$32.000',
    depositPaid: '$10.000',
    remainingBalance: '$22.000',
    currentStatus: 'delivered',
    diagnosticReport: 'Metal líquido oxidado y desplazado hacia la parte inferior del APU dejando una zona seca sin contacto con el disipador.',
    partsUsed: ['Metal líquido thermal compound original', 'Barrera selladora de espuma de silicona'],
    timeline: [
      { stage: 'received', label: 'Recepcionado', date: '13/08', completed: true, current: false },
      { stage: 'diagnosing', label: 'Diagnóstico', date: '13/08', completed: true, current: false },
      { stage: 'repairing', label: 'Mantenimiento de Metal Líquido', date: '14/08', completed: true, current: false },
      { stage: 'qa_testing', label: 'Stress Test 3 Horas', date: '14/08', completed: true, current: false },
      { stage: 'ready', label: 'Listo', date: '14/08', completed: true, current: false },
      { stage: 'delivered', label: 'Entregado con Garantía', date: '15/08 - 10:00', completed: true, current: true }
    ]
  }
};

export const REVIEWS = [
  {
    id: 'rev-1',
    name: 'Sebastián Ocampo',
    device: 'MacBook Pro M1',
    text: 'Increíble servicio. En otro lugar me dijeron que tenía que cambiar la placa completa por un valor absurdo. En TechFix cambiaron dos capacitores en corto y quedó perfecta en 48hs.',
    rating: 5,
    date: 'Hace 3 días'
  },
  {
    id: 'rev-2',
    name: 'Carolina Morales',
    device: 'iPhone 14 Pro',
    text: 'Cambiaron la pantalla en 40 minutos mientras esperaba en la cafetería de al lado. Mantuvieron el TrueTone y la calidad es idéntica a la original. Muy recomendados.',
    rating: 5,
    date: 'Hace 1 semana'
  },
  {
    id: 'rev-3',
    name: 'Martín Almada',
    device: 'PS5 & PC Gamer',
    text: 'Llevé mi PS5 con el puerto HDMI quebrado y mi PC para mantenimiento térmico. La atención fue impecable, me mandaron fotos del proceso y la temperatura de la PC bajó 20 grados.',
    rating: 5,
    date: 'Hace 2 semanas'
  }
];

export const FAQS = [
  {
    question: '¿Cuánto tiempo demora el diagnóstico de un equipo?',
    answer: 'El diagnóstico inicial en banco de trabajo es completamente gratuito y suele demorar entre 2 a 24 horas hábiles según la complejidad de la falla.'
  },
  {
    question: '¿Qué garantía tienen las reparaciones?',
    answer: 'Todas nuestras reparaciones de hardware cuentan con garantía escrita de 90 a 180 días (6 meses), respaldada por repuestos de calidad original u homologada.'
  },
  {
    question: '¿Cuentan con servicio a domicilio o retiro express?',
    answer: 'Sí, disponemos de servicio de mensajería con seguro de traslado en todo el radio metropolitano, además de recibir equipos por encomienda desde cualquier punto del país.'
  },
  {
    question: '¿Qué medios de pago aceptan?',
    answer: 'Aceptamos transferencias bancarias, tarjetas de débito/crédito (con planes en cuotas sin interés), efectivo y pagos digitales con factura A y B.'
  }
];
