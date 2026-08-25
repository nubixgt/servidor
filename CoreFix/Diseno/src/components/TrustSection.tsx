import React from 'react';
import { Star, ShieldCheck, Cpu, Wrench, Clock, Users } from 'lucide-react';
import { REVIEWS } from '../data/mockData';

export const TrustSection: React.FC = () => {
  return (
    <section className="py-16 bg-white border-b border-slate-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-14">
        
        {/* Why choose us counters */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
          <div className="p-6 rounded-2xl bg-slate-50 border border-slate-200/80">
            <span className="text-3xl sm:text-4xl font-extrabold text-blue-600 font-mono">+12.500</span>
            <p className="text-xs sm:text-sm font-semibold text-slate-900 mt-1">Equipos Reparados</p>
            <p className="text-[11px] text-slate-500 mt-0.5">En nuestros 8 años de trayectoria</p>
          </div>

          <div className="p-6 rounded-2xl bg-slate-50 border border-slate-200/80">
            <span className="text-3xl sm:text-4xl font-extrabold text-blue-600 font-mono">180 Días</span>
            <p className="text-xs sm:text-sm font-semibold text-slate-900 mt-1">Garantía Escrita</p>
            <p className="text-[11px] text-slate-500 mt-0.5">Certificado físico y digital</p>
          </div>

          <div className="p-6 rounded-2xl bg-slate-50 border border-slate-200/80">
            <span className="text-3xl sm:text-4xl font-extrabold text-blue-600 font-mono">45 Min</span>
            <p className="text-xs sm:text-sm font-semibold text-slate-900 mt-1">Reparación Express</p>
            <p className="text-[11px] text-slate-500 mt-0.5">Pantallas y baterías en el acto</p>
          </div>

          <div className="p-6 rounded-2xl bg-slate-50 border border-slate-200/80">
            <span className="text-3xl sm:text-4xl font-extrabold text-blue-600 font-mono">4.9 / 5</span>
            <p className="text-xs sm:text-sm font-semibold text-slate-900 mt-1">Calificación Google</p>
            <p className="text-[11px] text-slate-500 mt-0.5">+1.800 reseñas verificadas</p>
          </div>
        </div>

        {/* Customer Testimonials */}
        <div className="space-y-6">
          <div className="text-center space-y-1.5">
            <h3 className="text-2xl sm:text-3xl font-bold text-slate-900">Opiniones de Nuestros Clientes</h3>
            <p className="text-xs sm:text-sm text-slate-500">Transparencia, diagnóstico honesto y resultados garantizados</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {REVIEWS.map((rev) => (
              <div
                key={rev.id}
                className="bg-slate-50 rounded-2xl p-6 border border-slate-200/80 flex flex-col justify-between space-y-4"
              >
                <div className="space-y-3">
                  <div className="flex items-center gap-1 text-amber-400">
                    {[...Array(rev.rating)].map((_, i) => (
                      <Star key={i} className="w-4 h-4 fill-amber-400" />
                    ))}
                  </div>
                  <p className="text-xs sm:text-sm text-slate-700 leading-relaxed italic">
                    "{rev.text}"
                  </p>
                </div>

                <div className="pt-3 border-t border-slate-200 flex items-center justify-between text-xs">
                  <div>
                    <h5 className="font-bold text-slate-900">{rev.name}</h5>
                    <span className="text-blue-600 text-[11px] font-medium">{rev.device}</span>
                  </div>
                  <span className="text-slate-400 text-[10px]">{rev.date}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

      </div>
    </section>
  );
};
