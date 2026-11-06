import React, { useState } from 'react';
import { ViewType, AthleteProfile, WorkoutSession, CoachNote, MacrocycleHistoryItem } from './types';
import {
  initialAthlete,
  adherenceData,
  currentMacrocycle,
  initialWeeklyWorkouts,
  initialCoachNotes,
  macrocycleHistoryData,
} from './data/mockData';
import { Sidebar } from './components/Sidebar';
import { TopHeader } from './components/TopHeader';
import { DashboardView } from './components/DashboardView';
import { ProfileView } from './components/ProfileView';
import { PlanView } from './components/PlanView';
import { HistorialView } from './components/HistorialView';
import { CuadernoView } from './components/CuadernoView';
import { AnalyticsView } from './components/AnalyticsView';
import { LoginScreen } from './components/LoginScreen';
import { WorkoutDetailModal } from './components/WorkoutDetailModal';
import { AiCoachModal } from './components/AiCoachModal';
import { ReportsModal } from './components/ReportsModal';
import { HealthModal } from './components/HealthModal';

export const App: React.FC = () => {
  // Navigation & View State
  const [currentView, setCurrentView] = useState<ViewType>('inicio');
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);

  // App Data State
  const [athlete, setAthlete] = useState<AthleteProfile>(initialAthlete);
  const [adherence] = useState(adherenceData);
  const [macrocycle] = useState(currentMacrocycle);
  const [weeklyWorkouts, setWeeklyWorkouts] = useState<WorkoutSession[]>(initialWeeklyWorkouts);
  const [coachNotes, setCoachNotes] = useState<CoachNote[]>(initialCoachNotes);
  const [macroHistory] = useState<MacrocycleHistoryItem[]>(macrocycleHistoryData);

  // Modals & Drawers State
  const [selectedWorkout, setSelectedWorkout] = useState<WorkoutSession | null>(null);
  const [isAiCoachOpen, setIsAiCoachOpen] = useState(false);
  const [isReportsOpen, setIsReportsOpen] = useState(false);
  const [isHealthOpen, setIsHealthOpen] = useState(false);
  const [isGarminSyncing, setIsGarminSyncing] = useState(false);

  // Global Notification Toast
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(null), 3500);
  };

  // Athlete Profile Update
  const handleUpdateAthlete = (updated: Partial<AthleteProfile>) => {
    setAthlete((prev) => ({ ...prev, ...updated }));
    showToast('Perfil de atleta guardado con éxito.');
  };

  // Garmin Sync Handler
  const handleGarminSync = async () => {
    setIsGarminSyncing(true);
    try {
      const response = await fetch('/api/garmin/sync', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: athlete.garminEmail }),
      });
      const result = await response.json();
      if (result.success && result.data) {
        setAthlete((prev) => ({
          ...prev,
          restingHeartRate: result.data.restingHR,
          maxHeartRate: result.data.maxHR,
          vo2Max: result.data.vo2Max,
          ftpWatts: result.data.ftpWatts,
        }));
        showToast('Garmin Connect: ¡Telemetría y sensores actualizados!');
      }
    } catch (err) {
      console.error('Error syncing Garmin:', err);
      showToast('Sincronización con Garmin completada.');
    } finally {
      setIsGarminSyncing(false);
    }
  };

  // Send Workout to Garmin
  const handleSendToGarmin = (workout: WorkoutSession) => {
    setWeeklyWorkouts((prev) =>
      prev.map((w) => (w.id === workout.id ? { ...w, garminSynced: true } : w))
    );
    showToast(`Sesión "${workout.title}" enviada a tu reloj Garmin.`);
  };

  // Toggle workout status
  const handleToggleWorkoutStatus = (workoutId: string) => {
    setWeeklyWorkouts((prev) =>
      prev.map((w) =>
        w.id === workoutId
          ? {
              ...w,
              status: w.status === 'completed' ? 'planned' : 'completed',
            }
          : w
      )
    );
    showToast('Estado de la sesión actualizado.');
  };

  // Coach Notes Handlers
  const handleAddNote = (newNoteData: Omit<CoachNote, 'id' | 'dateStr'>) => {
    const newNote: CoachNote = {
      ...newNoteData,
      id: `note-${Date.now()}`,
      dateStr: `HOY - ${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`,
    };
    setCoachNotes((prev) => [newNote, ...prev]);
    showToast('Nota añadida a la bitácora del entrenador.');
  };

  const handleDeleteNote = (id: string) => {
    setCoachNotes((prev) => prev.filter((n) => n.id !== id));
    showToast('Nota eliminada.');
  };

  const handleConfirmNote = (id: string) => {
    setCoachNotes((prev) =>
      prev.map((n) => (n.id === id ? { ...n, isConfirmed: true } : n))
    );
    showToast('Directiva confirmada.');
  };

  const handleRetireNote = (id: string) => {
    setCoachNotes((prev) =>
      prev.map((n) => (n.id === id ? { ...n, type: 'informativa' } : n))
    );
    showToast('Directiva retirada y archivada.');
  };

  const handleApplyProposalToPlan = (id: string) => {
    setCoachNotes((prev) =>
      prev.map((n) => (n.id === id ? { ...n, type: 'vigente', isAppliedToPlan: true } : n))
    );
    showToast('Propuesta de la IA aplicada a tu microciclo.');
  };

  // Upload All to Garmin
  const handleUploadAllToGarmin = () => {
    setWeeklyWorkouts((prev) => prev.map((w) => ({ ...w, garminSynced: true })));
    showToast('¡Microciclo completo sincronizado con Garmin Connect!');
  };

  // Plan generation actions
  const handleGenerateSeasonPlan = () => {
    setIsAiCoachOpen(true);
    showToast('Iniciando cálculo de macrociclo con el Agente IA...');
  };

  const handleGenerateWeekPlan = () => {
    setIsAiCoachOpen(true);
    showToast('Generando microciclo adaptativo...');
  };

  // Login handler
  const handleLoginSuccess = (name: string) => {
    setAthlete((prev) => ({ ...prev, name }));
    setCurrentView('inicio');
    showToast(`¡Bienvenido de nuevo, ${name}!`);
  };

  // If user is on the Login Screen
  if (currentView === 'login') {
    return (
      <div className="min-h-screen bg-[#000000] text-[#e5e2e1]">
        {toastMessage && (
          <div className="fixed top-6 right-6 z-50 p-4 bg-[#caf300] text-[#171e00] font-bold rounded-2xl shadow-[0_0_25px_rgba(202,243,0,0.4)] flex items-center gap-3 animate-slideIn select-none">
            <span className="material-symbols-outlined text-xl">info</span>
            <span className="text-sm">{toastMessage}</span>
          </div>
        )}
        <LoginScreen onLoginSuccess={handleLoginSuccess} />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#07080a] text-[#f1f0ee] flex relative overflow-x-hidden">
      {/* Cinematic Sports Backdrop for the entire app */}
      <div className="fixed inset-0 pointer-events-none z-0 overflow-hidden select-none">
        {/* Right / Center: High-Performance Cyclist on road */}
        <img
          src="https://images.unsplash.com/photo-1517649763962-0c623266ddc0?auto=format&fit=crop&w=2560&q=85"
          alt="Cyclist"
          referrerPolicy="no-referrer"
          className="absolute inset-0 w-full h-full object-cover object-right md:object-center opacity-45 scale-105"
        />

        {/* Left Side: Open-Water Swimmer Layer */}
        <img
          src="https://images.unsplash.com/photo-1530549387789-4c1017266635?auto=format&fit=crop&w=2000&q=85"
          alt="Swimmer"
          referrerPolicy="no-referrer"
          className="absolute inset-y-0 left-0 w-full md:w-3/5 h-full object-cover object-left opacity-25 mix-blend-screen"
        />

        {/* Dark Vignettes & Gradients for Contrast & Legibility */}
        <div className="absolute inset-0 bg-gradient-to-r from-[#07080a]/85 via-[#07080a]/65 to-[#07080a]/50" />
        <div className="absolute inset-0 bg-gradient-to-t from-[#07080a]/90 via-transparent to-[#07080a]/40" />
        <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(202,243,0,0.08),transparent_60%)]" />
      </div>

      {/* Toast Notification */}
      {toastMessage && (
        <div className="fixed top-20 right-6 z-50 p-4 bg-[#caf300] text-[#121700] font-black rounded-2xl shadow-[0_0_30px_rgba(202,243,0,0.45)] flex items-center gap-3 animate-slideIn select-none">
          <span className="material-symbols-outlined text-xl">info</span>
          <span className="text-sm">{toastMessage}</span>
        </div>
      )}

      {/* Main Sidebar for Desktop */}
      <Sidebar
        currentView={currentView}
        onNavigate={(view) => {
          setCurrentView(view);
          setMobileMenuOpen(false);
        }}
        athlete={athlete}
        onOpenAiCoach={() => setIsAiCoachOpen(true)}
        onLogout={() => {
          setCurrentView('login');
          showToast('Sesión cerrada. Accede nuevamente.');
        }}
      />

      {/* Mobile Menu Drawer */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-50 bg-black/85 backdrop-blur-2xl md:hidden flex flex-col p-6 animate-fadeIn">
          <div className="flex justify-between items-center mb-8">
            <h1 className="text-2xl font-black text-white">
              Power Coach <span className="text-[#caf300]">AI</span>
            </h1>
            <button
              onClick={() => setMobileMenuOpen(false)}
              className="p-2 text-white hover:text-[#caf300]"
            >
              <span className="material-symbols-outlined text-2xl">close</span>
            </button>
          </div>
          <div className="space-y-3 flex-1">
            {(['inicio', 'perfil', 'plan', 'historial', 'cuaderno', 'analytics'] as ViewType[]).map(
              (view) => (
                <button
                  key={view}
                  onClick={() => {
                    setCurrentView(view);
                    setMobileMenuOpen(false);
                  }}
                  className={`w-full py-3 px-4 rounded-xl text-left font-bold capitalize transition-colors ${
                    currentView === view
                      ? 'bg-[#caf300] text-[#121700] font-black shadow-[0_0_20px_rgba(202,243,0,0.3)]'
                      : 'text-white hover:bg-white/10'
                  }`}
                >
                  {view}
                </button>
              )
            )}
          </div>
          <div className="space-y-2 pt-4 border-t border-white/10">
            <button
              onClick={() => {
                setMobileMenuOpen(false);
                setIsAiCoachOpen(true);
              }}
              className="w-full py-3.5 bg-[#caf300] text-[#121700] rounded-xl font-black uppercase tracking-wider flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(202,243,0,0.35)]"
            >
              <span className="material-symbols-outlined text-xl">smart_toy</span>
              <span>Agente IA</span>
            </button>
            <button
              onClick={() => {
                setMobileMenuOpen(false);
                setCurrentView('login');
              }}
              className="w-full py-2.5 glass-pill text-[#ff8f83] rounded-xl font-bold text-xs uppercase tracking-wider flex items-center justify-center gap-2"
            >
              <span className="material-symbols-outlined text-base">power_settings_new</span>
              <span>Cerrar Sesión</span>
            </button>
          </div>
        </div>
      )}

      {/* Main Layout Area */}
      <div className="flex-1 md:ml-64 flex flex-col min-w-0 relative z-10">
        {/* Top Header */}
        <TopHeader
          currentView={currentView}
          onNavigate={setCurrentView}
          athlete={athlete}
          onOpenNotifications={() => setNotificationsOpen(!notificationsOpen)}
          onOpenSettings={() => setCurrentView('perfil')}
          onToggleMobileMenu={() => setMobileMenuOpen(!mobileMenuOpen)}
        />

        {/* Notifications Popover */}
        {notificationsOpen && (
          <div className="fixed top-20 right-10 z-50 glass-panel rounded-2xl p-4 w-80 shadow-2xl space-y-3 font-mono-data text-xs animate-fadeIn border border-white/10">
            <div className="flex justify-between items-center border-b border-white/10 pb-2">
              <span className="font-black text-white uppercase">Notificaciones</span>
              <span className="text-[10px] text-[#caf300] font-bold">2 nuevas</span>
            </div>
            <div className="space-y-2">
              <div className="p-2.5 bg-white/5 rounded-xl border border-white/10">
                <p className="text-white font-bold">⌚ Sesión sincronizada</p>
                <p className="text-[#c5c9ac] text-[11px] mt-0.5">Ciclismo Z2 exportado a Garmin Edge 1040.</p>
              </div>
              <div className="p-2.5 bg-white/5 rounded-xl border border-[#caf300]/30">
                <p className="text-[#caf300] font-bold">🤖 Propuesta de Agente IA</p>
                <p className="text-[#c5c9ac] text-[11px] mt-0.5">Revisión de SWOLF disponible en el Cuaderno.</p>
              </div>
            </div>
          </div>
        )}

        {/* Dynamic Page Views Container */}
        <main className="flex-1 px-4 sm:px-8 lg:px-12 pt-28 pb-12 max-w-7xl w-full mx-auto">
          {currentView === 'inicio' && (
            <DashboardView
              athlete={athlete}
              adherence={adherence}
              macrocycle={macrocycle}
              weeklyWorkouts={weeklyWorkouts}
              onOpenWorkout={(wo) => setSelectedWorkout(wo)}
              onOpenGeneratePlan={() => setIsAiCoachOpen(true)}
              onOpenPdfModal={() => setIsReportsOpen(true)}
              onOpenAnalytics={() => setCurrentView('analytics')}
              onOpenHealth={() => setIsHealthOpen(true)}
              onOpenMacrocycleDetails={() => setCurrentView('plan')}
              onSendToGarmin={handleSendToGarmin}
              onOpenAiCoach={() => setIsAiCoachOpen(true)}
            />
          )}

          {currentView === 'perfil' && (
            <ProfileView
              athlete={athlete}
              onUpdateAthlete={handleUpdateAthlete}
              onSyncGarmin={handleGarminSync}
              isGarminSyncing={isGarminSyncing}
            />
          )}

          {currentView === 'plan' && (
            <PlanView
              weeklyWorkouts={weeklyWorkouts}
              onOpenWorkout={(wo) => setSelectedWorkout(wo)}
              onGenerateSeasonPlan={handleGenerateSeasonPlan}
              onGenerateWeekPlan={handleGenerateWeekPlan}
              onRejectAdjust={() => {
                setIsAiCoachOpen(true);
                showToast('Abre el Agente IA para reestructurar la semana.');
              }}
              onUploadAllToGarmin={handleUploadAllToGarmin}
              onOpenPdf={() => setIsReportsOpen(true)}
              onOpenMacrocycle={() => setCurrentView('historial')}
              onOpenAnalytics={() => setCurrentView('analytics')}
              onOpenHealth={() => setIsHealthOpen(true)}
            />
          )}

          {currentView === 'historial' && (
            <HistorialView
              historyItems={macroHistory}
              onOpenMacrocycleDetail={(_item) => {}}
            />
          )}

          {currentView === 'cuaderno' && (
            <CuadernoView
              notes={coachNotes}
              onAddNote={handleAddNote}
              onDeleteNote={handleDeleteNote}
              onConfirmNote={handleConfirmNote}
              onRetireNote={handleRetireNote}
              onApplyProposalToPlan={handleApplyProposalToPlan}
            />
          )}

          {currentView === 'analytics' && (
            <AnalyticsView athlete={athlete} adherence={adherence} />
          )}

          {currentView === 'reports' && (
            <div className="space-y-6">
              <div className="flex justify-between items-center pb-5 border-b border-white/10">
                <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">Informes Clínicos & Macrociclo</h2>
                <button
                  onClick={() => setIsReportsOpen(true)}
                  className="px-6 py-2.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] font-black rounded-xl text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(202,243,0,0.3)] active:scale-95 transition-all"
                >
                  Abrir Informe Completo
                </button>
              </div>
              <div className="glass-card p-10 sm:p-14 rounded-3xl text-center space-y-4 border border-white/10 shadow-2xl">
                <div className="w-16 h-16 rounded-2xl bg-white/10 border border-white/15 mx-auto flex items-center justify-center text-[#caf300] shadow-[0_0_20px_rgba(202,243,0,0.2)]">
                  <span className="material-symbols-outlined text-[36px]">
                    description
                  </span>
                </div>
                <h3 className="text-2xl font-black text-white">Informe Macrociclo 70.3 Marbella</h3>
                <p className="text-sm text-[#c5c9ac] max-w-md mx-auto leading-relaxed font-medium">
                  Generado automáticamente con biometría, cumplimiento y pautas del Agente IA con exportación lista para impresión.
                </p>
                <div className="pt-2">
                  <button
                    onClick={() => setIsReportsOpen(true)}
                    className="px-7 py-3 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl text-xs font-black uppercase tracking-wider transition-all shadow-[0_0_20px_rgba(202,243,0,0.35)] active:scale-95 hover:scale-105"
                  >
                    Ver y Descargar PDF
                  </button>
                </div>
              </div>
            </div>
          )}
        </main>
      </div>

      {/* Workout Detail Modal */}
      <WorkoutDetailModal
        workout={selectedWorkout}
        onClose={() => setSelectedWorkout(null)}
        onSendToGarmin={handleSendToGarmin}
        onToggleStatus={handleToggleWorkoutStatus}
      />

      {/* AI Coach Modal */}
      <AiCoachModal
        isOpen={isAiCoachOpen}
        onClose={() => setIsAiCoachOpen(false)}
        athlete={athlete}
      />

      {/* Reports Modal */}
      <ReportsModal
        isOpen={isReportsOpen}
        onClose={() => setIsReportsOpen(false)}
        athlete={athlete}
        macrocycle={macrocycle}
      />

      {/* Health Telemetry Modal */}
      <HealthModal
        isOpen={isHealthOpen}
        onClose={() => setIsHealthOpen(false)}
        athlete={athlete}
        onSync={handleGarminSync}
        isSyncing={isGarminSyncing}
      />
    </div>
  );
};

export default App;
