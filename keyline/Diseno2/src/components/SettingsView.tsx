import React, { useState } from 'react';
import { 
  User, 
  Lock, 
  ShieldCheck, 
  Save, 
  Check, 
  Globe, 
  Bell, 
  Ruler, 
  Info
} from 'lucide-react';
import { UserProfile } from '../types';

interface SettingsViewProps {
  currentUser: UserProfile;
  onUpdateProfile: (updated: Partial<UserProfile>) => void;
}

export const SettingsView: React.FC<SettingsViewProps> = ({
  currentUser,
  onUpdateProfile
}) => {
  const [profileData, setProfileData] = useState({
    name: currentUser.name,
    email: currentUser.email,
    phone: currentUser.phone || '+502 4432-9011',
    region: currentUser.region,
    project: 'Proyecto Agroforestal Cuenca Polochic - KeylineGT'
  });

  const [passwordData, setPasswordData] = useState({
    current: '',
    newPass: '',
    confirm: ''
  });

  const [unitSystem, setUnitSystem] = useState<'metric' | 'guatemala'>('metric');
  const [notificationsEnabled, setNotificationsEnabled] = useState(true);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [passSuccess, setPassSuccess] = useState(false);

  const handleSaveProfile = (e: React.FormEvent) => {
    e.preventDefault();
    onUpdateProfile({
      name: profileData.name,
      email: profileData.email,
      phone: profileData.phone,
      region: profileData.region
    });
    setSaveSuccess(true);
    setTimeout(() => setSaveSuccess(false), 3000);
  };

  const handleUpdatePassword = (e: React.FormEvent) => {
    e.preventDefault();
    if (passwordData.newPass && passwordData.newPass === passwordData.confirm) {
      setPassSuccess(true);
      setPasswordData({ current: '', newPass: '', confirm: '' });
      setTimeout(() => setPassSuccess(false), 3000);
    }
  };

  return (
    <div className="space-y-6 max-w-[1600px] mx-auto animate-fadeIn pb-12">
      {/* Header */}
      <div>
        <h2 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">Configuración del Sistema</h2>
        <p className="text-xs sm:text-sm text-[#cbd5e1] mt-0.5">
          Preferencias de usuario, unidades de medida y seguridad de la cuenta.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        {/* Left Column: Profile Card & Security (7 cols) */}
        <div className="lg:col-span-7 space-y-6">
          {/* Profile Form */}
          <div className="glass-panel rounded-2xl p-6 border border-white/15">
            <div className="flex items-center gap-3 pb-4 border-b border-white/10 mb-4">
              <div className="w-10 h-10 rounded-xl bg-black/30 border border-white/15 flex items-center justify-center text-[#4ade80]">
                <User className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-white">Perfil del Usuario</h3>
                <p className="text-xs text-[#cbd5e1]">Información personal y datos de contacto institucional.</p>
              </div>
            </div>

            <form onSubmit={handleSaveProfile} className="space-y-4 text-xs">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Nombre Completo</label>
                  <input
                    type="text"
                    value={profileData.name}
                    onChange={(e) => setProfileData({ ...profileData, name: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Correo Electrónico</label>
                  <input
                    type="email"
                    value={profileData.email}
                    onChange={(e) => setProfileData({ ...profileData, email: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Teléfono / WhatsApp</label>
                  <input
                    type="text"
                    value={profileData.phone}
                    onChange={(e) => setProfileData({ ...profileData, phone: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Departamento / Sede</label>
                  <input
                    type="text"
                    value={profileData.region}
                    onChange={(e) => setProfileData({ ...profileData, region: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
              </div>

              <div>
                <label className="text-[#cbd5e1] block mb-1 font-medium">Proyecto Asignado</label>
                <input
                  type="text"
                  value={profileData.project}
                  onChange={(e) => setProfileData({ ...profileData, project: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-white"
                />
              </div>

              <div className="flex items-center justify-between pt-2">
                {saveSuccess ? (
                  <span className="text-[#4ade80] flex items-center gap-1 font-semibold">
                    <Check className="w-4 h-4" /> Guardado exitosamente
                  </span>
                ) : <span />}

                <button
                  type="submit"
                  className="px-5 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white font-bold rounded-xl flex items-center gap-2 shadow-lg transition-all"
                >
                  <Save className="w-4 h-4" />
                  <span>Guardar Cambios</span>
                </button>
              </div>
            </form>
          </div>

          {/* Password & Security */}
          <div className="glass-panel rounded-2xl p-6 border border-white/15">
            <div className="flex items-center gap-3 pb-4 border-b border-white/10 mb-4">
              <div className="w-10 h-10 rounded-xl bg-black/30 border border-white/15 flex items-center justify-center text-[#facc15]">
                <Lock className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-bold text-white">Seguridad y Acceso</h3>
                <p className="text-xs text-[#cbd5e1]">Actualización de contraseña institucional.</p>
              </div>
            </div>

            <form onSubmit={handleUpdatePassword} className="space-y-3 text-xs">
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Contraseña Actual</label>
                  <input
                    type="password"
                    placeholder="••••••••"
                    value={passwordData.current}
                    onChange={(e) => setPasswordData({ ...passwordData, current: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Nueva Contraseña</label>
                  <input
                    type="password"
                    placeholder="••••••••"
                    value={passwordData.newPass}
                    onChange={(e) => setPasswordData({ ...passwordData, newPass: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
                <div>
                  <label className="text-[#cbd5e1] block mb-1 font-medium">Confirmar Nueva</label>
                  <input
                    type="password"
                    placeholder="••••••••"
                    value={passwordData.confirm}
                    onChange={(e) => setPasswordData({ ...passwordData, confirm: e.target.value })}
                    className="w-full glass-input rounded-xl p-2.5 text-white"
                  />
                </div>
              </div>

              <div className="flex items-center justify-between pt-2">
                {passSuccess ? (
                  <span className="text-[#4ade80] flex items-center gap-1 font-semibold">
                    <Check className="w-4 h-4" /> Contraseña actualizada
                  </span>
                ) : <span />}

                <button
                  type="submit"
                  className="px-4 py-2 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl border border-white/15 transition-all"
                >
                  Actualizar Contraseña
                </button>
              </div>
            </form>
          </div>
        </div>

        {/* Right Column: Preferences & System Info (5 cols) */}
        <div className="lg:col-span-5 space-y-6">
          {/* Preferences */}
          <div className="glass-panel rounded-2xl p-6 border border-white/15 space-y-5">
            <h3 className="text-base font-bold text-white pb-3 border-b border-white/10">Preferencias del Sistema</h3>

            {/* Units Selection */}
            <div>
              <label className="text-xs font-semibold text-white flex items-center gap-2 mb-2">
                <Ruler className="w-4 h-4 text-[#38bdf8]" />
                <span>Unidades de Medida de Superficie</span>
              </label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setUnitSystem('metric')}
                  className={`p-3 rounded-xl text-xs font-medium border text-left transition-all ${
                    unitSystem === 'metric'
                      ? 'bg-[#22c55e]/25 text-[#4ade80] border-[#4ade80]/40'
                      : 'bg-black/30 text-[#cbd5e1] border-white/10 hover:bg-white/5'
                  }`}
                >
                  <p className="font-bold">Métrico Decimal</p>
                  <p className="text-[10px] text-[#94a3b8]">Hectáreas (ha), Metros (m)</p>
                </button>

                <button
                  type="button"
                  onClick={() => setUnitSystem('guatemala')}
                  className={`p-3 rounded-xl text-xs font-medium border text-left transition-all ${
                    unitSystem === 'guatemala'
                      ? 'bg-[#22c55e]/25 text-[#4ade80] border-[#4ade80]/40'
                      : 'bg-black/30 text-[#cbd5e1] border-white/10 hover:bg-white/5'
                  }`}
                >
                  <p className="font-bold">Tradicional Guatemala</p>
                  <p className="text-[10px] text-[#94a3b8]">Manzanas (mz), Cuerdas</p>
                </button>
              </div>
            </div>

            {/* Notifications Toggle */}
            <div className="pt-2">
              <label className="text-xs font-semibold text-white flex items-center justify-between mb-1">
                <div className="flex items-center gap-2">
                  <Bell className="w-4 h-4 text-[#facc15]" />
                  <span>Notificaciones de Alerta de Erosión</span>
                </div>
                <input
                  type="checkbox"
                  checked={notificationsEnabled}
                  onChange={(e) => setNotificationsEnabled(e.target.checked)}
                  className="rounded bg-black/40 border-white/20 text-[#22c55e] focus:ring-[#22c55e]"
                />
              </label>
              <p className="text-[11px] text-[#94a3b8]">
                Recibir alertas prioritarias en la barra superior al detectar laderas con &gt;30° sin curvas a nivel.
              </p>
            </div>
          </div>

          {/* System Info Card */}
          <div className="glass-panel rounded-2xl p-6 border border-white/15 text-xs space-y-3">
            <div className="flex items-center gap-2 text-white font-bold">
              <Info className="w-4 h-4 text-[#4ade80]" />
              <span>Información de Versión</span>
            </div>
            <div className="bg-black/30 p-3 rounded-xl border border-white/5 space-y-1 text-[#cbd5e1]">
              <p><strong className="text-white">Versión:</strong> KeylineGT Enterprise v2.4</p>
              <p><strong className="text-white">Motor GIS:</strong> EPSG:4326 (WGS84) + GTM</p>
              <p><strong className="text-white">Servidor:</strong> Cloud Run Guatemala Node/React</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
