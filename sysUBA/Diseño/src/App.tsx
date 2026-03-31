import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import Layout from './components/Layout';
import Login from './pages/Login';
import AdminDashboard from './pages/AdminDashboard';
import Denuncias from './pages/Denuncias';
import Servicios from './pages/Servicios';
import Noticias from './pages/Noticias';
import Reportes from './pages/Reportes';
import TechDashboard from './pages/TechDashboard';
import TechInbox from './pages/TechInbox';
import CaseDetail from './pages/CaseDetail';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Login />} />
        
        <Route element={<Layout />}>
          {/* Admin Routes */}
          <Route path="/admin/dashboard" element={<AdminDashboard />} />
          <Route path="/admin/denuncias" element={<Denuncias />} />
          <Route path="/admin/servicios" element={<Servicios />} />
          <Route path="/admin/noticias" element={<Noticias />} />
          <Route path="/admin/reportes" element={<Reportes />} />
          
          {/* Tech Routes */}
          <Route path="/tech/dashboard" element={<TechDashboard />} />
          <Route path="/tech/bandeja" element={<TechInbox />} />
          <Route path="/tech/caso/:id" element={<CaseDetail />} />
          <Route path="/tech/reportes" element={<Reportes />} />
        </Route>
        
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </BrowserRouter>
  );
}
