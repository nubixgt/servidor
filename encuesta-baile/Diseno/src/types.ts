export interface VoteOption {
  id: string; // 'option-a' or 'option-b'
  title: string;
  badge: string;
  badgeColor: string;
  image: string;
  description: string;
  imageAlt: string;
}

export interface Comment {
  id: string;
  userName: string;
  avatarUrl?: string;
  optionId: 'option-a' | 'option-b';
  opinion: string;
  timestamp: string;
  votesUp: number;
}

export type ActiveScreen = 'dashboard' | 'survey' | 'results' | 'opinions';

export interface SurveyState {
  selectedOptionId: string | null;
  hasVoted: boolean;
  userVote: string | null;
  userOpinion: string;
  userName: string;
  comments: Comment[];
  votesA: number;
  votesB: number;
  isRegisteredSuccess: boolean;
}
