import React, { useState, useMemo } from 'react';
import { 
  Users, 
  UserPlus, 
  Search, 
  ShieldCheck, 
  MapPin, 
  CheckCircle2, 
  XCircle, 
  Trash2,
  X,
  Mail,
  Phone
} from 'lucide-react';
import { UserProfile, Role } from '../types';

interface UserManagementViewProps {
  users: UserProfile[];
  onAddUser: (user: UserProfile) => void;
  onUpdateUserStatus: (userId: string, status: 'Active' | 'Inactive') => void;
  onDeleteUser: (userId: string) => void;
}

export const UserManagementView: React.FC<UserManagementViewProps> = ({
  users,
  onAddUser,
  onUpdateUserStatus,
  onDeleteUser
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [roleFilter, setRoleFilter] = useState('Todos');
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);

  // New User Form State
  const [newUserData, setNewUserData] = useState({
    name: '',
    email: '',
    username: '',
    role: 'Técnico' as Role,
    region: 'Alta Verapaz',
    phone: '+502 '
  });

  const roles = ['Todos', 'Admin', 'Supervisor', 'Técnico'];

  const filteredUsers = useMemo(() => {
    return users.filter(u => {
      const matchesSearch = 
        u.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        u.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
        u.region.toLowerCase().includes(searchTerm.toLowerCase());
      
      const matchesRole = roleFilter === 'Todos' || u.role === roleFilter;

      return matchesSearch && matchesRole;
    });
  }, [users, searchTerm, roleFilter]);

  const handleCreateUser = (e: React.FormEvent) => {
    e.preventDefault();
    const initials = newUserData.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() || 'US';
    const newUser: UserProfile = {
      id: `u-${Date.now()}`,
      name: newUserData.name,
      email: newUserData.email,
      username: newUserData.username || newUserData.email.split('@')[0],
      role: newUserData.role,
      region: newUserData.region,
      status: 'Active',
      initials: initials,
      phone: newUserData.phone,
      lastAccess: 'Hoy'
    };

    onAddUser(newUser);
    setIsAddModalOpen(false);
    setNewUserData({
      name: '',
      email: '',
      username: '',
      role: 'Técnico',
      region: 'Alta Verapaz',
      phone: '+502 '
    });
  };

  return (
    <div className="space-y-6 max-w-[1600px] mx-auto animate-fadeIn pb-12">
      {/* Header */}
      <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">Usuarios y Equipo de Campo</h2>
          <p className="text-xs sm:text-sm text-[#cbd5e1] mt-0.5">
            Gestión de roles técnicos, supervisores zonales y administradores KeylineGT.
          </p>
        </div>

        <button
          onClick={() => setIsAddModalOpen(true)}
          className="flex items-center space-x-2 px-4 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-semibold tracking-wider uppercase transition-all shadow-[0_0_20px_rgba(34,197,94,0.4)]"
        >
          <UserPlus className="w-4 h-4" />
          <span>Nuevo Usuario</span>
        </button>
      </div>

      {/* KPI Stats */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Total Usuarios</span>
          <div className="mt-2">
            <div className="text-3xl font-bold text-white font-mono">{users.length}</div>
            <p className="text-xs text-[#4ade80] font-semibold mt-1">Activos en plataforma</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Técnicos de Campo</span>
          <div className="mt-2">
            <div className="text-3xl font-bold text-white font-mono">
              {users.filter(u => u.role === 'Técnico').length}
            </div>
            <p className="text-xs text-[#cbd5e1] mt-1">Levantamiento de parcelas</p>
          </div>
        </div>

        <div className="glass-panel rounded-2xl p-5 flex flex-col justify-between">
          <span className="text-[11px] font-bold tracking-wider text-[#94a3b8] uppercase">Supervisores & Admins</span>
          <div className="mt-2">
            <div className="text-3xl font-bold text-white font-mono">
              {users.filter(u => u.role === 'Supervisor' || u.role === 'Admin').length}
            </div>
            <p className="text-xs text-[#38bdf8] font-semibold mt-1">Validación y control de calidad</p>
          </div>
        </div>
      </div>

      {/* Search and Role Filter Bar */}
      <div className="glass-panel rounded-2xl p-4 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
        <div className="flex-1 relative group">
          <Search className="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-[#94a3b8] group-focus-within:text-white transition-colors" />
          <input
            type="text"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            placeholder="Buscar por nombre, correo o departamento..."
            className="w-full bg-black/30 border border-white/15 rounded-xl py-2 pl-10 pr-4 text-xs text-white focus:outline-none focus:border-white/40 transition-all placeholder:text-[#94a3b8]/60"
          />
        </div>

        <div className="flex items-center gap-2 bg-black/30 border border-white/15 rounded-xl px-3 py-1.5">
          <span className="text-[10px] text-[#94a3b8] font-bold uppercase tracking-wider">Rol:</span>
          <select
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value)}
            className="bg-transparent text-xs text-white focus:outline-none cursor-pointer"
          >
            {roles.map(r => (
              <option key={r} value={r} className="bg-black/90 text-white">
                {r}
              </option>
            ))}
          </select>
        </div>
      </div>

      {/* Users Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        {filteredUsers.map(user => {
          const isActive = user.status === 'Active';
          const isAdmin = user.role === 'Admin';
          const isSupervisor = user.role === 'Supervisor';

          return (
            <div
              key={user.id}
              className="glass-panel glass-card-hover rounded-2xl p-5 flex flex-col justify-between border border-white/15 relative"
            >
              <div>
                <div className="flex justify-between items-start mb-3">
                  <div className="flex items-center gap-3">
                    <div className="w-11 h-11 rounded-xl bg-black/30 border border-white/20 flex items-center justify-center text-sm font-bold text-[#4ade80] shadow-sm">
                      {user.initials}
                    </div>
                    <div>
                      <h3 className="text-sm font-bold text-white leading-tight">{user.name}</h3>
                      <span className={`text-[10px] font-bold px-2 py-0.5 rounded-full inline-block mt-1 ${
                        isAdmin 
                          ? 'bg-[#ef4444]/20 text-[#fca5a5] border border-[#ef4444]/30' 
                          : isSupervisor 
                          ? 'bg-[#38bdf8]/20 text-[#7dd3fc] border border-[#38bdf8]/30' 
                          : 'bg-[#22c55e]/20 text-[#4ade80] border border-[#4ade80]/30'
                      }`}>
                        {user.role}
                      </span>
                    </div>
                  </div>

                  <button
                    onClick={() => onUpdateUserStatus(user.id, isActive ? 'Inactive' : 'Active')}
                    className={`text-[10px] font-semibold px-2 py-0.5 rounded-full border transition-colors ${
                      isActive 
                        ? 'bg-[#22c55e]/20 text-[#4ade80] border-[#4ade80]/30' 
                        : 'bg-black/40 text-[#94a3b8] border-white/10'
                    }`}
                  >
                    {isActive ? 'Activo' : 'Inactivo'}
                  </button>
                </div>

                <div className="space-y-1.5 text-xs text-[#cbd5e1] mt-3">
                  <div className="flex items-center gap-2">
                    <Mail className="w-3.5 h-3.5 text-[#94a3b8]" />
                    <span className="truncate">{user.email}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <MapPin className="w-3.5 h-3.5 text-[#38bdf8]" />
                    <span>{user.region}</span>
                  </div>
                  {user.phone && (
                    <div className="flex items-center gap-2">
                      <Phone className="w-3.5 h-3.5 text-[#4ade80]" />
                      <span>{user.phone}</span>
                    </div>
                  )}
                </div>
              </div>

              <div className="mt-5 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                <span className="text-[11px] text-[#94a3b8]">@{user.username}</span>

                <button
                  onClick={() => {
                    if (confirm(`¿Eliminar usuario ${user.name}?`)) {
                      onDeleteUser(user.id);
                    }
                  }}
                  className="p-1.5 text-[#94a3b8] hover:text-[#f87171] hover:bg-[#ef4444]/10 rounded-lg transition-colors"
                  title="Eliminar usuario"
                >
                  <Trash2 className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          );
        })}
      </div>

      {/* Add User Modal */}
      {isAddModalOpen && (
        <div className="fixed inset-0 bg-black/80 backdrop-blur-md z-50 flex items-center justify-center p-4 animate-fadeIn">
          <div className="glass-panel max-w-md w-full rounded-2xl p-6 border border-white/20 shadow-2xl space-y-4">
            <div className="flex justify-between items-center pb-2 border-b border-white/10">
              <h3 className="text-base font-bold text-white">Nuevo Miembro del Equipo</h3>
              <button onClick={() => setIsAddModalOpen(false)} className="text-[#94a3b8] hover:text-white">
                <X className="w-5 h-5" />
              </button>
            </div>

            <form onSubmit={handleCreateUser} className="space-y-3">
              <div>
                <label className="text-xs text-[#cbd5e1] block mb-1">Nombre Completo</label>
                <input
                  type="text"
                  required
                  placeholder="ej. Carlos Morales"
                  value={newUserData.name}
                  onChange={(e) => setNewUserData({ ...newUserData, name: e.target.value })}
                  className="w-full glass-input rounded-xl p-2 text-xs text-white"
                />
              </div>

              <div>
                <label className="text-xs text-[#cbd5e1] block mb-1">Correo Institucional</label>
                <input
                  type="email"
                  required
                  placeholder="c.morales@keylinegt.com"
                  value={newUserData.email}
                  onChange={(e) => setNewUserData({ ...newUserData, email: e.target.value })}
                  className="w-full glass-input rounded-xl p-2 text-xs text-white"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Rol Asignado</label>
                  <select
                    value={newUserData.role}
                    onChange={(e) => setNewUserData({ ...newUserData, role: e.target.value as Role })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white bg-black/80"
                  >
                    <option value="Técnico">Técnico</option>
                    <option value="Supervisor">Supervisor</option>
                    <option value="Admin">Admin</option>
                  </select>
                </div>

                <div>
                  <label className="text-xs text-[#cbd5e1] block mb-1">Departamento / Región</label>
                  <input
                    type="text"
                    required
                    value={newUserData.region}
                    onChange={(e) => setNewUserData({ ...newUserData, region: e.target.value })}
                    className="w-full glass-input rounded-xl p-2 text-xs text-white"
                  />
                </div>
              </div>

              <div>
                <label className="text-xs text-[#cbd5e1] block mb-1">Teléfono Móvil</label>
                <input
                  type="text"
                  value={newUserData.phone}
                  onChange={(e) => setNewUserData({ ...newUserData, phone: e.target.value })}
                  className="w-full glass-input rounded-xl p-2 text-xs text-white"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-white/10">
                <button
                  type="button"
                  onClick={() => setIsAddModalOpen(false)}
                  className="px-4 py-2 bg-white/10 hover:bg-white/15 text-xs text-[#cbd5e1] rounded-xl"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-[#22c55e] hover:bg-[#16a34a] text-xs font-bold text-white rounded-xl shadow-lg"
                >
                  Crear Usuario
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
