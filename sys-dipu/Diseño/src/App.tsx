import React, { useState } from 'react';
import { Role, Category } from './types';
import Login from './pages/auth/Login';
import MainLayout from './layouts/MainLayout';
import ExecutiveDashboard from './pages/dashboards/ExecutiveDashboard';
import ResponsableDashboard from './pages/dashboards/ResponsableDashboard';
import Iniciativas from './pages/Iniciativas';
import Citaciones from './pages/Citaciones';
import Comisiones from './pages/Comisiones';
import Fiscalizacion from './pages/Fiscalizacion';
import Compromisos from './pages/Compromisos';
import Actividades from './pages/Actividades';
import Redes from './pages/Redes';
import Afiliaciones from './pages/Afiliaciones';
import Usuarios from './pages/Usuarios';
import Kanban from './pages/Kanban';
import Calendar from './pages/Calendar';
import SystemConfig from './pages/SystemConfig';
import Archivo from './pages/Archivo';

export default function App() {
  const [role, setRole] = useState<Role | null>(null);
  const [assignedCategory, setAssignedCategory] = useState<Category | null>(null);
  const [currentView, setCurrentView] = useState('dashboard');

  if (!role) {
    return <Login onLogin={(selectedRole, category) => {
      setRole(selectedRole);
      setAssignedCategory(category || null);
    }} />;
  }

  const renderDashboard = () => {
    switch (role) {
      case 'administrador':
        return <ExecutiveDashboard />;
      case 'tecnico':
        return <ResponsableDashboard />;
      default:
        return <ExecutiveDashboard />;
    }
  };

  return (
    <MainLayout 
      role={role} 
      assignedCategory={assignedCategory}
      currentView={currentView} 
      setCurrentView={setCurrentView}
      onLogout={() => {
        setRole(null);
        setAssignedCategory(null);
        setCurrentView('dashboard');
      }}
    >
      {currentView === 'dashboard' && renderDashboard()}
      {currentView === 'iniciativas' && <Iniciativas />}
      {currentView === 'citaciones' && <Citaciones />}
      {currentView === 'comisiones' && <Comisiones />}
      {currentView === 'fiscalizacion' && <Fiscalizacion />}
      {currentView === 'compromisos' && <Compromisos />}
      {currentView === 'actividades' && <Actividades />}
      {currentView === 'redes' && <Redes />}
      {currentView === 'afiliaciones' && <Afiliaciones />}
      {currentView === 'calendario' && <Calendar />}
      {currentView === 'kanban' && <Kanban />}
      {currentView === 'archivo' && <Archivo />}
      {currentView === 'usuarios' && <Usuarios />}
      {currentView === 'configuracion' && <SystemConfig />}
    </MainLayout>
  );
}
