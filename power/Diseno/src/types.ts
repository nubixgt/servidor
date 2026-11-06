export type ViewType = 'inicio' | 'perfil' | 'plan' | 'historial' | 'cuaderno' | 'login' | 'analytics' | 'reports';

export type SportType = 'bike' | 'run' | 'swim' | 'rest' | 'gym';

export type NoteType = 'vigente' | 'propuesta' | 'informativa';
export type NoteCategory = 'fisiologia' | 'biomecanica' | 'nutricion' | 'logistica' | 'recuperacion';

export interface AthleteProfile {
  id: string;
  name: string;
  roleTitle: string;
  avatarUrl: string;
  primarySport: string;
  garminEmail: string;
  garminConnected: boolean;
  completionRate: number;
  restingHeartRate: number;
  maxHeartRate: number;
  hrv: number;
  vo2Max: number;
  ftpWatts: number;
  runThresholdPace: string;
  swimThresholdPace: string;
  weightKg: number;
  heightCm: number;
  nextTargetRace: {
    name: string;
    location: string;
    daysRemaining: number;
    targetDate: string;
    progressPercent: number;
  };
}

export interface WorkoutSession {
  id: string;
  dayLabel: 'LUN' | 'MAR' | 'MIE' | 'JUE' | 'VIE' | 'SAB' | 'DOM';
  dayNumber: string;
  dateStr: string;
  title: string;
  sport: SportType;
  tag: string;
  tagColor?: string;
  durationStr: string;
  durationMinutes: number;
  intensityScore: number; // 1-5
  status: 'completed' | 'modified' | 'missed' | 'planned' | 'current';
  timeOfDay?: string;
  steps: string[];
  purpose: string;
  targetZone?: string;
  tss?: number;
  garminSynced?: boolean;
}

export interface CoachNote {
  id: string;
  type: NoteType;
  category: NoteCategory;
  dateStr: string;
  text: string;
  sport: SportType;
  isConfirmed?: boolean;
  isAppliedToPlan?: boolean;
}

export interface MacrocycleHistoryItem {
  id: string;
  period: string;
  weeks: number;
  sessionsCount: number;
  aiInvestment: number;
  totalVolumeHours: number;
  adherencePercent: number;
  phase: string;
  focus: string;
  summaryNotes: string;
}

export interface AdherenceStats {
  percentage: number;
  statusLabel: string;
  completedCount: number;
  modifiedCount: number;
  missedCount: number;
}

export interface CurrentMacrocycle {
  name: string;
  phase: string;
  weeksCount: number;
  sessionsCount: number;
  volumeHours: number;
  startDate: string;
  endDate: string;
  description: string;
}

export interface ChatMessage {
  id: string;
  sender: 'athlete' | 'ai';
  text: string;
  timestamp: string;
  badge?: string;
}
