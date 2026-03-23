import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './context/AuthContext';
import Layout from './components/Layout';
import Login from './pages/Login';
import AdminDashboard from './pages/admin/Dashboard';
import AdminUsers from './pages/admin/Users';
import AdminLocations from './pages/admin/Locations';
import AdminNewTransaction from './pages/admin/NewTransaction';
import AdminReports from './pages/admin/Reports';
import TechDashboard from './pages/tech/Dashboard';

// Placeholder components for unbuilt routes
const Placeholder = ({ title }: { title: string }) => (
  <div className="flex items-center justify-center h-full min-h-[400px]">
    <h2 className="text-2xl font-sans font-bold text-outline">{title} (En construcción)</h2>
  </div>
);

// Protected Route Wrapper
const ProtectedRoute = ({ children, allowedRole }: { children: React.ReactNode, allowedRole: 'admin' | 'tech' }) => {
  const { user } = useAuth();
  
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  
  if (user.role !== allowedRole) {
    return <Navigate to={`/${user.role}`} replace />;
  }
  
  return <>{children}</>;
};

function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      
      {/* Admin Routes */}
      <Route path="/admin" element={
        <ProtectedRoute allowedRole="admin">
          <Layout />
        </ProtectedRoute>
      }>
        <Route index element={<AdminDashboard />} />
        <Route path="users" element={<AdminUsers />} />
        <Route path="locations" element={<AdminLocations />} />
        <Route path="new" element={<AdminNewTransaction />} />
        <Route path="reports" element={<AdminReports />} />
      </Route>

      {/* Tech Routes */}
      <Route path="/tech" element={
        <ProtectedRoute allowedRole="tech">
          <Layout />
        </ProtectedRoute>
      }>
        <Route index element={<TechDashboard />} />
        <Route path="history" element={<Placeholder title="Historial Técnico" />} />
        <Route path="new-ingreso" element={<Placeholder title="Nuevo Ingreso" />} />
        <Route path="new-egreso" element={<Placeholder title="Nuevo Egreso" />} />
      </Route>

      {/* Default Redirect */}
      <Route path="/" element={<Navigate to="/login" replace />} />
      <Route path="*" element={<Navigate to="/login" replace />} />
    </Routes>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <Router>
        <AppRoutes />
      </Router>
    </AuthProvider>
  );
}
