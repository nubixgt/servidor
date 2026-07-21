export type Screen = 'login' | 'directory' | 'judging' | 'leaderboard';

export type ModelStatus = 'READY' | 'WALKING' | 'JUDGED';

export interface Scores {
  walk: number;
  presence: number;
  garment: number;
  originality: number;
  total: number;
}

export interface Model {
  id: string;
  name: string;
  look: string;
  designer: string;
  imageUrl: string;
  avatarUrl: string;
  podiumUrl: string;
  status: ModelStatus;
  scores: Scores;
}

export interface JudgeSession {
  judgeId: string;
  accessCode: string;
  name: string;
  authenticated: boolean;
}
