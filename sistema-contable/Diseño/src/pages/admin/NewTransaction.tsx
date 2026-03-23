import React, { useState } from 'react';
import { 
  ArrowDownToLine, 
  ArrowUpFromLine, 
  Upload, 
  FileText,
  Calendar,
  MapPin,
  Tag,
  DollarSign,
  AlignLeft,
  User
} from 'lucide-react';
import { cn } from '../../lib/utils';

export default function AdminNewTransaction() {
  const [type, setType] = useState<'ingreso' | 'egreso'>('ingreso');

  return (
    <div className="space-y-6 max-w-4xl mx-auto">
      <div className="text-center sm:text-left">
        <h1 className="text-2xl font-sans font-bold text-on-surface tracking-tight">Nueva Transacción</h1>
        <p className="text-sm text-on-surface-variant mt-1">Registra un nuevo ingreso o egreso en el sistema.</p>
      </div>

      <div className="glass-card overflow-hidden">
        {/* Type Selector */}
        <div className="flex border-b border-outline-variant/20">
          <button
            onClick={() => setType('ingreso')}
            className={cn(
              "flex-1 py-4 flex items-center justify-center gap-2 font-semibold transition-colors",
              type === 'ingreso' 
                ? "bg-secondary-container text-on-secondary-container border-b-2 border-secondary" 
                : "bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low"
            )}
          >
            <ArrowDownToLine className="w-5 h-5" />
            Ingreso
          </button>
          <button
            onClick={() => setType('egreso')}
            className={cn(
              "flex-1 py-4 flex items-center justify-center gap-2 font-semibold transition-colors",
              type === 'egreso' 
                ? "bg-error-container text-on-error-container border-b-2 border-error" 
                : "bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low"
            )}
          >
            <ArrowUpFromLine className="w-5 h-5" />
            Egreso
          </button>
        </div>

        <form className="p-6 sm:p-8 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Amount */}
            <div className="space-y-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <DollarSign className="w-4 h-4 text-outline" /> Monto (GTQ)
              </label>
              <input 
                type="number" 
                placeholder="0.00" 
                className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all font-mono text-lg"
                required
              />
            </div>

            {/* Date */}
            <div className="space-y-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <Calendar className="w-4 h-4 text-outline" /> Fecha
              </label>
              <input 
                type="date" 
                className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
                required
              />
            </div>

            {/* Location */}
            <div className="space-y-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <MapPin className="w-4 h-4 text-outline" /> Locación
              </label>
              <select className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none" required>
                <option value="">Selecciona una locación...</option>
                <optgroup label="Heladerías">
                  <option value="H-01">Heladería CC Pradera</option>
                  <option value="H-02">Heladería Gasolinera Texaco</option>
                  <option value="H-03">Heladería Tecpán</option>
                </optgroup>
                <optgroup label="Casas en Arrendamiento">
                  <option value="A-01">Casa en Arrendamiento 1</option>
                  <option value="A-02">Casa en Arrendamiento 2</option>
                  <option value="A-03">Casa en Arrendamiento 3</option>
                </optgroup>
                <optgroup label="Propiedad Comercial">
                  <option value="L-01">Local L-01</option>
                  <option value="B-01">Bodega B-01</option>
                </optgroup>
              </select>
            </div>

            {/* Category / Type */}
            <div className="space-y-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <Tag className="w-4 h-4 text-outline" /> {type === 'ingreso' ? 'Categoría' : 'Tipo de Gasto'}
              </label>
              <select className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all appearance-none" required>
                <option value="">Selecciona...</option>
                {type === 'ingreso' ? (
                  <>
                    <option value="venta">Venta Diaria</option>
                    <option value="renta">Pago de Renta</option>
                    <option value="otro">Otro Ingreso</option>
                  </>
                ) : (
                  <>
                    <option value="servicios">Servicios Básicos (Agua, Luz)</option>
                    <option value="mantenimiento">Mantenimiento / Reparaciones</option>
                    <option value="planilla">Planilla / Honorarios</option>
                    <option value="insumos">Compra de Insumos</option>
                    <option value="otro">Otro Gasto</option>
                  </>
                )}
              </select>
            </div>

            {/* Provider (Only for Egreso) */}
            {type === 'egreso' && (
              <div className="space-y-2 md:col-span-2">
                <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                  <User className="w-4 h-4 text-outline" /> Proveedor
                </label>
                <input 
                  type="text" 
                  placeholder="Nombre del proveedor o empresa" 
                  className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
                  required
                />
              </div>
            )}

            {/* Description */}
            <div className="space-y-2 md:col-span-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <AlignLeft className="w-4 h-4 text-outline" /> Descripción
              </label>
              <textarea 
                rows={3}
                placeholder="Detalles adicionales de la transacción..." 
                className="w-full px-4 py-3 bg-surface-container-low border border-outline-variant/30 rounded-xl text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all resize-none"
                required
              />
            </div>

            {/* File Upload */}
            <div className="space-y-2 md:col-span-2">
              <label className="text-sm font-medium text-on-surface-variant flex items-center gap-2">
                <FileText className="w-4 h-4 text-outline" /> Comprobante (Imagen o PDF)
              </label>
              <div className="border-2 border-dashed border-outline-variant/50 rounded-xl p-8 text-center hover:bg-surface-container-low transition-colors cursor-pointer group">
                <div className="w-12 h-12 bg-surface-container rounded-full flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-fixed group-hover:text-on-primary-fixed transition-colors">
                  <Upload className="w-6 h-6 text-outline group-hover:text-on-primary-fixed" />
                </div>
                <p className="text-sm font-medium text-on-surface">Haz clic para subir o arrastra el archivo aquí</p>
                <p className="text-xs text-outline mt-1">PNG, JPG o PDF (Máx. 5MB)</p>
              </div>
            </div>
          </div>

          <div className="pt-6 border-t border-outline-variant/20 flex justify-end gap-3">
            <button type="button" className="px-6 py-3 bg-surface-container-low text-on-surface-variant rounded-xl font-medium hover:bg-surface-container transition-colors">
              Cancelar
            </button>
            <button type="submit" className="px-6 py-3 bg-primary text-white rounded-xl font-medium hover:bg-primary-container transition-colors shadow-sm">
              Guardar Registro
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
