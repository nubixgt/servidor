import React, { useState } from 'react';
import { CoachNote, NoteType, NoteCategory } from '../types';

interface CuadernoViewProps {
  notes: CoachNote[];
  onAddNote: (newNote: Omit<CoachNote, 'id' | 'dateStr'>) => void;
  onDeleteNote: (id: string) => void;
  onConfirmNote: (id: string) => void;
  onRetireNote: (id: string) => void;
  onApplyProposalToPlan: (id: string) => void;
}

export const CuadernoView: React.FC<CuadernoViewProps> = ({
  notes,
  onAddNote,
  onDeleteNote,
  onConfirmNote,
  onRetireNote,
  onApplyProposalToPlan,
}) => {
  const [filter, setFilter] = useState<'todas' | 'vigentes' | 'propuestas'>('todas');
  const [newText, setNewText] = useState('');
  const [newCategory, setNewCategory] = useState<NoteCategory>('fisiologia');
  const [newType, setNewType] = useState<NoteType>('vigente');

  const handleAddNote = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newText.trim()) return;

    onAddNote({
      text: newText.trim(),
      category: newCategory,
      type: newType,
      sport: 'run',
      isConfirmed: newType === 'vigente',
    });

    setNewText('');
  };

  const filteredNotes = notes.filter((note) => {
    if (filter === 'vigentes') return note.type === 'vigente';
    if (filter === 'propuestas') return note.type === 'propuesta';
    return true;
  });

  return (
    <div id="cuaderno-view-container" className="space-y-7 pb-20 select-none">
      {/* Breadcrumb & Main Header */}
      <div className="border-b border-white/10 pb-5">
        <nav className="text-xs font-mono-data text-[#8f9378] tracking-widest uppercase mb-1">
          LABORATORIO DE RENDIMIENTO / ATLETA
        </nav>
        <h2 className="text-3xl sm:text-4xl font-black text-white tracking-tight">
          Cuaderno de tu entrenador
        </h2>
        <p className="text-sm text-[#c5c9ac] mt-1 font-medium">
          Bitácora táctica con prescripciones fisiológicas y propuestas inteligentes generadas por el Agente IA.
        </p>
      </div>

      {/* Two Column Layout: Left Form & Right Feed */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left Column: Form "Añadir nota" (5 cols) */}
        <section className="lg:col-span-5 glass-card rounded-3xl p-6 sm:p-7 space-y-5 sticky top-24 border border-white/10 shadow-2xl">
          <h3 className="text-lg font-black text-white tracking-tight flex items-center gap-2">
            <span className="material-symbols-outlined text-[#caf300]">edit_note</span>
            <span>Añadir nota</span>
          </h3>

          <form onSubmit={handleAddNote} className="space-y-4">
            {/* Note Textarea */}
            <div>
              <label className="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">
                Observación o Ajuste Táctico
              </label>
              <textarea
                id="input-note-text"
                rows={4}
                value={newText}
                onChange={(e) => setNewText(e.target.value)}
                className="w-full glass-input rounded-2xl p-4 text-white text-sm focus:outline-none transition-all resize-none placeholder-[#8f9378]"
                placeholder="Escribe una observación fisiológica, dolor articular, ajuste en ritmos o pautas nutricionales..."
                required
              />
            </div>

            {/* Note Category */}
            <div>
              <label className="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">
                Categoría Clínica
              </label>
              <select
                id="select-note-category"
                value={newCategory}
                onChange={(e) => setNewCategory(e.target.value as NoteCategory)}
                className="w-full glass-input rounded-2xl px-4 py-3 text-white text-sm focus:outline-none transition-all"
              >
                <option value="fisiologia" className="bg-[#121700] text-white">Fisiología & Carga</option>
                <option value="biomecanica" className="bg-[#121700] text-white">Biomecánica & Técnica</option>
                <option value="nutricion" className="bg-[#121700] text-white">Nutrición & Hidratación</option>
                <option value="recuperacion" className="bg-[#121700] text-white">Recuperación & VFC</option>
                <option value="logistica" className="bg-[#121700] text-white">Logística & Material</option>
              </select>
            </div>

            {/* Note Type */}
            <div>
              <label className="block text-xs font-black text-[#8f9378] uppercase tracking-wider mb-2">
                Tipo de Nota
              </label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  onClick={() => setNewType('vigente')}
                  className={`py-2.5 px-3 rounded-xl text-xs font-black uppercase transition-all ${
                    newType === 'vigente'
                      ? 'bg-[#a5eeff] text-[#00363f] shadow-[0_0_15px_rgba(165,238,255,0.3)]'
                      : 'glass-pill text-[#c5c9ac] hover:text-white'
                  }`}
                >
                  Directiva Vigente
                </button>
                <button
                  type="button"
                  onClick={() => setNewType('propuesta')}
                  className={`py-2.5 px-3 rounded-xl text-xs font-black uppercase transition-all ${
                    newType === 'propuesta'
                      ? 'bg-[#caf300] text-[#121700] shadow-[0_0_15px_rgba(202,243,0,0.3)]'
                      : 'glass-pill text-[#c5c9ac] hover:text-white'
                  }`}
                >
                  Propuesta IA
                </button>
              </div>
            </div>

            {/* Submit Button */}
            <button
              id="btn-submit-note"
              type="submit"
              className="w-full py-3.5 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl font-black text-xs uppercase tracking-wider shadow-[0_0_20px_rgba(202,243,0,0.35)] transition-all active:scale-95 hover:scale-[1.02] flex items-center justify-center gap-2"
            >
              <span className="material-symbols-outlined text-[20px]">add_circle</span>
              <span>Anotar</span>
            </button>
          </form>
        </section>

        {/* Right Column: Filter Bar & Notes Feed (7 cols) */}
        <section className="lg:col-span-7 space-y-5">
          {/* Filters Bar */}
          <div className="flex items-center justify-between gap-3 glass-pill p-1.5 rounded-2xl border border-white/10 shadow-lg">
            <div className="flex items-center gap-1.5">
              <button
                id="filter-todas"
                onClick={() => setFilter('todas')}
                className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all ${
                  filter === 'todas'
                    ? 'bg-[#caf300] text-[#121700] shadow-[0_0_15px_rgba(202,243,0,0.3)]'
                    : 'text-[#c5c9ac] hover:text-white'
                }`}
              >
                TODAS
              </button>
              <button
                id="filter-vigentes"
                onClick={() => setFilter('vigentes')}
                className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all ${
                  filter === 'vigentes'
                    ? 'bg-[#a5eeff] text-[#00363f] shadow-[0_0_15px_rgba(165,238,255,0.3)]'
                    : 'text-[#c5c9ac] hover:text-white'
                }`}
              >
                VIGENTES
              </button>
              <button
                id="filter-propuestas"
                onClick={() => setFilter('propuestas')}
                className={`px-4 py-2 rounded-xl text-xs font-black uppercase tracking-wider transition-all ${
                  filter === 'propuestas'
                    ? 'bg-[#caf300] text-[#121700] shadow-[0_0_15px_rgba(202,243,0,0.3)]'
                    : 'text-[#c5c9ac] hover:text-white'
                }`}
              >
                PROPUESTAS
              </button>
            </div>

            <span className="material-symbols-outlined text-[#8f9378] text-xl mr-2">
              tune
            </span>
          </div>

          {/* Notes List */}
          <div className="space-y-4">
            {filteredNotes.map((note) => {
              const isVigente = note.type === 'vigente';
              const isPropuesta = note.type === 'propuesta';

              return (
                <div
                  key={note.id}
                  id={`note-card-${note.id}`}
                  className={`glass-card rounded-3xl p-6 sm:p-7 transition-all border border-white/10 shadow-2xl ${
                    isVigente
                      ? 'border-l-4 border-l-[#a5eeff]'
                      : isPropuesta
                      ? 'border-l-4 border-l-[#caf300]'
                      : ''
                  }`}
                >
                  {/* Card Header: Tag & Date */}
                  <div className="flex justify-between items-center mb-3">
                    <div className="flex items-center gap-2">
                      <span
                        className={`px-3 py-0.5 rounded-lg font-mono-data text-[10px] font-black uppercase tracking-wider ${
                          isVigente
                            ? 'text-[#a5eeff] bg-[#a5eeff]/15 border border-[#a5eeff]/40'
                            : isPropuesta
                            ? 'text-[#caf300] bg-[#caf300]/15 border border-[#caf300]/40'
                            : 'text-[#c5c9ac] bg-white/10 border border-white/15'
                        }`}
                      >
                        {note.type.toUpperCase()}
                      </span>
                      <span className="text-[11px] font-mono-data text-[#8f9378]">
                        {note.dateStr}
                      </span>
                    </div>

                    <span className="text-[10px] font-black uppercase tracking-wider text-[#8f9378] font-mono-data">
                      {note.category}
                    </span>
                  </div>

                  {/* Note Body */}
                  <p className="text-sm text-[#e5e2e1] leading-relaxed mb-5 font-medium">
                    {note.text}
                  </p>

                  {/* Actions according to note type */}
                  <div className="flex flex-wrap items-center justify-end gap-3 pt-3 border-t border-white/10 text-xs font-black uppercase font-mono-data">
                    {isVigente && (
                      <>
                        <button
                          onClick={() => onConfirmNote(note.id)}
                          className="text-[#caf300] hover:underline"
                        >
                          Confirmar
                        </button>
                        <button
                          onClick={() => onRetireNote(note.id)}
                          className="text-[#e5e2e1] hover:underline"
                        >
                          Retirar
                        </button>
                        <button
                          onClick={() => onDeleteNote(note.id)}
                          className="text-[#ffb4ab] hover:underline"
                        >
                          Borrar
                        </button>
                      </>
                    )}

                    {isPropuesta && (
                      <>
                        <button
                          onClick={() => onApplyProposalToPlan(note.id)}
                          className="px-5 py-2 bg-[#caf300] hover:bg-[#bbf400] text-[#121700] rounded-xl shadow-[0_0_15px_rgba(202,243,0,0.3)] transition-all hover:scale-105"
                        >
                          Añadir a Plan
                        </button>
                        <button
                          onClick={() => onDeleteNote(note.id)}
                          className="text-[#c5c9ac] hover:text-[#ffb4ab]"
                        >
                          Descartar
                        </button>
                      </>
                    )}

                    {!isVigente && !isPropuesta && (
                      <button
                        onClick={() => onDeleteNote(note.id)}
                        className="text-[#8f9378] hover:text-[#ffb4ab]"
                      >
                        Archivar
                      </button>
                    )}
                  </div>
                </div>
              );
            })}

            {filteredNotes.length === 0 && (
              <div className="p-8 text-center glass-card rounded-3xl text-[#c5c9ac] text-sm border border-white/10">
                No hay notas en esta categoría. Usa el formulario para registrar observaciones.
              </div>
            )}
          </div>
        </section>
      </div>
    </div>
  );
};
