export type UserRole = "tecnico" | "admin" | "tecnico_dashboard" | "tecnico_piloto";

export interface User {
  username: string;
  role: UserRole;
  fullName: string;
}

export interface OperationalLog {
  id: string;
  operatorName: string;
  machineType: string;
  regType: "inicial" | "final";
  horometroValue: number;
  dateTime: string;
  photoUrl?: string;
  location: {
    lat: number;
    lng: number;
    formattedAddress: string;
  };
}
