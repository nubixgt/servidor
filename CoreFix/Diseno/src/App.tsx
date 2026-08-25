import React, { useState } from 'react';
import { NavTab, ServiceItem } from './types';
import { Navbar } from './components/Navbar';
import { Hero } from './components/Hero';
import { ServicesSection } from './components/ServicesSection';
import { ServicesView } from './components/ServicesView';
import { WorkPortfolioView } from './components/WorkPortfolioView';
import { ContactQuoteView } from './components/ContactQuoteView';
import { Footer } from './components/Footer';
import { WhatsAppWidget } from './components/WhatsAppWidget';
import { OrderTrackerModal } from './components/OrderTrackerModal';
import { ServiceDetailModal } from './components/ServiceDetailModal';
import { QuoteModal } from './components/QuoteModal';
import { TrustSection } from './components/TrustSection';

export default function App() {
  const [activeTab, setActiveTab] = useState<NavTab>('inicio');
  
  // Modals state
  const [isTrackerOpen, setIsTrackerOpen] = useState(false);
  const [trackerInitialCode, setTrackerInitialCode] = useState('TF-8841');
  const [selectedService, setSelectedService] = useState<ServiceItem | null>(null);
  const [isQuoteModalOpen, setIsQuoteModalOpen] = useState(false);
  const [quoteServiceTitle, setQuoteServiceTitle] = useState('');
  const [isWhatsAppOpen, setIsWhatsAppOpen] = useState(false);

  // Quick action triggers
  const handleOpenQuoteModal = (serviceTitle: string = '') => {
    setQuoteServiceTitle(serviceTitle);
    setIsQuoteModalOpen(true);
  };

  const handleOpenTrackerWithCode = (code: string) => {
    setTrackerInitialCode(code);
    setIsTrackerOpen(true);
  };

  const handleSelectServiceDetail = (service: ServiceItem) => {
    setSelectedService(service);
  };

  return (
    <div className="min-h-screen bg-white flex flex-col selection:bg-blue-600 selection:text-white">
      {/* Top Navbar */}
      <Navbar
        activeTab={activeTab}
        setActiveTab={setActiveTab}
        onOpenTracker={() => setIsTrackerOpen(true)}
        onOpenQuoteModal={() => handleOpenQuoteModal('Solicitud General')}
      />

      {/* Main Screen Views */}
      <main className="flex-1">
        {activeTab === 'inicio' && (
          <>
            {/* 1. Hero matching reference */}
            <Hero
              onSolicitarPresupuesto={() => handleOpenQuoteModal('Presupuesto Inicial')}
              onVerServicios={() => {
                const el = document.getElementById('servicios-section');
                if (el) {
                  el.scrollIntoView({ behavior: 'smooth' });
                } else {
                  setActiveTab('servicios');
                }
              }}
            />

            {/* 2. Nuestros Servicios Grid matching reference */}
            <ServicesSection
              onSelectService={handleSelectServiceDetail}
              onOpenQuoteWithService={(title) => handleOpenQuoteModal(title)}
            />

            {/* 3. Trust, stats and real client reviews */}
            <TrustSection />
          </>
        )}

        {activeTab === 'servicios' && (
          <ServicesView
            onSelectService={handleSelectServiceDetail}
            onOpenQuoteWithService={(title) => handleOpenQuoteModal(title)}
          />
        )}

        {activeTab === 'trabajos' && (
          <WorkPortfolioView
            onOpenQuoteWithDevice={(device) => {
              setQuoteServiceTitle(`Reparación para ${device}`);
              setActiveTab('contacto');
            }}
          />
        )}

        {activeTab === 'contacto' && (
          <ContactQuoteView
            initialService={quoteServiceTitle}
            onTicketGenerated={(code) => {
              handleOpenTrackerWithCode(code);
            }}
            onOpenWhatsApp={() => setIsWhatsAppOpen(true)}
          />
        )}
      </main>

      {/* Footer matching reference */}
      <Footer
        setActiveTab={setActiveTab}
        onOpenWhatsApp={() => setIsWhatsAppOpen(true)}
      />

      {/* Floating WhatsApp Widget */}
      <WhatsAppWidget
        isOpen={isWhatsAppOpen}
        setIsOpen={setIsWhatsAppOpen}
      />

      {/* Order Tracker Modal */}
      <OrderTrackerModal
        isOpen={isTrackerOpen}
        onClose={() => setIsTrackerOpen(false)}
        initialCode={trackerInitialCode}
      />

      {/* Service Details Modal */}
      <ServiceDetailModal
        service={selectedService}
        isOpen={selectedService !== null}
        onClose={() => setSelectedService(null)}
        onOpenQuote={(title) => handleOpenQuoteModal(title)}
      />

      {/* Quick Quote Modal */}
      <QuoteModal
        isOpen={isQuoteModalOpen}
        onClose={() => setIsQuoteModalOpen(false)}
        initialServiceTitle={quoteServiceTitle}
        onSuccess={(code) => {
          handleOpenTrackerWithCode(code);
        }}
      />
    </div>
  );
}
