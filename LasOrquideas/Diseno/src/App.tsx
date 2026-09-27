import { useState } from 'react';
import { Navbar } from './components/Navbar';
import { HeroSection } from './components/HeroSection';
import { ProjectSection } from './components/ProjectSection';
import { InteractiveLotsSection } from './components/InteractiveLotsSection';
import { LocationSection } from './components/LocationSection';
import { GallerySection } from './components/GallerySection';
import { BenefitsRibbon } from './components/BenefitsRibbon';
import { ContactSection } from './components/ContactSection';
import { CtaBanner } from './components/CtaBanner';
import { Footer } from './components/Footer';
import { ReservationModal } from './components/ReservationModal';
import { VideoModal } from './components/VideoModal';
import { LightboxModal } from './components/LightboxModal';
import { LOTS_DATA, Lot, GALLERY_ITEMS } from './data/lotData';

export default function App() {
  const [activeScreen, setActiveScreen] = useState('inicio');
  const [viewMode, setViewMode] = useState<'landing' | 'screens'>('landing');
  const [selectedLotId, setSelectedLotId] = useState('l-12');
  const [reservationLot, setReservationLot] = useState<Lot | null>(null);
  const [isVideoModalOpen, setIsVideoModalOpen] = useState(false);
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  const handleSelectLot = (lotId: string) => {
    setSelectedLotId(lotId);
    if (viewMode === 'screens') {
      setActiveScreen('lotes');
    } else {
      const el = document.getElementById('lotes');
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  const handleOpenReservation = (lot: Lot) => {
    setReservationLot(lot);
  };

  const handleOpenVideoTour = () => {
    setIsVideoModalOpen(true);
  };

  const handleOpenLightbox = (index: number) => {
    setLightboxIndex(index);
  };

  const handleNavigate = (sectionId: string) => {
    setActiveScreen(sectionId);
    if (viewMode === 'landing') {
      const el = document.getElementById(sectionId);
      if (el) el.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="min-h-screen bg-[#F2F6F4] text-slate-800 antialiased selection:bg-brand-forest selection:text-white relative overflow-x-hidden">
      {/* Foliage overlay decorative elements matching mockup */}
      <div className="foliage-blur-left hidden lg:block pointer-events-none fixed left-0 top-[15%] w-72 h-96 opacity-60 z-0">
        <svg className="w-full h-full" fill="none" viewBox="0 0 200 300" xmlns="http://www.w3.org/2000/svg">
          <path d="M-40 200C20 180 80 120 100 40C70 80 30 110 -40 200Z" fill="#2d6a4f" />
          <path d="M-20 280C50 250 140 180 160 80C110 130 50 180 -20 280Z" fill="#1b4332" />
          <path d="M-10 120C40 100 110 70 140 -20C90 20 40 60 -10 120Z" fill="#40916c" />
        </svg>
      </div>

      <div className="foliage-blur-right hidden lg:block pointer-events-none fixed right-0 top-[45%] w-64 h-96 opacity-50 z-0">
        <svg className="w-full h-full" fill="none" viewBox="0 0 200 300" xmlns="http://www.w3.org/2000/svg">
          <path d="M220 20C160 40 100 100 80 180C110 140 150 110 220 20Z" fill="#2d6a4f" />
          <path d="M240 100C170 130 80 200 60 300C110 250 170 200 240 100Z" fill="#1b4332" />
        </svg>
      </div>

      {/* Floating Capsule Navbar */}
      <Navbar
        activeScreen={activeScreen}
        setActiveScreen={setActiveScreen}
        viewMode={viewMode}
        setViewMode={setViewMode}
      />

      {/* Main Content Area */}
      <main className="relative z-10">
        {viewMode === 'landing' ? (
          /* Full Continuous Landing Experience (Matching 100% Mockup Design) */
          <>
            <HeroSection
              onExploreLots={() => handleSelectLot('l-12')}
              onOpenVideoTour={handleOpenVideoTour}
            />

            <ProjectSection
              onSelectLot={handleSelectLot}
              onOpenVideoTour={handleOpenVideoTour}
              onMoreInfo={() => handleNavigate('contacto')}
            />

            <InteractiveLotsSection
              selectedLotId={selectedLotId}
              onSelectLot={setSelectedLotId}
              onOpenReservation={handleOpenReservation}
            />

            <LocationSection />

            <GallerySection onOpenLightbox={handleOpenLightbox} />

            <BenefitsRibbon />

            <ContactSection initialLotInterest="Lote 12 (260 m²) - Manzana B" />

            <CtaBanner onContactClick={() => handleNavigate('contacto')} />
          </>
        ) : (
          /* Dedicated Screens Mode ("Pantallas" View) */
          <div className="pt-24 min-h-[80vh]">
            {activeScreen === 'inicio' && (
              <div>
                <HeroSection
                  onExploreLots={() => {
                    setActiveScreen('lotes');
                  }}
                  onOpenVideoTour={handleOpenVideoTour}
                />
                <BenefitsRibbon />
              </div>
            )}

            {activeScreen === 'proyecto' && (
              <div className="py-6">
                <ProjectSection
                  onSelectLot={handleSelectLot}
                  onOpenVideoTour={handleOpenVideoTour}
                  onMoreInfo={() => setActiveScreen('contacto')}
                />
              </div>
            )}

            {activeScreen === 'lotes' && (
              <div className="py-6">
                <InteractiveLotsSection
                  selectedLotId={selectedLotId}
                  onSelectLot={setSelectedLotId}
                  onOpenReservation={handleOpenReservation}
                />
              </div>
            )}

            {activeScreen === 'ubicacion' && (
              <div className="py-6">
                <LocationSection />
              </div>
            )}

            {activeScreen === 'galeria' && (
              <div className="py-6">
                <GallerySection onOpenLightbox={handleOpenLightbox} />
              </div>
            )}

            {activeScreen === 'contacto' && (
              <div className="py-6">
                <ContactSection initialLotInterest={`Lote ${selectedLotId.replace('l-', '')}`} />
              </div>
            )}

            <CtaBanner onContactClick={() => setActiveScreen('contacto')} />
          </div>
        )}
      </main>

      {/* Main Footer */}
      <Footer onNavigate={handleNavigate} />

      {/* Modals */}
      <ReservationModal
        lot={reservationLot}
        onClose={() => setReservationLot(null)}
      />

      <VideoModal
        isOpen={isVideoModalOpen}
        onClose={() => setIsVideoModalOpen(false)}
        onSelectLot={() => {
          setIsVideoModalOpen(false);
          handleSelectLot('l-12');
        }}
      />

      <LightboxModal
        currentIndex={lightboxIndex}
        onClose={() => setLightboxIndex(null)}
        onPrev={() =>
          setLightboxIndex((prev) =>
            prev !== null ? (prev > 0 ? prev - 1 : GALLERY_ITEMS.length - 1) : null
          )
        }
        onNext={() =>
          setLightboxIndex((prev) =>
            prev !== null ? (prev < GALLERY_ITEMS.length - 1 ? prev + 1 : 0) : null
          )
        }
      />
    </div>
  );
}
