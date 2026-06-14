import { VaccineRecord, ReminderTask, RegionContribution, ServiceCostMap } from './types';

export const SERVICIOS_PRESTADOS = [
  'Vacuna Cuello',
  'Despique',
  'Re-Despique',
  'Vacuna Pechuga',
  'Doble Pechuga',
  'Aerosol'
];

export const SERVICE_COSTS: ServiceCostMap = {
  'Vacuna Cuello': 0.0082,
  'Despique': 0.0092,
  'Re-Despique': 0.0101,
  'Vacuna Pechuga': 0.0070,
  'Doble Pechuga': 0.0070,
  'Aerosol_GT_100': 0.0015,
  'Aerosol_LT_100': 0.0010
};

export function getServiceUnitCost(servicio: string, cantidad: number): number {
  if (servicio === 'Aerosol') {
    return cantidad >= 100 ? SERVICE_COSTS.Aerosol_GT_100 : SERVICE_COSTS.Aerosol_LT_100;
  }
  return SERVICE_COSTS[servicio] || 0.0050; // basic default fallback
}

export const SEED_RECORDS: VaccineRecord[] = [
  // --- ENERO (Month 1) ---
  {
    id: 'rec_1_1',
    fecha: '2026-01-12',
    hora: '09:15 AM',
    cliente: 'Granja Los Pinos',
    clienteIniciales: 'GL',
    servicio: 'Vacuna Cuello',
    cantidad: 15000,
    costoPorAve: 0.0082,
    total: 123.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'KM 14.5 Ruta Interamericana, Sector 3, Mixco'
  },
  {
    id: 'rec_1_2',
    fecha: '2026-01-11',
    hora: '11:30 AM',
    cliente: 'Avícola El Sol',
    clienteIniciales: 'AS',
    servicio: 'Despique',
    cantidad: 8000,
    costoPorAve: 0.0092,
    total: 73.60,
    estado: 'Pendiente',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'KM 22 Ruta al Atlántico, Palencia'
  },
  {
    id: 'rec_1_3',
    fecha: '2026-01-10',
    hora: '08:00 AM',
    cliente: 'Doble Pechuga Progresista',
    clienteIniciales: 'DP',
    servicio: 'Doble Pechuga',
    cantidad: 20000,
    costoPorAve: 0.0070,
    total: 140.00,
    estado: 'Completado',
    vacunador: 'Dra. Luisa Gómez',
    direccion: 'San Lucas Sacatepéquez, Lote 42'
  },
  {
    id: 'rec_1_4',
    fecha: '2026-01-09',
    hora: '04:15 PM',
    cliente: 'Avícola Guate',
    clienteIniciales: 'AG',
    servicio: 'Vacuna Pechuga',
    cantidad: 12500,
    costoPorAve: 0.0070,
    total: 87.50,
    estado: 'Completado',
    vacunador: 'Dr. Carlos Estrada',
    direccion: 'Zacapa, Teculután, Sector El Campo'
  },

  // --- FEBRERO (Month 2) ---
  {
    id: 'rec_2_1',
    fecha: '2026-02-14',
    hora: '10:15 AM',
    cliente: 'La Pradera Avícola',
    clienteIniciales: 'LP',
    servicio: 'Re-Despique',
    cantidad: 18000,
    costoPorAve: 0.0101,
    total: 181.80,
    estado: 'Completado',
    vacunador: 'Dra. Luisa Gómez',
    direccion: 'Chimaltenango, KM 54'
  },
  {
    id: 'rec_2_2',
    fecha: '2026-02-18',
    hora: '02:30 PM',
    cliente: 'Hacienda San José',
    clienteIniciales: 'HS',
    servicio: 'Vacuna Pechuga',
    cantidad: 25000,
    costoPorAve: 0.0070,
    total: 175.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'Escuintla, Masagua, Finca San José'
  },
  {
    id: 'rec_2_3',
    fecha: '2026-02-22',
    hora: '09:00 AM',
    cliente: 'Granja El Milagro',
    clienteIniciales: 'GM',
    servicio: 'Aerosol',
    cantidad: 30000,
    costoPorAve: 0.0015,
    total: 45.00,
    estado: 'Completado',
    vacunador: 'Dr. Carlos Estrada',
    direccion: 'Villa Nueva, Bárcenas Lote 8'
  },

  // --- MARZO (Month 3) ---
  {
    id: 'rec_3_1',
    fecha: '2026-03-05',
    hora: '08:45 AM',
    cliente: 'Granja Los Pinos',
    clienteIniciales: 'GL',
    servicio: 'Doble Pechuga',
    text: 'Formulario Completo',
    cantidad: 45000,
    costoPorAve: 0.0070,
    total: 315.00,
    estado: 'Completado',
    vacunador: 'Dra. Luisa Gómez',
    direccion: 'KM 14.5 Ruta Interamericana, Sector 3, Mixco'
  } as any as VaccineRecord,
  {
    id: 'rec_3_2',
    fecha: '2026-03-12',
    hora: '11:00 AM',
    cliente: 'Avícola San Miguel',
    clienteIniciales: 'SM',
    servicio: 'Vacuna Cuello',
    cantidad: 35000,
    costoPorAve: 0.0082,
    total: 287.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'Quetzaltenango, Salcajá Barrio Nuevo'
  },
  {
    id: 'rec_3_3',
    fecha: '2026-03-24',
    hora: '03:15 PM',
    cliente: 'Granja El Milagro',
    clienteIniciales: 'GM',
    servicio: 'Despique',
    cantidad: 15000,
    costoPorAve: 0.0092,
    total: 138.00,
    estado: 'Completado',
    vacunador: 'Dr. Carlos Estrada',
    direccion: 'Villa Nueva, Bárcenas Lote 8'
  },

  // --- ABRIL (Month 4) ---
  {
    id: 'rec_4_1',
    fecha: '2026-04-03',
    hora: '10:00 AM',
    cliente: 'Avícola El Sol',
    clienteIniciales: 'AS',
    servicio: 'Doble Pechuga',
    cantidad: 60000,
    costoPorAve: 0.0070,
    total: 420.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'KM 22 Ruta al Atlántico, Palencia'
  },
  {
    id: 'rec_4_2',
    fecha: '2026-04-18',
    hora: '01:30 PM',
    cliente: 'La Pradera Avícola',
    clienteIniciales: 'LP',
    servicio: 'Re-Despique',
    cantidad: 12000,
    costoPorAve: 0.0101,
    total: 121.20,
    estado: 'Completado',
    vacunador: 'Dra. Luisa Gómez',
    direccion: 'Chimaltenango, KM 54'
  },

  // --- MAYO (Month 5) ---
  {
    id: 'rec_5_1',
    fecha: '2026-05-02',
    hora: '09:45 AM',
    cliente: 'Hacienda San José',
    clienteIniciales: 'HS',
    servicio: 'Vacuna Cuello',
    cantidad: 100000,
    costoPorAve: 0.0082,
    total: 820.00,
    estado: 'Completado',
    vacunador: 'Dr. Carlos Estrada',
    direccion: 'Escuintla, Masagua, Finca San José'
  },
  {
    id: 'rec_5_2',
    fecha: '2026-05-15',
    hora: '11:15 AM',
    cliente: 'Granja Los Pinos',
    clienteIniciales: 'GL',
    servicio: 'Doble Pechuga',
    cantidad: 80000,
    costoPorAve: 0.0070,
    total: 560.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'KM 14.5 Ruta Interamericana, Sector 3, Mixco'
  },

  // --- JUNIO (Month 6) ---
  {
    id: 'rec_6_1',
    fecha: '2026-06-04',
    hora: '08:15 AM',
    cliente: 'Avícola Guate',
    clienteIniciales: 'AG',
    servicio: 'Doble Pechuga',
    cantidad: 50000,
    costoPorAve: 0.0070,
    total: 350.00,
    estado: 'Completado',
    vacunador: 'Dr. Carlos Estrada',
    direccion: 'Zacapa, Teculután, Sector El Campo'
  },
  {
    id: 'rec_6_2',
    fecha: '2026-06-12',
    hora: '10:30 AM',
    cliente: 'Avícola El Sol',
    clienteIniciales: 'AS',
    servicio: 'Despique',
    cantidad: 22000,
    costoPorAve: 0.0092,
    total: 202.40,
    estado: 'Completado',
    vacunador: 'Dra. Luisa Gómez',
    direccion: 'KM 22 Ruta al Atlántico, Palencia'
  },
  {
    id: 'rec_6_3',
    fecha: '2026-06-14',
    hora: '09:00 AM',
    cliente: 'Granja El Milagro',
    clienteIniciales: 'GM',
    servicio: 'Vacuna Pechuga',
    cantidad: 40000,
    costoPorAve: 0.0070,
    total: 280.00,
    estado: 'Completado',
    vacunador: 'Dr. Rodrigo M.',
    direccion: 'Villa Nueva, Bárcenas Lote 8'
  }
];

