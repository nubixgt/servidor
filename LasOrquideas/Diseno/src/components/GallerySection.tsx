import { useState } from 'react';
import { GALLERY_ITEMS } from '../data/lotData';
import { Maximize2 } from 'lucide-react';

interface GallerySectionProps {
  onOpenLightbox: (index: number) => void;
}

export function GallerySection({ onOpenLightbox }: GallerySectionProps) {
  const [filter, setFilter] = useState<'todos' | 'seguridad' | 'naturaleza' | 'vistas'>('todos');

  const filteredItems = GALLERY_ITEMS.filter((item) => {
    if (filter === 'todos') return true;
    return item.category === filter;
  });

  return (
    <section className="py-16 bg-[#eef4f1]" id="galeria">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex flex-col md:flex-row md:items-end justify-between mb-8">
          <div>
            <div className="flex items-center gap-2 mb-2">
              <div className="w-6 h-[2px] bg-brand-forest" />
              <span className="text-xs font-bold uppercase tracking-tagline text-brand-forest">
                GALERÍA VISUAL
              </span>
            </div>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
              Recorre cada espacio pensado para ti
            </h2>
          </div>
          <p className="text-xs sm:text-sm text-slate-500 max-w-sm mt-3 md:mt-0">
            Fotografías del avance de obra, garita de acceso monumental y áreas comunes en
            construcción.
          </p>
        </div>

        {/* Category Filters */}
        <div className="flex items-center gap-2 mb-8 overflow-x-auto pb-2 text-xs font-semibold">
          <button
            onClick={() => setFilter('todos')}
            className={`px-4 py-1.5 rounded-full transition cursor-pointer ${
              filter === 'todos'
                ? 'bg-brand-forest text-white shadow-xs'
                : 'bg-white hover:bg-slate-100 text-slate-700'
            }`}
          >
            Todas las fotos ({GALLERY_ITEMS.length})
          </button>
          <button
            onClick={() => setFilter('seguridad')}
            className={`px-4 py-1.5 rounded-full transition cursor-pointer ${
              filter === 'seguridad'
                ? 'bg-brand-forest text-white shadow-xs'
                : 'bg-white hover:bg-slate-100 text-slate-700'
            }`}
          >
            Garita & Seguridad
          </button>
          <button
            onClick={() => setFilter('naturaleza')}
            className={`px-4 py-1.5 rounded-full transition cursor-pointer ${
              filter === 'naturaleza'
                ? 'bg-brand-forest text-white shadow-xs'
                : 'bg-white hover:bg-slate-100 text-slate-700'
            }`}
          >
            Áreas Verdes & Parques
          </button>
          <button
            onClick={() => setFilter('vistas')}
            className={`px-4 py-1.5 rounded-full transition cursor-pointer ${
              filter === 'vistas'
                ? 'bg-brand-forest text-white shadow-xs'
                : 'bg-white hover:bg-slate-100 text-slate-700'
            }`}
          >
            Vistas Panorámicas
          </button>
        </div>

        {/* Mosaic Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredItems.map((photo, idx) => {
            const originalIndex = GALLERY_ITEMS.findIndex((item) => item.id === photo.id);
            return (
              <div
                key={photo.id}
                onClick={() => onOpenLightbox(originalIndex)}
                className="group relative rounded-3xl overflow-hidden shadow-md h-72 border-2 border-white cursor-pointer active:scale-98 transition-all"
              >
                <img
                  src={photo.imageUrl}
                  alt={photo.title}
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                />
                <div className="absolute inset-0 bg-linear-to-t from-slate-900/85 via-slate-900/20 to-transparent opacity-80 group-hover:opacity-90 transition" />

                {/* Floating zoom icon */}
                <div className="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/20 backdrop-blur-md text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                  <Maximize2 className="w-4 h-4" />
                </div>

                <div className="absolute bottom-4 left-4 right-4 text-white">
                  <span
                    className={`text-[10px] font-bold uppercase tracking-wider backdrop-blur-md px-2.5 py-1 rounded-full text-white ${
                      photo.category === 'naturaleza' ? 'bg-emerald-600/85' : 'bg-white/20'
                    }`}
                  >
                    {photo.tag}
                  </span>
                  <h4 className="font-extrabold text-base mt-2">{photo.title}</h4>
                  <p className="text-xs text-slate-200 line-clamp-1">{photo.description}</p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
