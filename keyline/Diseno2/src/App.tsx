import React, { useState } from 'react';
import { NavigationTab, Parcela, BioindicatorSample, UserProfile, SystemAlert } from './types';
import { INITIAL_PARCELAS, INITIAL_BIO_SAMPLES, INITIAL_USERS, INITIAL_ALERTS } from './data/initialData';
import { SideNavBar } from './components/SideNavBar';
import { TopAppBar } from './components/TopAppBar';
import { DashboardView } from './components/DashboardView';
import { PlotInventoryView } from './components/PlotInventoryView';
import { PlotRegistrationWizard } from './components/PlotRegistrationWizard';
import { BioindicatorsView } from './components/BioindicatorsView';
import { ReportsAnalysisView } from './components/ReportsAnalysisView';
import { UserManagementView } from './components/UserManagementView';
import { SettingsView } from './components/SettingsView';
import { LoginView } from './components/LoginView';
import { PlotDetailModal } from './components/PlotDetailModal';
import { ExportModal } from './components/ExportModal';
import { SupportModal } from './components/SupportModal';

export const App: React.FC = () => {
  // Authentication state
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(true);
  const [currentUser, setCurrentUser] = useState<UserProfile>(INITIAL_USERS[1]); // Default: Ana Martínez (Supervisor)

  // Navigation state
  const [activeTab, setActiveTab] = useState<NavigationTab>('dashboard');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [selectedRegion, setSelectedRegion] = useState('Guatemala');

  // App Data collections
  const [parcelas, setParcelas] = useState<Parcela[]>(INITIAL_PARCELAS);
  const [bioSamples, setBioSamples] = useState<BioindicatorSample[]>(INITIAL_BIO_SAMPLES);
  const [users, setUsers] = useState<UserProfile[]>(INITIAL_USERS);
  const [alerts, setAlerts] = useState<SystemAlert[]>(INITIAL_ALERTS);

  // Modals & Toast State
  const [inspectingParcel, setInspectingParcel] = useState<Parcela | null>(null);
  const [isExportModalOpen, setIsExportModalOpen] = useState(false);
  const [isSupportModalOpen, setIsSupportModalOpen] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  const triggerToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(null), 3500);
  };

  const handleLogin = (user: UserProfile) => {
    setCurrentUser(user);
    setIsAuthenticated(true);
    triggerToast(`Sesión iniciada como ${user.name}`);
  };

  const handleLogout = () => {
    setIsAuthenticated(false);
    triggerToast('Sesión finalizada');
  };

  const handleSaveNewParcel = (newParcel: Parcela) => {
    setParcelas([newParcel, ...parcelas]);
    setActiveTab('plot-inventory');
    triggerToast(`Parcela ${newParcel.code} registrada con éxito`);
  };

  const handleDeleteParcel = (id: string) => {
    setParcelas(parcelas.filter(p => p.id !== id));
    triggerToast('Parcela eliminada');
  };

  const handleApproveParcel = (id: string) => {
    setParcelas(parcelas.map(p => p.id === id ? { ...p, status: 'Aprobado' as const } : p));
    triggerToast('Parcela aprobada exitosamente');
  };

  const handleAddBioSample = (sample: BioindicatorSample) => {
    setBioSamples([sample, ...bioSamples]);
    triggerToast('Muestreo biológico guardado');
  };

  const handleCreateUser = (newUser: UserProfile) => {
    setUsers([...users, newUser]);
    triggerToast(`Usuario ${newUser.name} creado`);
  };

  const handleUpdateUserStatus = (id: string, status: 'Active' | 'Inactive') => {
    setUsers(users.map(u => u.id === id ? { ...u, status } : u));
    triggerToast(`Estado de usuario actualizado a ${status}`);
  };

  const handleDeleteUser = (id: string) => {
    setUsers(users.filter(u => u.id !== id));
    triggerToast('Usuario eliminado');
  };

  const handleUpdateProfile = (updated: Partial<UserProfile>) => {
    setCurrentUser(prev => ({ ...prev, ...updated }));
    setUsers(users.map(u => u.id === currentUser.id ? { ...u, ...updated } : u));
    triggerToast('Perfil actualizado correctamente');
  };

  // If user is not logged in, render the login view
  if (!isAuthenticated) {
    return <LoginView onLogin={handleLogin} />;
  }

  // Filter parcelas if specific region selected in top bar
  const displayedParcelas = selectedRegion === 'Guatemala' 
    ? parcelas 
    : parcelas.filter(p => p.department.toLowerCase() === selectedRegion.toLowerCase());

  return (
    <div className="min-h-screen text-[#f1f5f9] flex overflow-x-hidden relative bg-[#071510] font-[Arial,Helvetica,sans-serif]">
      {/* Toast Notification */}
      {toastMessage && (
        <div className="fixed bottom-6 right-6 z-50 bg-[#0c1e17] border border-[#17382b] text-white text-xs px-4 py-3 rounded-xl shadow-2xl animate-fadeIn flex items-center gap-2">
          <div className="w-2 h-2 rounded-full bg-[#22c55e] animate-pulse" />
          <span>{toastMessage}</span>
        </div>
      )}

      {/* Side Navigation Bar */}
      <SideNavBar
        activeTab={activeTab}
        onSelectTab={(tab) => setActiveTab(tab)}
        onOpenNewRegistration={() => setActiveTab('registration-wizard')}
        onOpenSupport={() => setIsSupportModalOpen(true)}
        onLogout={handleLogout}
        currentUser={currentUser}
        isOpenMobile={isMobileMenuOpen}
        onCloseMobile={() => setIsMobileMenuOpen(false)}
      />

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col md:ml-[260px] min-h-screen relative z-10 bg-[#071510]">
        {/* Top App Bar */}
        <TopAppBar
          currentUser={currentUser}
          alerts={alerts}
          onOpenMobileMenu={() => setIsMobileMenuOpen(true)}
          onSearch={(query) => {
            if (query.trim() && activeTab !== 'plot-inventory') {
              setActiveTab('plot-inventory');
            }
          }}
          selectedRegion={selectedRegion}
          onChangeRegion={(reg) => {
            setSelectedRegion(reg);
            triggerToast(`Filtrando vista para: ${reg}`);
          }}
          onLogout={handleLogout}
          onSelectTab={(tab) => setActiveTab(tab)}
        />

        {/* Dynamic View Body */}
        <main className="flex-1 p-4 sm:p-6 lg:p-8 mt-[64px] pb-16 bg-[#071510]">
          {activeTab === 'dashboard' && (
            <DashboardView
              parcelas={displayedParcelas}
              onSelectParcel={(parcel) => setInspectingParcel(parcel)}
              onNavigateToTab={(tab) => setActiveTab(tab)}
              onOpenExportReport={() => setIsExportModalOpen(true)}
            />
          )}

          {activeTab === 'plot-inventory' && (
            <PlotInventoryView
              parcelas={displayedParcelas}
              onSelectParcel={(parcel) => setInspectingParcel(parcel)}
              onOpenNewRegistration={() => setActiveTab('registration-wizard')}
              onDeleteParcel={handleDeleteParcel}
            />
          )}

          {activeTab === 'registration-wizard' && (
            <PlotRegistrationWizard
              onSaveParcel={handleSaveNewParcel}
              onCancel={() => setActiveTab('plot-inventory')}
            />
          )}

          {(activeTab === 'field-surveys' || activeTab === 'technical-variables') && (
            <BioindicatorsView
              samples={bioSamples}
              onAddSample={handleAddBioSample}
            />
          )}

          {activeTab === 'supervisor-reviews' && (
            <ReportsAnalysisView
              parcelas={displayedParcelas}
              onApproveParcel={handleApproveParcel}
              onOpenExportModal={() => setIsExportModalOpen(true)}
            />
          )}

          {activeTab === 'user-management' && (
            <UserManagementView
              users={users}
              currentUser={currentUser}
              onCreateUser={handleCreateUser}
              onUpdateStatus={handleUpdateUserStatus}
              onDeleteUser={handleDeleteUser}
            />
          )}

          {activeTab === 'settings' && (
            <SettingsView
              currentUser={currentUser}
              onUpdateProfile={handleUpdateProfile}
            />
          )}
        </main>
      </div>

      {/* Global Modals */}
      {inspectingParcel && (
        <PlotDetailModal
          parcel={inspectingParcel}
          onClose={() => setInspectingParcel(null)}
          onUpdateParcel={(updated) => {
            setParcelas(parcelas.map(p => p.id === updated.id ? updated : p));
            setInspectingParcel(updated);
            triggerToast(`Parcela ${updated.code} actualizada`);
          }}
        />
      )}

      {isExportModalOpen && (
        <ExportModal
          parcelas={displayedParcelas}
          onClose={() => setIsExportModalOpen(false)}
        />
      )}

      {isSupportModalOpen && (
        <SupportModal
          onClose={() => setIsSupportModalOpen(false)}
        />
      )}
    </div>
  );
};

export default App;
