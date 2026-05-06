/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState } from "react";
import { 
  ChevronDown, 
  Tractor, 
  Construction, 
  Wrench, 
  CircleSlash, 
  Droplets, 
  Truck, 
  Camera, 
  MapPin 
} from "lucide-react";
import { motion, AnimatePresence } from "motion/react";

type MachineType = "tractor" | "excavadora" | "retro" | "rodo" | "pipa" | "volteo";
type RegType = "incinal" | "final";

export default function App() {
  const [machineType, setMachineType] = useState<MachineType>("excavadora");
  const [regType, setRegType] = useState<RegType>("incinal");

  const machines = [
    { id: "tractor", label: "Tractor", icon: Tractor },
    { id: "excavadora", label: "Excavadora", icon: Construction },
    { id: "retro", label: "Retro Excavadora", icon: Wrench },
    { id: "rodo", label: "Rodo", icon: CircleSlash },
    { id: "pipa", label: "Pipa", icon: Droplets },
    { id: "volteo", label: "Camion Volteo", icon: Truck },
  ];

  return (
    <div className="min-h-screen flex flex-col items-center py-12 px-6 md:px-8">
      {/* Header with Cooitzá Logo */}
      <header className="mb-10 text-center flex flex-col items-center">
        <div className="mb-4 max-w-[280px]">
          <div className="flex flex-col items-center">
            <div className="relative w-32 h-32 mb-2">
              {/* Manual Logo Construction for maximum reliability */}
              <div className="absolute top-0 right-0 w-24 h-24 bg-[#FFD200] rounded-full" />
              <div className="absolute inset-0 flex items-center justify-center">
                <span className="font-display text-4xl font-black text-[#0054A3] tracking-tighter italic" style={{ fontSize: '2.5rem' }}>
                  COOITZÁ
                </span>
              </div>
            </div>
            <div className="bg-[#0054A3] px-6 py-2 w-full max-w-[320px]">
              <span className="text-white font-sans text-xs font-bold whitespace-nowrap">
                Cooperativa de Ahorro y Crédito
              </span>
            </div>
          </div>
        </div>
        <p className="font-mono-label text-sm text-on-surface-variant mt-6 font-bold uppercase tracking-widest">
          Bitácora de Operación v4.2.1
        </p>
      </header>

      {/* Main Registration Card */}
      <main className="w-full max-w-[640px] bg-surface-container-lowest border border-outline-variant p-8 shadow-sm">
        <form className="flex flex-col gap-8" onSubmit={(e) => e.preventDefault()}>
          
          {/* Pilot Selection */}
          <div className="flex flex-col gap-2">
            <label className="label-caps text-on-surface-variant">Operador Asignado</label>
            <div className="relative">
              <select className="w-full bg-surface-container-lowest border border-outline-variant py-3 px-4 font-sans text-on-surface focus:ring-1 focus:ring-primary-container focus:border-primary outline-none appearance-none transition-all">
                <option value="">Seleccionar Operador...</option>
                <option value="p1">Robert Andersson</option>
                <option value="p2">Elena Rodriguez</option>
                <option value="p3">Marcus Thorne</option>
              </select>
              <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-on-surface-variant">
                <ChevronDown size={20} />
              </div>
            </div>
          </div>

          {/* Machine Type Grid */}
          <div className="flex flex-col gap-2">
            <label className="label-caps text-on-surface-variant">Tipo de Maquinaria</label>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-1">
              {machines.map((m) => {
                const Icon = m.icon;
                const isSelected = machineType === m.id;
                return (
                  <button
                    key={m.id}
                    type="button"
                    onClick={() => setMachineType(m.id as MachineType)}
                    className={`border p-4 text-center flex flex-col items-center gap-1 transition-all ${
                      isSelected 
                        ? "border-primary bg-primary-container/10 ring-1 ring-primary" 
                        : "border-outline-variant hover:border-primary-container bg-white"
                    }`}
                  >
                    <Icon size={24} className={isSelected ? "text-primary" : "text-on-surface-variant"} />
                    <span className={`font-display text-sm font-medium ${isSelected ? "text-primary font-bold" : "text-on-surface"}`}>
                      {m.label}
                    </span>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Registration Type Toggle */}
          <div className="flex flex-col gap-2">
            <label className="label-caps text-on-surface-variant">Tipo de Registro</label>
            <div className="flex bg-surface-container p-1 gap-1">
              <button
                type="button"
                onClick={() => setRegType("incinal")}
                className={`flex-1 py-2 text-center font-display text-sm font-medium transition-all ${
                  regType === "incinal" 
                    ? "bg-white border border-outline-variant text-primary shadow-sm" 
                    : "text-on-surface-variant opacity-60 hover:opacity-100"
                }`}
              >
                Horometro Inicial
              </button>
              <button
                type="button"
                onClick={() => setRegType("final")}
                className={`flex-1 py-2 text-center font-display text-sm font-medium transition-all ${
                  regType === "final" 
                    ? "bg-white border border-outline-variant text-primary shadow-sm" 
                    : "text-on-surface-variant opacity-60 hover:opacity-100"
                }`}
              >
                Horometro Final
              </button>
            </div>
          </div>

          {/* Value Inputs */}
          <motion.div
            initial={{ opacity: 0, y: 10 }}
            animate={{ opacity: 1, y: 0 }}
            className="flex flex-col gap-6 p-6 bg-surface-container-low border-l-4 border-primary"
          >
            <div className="flex flex-col gap-2">
              <label className="label-caps text-on-surface-variant">
                {regType === "incinal" ? "Horometro Inicial (Value)" : "Horometro Final (Value)"}
              </label>
              <div className="flex items-center">
                <input
                  type="number"
                  placeholder="00000.0"
                  step="0.1"
                  className="w-full bg-white border border-outline-variant py-3 px-4 font-display font-medium text-on-surface outline-none focus:border-primary-container"
                />
                <div className="ml-3 bg-surface-container-high px-4 py-3 border border-outline-variant font-display text-sm font-bold text-on-surface-variant">
                  HRS
                </div>
              </div>
            </div>

            <div className="flex flex-col gap-2">
              <label className="label-caps text-on-surface-variant">Foto de inicio en el horometro</label>
              <div className="border-2 border-dashed border-outline-variant py-8 px-4 flex flex-col items-center gap-2 hover:border-primary focus-within:border-primary cursor-pointer transition-colors bg-white group">
                <Camera size={32} className="text-on-surface-variant group-hover:text-primary transition-colors" />
                <span className="font-sans text-sm text-on-surface-variant group-hover:text-on-surface">
                  Tap to capture or upload photo
                </span>
                <span className="font-display text-[10px] uppercase tracking-widest text-outline">
                  JPG, PNG UP TO 10MB
                </span>
              </div>
            </div>
          </motion.div>

          {/* Current Location */}
          <div className="flex flex-col gap-2">
            <div className="flex justify-between items-end">
              <label className="label-caps text-on-surface-variant">Ubicación Actual</label>
              <span className="font-display text-[10px] text-primary flex items-center gap-1 font-bold">
                <span className="w-2 h-2 rounded-full bg-primary animate-pulse" />
                GPS ACTIVO
              </span>
            </div>
            <div className="h-48 w-full border border-border-outline-variant bg-surface-container relative overflow-hidden group">
              <img
                src="https://lh3.googleusercontent.com/aida-public/AB6AXuAhZvPhcwVmZ7QlGYcRR63FPRjn0fznOJewT9Vgz8qtQPnSidN-BRoEUsIf_WG1_bYH-9HmsWEF3qHEdIBKEK-F6TXjoAmEvYlxhcuhwuJGSR-FOmqD9Lp9dtpPzYWluYjv4_04AH-Eymk7FKKZinrAUTww4IbXAckEASbtl8AYHSmZrA4s0AUKn9npDtqISyaruKxUXGTRp_FqPtYXdTV9a9Y7Uo6_e_gVEk3XzEjagI5YPiS13XVGr3AgsLFFc0KkviiacPo7T4un"
                alt="Construction map"
                className="w-full h-full object-cover grayscale opacity-50 group-hover:opacity-70 transition-opacity duration-700"
                referrerPolicy="no-referrer"
              />
              <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div className="relative">
                  <div className="absolute inset-0 bg-primary/20 rounded-full animate-ping scale-150" />
                  <div className="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center border border-primary relative">
                    <MapPin size={24} className="text-primary fill-primary" />
                  </div>
                </div>
              </div>
              <div className="absolute bottom-2 right-2 bg-surface-container-lowest/80 backdrop-blur-sm px-2 py-1 border border-outline-variant">
                <p className="font-display text-[10px] font-bold text-on-surface">
                  14.6349° N, 90.5069° W
                </p>
              </div>
            </div>
          </div>

          {/* Submit Button */}
          <div className="mt-4">
            <button
              type="submit"
              className="w-full bg-primary-container py-4 text-on-primary-container font-display text-xl font-bold uppercase tracking-wider hover:brightness-95 transition-all active:scale-[0.98] cursor-pointer"
            >
              Registrar Operación
            </button>
            <p className="text-center font-display text-[10px] text-on-surface-variant mt-4 font-medium uppercase tracking-tighter">
              By submitting, you confirm the accuracy of the industrial telemetry data provided above.
            </p>
          </div>
        </form>
      </main>

      {/* Footer */}
      <footer className="mt-12 w-full max-w-5xl flex flex-col md:flex-row justify-between items-center opacity-70 hover:opacity-100 transition-opacity duration-300 gap-6">
        <div className="font-display text-xs font-bold text-primary tracking-tight">
          COOITZÁ - Cooperativa de Ahorro y Crédito
        </div>
        <nav className="flex flex-wrap justify-center gap-6 font-display text-[10px] uppercase font-bold tracking-widest text-on-surface-variant">
          <a href="#" className="hover:text-primary transition-colors">Términos de Operación</a>
          <a href="#" className="hover:text-primary transition-colors">Protocolos de Seguridad</a>
          <a href="#" className="hover:text-primary transition-colors">Política de Privacidad</a>
        </nav>
        <div className="font-display text-[10px] uppercase font-medium text-on-surface-variant opacity-60">
          © 2024 Cooitzá R.L. División de Maquinaria Pesada. v4.2.1-PRO
        </div>
      </footer>
    </div>
  );
}
