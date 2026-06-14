import React, { useState } from 'react';
import { User, Shield, Sparkles, Sliders, CheckCircle, Save, Percent, BadgeAlert } from 'lucide-react';

interface AjustesProps {
  adminName: string;
  setAdminName: (name: string) => void;
  adminRole: string;
  setAdminRole: (role: string) => void;
  avatarUrl: string;
  setAvatarUrl: (url: string) => void;
  efficiencyRate: number;
  setEfficiencyRate: (val: number) => void;
  onRatesModified: (service: string, unitPrice: number) => void;
  vaccineRates: { [key: string]: number };
}

export default function Ajustes({
  adminName,
  setAdminName,
  adminRole,
  setAdminRole,
  avatarUrl,
  setAvatarUrl,
  efficiencyRate,
  setEfficiencyRate,
  onRatesModified,
  vaccineRates
}: AjustesProps) {
  const [tempName, setTempName] = useState(adminName);
  const [tempRole, setTempRole] = useState(adminRole);
  const [tempAvatar, setTempAvatar] = useState(avatarUrl);
  const [tempEfficiency, setTempEfficiency] = useState(efficiencyRate);
  
  // Rate changes
  const [tempRates, setTempRates] = useState({ ...vaccineRates });

  const [savedSuccess, setSavedSuccess] = useState(false);

  const handleSaveSettings = (e: React.FormEvent) => {
    e.preventDefault();
    setAdminName(tempName);
    setAdminRole(tempRole);
    setAvatarUrl(tempAvatar);
    setEfficiencyRate(tempEfficiency);

    // Save individual customized rates
    Object.entries(tempRates).forEach(([srvName, val]) => {
      onRatesModified(srvName, Number(val));
    });

    setSavedSuccess(true);
    setTimeout(() => {
      setSavedSuccess(false);
    }, 2500);
  };

  const handleRateValueChange = (srvKey: string, val: string) => {
    const numeric = parseFloat(val) || 0;
    setTempRates(prev => ({
      ...prev,
      [srvKey]: numeric
    }));
  };

  return (
    <div className="space-y-8 animate-fade-in max-w-3xl mx-auto pb-10">
      
      {/* Title */}
      <div>
        <h2 className="text-3xl font-extrabold text-[#3455b9] mb-1">Ajustes de VaxPoultry</h2>
        <p className="text-gray-500 font-medium text-sm">Personalice el perfil veterinario, configure tarifas por ave y configure metas clínicas.</p>
      </div>

      <form onSubmit={handleSaveSettings} className="space-y-6">
        
        {/* Profile Card */}
        <div className="bg-white/50 backdrop-blur-[20px] border border-white/50 p-6 md:p-8 rounded-[30px] shadow-sm space-y-6">
          <div className="flex items-center gap-3">
            <User className="w-5 h-5 text-[#3455b9]" />
            <h4 className="text-base font-extrabold text-gray-800">Identidad de la Cuenta</h4>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <div className="space-y-1.5">
              <label className="text-xs font-bold text-gray-500 uppercase tracking-widest">Nombre Completo</label>
              <input
                type="text"
                required
                value={tempName}
                onChange={e => setTempName(e.target.value)}
                className="w-full bg-white/60 border border-gray-200/50 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-800 focus:outline-none focus:border-[#3455b9]"
              />
            </div>

            <div className="space-y-1.5">
              <label className="text-xs font-bold text-gray-500 uppercase tracking-widest">Rol / Clasificación</label>
              <input
                type="text"
                required
                value={tempRole}
                onChange={e => setTempRole(e.target.value)}
                className="w-full bg-white/60 border border-gray-200/50 rounded-xl px-4 py-2.5 text-sm font-semibold text-gray-800 focus:outline-none focus:border-[#3455b9]"
              />
            </div>

            <div className="space-y-1.5 md:col-span-2">
              <label className="text-xs font-bold text-gray-500 uppercase tracking-widest">URL de Avatar (Foto de perfil)</label>
              <input
                type="url"
                placeholder="https://lh3.googleusercontent.com/..."
                value={tempAvatar}
                onChange={e => setTempAvatar(e.target.value)}
                className="w-full bg-white/60 border border-gray-200/50 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-[#3455b9] text-gray-700 font-mono"
              />
              <p className="text-[10px] text-gray-400 font-medium">Deje vacío para generar automáticamente insignias de iniciales.</p>
            </div>

          </div>
        </div>

        {/* Global target performance variables */}
        <div className="bg-white/50 backdrop-blur-[20px] border border-white/50 p-6 md:p-8 rounded-[30px] shadow-sm space-y-6">
          <div className="flex items-center gap-3">
            <Percent className="w-5 h-5 text-[#3455b9]" />
            <h4 className="text-base font-extrabold text-gray-800">Objetivos Veterinarios</h4>
          </div>

          <div className="space-y-2">
            <div className="flex justify-between items-center">
              <span className="text-xs font-bold text-gray-500 uppercase">Eficiencia Sanitaria Deseada (%)</span>
              <span className="text-sm font-extrabold text-[#3455b9]">{tempEfficiency}%</span>
            </div>
            
            <input
              type="range"
              min="85"
              max="100"
              step="0.1"
              value={tempEfficiency}
              onChange={e => setTempEfficiency(Number(e.target.value))}
              className="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-[#3455b9]"
            />
            <div className="flex justify-between text-[10px] text-gray-400 font-bold">
              <span>85.0% Mínimo</span>
              <span>100.0% Máxima</span>
            </div>
          </div>
        </div>

        {/* Customizable price rates cards */}
        <div className="bg-white/50 backdrop-blur-[20px] border border-white/50 p-6 md:p-8 rounded-[30px] shadow-sm space-y-4">
          <div className="flex items-center gap-3 mb-2">
            <Sliders className="w-5 h-5 text-[#3455b9]" />
            <h4 className="text-base font-extrabold text-gray-800">Tarifas de Servicios (Quetzales por Ave)</h4>
          </div>

          <p className="text-xs text-gray-500 mb-4">Modifique las tasas unitarias para recalcular automáticamente los totales estimados de vacunación en campo.</p>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            {Object.entries(tempRates).map(([srvKey, srvPrice]) => {
              // Beautify keys
              let titleLabel = srvKey;
              if (srvKey === 'Aerosol_GT_100') titleLabel = 'Aerosol (>= 100 aves)';
              if (srvKey === 'Aerosol_LT_100') titleLabel = 'Aerosol (< 100 aves)';

              return (
                <div key={srvKey} className="flex flex-col gap-1 p-3 bg-white/45 border border-white/60 rounded-xl">
                  <span className="text-[11px] font-bold text-gray-600 uppercase tracking-wide">{titleLabel}</span>
                  <div className="relative mt-1">
                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-extrabold text-[#3455b9]">Q</span>
                    <input
                      type="number"
                      step="0.0001"
                      min="0.0001"
                      required
                      value={srvPrice}
                      onChange={e => handleRateValueChange(srvKey, e.target.value)}
                      className="w-full pl-7 pr-3 py-1.5 bg-white/80 border border-gray-200/50 rounded-lg text-xs font-bold text-gray-700 focus:outline-none focus:border-[#3455b9]"
                    />
                  </div>
                </div>
              );
            })}

          </div>
        </div>

        {/* Status notification alerts and submit */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
          {savedSuccess ? (
            <div className="flex items-center gap-2 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 px-4 py-2.5 rounded-xl text-xs font-extrabold animate-pulse">
              <CheckCircle className="w-4.5 h-4.5" />
              <span>Ajustes actualizados exitosamente</span>
            </div>
          ) : (
            <div className="text-[11px] text-gray-400 font-medium">
              * Cambios guardados localmente en su navegador para acceso permanente.
            </div>
          )}

          <button
            type="submit"
            className="w-full sm:w-auto px-6 py-3.5 bg-[#3455b9] text-white hover:opacity-95 rounded-xl font-extrabold text-sm flex items-center justify-center gap-2 shadow-xs hover:scale-[1.01] transition-all cursor-pointer"
          >
            <Save className="w-4.5 h-4.5" />
            <span>Guardar Configuración</span>
          </button>
        </div>

      </form>
    </div>
  );
}
