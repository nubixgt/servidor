export type NavTab = 'inicio' | 'servicios' | 'trabajos' | 'contacto';

export interface ServiceItem {
  id: string;
  title: string;
  category: 'hardware' | 'mobile' | 'displays' | 'gaming' | 'software' | 'maintenance';
  iconName: 'laptop' | 'smartphone' | 'tv' | 'gamepad' | 'terminal' | 'grid' | 'wrench';
  shortDescription: string;
  fullDescription: string;
  features: string[];
  estimatedTime: string;
  startingPrice: string;
  warranty: string;
  commonIssues: string[];
}

export interface PortfolioItem {
  id: string;
  title: string;
  device: string;
  category: string;
  problem: string;
  solution: string;
  turnaround: string;
  warranty: string;
  beforeImage: string;
  afterImage: string;
  beforeLabel?: string;
  afterLabel?: string;
  details: string[];
}

export type OrderStatusStage = 
  | 'received'       // Recepcionado
  | 'diagnosing'     // En Diagnóstico
  | 'waiting_parts'  // Esperando Repuestos
  | 'repairing'      // En Reparación
  | 'qa_testing'     // Control de Calidad
  | 'ready'          // Listo para Retiro / Envío
  | 'delivered';     // Entregado

export interface OrderTimelineStep {
  stage: OrderStatusStage;
  label: string;
  date: string;
  completed: boolean;
  current: boolean;
  notes?: string;
}

export interface RepairOrder {
  id: string;
  ticketCode: string;
  clientName: string;
  device: string;
  serialOrModel: string;
  reportedIssue: string;
  technician: string;
  intakeDate: string;
  estimatedCompletion: string;
  costEstimate: string;
  depositPaid: string;
  remainingBalance: string;
  currentStatus: OrderStatusStage;
  timeline: OrderTimelineStep[];
  diagnosticReport: string;
  partsUsed?: string[];
}

export interface QuoteFormData {
  deviceType: string;
  brand: string;
  model: string;
  issueType: string;
  description: string;
  serviceMode: 'sucursal' | 'domicilio' | 'envio';
  urgency: 'normal' | 'express';
  clientName: string;
  clientEmail: string;
  clientPhone: string;
}
