import React, { useState } from 'react';
import { 
  Building2, 
  Store, 
  Home, 
  Search, 
  Filter, 
  Plus, 
  MoreVertical,
  CheckCircle2,
  XCircle,
  Wrench
} from 'lucide-react';
import { cn } from '../../lib/utils';

// Mock data based on PRD
const locations = [
  { id: 'H-01', name: 'Heladería CC Pradera', type: 'Heladería', address: 'CC Pradera, Guatemala', status: 'Activa', responsible: 'Ana Gómez' },
  { id: 'H-02', name: 'Heladería Gasolinera Texaco', type: 'Heladería', address: 'Gasolinera Texaco', status: 'Activa', responsible: 'Carlos Ruiz' },
  { id: 'H-03', name: 'Heladería Tecpán', type: 'Heladería', address: 'Tecpán Guatemala, Chimaltenango', status: 'Activa', responsible: 'María López' },
  { id: 'A-01', name: 'Casa en Arrendamiento 1', type: 'Casa', address: 'Zona 14, Ciudad', status: 'Ocupada', tenant: 'Familia Pérez', rent: 'GTQ 5,000' },
  { id: 'A-02', name: 'Casa en Arrendamiento 2', type: 'Casa', address: 'Zona 15, Ciudad', status: 'Disponible', tenant: '-', rent: 'GTQ 6,500' },
  { id: 'A-03', name: 'Casa en Arrendamiento 3', type: 'Casa', address: 'Carretera a El Salvador', status: 'Mantenimiento', tenant: '-', rent: 'GTQ 4,800' },
];

const commercialUnits = [
  { id: 'L-01', type: 'Local', status: 'Ocupada', tenant: 'Cafetería El Grano', rent: 'GTQ 3,500', nextPayment: '05/04/2026' },
  { id: 'L-02', type: 'Local', status: 'Disponible', tenant: '-', rent: 'GTQ 3,500', nextPayment: '-' },
  { id: 'B-01', type: 'Bodega', status: 'Ocupada', tenant: 'Logística S.A.', rent: 'GTQ 2,000', nextPayment: '10/04/2026' },
  { id: 'B-02', type: 'Bodega', status: 'Mantenimiento', tenant: '-', rent: 'GTQ 2,000', nextPayment: '-' },
  // ... representing the 18 units
];

