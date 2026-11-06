import React, { useState } from 'react';
import { AthleteProfile } from '../types';

interface ProfileViewProps {
  athlete: AthleteProfile;
  onUpdateAthlete: (updated: Partial<AthleteProfile>) => void;
  onSyncGarmin: () => void;
  isGarminSyncing?: boolean;
}

export const ProfileView: React.FC<ProfileViewProps> = ({
  athlete,
  onUpdateAthlete,
  onSyncGarmin,
  isGarminSyncing = false,
}) => {
  const [activeTab, setActiveTab] = useState<
    | 'identidad'
    | 'objetivos'
    | 'historial'
    | 'fisiologia'
    | 'equipo'
    | 'estilo'
    | 'salud'
    | 'preferencias'
  >('identidad');

  const [formData, setFormData] = useState({
    name: athlete.name,
    primarySport: athlete.primarySport,
    garminEmail: athlete.garminEmail,
    garminPassword: 'password123456',
    restingHeartRate: athlete.restingHeartRate,
    maxHeartRate: athlete.maxHeartRate,
    vo2Max: athlete.vo2Max,
    ftpWatts: athlete.ftpWatts,
    hrv: athlete.hrv,
    weightKg: athlete.weightKg,
    heightCm: athlete.heightCm,
  });

  const [saveToast, setSaveToast] = useState(false);

  const tabs = [
    { id: 'identidad', label: 'Identidad' },
    { id: 'objetivos', label: 'Objetivos' },
    { id: 'historial', label: 'Historial' },
    { id: 'fisiologia', label: 'Fisiología' },
    { id: 'equipo', label: 'Equipo y Entorno' },
    { id: 'estilo', label: 'Estilo de Vida' },
    { id: 'salud', label: 'Salud' },
    { id: 'preferencias', label: 'Preferencias' },
  ] as const;

  const handleSave = () => {
    onUpdateAthlete({
      name: formData.name,
      primarySport: formData.primarySport,
      garminEmail: formData.garminEmail,
      restingHeartRate: Number(formData.restingHeartRate),
      maxHeartRate: Number(formData.maxHeartRate),
      vo2Max: Number(formData.vo2Max),
      ftpWatts: Number(formData.ftpWatts),
      hrv: Number(formData.hrv),
      weightKg: Number(formData.weightKg),
      heightCm: Number(formData.heightCm),
    });
    setSaveToast(true);
    setTimeout(() => setSaveToast(false), 3000);
  };

  return (
    <div id="profile-view-container" className="space-y-7 pb-20 select-none">
      {/* Top Profile Header */}
      <header className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-5 border-b border-white/10">
        <div>
          <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
            Perfil del Atleta
          </h2>
          <div className="flex items-center gap-3 mt-2">
            <span className="text-xs font-bold text-[#caf300] uppercase tracking-wider">
              Completado {athlete.completionRate}%
            </span>
            <div className="w-40 h-2 bg-white/10 rounded-full overflow-hidden p-0.5">
              <div
                className="h-full bg-[#caf300] rounded-full shadow-[0_0_10px_#caf300]"
                style={{ width: `${athlete.completionRate}%` }}
              ></div>
            </div>
          </div>
        </div>

        {/* Header Actions */}
        <div className="flex items-center gap-3 self-end sm:self-auto">
          <button
            id="btn-delete-profile"
            onClick={() => alert('Para reiniciar o eliminar perfil contacta al administrador.')}
            className="flex items-center gap-1.5 px-4 py-2.5 glass-pill text-[#ffb4ab] hover:bg-[#93000a]/30 rounded-xl text-xs font-bold uppercase tracking-wider transition-colors"
          >
            <span className="material-symbols-outlined text-[18px]">delete</span>
            <span>Eliminar</span>
          </button>

          <button
            id="btn-save-profile"
            onClick={handleSave}
            className="flex items-center gap-1.5 px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)] active:scale-95 hover:scale-[1.02]"
          >
            <span className="material-symbols-outlined text-[18px]">save</span>
            <span>Guardar</span>
          </button>
        </div>
      </header>

      {/* Save feedback toast */}
      {saveToast && (
        <div className="p-4 glass-card border-2 border-[#caf300] text-[#caf300] rounded-2xl text-sm font-bold flex items-center gap-3 shadow-[0_0_25px_rgba(202,243,0,0.3)] animate-bounce">
          <span className="material-symbols-outlined text-xl">check_circle</span>
          <span>¡Perfil del atleta actualizado correctamente con el laboratorio!</span>
        </div>
      )}

      {/* Tabs Navigation Strip */}
      <nav className="flex items-center gap-2 overflow-x-auto hide-scrollbar pb-1">
        {tabs.map((tab) => {
          const isActive = activeTab === tab.id;
          return (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`px-4 py-2.5 rounded-xl text-xs font-black whitespace-nowrap transition-all uppercase tracking-wider ${
                isActive
                  ? 'bg-[#caf300] text-[#121700] shadow-[0_0_20px_rgba(202,243,0,0.35)] scale-[1.02]'
                  : 'glass-pill text-[#c5c9ac] hover:text-white'
              }`}
            >
              {tab.label}
            </button>
          );
        })}
      </nav>

      {/* Tab Content 1: Identidad & Credenciales */}
      {activeTab === 'identidad' && (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-7">
          {/* Left Form: Datos y Credenciales (8 cols) */}
          <section className="lg:col-span-8 glass-card rounded-3xl p-6 sm:p-8 space-y-6 border border-white/10">
            <h3 className="text-xl font-black text-white tracking-tight border-b border-white/10 pb-4 flex items-center gap-2.5">
              <span className="material-symbols-outlined text-[#caf300]">badge</span>
              <span>Datos y Credenciales</span>
            </h3>

            <div className="space-y-5">
              {/* Nombre Completo */}
              <div>
                <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                  Nombre Completo
                </label>
                <input
                  id="input-athlete-name"
                  type="text"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  className="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors"
                  placeholder="Ej. Alex Triathlete"
                />
              </div>

              {/* Deporte Principal */}
              <div>
                <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                  Deporte Principal
                </label>
                <select
                  id="select-athlete-sport"
                  value={formData.primarySport}
                  onChange={(e) => setFormData({ ...formData, primarySport: e.target.value })}
                  className="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors"
                >
                  <option value="Triatlón de Larga Distancia (Ironman)" className="bg-[#17191e] text-white">
                    Triatlón de Larga Distancia (Ironman)
                  </option>
                  <option value="Triatlón Media Distancia (Ironman 70.3)" className="bg-[#17191e] text-white">
                    Triatlón Media Distancia (Ironman 70.3)
                  </option>
                  <option value="Triatlón Olímpico / Distancia Estándar" className="bg-[#17191e] text-white">
                    Triatlón Olímpico / Distancia Estándar
                  </option>
                  <option value="Ciclismo en Ruta & Gran Fondo" className="bg-[#17191e] text-white">
                    Ciclismo en Ruta & Gran Fondo
                  </option>
                  <option value="Maratón & Ultrafondo" className="bg-[#17191e] text-white">
                    Maratón & Ultrafondo
                  </option>
                </select>
              </div>

              {/* Email Garmin Connect */}
              <div>
                <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                  Email Garmin Connect
                </label>
                <input
                  id="input-garmin-email"
                  type="email"
                  value={formData.garminEmail}
                  onChange={(e) => setFormData({ ...formData, garminEmail: e.target.value })}
                  className="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors font-mono-data"
                  placeholder="tu_email@garmin.com"
                />
              </div>

              {/* Contraseña Garmin Connect */}
              <div>
                <label className="block text-[11px] font-black text-[#8f9378] uppercase tracking-widest mb-2">
                  Contraseña Garmin Connect
                </label>
                <input
                  id="input-garmin-password"
                  type="password"
                  value={formData.garminPassword}
                  onChange={(e) => setFormData({ ...formData, garminPassword: e.target.value })}
                  className="w-full glass-input rounded-xl px-4 py-3 text-white text-sm focus:outline-none transition-colors"
                />
              </div>

              {/* Button: Conectar y Auto-completar desde Garmin */}
              <div className="pt-2">
                <button
                  id="btn-sync-garmin-profile"
                  onClick={onSyncGarmin}
                  disabled={isGarminSyncing}
                  className="w-full flex items-center justify-center gap-2.5 py-3.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black tracking-wider uppercase shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-[0.98] disabled:opacity-50"
                >
                  <span className="material-symbols-outlined text-[22px]">
                    {isGarminSyncing ? 'sync' : 'watch'}
                  </span>
                  <span>
                    {isGarminSyncing
                      ? 'Sincronizando con Garmin Connect...'
                      : 'Conectar y Auto-completar desde Garmin'}
                  </span>
                </button>
              </div>
            </div>
          </section>

          {/* Right Card: Telemetría Rápida (4 cols) */}
          <section className="lg:col-span-4 space-y-5">
            <div className="glass-card rounded-3xl p-6 sm:p-7 space-y-4 border border-white/10 shadow-2xl">
              <h4 className="text-xs font-black text-[#caf300] uppercase tracking-widest flex items-center gap-2">
                <span className="material-symbols-outlined text-lg">ecg_heart</span>
                <span>Estado del Atleta</span>
              </h4>

              <div className="space-y-3 font-mono-data text-sm">
                <div className="flex justify-between items-center py-2.5 border-b border-white/10">
                  <span className="text-[#c5c9ac] text-xs">VFC Promedio:</span>
                  <span className="text-white font-black">{formData.hrv} ms (Óptimo)</span>
                </div>
                <div className="flex justify-between items-center py-2.5 border-b border-white/10">
                  <span className="text-[#c5c9ac] text-xs">FC Reposo:</span>
                  <span className="text-white font-black">{formData.restingHeartRate} bpm</span>
                </div>
                <div className="flex justify-between items-center py-2.5 border-b border-white/10">
                  <span className="text-[#c5c9ac] text-xs">FC Máxima:</span>
                  <span className="text-white font-black">{formData.maxHeartRate} bpm</span>
                </div>
                <div className="flex justify-between items-center py-2.5 border-b border-white/10">
                  <span className="text-[#c5c9ac] text-xs">FTP Ciclista:</span>
                  <span className="text-[#caf300] font-black">{formData.ftpWatts} W (4.12 W/kg)</span>
                </div>
                <div className="flex justify-between items-center py-2.5">
                  <span className="text-[#c5c9ac] text-xs">Vo2Max Estimado:</span>
                  <span className="text-[#a5eeff] font-black">{formData.vo2Max} ml/kg/min</span>
                </div>
              </div>
            </div>

            {/* Garmin Sync Status Pill */}
            <div className="glass-card rounded-3xl p-5 sm:p-6 flex items-center gap-3.5 border border-white/10">
              <div className="w-3.5 h-3.5 rounded-full bg-[#caf300] shadow-[0_0_10px_#caf300]"></div>
              <div>
                <p className="text-xs font-black text-white uppercase tracking-wider">
                  Garmin API Activa
                </p>
                <p className="text-[11px] text-[#8f9378] font-bold mt-0.5">
                  Última sincronización: Hoy, 06:14h
                </p>
              </div>
            </div>
          </section>
        </div>
      )}

      {/* Tab Content 2: Fisiología & Zonas */}
      {activeTab === 'fisiologia' && (
        <div className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div className="glass-card rounded-3xl p-6 sm:p-7 border border-white/10">
              <p className="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">FTP (Potencia Umbral)</p>
              <p className="text-4xl font-black text-[#caf300] font-mono-data mt-2">295 W</p>
              <p className="text-xs text-[#c5c9ac] mt-1 font-medium">4.12 W/kg (Peso: 71.5 kg)</p>
            </div>
            <div className="glass-card rounded-3xl p-6 sm:p-7 border border-white/10">
              <p className="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">Ritmo Umbral Carrera</p>
              <p className="text-4xl font-black text-[#a5eeff] font-mono-data mt-2">3:48</p>
              <p className="text-xs text-[#c5c9ac] mt-1 font-medium">min/km (vAM: 18.2 km/h)</p>
            </div>
            <div className="glass-card rounded-3xl p-6 sm:p-7 border border-white/10">
              <p className="text-[11px] font-black text-[#8f9378] uppercase tracking-widest">Ritmo CSS Natación</p>
              <p className="text-4xl font-black text-white font-mono-data mt-2">1:22</p>
              <p className="text-xs text-[#c5c9ac] mt-1 font-medium">min / 100m (SWOLF: 31)</p>
            </div>
          </div>

          <div className="glass-card rounded-3xl p-6 sm:p-8 border border-white/10 space-y-4">
            <h4 className="text-base font-black text-white uppercase tracking-wider">
              Zonas de Entrenamiento Calculadas (Potencia / FC)
            </h4>
            <div className="space-y-3 font-mono-data text-xs">
              <div className="flex items-center justify-between p-3.5 glass-card rounded-2xl border-l-4 border-l-[#8f9378]">
                <span>Z1 - Recuperación Activa (&lt;55% FTP / &lt;128 bpm)</span>
                <span className="text-[#8f9378] font-bold">&lt; 162 W</span>
              </div>
              <div className="flex items-center justify-between p-3.5 glass-card rounded-2xl border-l-4 border-l-[#caf300]">
                <span>Z2 - Resistencia Aeróbica Base (56-75% FTP / 129-149 bpm)</span>
                <span className="text-[#caf300] font-bold">163 - 221 W</span>
              </div>
              <div className="flex items-center justify-between p-3.5 glass-card rounded-2xl border-l-4 border-l-[#a5eeff]">
                <span>Z3 - Tempo Ironman (76-90% FTP / 150-164 bpm)</span>
                <span className="text-[#a5eeff] font-bold">222 - 265 W</span>
              </div>
              <div className="flex items-center justify-between p-3.5 glass-card rounded-2xl border-l-4 border-l-[#ffb4ab]">
                <span>Z4 - Umbral Lactato (91-105% FTP / 165-177 bpm)</span>
                <span className="text-[#ffb4ab] font-bold">266 - 310 W</span>
              </div>
              <div className="flex items-center justify-between p-3.5 glass-card rounded-2xl border-l-4 border-l-[#ff5252]">
                <span>Z5 - Vo2Max Capacidad Anaeróbica (&gt;106% FTP / &gt;178 bpm)</span>
                <span className="text-[#ff5252] font-bold">&gt; 311 W</span>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Other tabs placeholder fallback for completeness */}
      {activeTab !== 'identidad' && activeTab !== 'fisiologia' && (
        <div className="glass-card rounded-3xl p-8 sm:p-10 text-center space-y-4 border border-white/10">
          <span className="material-symbols-outlined text-4xl text-[#caf300]">tune</span>
          <h4 className="text-lg font-black text-white capitalize">
            Configuración de {activeTab}
          </h4>
          <p className="text-sm text-[#c5c9ac] max-w-lg mx-auto leading-relaxed">
            Los parámetros de {activeTab} están sincronizados y calibrados con el motor de prescripción de carga del Agente IA.
          </p>
          <button
            onClick={() => setActiveTab('identidad')}
            className="px-5 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider shadow-[0_0_15px_rgba(202,243,0,0.3)] transition-all"
          >
            Volver a Identidad
          </button>
        </div>
      )}
    </div>
  );
};