export const INITIAL_REMINDERS: ReminderTask[] = [
  {
    id: 'rem_1',
    titulo: 'Segunda Dosis: Lote A4',
    descripcion: 'Mañana, 08:00 AM - Granja Los Pinos',
    importancia: 'alta'
  },
  {
    id: 'rem_2',
    titulo: 'Reporte Semanal',
    descripcion: 'Hoy, 05:00 PM - Revisión de KPIs',
    importancia: 'media'
  },
  {
    id: 'rem_3',
    titulo: 'Cita: Avícola El Sol',
    descripcion: '15 Ene - Coordinación de logística',
    importancia: 'baja'
  }
];

export const REGIONAL_DISTRIBUTION: RegionContribution[] = [
  { name: 'Guatemala', percentage: 45, color: '#3455b9' },
  { name: 'Escuintla', percentage: 30, color: '#006a63' },
  { name: 'Quetzaltenango', percentage: 25, color: '#a8295b' }
];

export function formatCurrency(amount: number): string {
  return `Q ${amount.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function formatNumber(num: number): string {
  return num.toLocaleString('es-GT');
}

export function formatCompactDate(dateString: string): string {
  try {
    const parts = dateString.split('-');
    if (parts.length !== 3) return dateString;
    const year = parts[0];
    const monthIndex = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    
    // Custom friendly month in Spanish
    const months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${day} ${months[monthIndex] || parts[1]}`;
  } catch (e) {
    return dateString;
  }
}