export default function AdminLocations() {
  const [activeTab, setActiveTab] = useState<'general' | 'comercial'>('general');

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'Activa':
      case 'Ocupada':
        return <CheckCircle2 className="w-4 h-4 text-secondary" />;
      case 'Disponible':
        return <CheckCircle2 className="w-4 h-4 text-outline" />;
      case 'Mantenimiento':
        return <Wrench className="w-4 h-4 text-tertiary-fixed-dim" />;
      default:
        return <XCircle className="w-4 h-4 text-error" />;
    }
  };

  const getStatusClass = (status: string) => {
    switch (status) {
      case 'Activa':
      case 'Ocupada':
        return "bg-secondary-container text-on-secondary-container";
      case 'Disponible':
        return "bg-surface-container-high text-on-surface-variant";
      case 'Mantenimiento':
        return "bg-tertiary-fixed-dim/20 text-tertiary";
      default:
        return "bg-error-container text-on-error-container";
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-sans font-bold text-on-surface tracking-tight">Gestión de Locaciones</h1>
          <p className="text-sm text-on-surface-variant mt-1">Administra heladerías, casas y la propiedad comercial.</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-container transition-colors shadow-sm">
          <Plus className="w-4 h-4" />
          Nueva Locación
        </button>
      </div>

      {/* Tabs */}
      <div className="flex space-x-1 bg-surface-container-low p-1 rounded-xl w-fit">
        <button
          onClick={() => setActiveTab('general')}
          className={cn(
            "px-4 py-2 text-sm font-medium rounded-lg transition-colors",
            activeTab === 'general' ? "bg-white text-on-surface shadow-sm" : "text-on-surface-variant hover:text-on-surface"
          )}
        >
          Heladerías y Casas
        </button>
        <button
          onClick={() => setActiveTab('comercial')}
          className={cn(
            "px-4 py-2 text-sm font-medium rounded-lg transition-colors",
            activeTab === 'comercial' ? "bg-white text-on-surface shadow-sm" : "text-on-surface-variant hover:text-on-surface"
          )}
        >
          Propiedad Comercial (Sub-unidades)
        </button>
      </div>

      <div className="glass-card overflow-hidden">
        <div className="p-4 border-b border-outline-variant/20 flex flex-col sm:flex-row gap-4 justify-between items-center bg-surface-container-lowest">
          <div className="relative w-full sm:w-96">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <input 
              type="text" 
              placeholder="Buscar locación..." 
              className="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
            />
          </div>
          <button className="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-surface-container-low text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30">
            <Filter className="w-4 h-4" />
            Filtros
          </button>
        </div>

        <div className="overflow-x-auto">
          {activeTab === 'general' ? (
            <table className="w-full text-left text-sm">
              <thead className="bg-surface-container-low text-on-surface-variant font-medium">
                <tr>
                  <th className="px-6 py-4 font-medium">ID</th>
                  <th className="px-6 py-4 font-medium">Nombre / Dirección</th>
                  <th className="px-6 py-4 font-medium">Tipo</th>
                  <th className="px-6 py-4 font-medium">Estado</th>
                  <th className="px-6 py-4 font-medium">Responsable / Inquilino</th>
                  <th className="px-6 py-4 font-medium text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-outline-variant/20">
                {locations.map((loc) => (
                  <tr key={loc.id} className="hover:bg-surface-container-lowest transition-colors group">
                    <td className="px-6 py-4 font-medium text-on-surface">{loc.id}</td>
                    <td className="px-6 py-4">
                      <div className="font-semibold text-on-surface">{loc.name}</div>
                      <div className="text-xs text-on-surface-variant">{loc.address}</div>
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2 text-on-surface-variant">
                        {loc.type === 'Heladería' ? <Store className="w-4 h-4" /> : <Home className="w-4 h-4" />}
                        {loc.type}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={cn("px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit", getStatusClass(loc.status))}>
                        {getStatusIcon(loc.status)}
                        {loc.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-on-surface-variant">
                      {loc.responsible || loc.tenant}
                      {loc.rent && <div className="text-xs font-medium text-primary mt-0.5">{loc.rent}/mes</div>}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <button className="p-2 text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                        <MoreVertical className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          ) : (
            <table className="w-full text-left text-sm">
              <thead className="bg-surface-container-low text-on-surface-variant font-medium">
                <tr>
                  <th className="px-6 py-4 font-medium">ID Unidad</th>
                  <th className="px-6 py-4 font-medium">Tipo</th>
                  <th className="px-6 py-4 font-medium">Estado</th>
                  <th className="px-6 py-4 font-medium">Inquilino</th>
                  <th className="px-6 py-4 font-medium">Renta Mensual</th>
                  <th className="px-6 py-4 font-medium">Próximo Pago</th>
                  <th className="px-6 py-4 font-medium text-right">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-outline-variant/20">
                {commercialUnits.map((unit) => (
                  <tr key={unit.id} className="hover:bg-surface-container-lowest transition-colors group">
                    <td className="px-6 py-4 font-medium text-on-surface">{unit.id}</td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2 text-on-surface-variant">
                        <Building2 className="w-4 h-4" />
                        {unit.type}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <span className={cn("px-2.5 py-1 rounded-full text-xs font-medium flex items-center gap-1.5 w-fit", getStatusClass(unit.status))}>
                        {getStatusIcon(unit.status)}
                        {unit.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-on-surface-variant">{unit.tenant}</td>
                    <td className="px-6 py-4 font-medium text-primary">{unit.rent}</td>
                    <td className="px-6 py-4 text-on-surface-variant">{unit.nextPayment}</td>
                    <td className="px-6 py-4 text-right">
                      <button className="p-2 text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors opacity-0 group-hover:opacity-100">
                        <MoreVertical className="w-4 h-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}
