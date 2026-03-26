import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';

export default function CaseDetail() {
  const { id } = useParams();
  const navigate = useNavigate();

  return (
    <div className="animate-in fade-in duration-500">
      {/* Header Section */}
      <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
          <nav className="flex items-center gap-2 text-xs font-bold text-outline uppercase tracking-widest mb-2">
            <button onClick={() => navigate('/tech/bandeja')} className="hover:text-primary transition-colors">Casos</button>
            <span className="material-symbols-outlined text-[10px]">chevron_right</span>
            <span className="text-primary">Vista Detallada</span>
          </nav>
          <h2 className="font-headline text-4xl font-extrabold text-on-surface tracking-tight">{id || '#UBA-2024-8842'}</h2>
          <p className="text-on-surface-variant mt-1 font-body">Reportado el 24 Oct, 2024 • San José Sector 4</p>
        </div>
        <div className="flex items-center gap-3">
          <span className="px-4 py-2 rounded-full bg-error-container text-on-error-container text-xs font-bold flex items-center gap-2">
            <span className="w-2 h-2 rounded-full bg-error animate-pulse"></span>
            Prioridad Alta
          </span>
          <span className="px-4 py-2 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-bold flex items-center gap-2">
            <span className="material-symbols-outlined text-sm">pending_actions</span>
            En Progreso
          </span>
          <button className="bg-gradient-to-br from-primary to-primary-container text-white px-6 py-2.5 rounded-full font-bold shadow-lg hover:shadow-primary/20 transition-all flex items-center gap-2">
            <span className="material-symbols-outlined text-sm">check_circle</span>
            Resolver Caso
          </button>
        </div>
      </div>

      {/* Bento Grid Layout */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        {/* Left Column: Case Information */}
        <div className="lg:col-span-3 space-y-6">
          <div className="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/20 shadow-sm">
            <h3 className="font-headline font-bold text-lg mb-4 flex items-center gap-2 text-primary">
              <span className="material-symbols-outlined">person</span>
              Denunciante
            </h3>
            <div className="space-y-4">
              <div>
                <label className="text-[10px] font-bold text-outline uppercase tracking-wider block">Nombre Completo</label>
                <p className="text-sm font-semibold text-on-surface">Elena Rodriguez</p>
              </div>
              <div>
                <label className="text-[10px] font-bold text-outline uppercase tracking-wider block">Contacto</label>
                <p className="text-sm font-semibold text-on-surface">+506 8832-1109</p>
              </div>
              <div>
                <label className="text-[10px] font-bold text-outline uppercase tracking-wider block">Usuario Verificado</label>
                <div className="mt-1 flex items-center gap-1 text-tertiary font-bold text-xs">
                  <span className="material-symbols-outlined text-xs" style={{ fontVariationSettings: "'FILL' 1" }}>verified</span>
                  Identidad Confirmada
                </div>
              </div>
            </div>
          </div>

          <div className="bg-surface-container-lowest rounded-xl p-6 border border-outline-variant/20 shadow-sm">
            <h3 className="font-headline font-bold text-lg mb-4 flex items-center gap-2 text-primary">
              <span className="material-symbols-outlined">pets</span>
              Datos del Animal
            </h3>
            <div className="space-y-4">
              <div className="flex justify-between items-center bg-surface-container-low p-3 rounded-lg">
                <span className="text-sm text-on-surface-variant">Especie</span>
                <span className="text-sm font-bold px-3 py-1 bg-white rounded-md shadow-sm">Canino</span>
              </div>
              <div className="flex justify-between items-center bg-surface-container-low p-3 rounded-lg">
                <span className="text-sm text-on-surface-variant">Cantidad</span>
                <span className="text-sm font-bold px-3 py-1 bg-white rounded-md shadow-sm">02</span>
              </div>
              <div>
                <label className="text-[10px] font-bold text-outline uppercase tracking-wider block mb-2">Descripción Detallada</label>
                <p className="text-sm text-on-surface-variant leading-relaxed italic">
                  "Dos perros de tamaño mediano encontrados en un lote abandonado cerca de la construcción. Uno parece tener una herida en la pata. Falta de agua o refugio."
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Central Column: Interactive Timeline */}
        <div className="lg:col-span-6">
          <div className="bg-surface-container-lowest rounded-xl p-8 border border-outline-variant/20 shadow-sm h-full">
            <div className="flex items-center justify-between mb-8">
              <h3 className="font-headline font-extrabold text-xl flex items-center gap-3">
                <span className="w-2 h-8 bg-secondary rounded-full"></span>
                Historial y Línea de Tiempo
              </h3>
              <button className="text-primary text-sm font-bold flex items-center gap-1 hover:underline">
                <span className="material-symbols-outlined text-sm">filter_list</span>
                Filtrar
              </button>
            </div>

            <div className="relative space-y-8 before:content-[''] before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-surface-container-highest">
              
              {/* Timeline Item 1 */}
              <div className="relative pl-12">
                <div className="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-primary shadow-sm z-10"></div>
                <div className="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                  <div className="flex justify-between items-start mb-2">
                    <h4 className="text-sm font-bold">Caso Asignado a Marcus Thorne</h4>
                    <span className="text-[10px] font-medium text-outline">HOY, 09:15 AM</span>
                  </div>
                  <p className="text-sm text-on-surface-variant">Asignación automática del sistema basada en proximidad y nivel de urgencia.</p>
                </div>
              </div>

              {/* Timeline Item 2 */}
              <div className="relative pl-12">
                <div className="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-tertiary shadow-sm z-10"></div>
                <div className="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                  <div className="flex justify-between items-start mb-2">
                    <div className="flex items-center gap-2">
                      <h4 className="text-sm font-bold">Nuevo Comentario</h4>
                      <span className="text-[10px] px-2 py-0.5 bg-tertiary-fixed text-on-tertiary-fixed rounded font-bold uppercase">Nota de Campo</span>
                    </div>
                    <span className="text-[10px] font-medium text-outline">OCT 24, 04:30 PM</span>
                  </div>
                  <p className="text-sm text-on-surface-variant mb-3">"Visita inicial al sitio completada. El vecino confirmó que los perros han estado allí durante 3 días. Se les dio raciones de emergencia."</p>
                  <div className="flex items-center gap-2">
                    <div className="w-8 h-8 rounded bg-surface-container-high flex items-center justify-center">
                      <span className="material-symbols-outlined text-sm text-outline">picture_as_pdf</span>
                    </div>
                    <span className="text-xs font-bold text-primary cursor-pointer hover:underline">inspeccion_inicial_v1.pdf</span>
                  </div>
                </div>
              </div>

              {/* Timeline Item 3 */}
              <div className="relative pl-12">
                <div className="absolute left-[5px] top-1 w-5 h-5 rounded-full bg-surface-container-lowest border-4 border-outline-variant shadow-sm z-10"></div>
                <div className="bg-surface-container-low/30 p-4 rounded-xl border border-outline-variant/20">
                  <div className="flex justify-between items-start mb-2">
                    <h4 className="text-sm font-bold">Caso Abierto</h4>
                    <span className="text-[10px] font-medium text-outline">OCT 24, 02:00 PM</span>
                  </div>
                  <p className="text-sm text-on-surface-variant">Reporte recibido vía App Ciudadana #4492.</p>
                </div>
              </div>

            </div>
          </div>
        </div>

        {/* Right Column: Evidence, Map, Form */}
        <div className="lg:col-span-3 space-y-6">
          
          {/* Evidence Gallery */}
          <div className="bg-surface-container-lowest rounded-xl p-5 border border-outline-variant/20 shadow-sm">
            <h3 className="font-headline font-bold text-md mb-4 flex items-center gap-2 text-primary uppercase tracking-wide text-xs">
              <span className="material-symbols-outlined text-lg">photo_library</span>
              Galería de Evidencia
            </h3>
            <div className="grid grid-cols-2 gap-2">
              <img 
                className="w-full h-24 object-cover rounded-lg hover:scale-105 transition-transform cursor-pointer" 
                src="https://images.unsplash.com/photo-1548199973-03cce0bbc87b?auto=format&fit=crop&q=80&w=400&h=300" 
                alt="Evidencia 1" 
                referrerPolicy="no-referrer"
              />
              <img 
                className="w-full h-24 object-cover rounded-lg hover:scale-105 transition-transform cursor-pointer" 
                src="https://images.unsplash.com/photo-1537151608804-ea6f11840315?auto=format&fit=crop&q=80&w=400&h=300" 
                alt="Evidencia 2" 
                referrerPolicy="no-referrer"
              />
              <div className="w-full h-24 bg-surface-container-high rounded-lg flex flex-col items-center justify-center text-outline group cursor-pointer border-2 border-dashed border-outline-variant/50 hover:border-primary/50 transition-colors">
                <span className="material-symbols-outlined">add</span>
                <span className="text-[10px] font-bold">AÑADIR</span>
              </div>
            </div>
          </div>

          {/* Map Location */}
          <div className="bg-surface-container-lowest rounded-xl border border-outline-variant/20 shadow-sm overflow-hidden">
            <div className="p-4 flex items-center justify-between bg-surface-container-low/50">
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-primary">location_on</span>
                <span className="text-xs font-bold">SAN JOSÉ, SECTOR 4</span>
              </div>
              <button className="text-[10px] font-bold text-primary hover:underline">EXPANDIR</button>
            </div>
            <div className="h-40 bg-surface-container-high relative">
              <img 
                className="w-full h-full object-cover opacity-60 grayscale" 
                src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&q=80&w=600&h=400" 
                alt="Mapa" 
                referrerPolicy="no-referrer"
              />
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center animate-ping"></div>
                <div className="absolute w-4 h-4 rounded-full bg-primary border-2 border-white shadow-lg"></div>
              </div>
            </div>
          </div>

          {/* Action Form */}
          <div className="bg-surface-container-low rounded-xl p-6 border border-primary/10 shadow-sm">
            <h3 className="font-headline font-bold text-md mb-4 flex items-center gap-2 text-primary">
              <span className="material-symbols-outlined">edit_note</span>
              Actualizar Caso
            </h3>
            <form className="space-y-4">
              <textarea 
                className="w-full h-24 rounded-xl border-none bg-surface-container-lowest focus:ring-2 focus:ring-secondary text-sm font-body placeholder:text-outline p-3 shadow-sm" 
                placeholder="Escribe notas u observaciones técnicas..."
              ></textarea>
              <div className="border-2 border-dashed border-outline-variant/50 rounded-xl p-4 flex flex-col items-center justify-center bg-surface-container-lowest group hover:border-primary transition-colors cursor-pointer">
                <span className="material-symbols-outlined text-outline group-hover:text-primary mb-2">upload_file</span>
                <p className="text-[10px] font-bold text-outline uppercase">Soltar PDF Aquí</p>
              </div>
              <button 
                className="w-full py-3 bg-primary text-white font-bold rounded-full text-sm shadow-md hover:bg-primary-container transition-all" 
                type="button"
              >
                Publicar Actualización
              </button>
            </form>
          </div>

        </div>
      </div>
    </div>
  );
}
