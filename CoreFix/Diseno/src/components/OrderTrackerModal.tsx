import React, { useState } from 'react';
import { 
  Search, 
  X, 
  CheckCircle2, 
  Clock, 
  AlertCircle, 
  Wrench, 
  UserCheck, 
  Calendar, 
  DollarSign, 
  FileText,
  Truck,
  Sparkles
} from 'lucide-react';
import { MOCK_ORDERS } from '../data/mockData';
import { RepairOrder, OrderStatusStage } from '../types';

interface OrderTrackerModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialCode?: string;
}

export const OrderTrackerModal: React.FC<OrderTrackerModalProps> = ({
  isOpen,
  onClose,
  initialCode = '',
}) => {
  const [ticketQuery, setTicketQuery] = useState(initialCode || 'TF-8841');
  const [currentOrder, setCurrentOrder] = useState<RepairOrder | null>(
    MOCK_ORDERS[initialCode] || MOCK_ORDERS['TF-8841']
  );
  const [notFound, setNotFound] = useState(false);

  if (!isOpen) return null;

  const handleSearch = (codeToSearch?: string) => {
    const code = (codeToSearch || ticketQuery).trim().toUpperCase();
    if (!code) return;

    if (MOCK_ORDERS[code]) {
      setCurrentOrder(MOCK_ORDERS[code]);
      setNotFound(false);
      setTicketQuery(code);
    } else {
      setCurrentOrder(null);
      setNotFound(true);
    }
  };

  const getStatusBadge = (status: OrderStatusStage) => {
    switch (status) {
      case 'received':
        return <span className="px-3 py-1 bg-slate-100 text-slate-800 rounded-full text-xs font-semibold">Recepcionado</span>;
      case 'diagnosing':
        return <span className="px-3 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">En Diagnóstico</span>;
      case 'waiting_parts':
        return <span className="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-xs font-semibold">Esperando Repuestos</span>;
      case 'repairing':
        return <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold animate-pulse">En Reparación</span>;
      case 'qa_testing':
        return <span className="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-xs font-semibold">Control de Calidad</span>;
      case 'ready':
        return <span className="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">Listo para Retiro</span>;
      case 'delivered':
        return <span className="px-3 py-1 bg-emerald-700 text-white rounded-full text-xs font-semibold">Entregado</span>;
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div className="bg-white rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-slate-200 flex flex-col">
        
        {/* Modal Header */}
        <div className="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-xs">
              <Search className="w-5 h-5" />
            </div>
            <div>
              <h3 className="text-lg font-bold text-slate-900">Rastreador de Estado de Reparación</h3>
              <p className="text-xs text-slate-500">Consulta el avance técnico de tu equipo en tiempo real</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-200/60 transition-colors"
          >
            <X className="w-5 h-5" />
          </button>
        </div>

        {/* Search Bar & Quick Test Tickets */}
        <div className="p-5 sm:p-6 space-y-4 border-b border-slate-100">
          <form
            onSubmit={(e) => {
              e.preventDefault();
              handleSearch();
            }}
            className="flex gap-2"
          >
            <div className="relative flex-1">
              <input
                type="text"
                value={ticketQuery}
                onChange={(e) => setTicketQuery(e.target.value)}
                placeholder="Ejemplo: TF-8841"
                className="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold tracking-wider text-slate-900 placeholder:font-normal placeholder:tracking-normal focus:outline-none focus:ring-2 focus:ring-blue-600 focus:bg-white"
              />
              <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
            </div>
            <button
              type="submit"
              className="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-xl text-sm transition-colors cursor-pointer shadow-xs"
            >
              Consultar
            </button>
          </form>

          {/* Quick preset tickets */}
          <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span>Tickets de prueba:</span>
            {Object.keys(MOCK_ORDERS).map((code) => (
              <button
                key={code}
                type="button"
                onClick={() => handleSearch(code)}
                className={`px-2.5 py-1 rounded-md border font-mono transition-colors cursor-pointer ${
                  currentOrder?.ticketCode === code
                    ? 'bg-blue-50 border-blue-300 text-blue-700 font-bold'
                    : 'bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100'
                }`}
              >
                {code}
              </button>
            ))}
          </div>
        </div>

        {/* Modal Content: Order Details */}
        <div className="p-5 sm:p-6 space-y-6">
          {notFound && (
            <div className="text-center py-10 space-y-3">
              <div className="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto">
                <AlertCircle className="w-6 h-6" />
              </div>
              <h4 className="text-base font-bold text-slate-800">No encontramos el ticket "{ticketQuery}"</h4>
              <p className="text-xs text-slate-500 max-w-sm mx-auto">
                Verifica que el código coincida con el comprobante que te enviamos por correo o WhatsApp.
              </p>
            </div>
          )}

          {currentOrder && (
            <div className="space-y-6">
              {/* Top Overview Card */}
              <div className="bg-slate-50 rounded-xl p-4 sm:p-5 border border-slate-200 space-y-3">
                <div className="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-mono text-sm font-bold text-blue-700 bg-blue-100/70 px-2 py-0.5 rounded">
                        {currentOrder.ticketCode}
                      </span>
                      {getStatusBadge(currentOrder.currentStatus)}
                    </div>
                    <h4 className="text-base font-bold text-slate-900 mt-1.5">
                      {currentOrder.device}
                    </h4>
                    <p className="text-xs text-slate-500">{currentOrder.serialOrModel}</p>
                  </div>

                  <div className="text-right">
                    <span className="text-xs text-slate-500 block">Cliente</span>
                    <span className="text-sm font-semibold text-slate-800">{currentOrder.clientName}</span>
                  </div>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-3 border-t border-slate-200/80 text-xs">
                  <div>
                    <span className="text-slate-500 block">Técnico Asignado</span>
                    <span className="font-medium text-slate-800 flex items-center gap-1 mt-0.5">
                      <UserCheck className="w-3.5 h-3.5 text-blue-600" />
                      {currentOrder.technician}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block">Fecha Ingreso</span>
                    <span className="font-medium text-slate-800 flex items-center gap-1 mt-0.5">
                      <Calendar className="w-3.5 h-3.5 text-slate-500" />
                      {currentOrder.intakeDate}
                    </span>
                  </div>
                  <div>
                    <span className="text-slate-500 block">Presupuesto / Saldo</span>
                    <span className="font-semibold text-emerald-700 flex items-center gap-1 mt-0.5">
                      <DollarSign className="w-3.5 h-3.5 text-emerald-600" />
                      {currentOrder.costEstimate} (Resta: {currentOrder.remainingBalance})
                    </span>
                  </div>
                </div>
              </div>

              {/* Technical Diagnosis Note */}
              <div className="space-y-2">
                <h5 className="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                  <FileText className="w-4 h-4 text-blue-600" />
                  Informe de Diagnóstico Técnico
                </h5>
                <div className="bg-blue-50/50 rounded-xl p-3.5 border border-blue-100 text-xs text-slate-700 leading-relaxed">
                  <p><strong>Falla reportada:</strong> {currentOrder.reportedIssue}</p>
                  <p className="mt-1.5"><strong>Hallazgo de laboratorio:</strong> {currentOrder.diagnosticReport}</p>
                  {currentOrder.partsUsed && currentOrder.partsUsed.length > 0 && (
                    <div className="mt-2 pt-2 border-t border-blue-200/60">
                      <span className="font-semibold text-slate-800">Repuestos aplicados: </span>
                      <span>{currentOrder.partsUsed.join(', ')}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Step-by-Step Progress Timeline */}
              <div className="space-y-3">
                <h5 className="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                  <Clock className="w-4 h-4 text-blue-600" />
                  Línea de Tiempo del Proceso
                </h5>

                <div className="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                  {currentOrder.timeline.map((step, idx) => (
                    <div key={idx} className="relative">
                      {/* Step marker */}
                      <div 
                        className={`absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center border-2 bg-white ${
                          step.completed 
                            ? 'border-blue-600 text-blue-600' 
                            : step.current 
                              ? 'border-blue-600 bg-blue-600 text-white animate-pulse'
                              : 'border-slate-300 text-slate-300'
                        }`}
                      >
                        {step.completed ? (
                          <CheckCircle2 className="w-3.5 h-3.5 fill-blue-600 text-white" />
                        ) : (
                          <span className="w-1.5 h-1.5 rounded-full bg-current" />
                        )}
                      </div>

                      <div className="space-y-0.5">
                        <div className="flex items-center justify-between">
                          <h6 className={`text-sm font-bold ${step.completed || step.current ? 'text-slate-900' : 'text-slate-400'}`}>
                            {step.label}
                          </h6>
                          <span className="text-[11px] text-slate-500 font-mono">{step.date}</span>
                        </div>
                        {step.notes && (
                          <p className="text-xs text-slate-600 pt-0.5">{step.notes}</p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Ready for pickup notification banner */}
              {currentOrder.currentStatus === 'ready' && (
                <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                  <div className="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shrink-0">
                    <CheckCircle2 className="w-5 h-5" />
                  </div>
                  <div>
                    <h5 className="text-sm font-bold text-emerald-900">¡Tu equipo está listo para ser retirado!</h5>
                    <p className="text-xs text-emerald-700 mt-0.5">
                      Pasa por nuestra sucursal con este número de ticket o solicita envío a domicilio.
                    </p>
                  </div>
                </div>
              )}

            </div>
          )}
        </div>

        {/* Footer Actions */}
        <div className="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex justify-between items-center text-xs">
          <span className="text-slate-500">¿Dudas sobre tu orden? Llámanos al +1 234 567 8900</span>
          <button
            onClick={onClose}
            className="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 font-medium rounded-lg transition-colors cursor-pointer"
          >
            Cerrar
          </button>
        </div>

      </div>
    </div>
  );
};
