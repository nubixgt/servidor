import React, { useState, useEffect } from 'react';
import Sidebar from './components/Sidebar';
import Dashboard from './components/Dashboard';
import NuevoRegistro from './components/NuevoRegistro';
import RegistrosList from './components/RegistrosList';
import Ajustes from './components/Ajustes';
import Catalogo from './components/Catalogo';
import { VaccineRecord, ReminderTask, RegionContribution } from './types';
import { SEED_RECORDS, INITIAL_REMINDERS, REGIONAL_DISTRIBUTION } from './data';
import { LayoutDashboard, FileSpreadsheet, Plus, Calendar, Settings, BookOpen } from 'lucide-react';

const srvRatesFallback = {
  'Vacuna Cuello': 0.0082,
  'Despique': 0.0092,
  'Re-Despique': 0.0101,
  'Vacuna Pechuga': 0.0070,
  'Aerosol_GT_100': 0.0015,
  'Aerosol_LT_100': 0.0010
};

export default function App() {
  const [activeTab, setActiveTab] = useState<'dashboard' | 'registros' | 'nuevo-registro' | 'ajustes' | 'catalogo'>('dashboard');
  const [selectedMonth, setSelectedMonth] = useState<number>(0); // 0 = Todo el año, 1 = Enero, etc.
  const [selectedYear, setSelectedYear] = useState<string>('2026'); // '2026' or 'all' etc.

  // State loaded from localStorage or seeded
  const [records, setRecords] = useState<VaccineRecord[]>(() => {
    const local = localStorage.getItem('vaxpoultry_records');
    if (local) {
      try { return JSON.parse(local); } catch (e) { console.error(e); }
    }
    return SEED_RECORDS;
  });

  const [reminders, setReminders] = useState<ReminderTask[]>(() => {
    const local = localStorage.getItem('vaxpoultry_reminders');
    if (local) {
      try { return JSON.parse(local); } catch (e) { console.error(e); }
    }
    return INITIAL_REMINDERS;
  });

  // Admin Configuration parameters
  const [adminName, setAdminName] = useState<string>(() => {
    return localStorage.getItem('vaxpoultry_adminName') || 'Dr. Rodrigo M.';
  });
  const [adminRole, setAdminRole] = useState<string>(() => {
    return localStorage.getItem('vaxpoultry_adminRole') || 'Administrador';
  });
  const [avatarUrl, setAvatarUrl] = useState<string>(() => {
    return localStorage.getItem('vaxpoultry_avatarUrl') || 'https://lh3.googleusercontent.com/aida-public/AB6AXuBPWDjvnTaerXWIKJZGk-08L0ZkEpA_hXHCqf4K-1A-PYPlk7sgIcB374lQNK6GEAD3sLtjoUXdMiupNQ5PktuJMeQhBnlJ5U5qR1QiZ8aKmzLOVwB0XGj1fI6DywD1iDpOyEJ3UGGvUsQuWfe9-HsTLHi915dsj5mhpvYZHsWoS0LS_z1A0SFAGfRu9doaq2csh37D0ZUJrqgV0Xw40MS14iIpJOyteW0uA1uYycC0XhToX0SdvPP50xQMSrY2zYFUXe-YaGOgxZc';
  });
  const [efficiencyRate, setEfficiencyRate] = useState<number>(() => {
    const saved = localStorage.getItem('vaxpoultry_efficiency');
    return saved ? parseFloat(saved) : 98.4;
  });

  // Customizable price rates
  const [vaccineRates, setVaccineRates] = useState<{ [key: string]: number }>(() => {
    const saved = localStorage.getItem('vaxpoultry_rates');
    if (saved) {
      try {
        return JSON.parse(saved);
      } catch (e) {
        console.error(srvRatesFallback);
      }
    }
    return srvRatesFallback;
  });

  // Synchronize localStorage
  useEffect(() => {
    localStorage.setItem('vaxpoultry_records', JSON.stringify(records));
  }, [records]);

  useEffect(() => {
    localStorage.setItem('vaxpoultry_reminders', JSON.stringify(reminders));
  }, [reminders]);

  useEffect(() => {
    localStorage.setItem('vaxpoultry_adminName', adminName);
    localStorage.setItem('vaxpoultry_adminRole', adminRole);
    localStorage.setItem('vaxpoultry_avatarUrl', avatarUrl);
    localStorage.setItem('vaxpoultry_efficiency', efficiencyRate.toString());
    localStorage.setItem('vaxpoultry_rates', JSON.stringify(vaccineRates));
  }, [adminName, adminRole, avatarUrl, efficiencyRate, vaccineRates]);

  // Handle adding custom vaccine rate modifiers
  const handleRatesModified = (serviceKey: string, newRate: number) => {
    setVaccineRates(prev => ({
      ...prev,
      [serviceKey]: newRate
    }));
  };

  // State addition handlers
  const handleAddRecord = (recordData: Omit<VaccineRecord, 'id' | 'hora' | 'clienteIniciales'>) => {
    // Generate simple initials: e.g. "Granja Los Pinos" -> "GP"
    const words = recordData.cliente.trim().split(/\s+/);
    let initials = 'AV';
    if (words.length > 0) {
      if (words.length >= 2) {
        initials = (words[0][0] + words[1][0]).toUpperCase();
      } else {
        initials = words[0].slice(0, 2).toUpperCase();
      }
    }

    // Get current client readable hour
    const now = new Date();
    let hoursStr = now.getHours();
    const ampm = hoursStr >= 12 ? 'PM' : 'AM';
    hoursStr = hoursStr % 12;
    hoursStr = hoursStr ? hoursStr : 12; // the hour '0' should be '12'
    const minutesStr = now.getMinutes().toString().padStart(2, '0');
    const hora = `${hoursStr.toString().padStart(2, '0')}:${minutesStr} ${ampm}`;

    const newRecord: VaccineRecord = {
      ...recordData,
      id: `rec_${Date.now()}`,
      hora,
      clienteIniciales: initials
    };

    setRecords(prev => [newRecord, ...prev]);
  };

  const handleDeleteRecord = (id: string) => {
    setRecords(prev => prev.filter(r => r.id !== id));
  };

  const handleUpdateStatus = (id: string, newStatus: 'Completado' | 'Pendiente' | 'Cancelado') => {
    setRecords(prev => prev.map(r => r.id === id ? { ...r, estado: newStatus } : r));
  };

  const handleAddReminder = (titulo: string, descripcion: string, importancia: 'alta' | 'media' | 'baja') => {
    const newRem: ReminderTask = {
      id: `rem_${Date.now()}`,
      titulo,
      descripcion,
      importancia
    };
    setReminders(prev => [newRem, ...prev]);
  };

  const handleDeleteReminder = (id: string) => {
    setReminders(prev => prev.filter(rem => rem.id !== id));
  };

  const handleResetData = () => {
    if (confirm('¿Desea reiniciar todos los registros a la configuración inicial?')) {
      setRecords(SEED_RECORDS);
      setReminders(INITIAL_REMINDERS);
      setAdminName('Dr. Rodrigo M.');
      setAdminRole('Administrador');
      setAvatarUrl('https://lh3.googleusercontent.com/aida-public/AB6AXuBPWDjvnTaerXWIKJZGk-08L0ZkEpA_hXHCqf4K-1A-PYPlk7sgIcB374lQNK6GEAD3sLtjoUXdMiupNQ5PktuJMeQhBnlJ5U5qR1QiZ8aKmzLOVwB0XGj1fI6DywD1iDpOyEJ3UGGvUsQuWfe9-HsTLHi915dsj5mhpvYZHsWoS0LS_z1A0SFAGfRu9doaq2csh37D0ZUJrqgV0Xw40MS14iIpJOyteW0uA1uYycC0XhToX0SdvPP50xQMSrY2zYFUXe-YaGOgxZc');
      setEfficiencyRate(98.4);
      setVaccineRates(srvRatesFallback);
      setActiveTab('dashboard');
    }
  };

  const handleExportAll = () => {
    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += 'ID,Fecha,Cliente,Servicio,Cantidad,Costo por Ave (Q),Total (Q),Estado,Vacunador,Direccion\n';

    records.forEach(r => {
      csvContent += `"${r.id}","${r.fecha}","${r.cliente}","${r.servicio}",${r.cantidad},${r.costoPorAve},${r.total},"${r.estado}","${r.vacunador}","${r.direccion}"\n`;
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'VaxPoultry_Historial_Completo.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  return (
    <div className="font-sans text-slate-800 min-h-screen relative selection:bg-[#3455b9]/20">
      
      {/* Absolute floating atmospheric backdrop blobs */}
      <div className="fixed top-0 left-0 right-0 bottom-0 overflow-hidden pointer-events-none -z-10">
        <div className="absolute top-[-10%] left-[-15%] w-[80vw] h-[80vw] sm:w-[50vw] sm:h-[50vw] rounded-full bg-blue-100/15 blur-[150px]" />
        <div className="absolute bottom-[-10%] right-[-15%] w-[80vw] h-[80vw] sm:w-[50vw] sm:h-[50vw] rounded-full bg-slate-200/10 blur-[150px]" />
        <div className="absolute top-[40%] right-[30%] w-[30vh] h-[30vh] rounded-full bg-indigo-100/10 blur-[120px]" />
      </div>

      {/* Sidebar Navigation */}
      <Sidebar
        activeTab={activeTab}
        setActiveTab={setActiveTab}
      />

      {/* Switch Screens Content Wrapper */}
      <main className="md:pl-64 pt-10 md:pt-14 pb-24 md:pb-12 px-4 sm:px-8 md:px-10 max-w-[1440px] mx-auto z-10 relative">
        {activeTab === 'dashboard' && (
          <Dashboard
            records={records}
            reminders={reminders}
            onAddReminder={handleAddReminder}
            onDeleteReminder={handleDeleteReminder}
            onNavigateToRegistros={() => setActiveTab('registros')}
            onNavigateToNuevo={() => setActiveTab('nuevo-registro')}
            regionalData={REGIONAL_DISTRIBUTION}
            efficiencyRate={efficiencyRate}
            selectedMonth={selectedMonth}
            setSelectedMonth={setSelectedMonth}
            selectedYear={selectedYear}
            setSelectedYear={setSelectedYear}
          />
        )}

        {activeTab === 'nuevo-registro' && (
          <NuevoRegistro
            onAddRecord={handleAddRecord}
            records={records}
            efficiencyRate={efficiencyRate}
          />
        )}

        {activeTab === 'registros' && (
          <RegistrosList
            records={records}
            onDeleteRecord={handleDeleteRecord}
            onUpdateStatus={handleUpdateStatus}
            onNavigateToNuevo={() => setActiveTab('nuevo-registro')}
          />
        )}

        {activeTab === 'catalogo' && (
          <Catalogo />
        )}

        {activeTab === 'ajustes' && (
          <Ajustes
            adminName={adminName}
            setAdminName={setAdminName}
            adminRole={adminRole}
            setAdminRole={setAdminRole}
            avatarUrl={avatarUrl}
            setAvatarUrl={setAvatarUrl}
            efficiencyRate={efficiencyRate}
            setEfficiencyRate={setEfficiencyRate}
            onRatesModified={handleRatesModified}
            vaccineRates={vaccineRates}
          />
        )}
      </main>

      {/* Mobile Sticky Tab bar navigation */}
      <nav className="fixed bottom-0 left-0 right-0 h-16 bg-white/40 backdrop-blur-2xl border-t border-white/50 flex justify-around items-center px-4 md:hidden z-40 shadow-lg">
        
        <button
          onClick={() => setActiveTab('dashboard')}
          className={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
            activeTab === 'dashboard' ? 'text-[#3455b9]' : 'text-gray-500'
          }`}
        >
          <LayoutDashboard className="w-5 h-5" />
          <span className="text-[10px] font-bold">Dash</span>
        </button>

        <button
          onClick={() => setActiveTab('registros')}
          className={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
            activeTab === 'registros' ? 'text-[#3455b9]' : 'text-gray-500'
          }`}
        >
          <FileSpreadsheet className="w-5 h-5" />
          <span className="text-[10px] font-bold">Historial</span>
        </button>

        <button
          onClick={() => setActiveTab('catalogo')}
          className={`flex flex-col items-center gap-1 transition-all cursor-pointer ${
            activeTab === 'catalogo' ? 'text-[#3455b9]' : 'text-gray-500'
          }`}
        >
          <BookOpen className="w-5 h-5" />
          <span className="text-[10px] font-bold">Catálogo</span>
        </button>

        {/* Floating Add Center Button */}
        <button
          onClick={() => setActiveTab('nuevo-registro')}
          className="w-12 h-12 -mt-7 bg-[#3455b9] rounded-full shadow-md flex items-center justify-center text-white border-4 border-white cursor-pointer active:scale-90 transition-transform"
        >
          <Plus className="w-6 h-6" />
        </button>

        <button
          onClick={() => {
            setActiveTab('dashboard');
            setTimeout(() => {
              const el = document.querySelector('.bg-white\\/75');
              if (el) el.scrollIntoView({ behavior: 'smooth' });
            }, 100);
          }}
          className="flex flex-col items-center gap-1 text-gray-500 cursor-pointer hover:text-[#3455b9]"
        >
          <Calendar className="w-5 h-5" />
          <span className="text-[10px] font-bold">Tareas</span>
        </button>

      </nav>

    </div>
  );
}
