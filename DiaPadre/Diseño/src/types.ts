export interface Registration {
  id: string;
  fullName: string;
  phone: string;
  email: string;
  direction: string;
  department: string;
  dateCreated: string;
  code: string;
}

export type ScreenType = 'form' | 'success' | 'admin';
