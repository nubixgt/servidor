import React, { createContext, useContext, useState, ReactNode } from 'react';

type Role = 'admin' | 'tech' | null;

interface User {
  id: string;
  name: string;
  role: Role;
  email: string;
}

interface AuthContextType {
  user: User | null;
  login: (role: Role) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);

  const login = (role: Role) => {
    if (role === 'admin') {
      setUser({ id: '1', name: 'Admin User', role: 'admin', email: 'admin@example.com' });
    } else if (role === 'tech') {
      setUser({ id: '2', name: 'Tech User', role: 'tech', email: 'tech@example.com' });
    }
  };

  const logout = () => {
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
