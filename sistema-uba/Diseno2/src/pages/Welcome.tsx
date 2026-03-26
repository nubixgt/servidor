import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'motion/react';

const slides = [
  {
    title: "Bienvenido a AppUBA",
    subtitle: "Unidad de Bienestar Animal — MAGA Guatemala",
    text: "Tu herramienta oficial para proteger y defender los derechos de los animales en Guatemala. Reporta, da seguimiento y mantente informado.",
    image: "https://images.unsplash.com/photo-1541364983171-a8ba01e95cfc?q=80&w=800&auto=format&fit=crop"
  },
  {
    title: "Denuncia un caso de maltrato",
    subtitle: "",
    text: "¿Encontraste un animal en situación de abandono, maltrato o peligro? Repórtalo aquí de forma rápida y segura. Nuestros inspectores de la Unidad de Bienestar Animal recibirán tu denuncia y actuarán a la brevedad.\n\nCada denuncia cuenta. Tu reporte puede salvar una vida.",
    image: "https://images.unsplash.com/photo-1527362950785-f487a7c1fe48?q=80&w=800&auto=format&fit=crop"
  },
  {
    title: "Servicios de Bienestar Animal",
    subtitle: "",
    text: "Accede a los servicios que la Unidad de Bienestar Animal del MAGA pone a tu disposición: registro de mascotas, solicitud de inspecciones, campañas de vacunación, esterilización y atención veterinaria en tu municipio.\n\nTodos los servicios son gratuitos y están disponibles para ciudadanos guatemaltecos.",
    image: "https://images.unsplash.com/photo-1576201836106-db1758fd1c97?q=80&w=800&auto=format&fit=crop"
  },
  {
    title: "Noticias y Actualizaciones",
    subtitle: "",
    text: "Mantente al día con las últimas acciones, operativos y campañas de la Unidad de Bienestar Animal. Conoce los casos resueltos, próximas jornadas de vacunación y novedades en la legislación de protección animal en Guatemala.\n\nInformación oficial, directamente desde el MAGA.",
    image: "https://images.unsplash.com/photo-1583337130417-3346a1be7dee?q=80&w=800&auto=format&fit=crop"
  }
];

export default function Welcome() {
  const [currentIndex, setCurrentIndex] = useState(0);
  const navigate = useNavigate();

  const handleNext = () => {
    if (currentIndex < slides.length - 1) {
      setCurrentIndex(currentIndex + 1);
    } else {
      handleSkip();
    }
  };

  const handleSkip = () => {
    localStorage.setItem('onboardingComplete', 'true');
    navigate('/');
  };

  return (
    <div className="fixed inset-0 bg-surface flex flex-col overflow-hidden font-body z-[100]">
      <AnimatePresence mode="wait">
        <motion.div
          key={currentIndex}
          initial={{ opacity: 0, x: 50 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -50 }}
          transition={{ duration: 0.3 }}
          className="flex-1 flex flex-col"
        >
          <div className="relative h-[55%] w-full shrink-0">
            <img 
              src={slides[currentIndex].image} 
              alt="Onboarding" 
              className="w-full h-full object-cover rounded-b-[3rem] shadow-lg" 
            />
            <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent rounded-b-[3rem]"></div>
          </div>
          <div className="flex-1 px-8 pt-8 pb-28 flex flex-col justify-start text-center overflow-y-auto">
            <h1 className="text-3xl font-headline font-extrabold text-primary mb-2 leading-tight">
              {slides[currentIndex].title}
            </h1>
            {slides[currentIndex].subtitle && (
              <h2 className="text-sm font-bold text-secondary uppercase tracking-widest mb-4">
                {slides[currentIndex].subtitle}
              </h2>
            )}
            <p className="text-on-surface-variant text-base leading-relaxed whitespace-pre-line mt-2">
              {slides[currentIndex].text}
            </p>
          </div>
        </motion.div>
      </AnimatePresence>

      <div className="absolute bottom-0 left-0 w-full p-6 bg-white/80 backdrop-blur-xl border-t border-outline-variant/20 flex items-center justify-between z-10">
        <button 
          onClick={handleSkip} 
          className="text-on-surface-variant font-bold px-4 py-2 hover:bg-surface-container rounded-full transition-colors"
        >
          Omitir
        </button>
        <div className="flex gap-2">
          {slides.map((_, idx) => (
            <div 
              key={idx} 
              className={`h-2 rounded-full transition-all duration-300 ${
                idx === currentIndex ? 'w-6 bg-primary' : 'w-2 bg-primary/20'
              }`} 
            />
          ))}
        </div>
        <button 
          onClick={handleNext} 
          className="primary-gradient text-white font-bold px-6 py-3 rounded-full shadow-lg shadow-primary/20 active:scale-95 transition-all"
        >
          {currentIndex === slides.length - 1 ? 'Comenzar' : 'Siguiente'}
        </button>
      </div>
    </div>
  );
}
