export interface Highlight {
  iconName: string;
  label: string;
  subLabel?: string;
}

export interface PollOption {
  id: string;
  title: string;
  subtitle?: string;
  imageUrl?: string;
  decorIcon: string;
  highlights: Highlight[];
}

export interface PollStep {
  id: number;
  titlePrerender: string;
  titleItalic: string;
  subtitle: string;
  options: PollOption[];
}

export interface UserSelections {
  step1: string | null;
  step2: string | null;
  step3: string | null;
  userName?: string;
}

export interface PollStats {
  format: {
    stadium: number;
    ballroom: number;
  };
  gastronomy: {
    cocktails: number;
    dinner: number;
  };
  dressCode: {
    blackTie: number;
    contemporary: number;
  };
}
