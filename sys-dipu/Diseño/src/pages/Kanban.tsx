import React from 'react';

export default function Kanban() {
  return (
    <div className="space-y-8 animate-in fade-in duration-500 h-full flex flex-col">
      {/* Contextual Dashboard Header */}
      <div className="flex justify-between items-start mb-12">
        <div>
          <span className="text-[10px] font-bold text-primary uppercase tracking-[0.25em] mb-2 block">Workspace Control</span>
          <h2 className="text-4xl font-extrabold text-on-surface tracking-tighter font-headline">Gestión de Tareas</h2>
        </div>
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest">
            <span className="material-symbols-outlined text-[16px]">picture_as_pdf</span>
            PDF
          </button>
          <button className="flex items-center gap-2 px-5 py-2.5 bg-surface-container text-on-surface-variant text-xs font-bold rounded-lg hover:bg-surface-container-high transition-all border border-outline-variant/20 uppercase tracking-widest">
            <span className="material-symbols-outlined text-[16px]">description</span>
            Excel
          </button>
          <button className="flex items-center gap-2 px-6 py-2.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/10 uppercase tracking-widest ml-2">
            <span className="material-symbols-outlined text-[16px]">add</span>
            Nueva Tarea
          </button>
        </div>
      </div>

      {/* Filters Bar */}
      <div className="bg-surface-container-lowest p-6 rounded-2xl mb-12 border border-outline-variant/30 flex flex-wrap gap-8 items-end shadow-sm">
        <div className="flex-1 min-w-[200px]">
          <p className="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Filtrar por Módulo</p>
          <select className="w-full bg-surface-container-low border-none text-sm rounded-lg py-2.5 px-4 focus:ring-1 focus:ring-primary/30 appearance-none font-medium text-on-surface-variant outline-none">
            <option>Todos los Módulos</option>
            <option>Legislación</option>
            <option>Constituyentes</option>
          </select>
        </div>
        <div className="flex-1 min-w-[200px]">
          <p className="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Responsable</p>
          <select className="w-full bg-surface-container-low border-none text-sm rounded-lg py-2.5 px-4 focus:ring-1 focus:ring-primary/30 appearance-none font-medium text-on-surface-variant outline-none">
            <option>Cualquier Responsable</option>
            <option>Senior Advisor</option>
            <option>Staff Lead</option>
          </select>
        </div>
        <div className="flex-[1.5] min-w-[200px]">
          <p className="text-[9px] font-bold text-primary uppercase tracking-widest mb-2 opacity-70">Prioridad</p>
          <div className="flex gap-2">
            <button className="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Alta</button>
            <button className="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Media</button>
            <button className="flex-1 py-2 px-4 bg-surface-container-low text-[11px] font-bold rounded-lg border border-transparent hover:border-outline-variant transition-all uppercase tracking-wider">Baja</button>
          </div>
        </div>
        <button className="p-2.5 text-on-surface-variant hover:bg-surface-container rounded-lg transition-all border border-outline-variant/30">
          <span className="material-symbols-outlined">filter_list</span>
        </button>
      </div>

      {/* Kanban Grid */}
      <div className="flex gap-8 overflow-x-auto pb-8 -mx-2 px-2 scroll-smooth flex-1">
        
        {/* Column: Pendiente */}
        <div className="flex flex-col gap-6 flex-shrink-0 w-[300px]">
          <div className="flex justify-between items-center px-1">
            <h3 className="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
              Pendiente
              <span className="text-[10px] bg-outline-variant/30 px-2 py-0.5 rounded-full text-on-surface-variant">3</span>
            </h3>
            <span className="material-symbols-outlined text-outline/50 cursor-pointer hover:text-outline">more_horiz</span>
          </div>
          <div className="flex flex-col gap-4 min-h-[500px]">
            {/* Card 1 */}
            <div className="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-tertiary-container text-on-tertiary-container rounded uppercase tracking-tighter">Alta</span>
                <span className="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
              </div>
              <h4 className="text-sm font-bold text-on-surface mb-6 leading-relaxed">Revisión de Enmienda Presupuestaria Q3</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale brightness-110" alt="Carlos Méndez portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDIag1zI50dOKrGpm8WyMPGkfRMDwAd5jquHQ0Xrj6UeEbpOrP3h_o7JnWinWBPbr35IWhKg2v958XfCeYwuGlwfkyO-AyGgoacieFxFEr4QrkUgq1KVyyqGFCQB8R9u6aR9_wBKbDF82HP-MP2iXdjs03Fjmkqz5GuuKBnsU0F-VY4q8W57KiqT7sYuuwZG3B3XQpGAIdpgm3pSb77B4jUOVCIitnpJ8WCunNPiJzc27ea-E0lpZ8BjJc1eEYr2rGMZ8equG_MiUK4"/>
                <span className="text-[10px] text-outline font-medium">Carlos Méndez</span>
              </div>
              <div className="flex justify-between items-center pt-4 border-t border-outline-variant/20">
                <div className="flex items-center gap-1.5 text-outline">
                  <span className="material-symbols-outlined text-[14px]">calendar_today</span>
                  <span className="text-[10px] font-bold">15 OCT</span>
                </div>
                <div className="text-[10px] font-black text-primary/40">0%</div>
              </div>
            </div>
            {/* Card 2 */}
            <div className="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-primary-container text-on-primary-container rounded uppercase tracking-tighter">Baja</span>
                <span className="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
              </div>
              <h4 className="text-sm font-bold text-on-surface mb-6 leading-relaxed">Actualización de Directorio de Comisiones</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale brightness-110" alt="Elena Rivas portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAxY-pYiC-imaR5xqKjNtB8SoprzrLZjBmGPUV5m8xGHe4ClOYOTKEwbJ1LlP6r7ZHUa0VDK4SJMR_EBrMm7_XJTDFnlAX6d0O-hS5KtjmilMqyaw4F4izuETG3saPseeNvDjZ_LMKAWmQzl0Ol5BwQr9ueMevhXcbVPqTve6MvpcUF_bsWTLIabwvQK1DZDYwCu2EJ9eL-OJUUeFglmPTdedMhkgSlrjMNTbknAI3UCPHYHlrSF3EdAhrrzNsfYVtzzj9Fet5ST2AC"/>
                <span className="text-[10px] text-outline font-medium">Elena Rivas</span>
              </div>
              <div className="flex justify-between items-center pt-4 border-t border-outline-variant/20">
                <div className="flex items-center gap-1.5 text-outline">
                  <span className="material-symbols-outlined text-[14px]">calendar_today</span>
                  <span className="text-[10px] font-bold">22 OCT</span>
                </div>
                <div className="text-[10px] font-black text-primary/40">0%</div>
              </div>
            </div>
          </div>
        </div>

        {/* Column: En Proceso */}
        <div className="flex flex-col gap-6 flex-shrink-0 w-[300px]">
          <div className="flex justify-between items-center px-1">
            <h3 className="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
              En Proceso
              <span className="text-[10px] bg-tertiary-container/30 px-2 py-0.5 rounded-full text-on-tertiary-container">2</span>
            </h3>
            <span className="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
          </div>
          <div className="flex flex-col gap-4 min-h-[500px]">
            {/* Card 3 */}
            <div className="group bg-surface-container-lowest p-5 rounded-xl border border-primary/30 shadow-sm transition-all cursor-grab active:cursor-grabbing">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-secondary-container text-on-secondary-container rounded uppercase tracking-tighter">Media</span>
                <span className="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
              </div>
              <h4 className="text-sm font-bold text-on-surface mb-6 leading-relaxed">Redacción Informe de Impacto Ambiental</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale brightness-110" alt="Senior Advisor portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCzIf91ekZhOd7FyYH9DTI6OY3LnaFUwY3CoFkBmaf9v9wCEo4Ip2jlScoACN_cwpC7nfQ_Y5gbMlQWdEsVCZ1Bm3aMe2kcHVf18Fgfibp0o_9ZpSSEtQJ8S7YAtXKHxDIs7sdCPnvqWXfCvd7RJFaH3FtGIyiajht8c6WBMRiBxlac9cZH7yLe3EyXv3c7z9PHZKSPlcDviAzm5cw6rEvSOdB2BvCrhYc8gsI5qLk2fUv0qXRyO-vrq0eclTb7INXTASg87J4ZE3lY"/>
                <span className="text-[10px] text-outline font-medium">Senior Advisor</span>
              </div>
              <div className="w-full bg-surface-container rounded-full h-1 mb-6 overflow-hidden">
                <div className="bg-primary h-full rounded-full" style={{ width: '65%' }}></div>
              </div>
              <div className="flex justify-between items-center">
                <div className="flex items-center gap-1.5 text-outline">
                  <span className="material-symbols-outlined text-[14px]">calendar_today</span>
                  <span className="text-[10px] font-bold">12 OCT</span>
                </div>
                <div className="text-[10px] font-black text-primary">65%</div>
              </div>
            </div>
          </div>
        </div>

        {/* Column: En Revisión */}
        <div className="flex flex-col gap-6 flex-shrink-0 w-[300px]">
          <div className="flex justify-between items-center px-1">
            <h3 className="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
              En Revisión
              <span className="text-[10px] bg-secondary-container/30 px-2 py-0.5 rounded-full text-on-secondary-container">1</span>
            </h3>
            <span className="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
          </div>
          <div className="flex flex-col gap-4 min-h-[500px]">
            {/* Card 4 */}
            <div className="group bg-surface-container-lowest p-5 rounded-xl border border-outline-variant/40 hover:border-primary/50 transition-all hover:shadow-md cursor-grab active:cursor-grabbing">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-tertiary-container text-on-tertiary-container rounded uppercase tracking-tighter">Alta</span>
                <span className="material-symbols-outlined text-outline/20 group-hover:text-outline/40">drag_indicator</span>
              </div>
              <h4 className="text-sm font-bold text-on-surface mb-6 leading-relaxed">Aprobación de Agenda de Sesión Plenaria</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale brightness-110" alt="Marta Soler portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDKZLXr6T8QHQCQF687pL1tS2HX8ynPqtk9khzWTEZzDov4381iYLb9rZrlVX7UJGHHTTELsmQr_L7mZWj4l0g58Bt8JBslmC5rDRiTe6tQ1FhYlXFlBJvQUf88ODTDGRzK7TRLSi6lBrdI7zWl7TD0fMriySCdGEXmNJVtBO1roz8k-RmWMsSUuQPOs26HuWltTyVFa4WT5ZYjH8Ov9MJYXSzRV_mvww_9yq0jH4oTK6iYanDmuCNg1mETAdDvKzGAiTwAkZV9wwNk"/>
                <span className="text-[10px] text-outline font-medium">Marta Soler</span>
              </div>
              <div className="w-full bg-surface-container rounded-full h-1 mb-6 overflow-hidden">
                <div className="bg-primary h-full rounded-full" style={{ width: '90%' }}></div>
              </div>
              <div className="flex justify-between items-center">
                <div className="flex items-center gap-1.5 text-outline">
                  <span className="material-symbols-outlined text-[14px]">calendar_today</span>
                  <span className="text-[10px] font-bold uppercase">Hoy</span>
                </div>
                <div className="text-[10px] font-black text-primary">90%</div>
              </div>
            </div>
          </div>
        </div>

        {/* Column: Completada */}
        <div className="flex flex-col gap-6 flex-shrink-0 w-[300px]">
          <div className="flex justify-between items-center px-1">
            <h3 className="text-xs font-bold text-on-surface uppercase tracking-widest flex items-center gap-3">
              Completada
              <span className="text-[10px] bg-primary-container px-2 py-0.5 rounded-full text-on-primary-container">12</span>
            </h3>
            <span className="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
          </div>
          <div className="flex flex-col gap-4 min-h-[500px]">
            {/* Card 5 */}
            <div className="group bg-surface-container p-5 rounded-xl border border-transparent opacity-60">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-secondary-container text-on-secondary-container rounded uppercase tracking-tighter">Media</span>
                <span className="material-symbols-outlined text-primary text-[18px]">check_circle</span>
              </div>
              <h4 className="text-sm font-bold text-on-surface mb-6 leading-relaxed line-through decoration-outline/50">Respuesta a Solicitud de Transparencia #442</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale opacity-50" alt="Carlos Méndez portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCeywlZbKgGXia2SkkEOT5WbzCpP-vklpVq6Fwq3EGNsXClQQKBPcY_s-s_QH8rhd6apbiyidcRRBzfTr7VKinDeCoI3IejHwessHqU1QHH6biz6v4tbJqzUOipiNSaKjYg2WZKA6D5SsmDE_lyK92UaMID6NEjZVUsEY0nfHfhRATHx1b-quNx4o06bFZSnkVQpV5BXFF1LD6rXvNpPxHd40s-GHMEHTyvVg7Ri_Dg_P0189exzUJFxloRu3y55IytXRUitddNjEWr"/>
                <span className="text-[10px] text-outline font-medium">Carlos Méndez</span>
              </div>
              <div className="flex justify-between items-center">
                <div className="flex items-center gap-1.5 text-primary">
                  <span className="material-symbols-outlined text-[14px]">done_all</span>
                  <span className="text-[10px] font-bold uppercase tracking-tighter">Finalizada</span>
                </div>
                <div className="text-[10px] font-black text-primary">100%</div>
              </div>
            </div>
          </div>
        </div>

        {/* Column: Vencida */}
        <div className="flex flex-col gap-6 flex-shrink-0 w-[300px]">
          <div className="flex justify-between items-center px-1">
            <h3 className="text-xs font-bold text-error uppercase tracking-widest flex items-center gap-3">
              Vencida
              <span className="text-[10px] bg-error-container px-2 py-0.5 rounded-full text-on-error-container">1</span>
            </h3>
            <span className="material-symbols-outlined text-outline/50 cursor-pointer">more_horiz</span>
          </div>
          <div className="flex flex-col gap-4 min-h-[500px]">
            {/* Card 6 */}
            <div className="group bg-error-container/5 p-5 rounded-xl border border-error/20 hover:border-error shadow-sm transition-all cursor-grab active:cursor-grabbing">
              <div className="flex justify-between items-start mb-4">
                <span className="text-[9px] font-bold px-2 py-0.5 bg-error-container text-on-error-container rounded uppercase tracking-tighter">Alta</span>
                <span className="material-symbols-outlined text-error text-[18px]">priority_high</span>
              </div>
              <h4 className="text-sm font-bold text-error mb-6 leading-relaxed">Presentación de Informe Anual 2023</h4>
              <div className="flex items-center gap-2 mb-6">
                <img className="w-6 h-6 rounded-full object-cover grayscale brightness-110" alt="Elena Rivas portrait" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAD_UnDDIehb8nvjOHl83RhCEv70S1tqcRiu0v_h04GlUQ-SzGtWpu2MV6IDvmF__qSE0hnCoAWJFs_Q7Viaoh03dkVW1RpENnXg3KtPz-6XaOziuV7Mk57408QOIsDvLfsCXHb_quh9wIqDSfPzGdlCQ988OTCiFqRp9nfL_wnr2P1fWbbklnda75QT4w4raxzY1Bmf7wHi7lzWg4F0vC4wH2PMQnBMahMYnSKCYV2tqNmB6soW7t4TlnW1-IgJa4HoF4waFrAa3xx"/>
                <span className="text-[10px] text-outline font-medium">Elena Rivas</span>
              </div>
              <div className="flex justify-between items-center pt-4 border-t border-error/10">
                <div className="flex items-center gap-1.5 text-error">
                  <span className="material-symbols-outlined text-[14px]">event_busy</span>
                  <span className="text-[10px] font-bold uppercase">Ayer</span>
                </div>
                <div className="text-[10px] font-black text-error">40%</div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  );
}
