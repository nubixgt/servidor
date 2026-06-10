import React from 'react';
import { X, ShieldAlert, CheckCircle2 } from 'lucide-react';

interface CivicModalProps {
  isOpen: boolean;
  title: string;
  children: React.ReactNode;
  onClose: () => void;
}

export default function CivicModal({ isOpen, title, children, onClose }: CivicModalProps) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-[#091426]/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div 
        className="bg-white rounded-xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 flex flex-col max-h-[90vh] animate-fade-in"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
          <div className="flex items-center gap-2">
            <ShieldAlert className="w-5 h-5 text-secondary-base" />
            <h3 className="text-base font-bold text-primary-base">{title}</h3>
          </div>
          <button
            onClick={onClose}
            className="p-1.5 hover:bg-slate-200 text-slate-400 hover:text-slate-600 rounded transition-colors cursor-pointer"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Content Box */}
        <div className="p-6 overflow-y-auto text-sm text-slate-600 leading-relaxed space-y-4">
          {children}
        </div>

        {/* Action button */}
        <div className="p-4 bg-slate-50 border-t border-slate-100 flex justify-end">
          <button
            onClick={onClose}
            className="px-5 py-2.5 bg-primary-base text-white text-xs font-bold rounded-lg hover:bg-slate-800 transition-colors uppercase cursor-pointer"
          >
            Entendido
          </button>
        </div>
      </div>
    </div>
  );
}
