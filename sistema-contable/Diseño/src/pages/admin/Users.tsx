import React, { useState } from 'react';
import { 
  Users as UsersIcon, 
  Search, 
  Filter, 
  Plus, 
  MoreVertical,
  Edit2,
  Trash2
} from 'lucide-react';
import { cn } from '../../lib/utils';

const initialUsers = [
  { id: '1', name: 'Ana Gómez', email: 'ana.gomez@empresa.com', role: 'Administrador', status: 'Activo', lastLogin: 'Hoy, 09:00 AM' },
  { id: '2', name: 'Carlos Ruiz', email: 'carlos.ruiz@empresa.com', role: 'Técnico', status: 'Activo', lastLogin: 'Ayer, 15:30 PM' },
  { id: '3', name: 'María López', email: 'maria.lopez@empresa.com', role: 'Técnico', status: 'Inactivo', lastLogin: 'Hace 3 días' },
  { id: '4', name: 'Juan Pérez', email: 'juan.perez@empresa.com', role: 'Técnico', status: 'Activo', lastLogin: 'Hoy, 11:15 AM' },
  { id: '5', name: 'Laura Martínez', email: 'laura.martinez@empresa.com', role: 'Administrador', status: 'Activo', lastLogin: 'Hoy, 08:45 AM' },
];

export default function AdminUsers() {
  const [searchTerm, setSearchTerm] = useState('');
  const [users] = useState(initialUsers);

  const filteredUsers = users.filter(user => 
    user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-sans font-bold text-on-surface tracking-tight">Gestión de Usuarios</h1>
          <p className="text-sm text-on-surface-variant mt-1">Administra los accesos y roles del sistema.</p>
        </div>
        <button className="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-xl text-sm font-medium hover:bg-primary-container transition-colors shadow-sm">
          <Plus className="w-4 h-4" />
          Nuevo Usuario
        </button>
      </div>

      <div className="glass-card overflow-hidden">
        {/* Toolbar */}
        <div className="p-4 border-b border-outline-variant/20 flex flex-col sm:flex-row gap-4 justify-between items-center bg-surface-container-lowest">
          <div className="relative w-full sm:w-96">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-outline" />
            <input 
              type="text" 
              placeholder="Buscar por nombre o email..." 
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="w-full pl-10 pr-4 py-2 bg-surface-container-low border border-outline-variant/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
            />
          </div>
          <div className="flex items-center gap-2 w-full sm:w-auto">
            <button className="flex-1 sm:flex-none flex items-center justify-center gap-2 px-4 py-2 bg-surface-container-low text-on-surface-variant rounded-xl text-sm font-medium hover:bg-surface-container transition-colors border border-outline-variant/30">
              <Filter className="w-4 h-4" />
              Filtros
            </button>
          </div>
        </div>

        {/* Table */}
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="bg-surface-container-low text-on-surface-variant font-medium">
              <tr>
                <th className="px-6 py-4 font-medium">Usuario</th>
                <th className="px-6 py-4 font-medium">Rol</th>
                <th className="px-6 py-4 font-medium">Estado</th>
                <th className="px-6 py-4 font-medium">Último Acceso</th>
                <th className="px-6 py-4 font-medium text-right">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-outline-variant/20">
              {filteredUsers.map((user) => (
                <tr key={user.id} className="hover:bg-surface-container-lowest transition-colors group">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center font-bold">
                        {user.name.charAt(0)}
                      </div>
                      <div>
                        <div className="font-semibold text-on-surface">{user.name}</div>
                        <div className="text-xs text-on-surface-variant">{user.email}</div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4">
                    <span className={cn(
                      "px-2.5 py-1 rounded-full text-xs font-medium",
                      user.role === 'Administrador' ? "bg-primary-container text-on-primary-container" : "bg-surface-container-high text-on-surface-variant"
                    )}>
                      {user.role}
                    </span>
                  </td>
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <div className={cn(
                        "w-2 h-2 rounded-full",
                        user.status === 'Activo' ? "bg-secondary" : "bg-error"
                      )} />
                      <span className="text-on-surface-variant">{user.status}</span>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-on-surface-variant">{user.lastLogin}</td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                      <button className="p-2 text-outline hover:text-primary hover:bg-primary-fixed/50 rounded-lg transition-colors">
                        <Edit2 className="w-4 h-4" />
                      </button>
                      <button className="p-2 text-outline hover:text-error hover:bg-error-container/50 rounded-lg transition-colors">
                        <Trash2 className="w-4 h-4" />
                      </button>
                      <button className="p-2 text-outline hover:text-on-surface hover:bg-surface-container rounded-lg transition-colors">
                        <MoreVertical className="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          
          {filteredUsers.length === 0 && (
            <div className="p-8 text-center text-on-surface-variant">
              <UsersIcon className="w-12 h-12 mx-auto text-outline-variant mb-3" />
              <p className="font-medium">No se encontraron usuarios</p>
              <p className="text-sm mt-1">Intenta con otros términos de búsqueda.</p>
            </div>
          )}
        </div>
        
        {/* Pagination */}
        <div className="p-4 border-t border-outline-variant/20 flex items-center justify-between bg-surface-container-lowest text-sm text-on-surface-variant">
          <div>Mostrando {filteredUsers.length} de {users.length} resultados</div>
          <div className="flex gap-1">
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container disabled:opacity-50">Anterior</button>
            <button className="px-3 py-1 bg-primary text-white rounded-lg">1</button>
            <button className="px-3 py-1 border border-outline-variant/30 rounded-lg hover:bg-surface-container disabled:opacity-50">Siguiente</button>
          </div>
        </div>
      </div>
    </div>
  );
}
