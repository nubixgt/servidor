export interface VaccineRecord {
  id: string;
  fecha: string; // ISO date or formatted e.g. "2026-06-12"
  hora: string; // Formatting e.g. "09:15 AM"
  cliente: string;
  clienteIniciales: string;
  servicio: string;
  cantidad: number;
  costoPorAve: number;
  total: number;
  estado: 'Completado' | 'Pendiente' | 'Cancelado';
  vacunador: string;
  direccion: string;
}

export interface ReminderTask {
  id: string;
  titulo: string;
  descripcion: string;
  importancia: 'alta' | 'media' | 'baja';
}

export interface RegionContribution {
  name: string;
  percentage: number;
  color: string;
}

export interface ServiceCostMap {
  [serviceName: string]: number;
}
