import { Clock, CheckCircle2, Droplets, TrendingUp } from 'lucide-react';

export function BenefitsRibbon() {
  const benefits = [
    {
      icon: Clock,
      title: 'Escrituración Rápida',
      description: 'Trámite ágil y certeza jurídica 100% comprobable.',
    },
    {
      icon: CheckCircle2,
      title: 'Financiamiento Propio',
      description: 'Sin complicaciones bancarias ni trámites excesivos.',
    },
    {
      icon: Droplets,
      title: 'Agua Propia Abundante',
      description: 'Pozo propio de gran capacidad con tanque elevado.',
    },
    {
      icon: TrendingUp,
      title: 'Plusvalía Acelerada',
      description: 'Crecimiento del valor de la tierra garantizado por contrato.',
    },
  ];

  return (
    <section className="py-12 bg-white border-y border-slate-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
          {benefits.map((b) => {
            const Icon = b.icon;
            return (
              <div
                key={b.title}
                className="flex flex-col items-center p-4 rounded-2xl hover:bg-slate-50/80 transition"
              >
                <div className="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-brand-forest mb-3">
                  <Icon className="w-6 h-6 text-brand-forest" />
                </div>
                <h4 className="font-bold text-slate-800 text-sm">{b.title}</h4>
                <p className="text-xs text-slate-500 mt-1 max-w-xs">{b.description}</p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
