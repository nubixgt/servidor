export type NavigationTab = 
  | 'dashboard' 
  | 'plot-inventory' 
  | 'registration-wizard'
  | 'technical-variables'
  | 'supervisor-reviews' 
  | 'field-surveys' 
  | 'user-management' 
  | 'settings';

export type Role = 'Admin' | 'Supervisor' | 'Técnico';

export type PlotStatus = 'Levantamiento' | 'En Revisión' | 'Aprobado' | 'Validado V2' | 'Pendiente';

export type BioindicatorType = 
  | 'Lombrices' 
  | 'Hongos' 
  | 'Hormigas' 
  | 'Hojarasca' 
  | 'Micelio' 
  | 'Estructura granular' 
  | 'Ciempiés' 
  | 'Escarabajos';

export interface Parcela {
  id: string; // e.g. PLT-2023-001
  code: string;
  name: string;
  producer: string;
  department: string;
  municipality: string;
  community: string;
  benefitedFamilies: number;
  registrationDate: string;
  areaHa: number;
  latitude: number;
  longitude: number;
  accuracyMeters: number;
  technicianName: string;
  technicianId?: string;
  technicianAvatar?: string;
  status: PlotStatus;
  validationTag?: string; // e.g. "Validado V2"
  
  // Soil & Keyline Details
  soilTexture: string; // "Franco-arcilloso", "Arcilloso", "Franco-arenoso"
  erosionLevel: 'Leve' | 'Moderada' | 'Severa';
  slopeDegrees: number;
  arableDepthCm: number;
  moistureRetention: string;
  hasWaterSources: boolean;
  
  // Keyline Details
  keylinePractice: string; // "Zanjas de infiltración (Swales)", "Terrazas de contorno", "Subsolado en curva de nivel"
  lineSpacingMeters: number;
  totalLengthMeters: number;
  associatedSpecies: string[];
  
  // Bioindicators & Photos
  bioindicators: BioindicatorType[];
  photos: string[];
  notes?: string;
}

export interface BioindicatorSample {
  id: string; // e.g. P-GT-0142
  parcelId: string;
  parcelName: string;
  technicianInitials: string;
  technicianName: string;
  sampleDate: string;
  keyIndicators: BioindicatorType[];
  status: 'Óptimo' | 'En Recuperación' | 'Alerta Plaga' | 'Pendiente';
  depthCm: number;
  ph: number;
  organicMatterPct: number;
  notes?: string;
}

export interface UserProfile {
  id: string;
  name: string;
  username: string;
  email: string;
  role: Role;
  region: string;
  status: 'Active' | 'Inactive';
  lastAccess: string;
  avatarUrl?: string;
  initials?: string;
  employeeId?: string;
  phone?: string;
}

export interface SystemAlert {
  id: string;
  title: string;
  description: string;
  type: 'erosion' | 'tactical' | 'weather' | 'review';
  date: string;
  location: string;
  severity: 'high' | 'medium' | 'info';
}
