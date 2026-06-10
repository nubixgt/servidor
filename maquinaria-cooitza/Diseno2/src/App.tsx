/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState, useEffect } from "react";
import { User, UserRole, OperationalLog } from "./types";
import Login from "./login/Login";
import TecnicoForm from "./tecnico/TecnicoForm";
import TecnicoDashboard from "./tecnico/TecnicoDashboard";
import TecnicoPiloto from "./tecnico/TecnicoPiloto";
import AdminDashboard from "./admin/AdminDashboard";

export default function App() {
  const [currentUser, setCurrentUser] = useState<User | null>(null);
  const [logs, setLogs] = useState<OperationalLog[]>([]);

  // Load active session and records upon mounting
  useEffect(() => {
    const savedUser = localStorage.getItem("cooitza_current_user");
    if (savedUser) {
      try {
        setCurrentUser(JSON.parse(savedUser));
      } catch (e) {
        // Safe bypass
      }
    }

    const savedLogs = localStorage.getItem("cooitza_machinery_logs");
    if (savedLogs) {
      try {
        setLogs(JSON.parse(savedLogs));
      } catch (e) {
        // Safe bypass
      }
    } else {
      // Pre-populate with beautiful, highly dynamic dummy logs if none exist for a professional look!
      const initialLogs: OperationalLog[] = [
        {
          id: "log_1",
          operatorName: "Robert Andersson (Técnico)",
          machineType: "excavadora",
          regType: "inicial",
          horometroValue: 4235.8,
          dateTime: new Date(Date.now() - 3600000 * 5).toLocaleString("es-GT"),
          location: {
            lat: 14.6284,
            lng: -90.5218,
            formattedAddress: "Sector 3 Cantera, Chimaltenango"
          }
        },
        {
          id: "log_2",
          operatorName: "Elena Rodriguez (Técnico)",
          machineType: "volteo",
          regType: "final",
          horometroValue: 1290.4,
          dateTime: new Date(Date.now() - 3600000 * 24).toLocaleString("es-GT"),
          location: {
            lat: 14.6412,
            lng: -90.4913,
            formattedAddress: "Proyecto Vial CO-A4, San Carlos Alzatate"
          }
        },
        {
          id: "log_3",
          operatorName: "Marcus Thorne (Técnico)",
          machineType: "retro",
          regType: "inicial",
          horometroValue: 843.5,
          dateTime: new Date(Date.now() - 3600000 * 48).toLocaleString("es-GT"),
          location: {
            lat: 14.5911,
            lng: -90.6120,
            formattedAddress: "Planta de Envasado, El Rodeo"
          }
        }
      ];
      setLogs(initialLogs);
      localStorage.setItem("cooitza_machinery_logs", JSON.stringify(initialLogs));
    }
  }, []);

  const handleLoginSuccess = (username: string, role: UserRole, fullName: string) => {
    const userObj: User = { username, role, fullName };
    setCurrentUser(userObj);
    localStorage.setItem("cooitza_current_user", JSON.stringify(userObj));
  };

  const handleLogout = () => {
    setCurrentUser(null);
    localStorage.removeItem("cooitza_current_user");
  };

  const handleAddLog = (newLogData: Omit<OperationalLog, "id" | "dateTime">) => {
    const freshLog: OperationalLog = {
      ...newLogData,
      id: "log_" + Date.now(),
      dateTime: new Date().toLocaleString("es-GT")
    };
    
    const updated = [freshLog, ...logs];
    setLogs(updated);
    localStorage.setItem("cooitza_machinery_logs", JSON.stringify(updated));
  };

  const handleDeleteLog = (id: string) => {
    const updated = logs.filter(log => log.id !== id);
    setLogs(updated);
    localStorage.setItem("cooitza_machinery_logs", JSON.stringify(updated));
  };

  // Generates randomized telemetry logs instantly to help testing dashboards
  const handleAddRandomMockLog = () => {
    const names = ["Robert Andersson (Técnico)", "Elena Rodriguez (Técnico)", "Marcus Thorne (Técnico)", "Carlos Pérez (Técnico)"];
    const types = ["tractor", "excavadora", "retro", "rodo", "pipa", "volteo"];
    const places = [
      { addr: "Sede Centro - Ciudad de Guatemala", lat: 14.6349, lng: -90.5069 },
      { addr: "Cantera Cooitzá - Chimaltenango", lat: 14.6500, lng: -90.5500 },
      { addr: "Proyecto Vial A-23, Quetzaltenango", lat: 14.8333, lng: -91.5167 }
    ];

    const randomName = names[Math.floor(Math.random() * names.length)];
    const randomType = types[Math.floor(Math.random() * types.length)];
    const randomPlace = places[Math.floor(Math.random() * places.length)];
    const randomHours = parseFloat((Math.random() * 8000 + 100).toFixed(1));
    const randomReg = Math.random() > 0.5 ? "inicial" : "final";

    // Dynamic clean canvas simulated image representation
    const canvas = document.createElement("canvas");
    canvas.width = 400;
    canvas.height = 200;
    const ctx = canvas.getContext("2d");
    if (ctx) {
      ctx.fillStyle = "#0f172a";
      ctx.fillRect(0, 0, 400, 200);
      ctx.strokeStyle = "#0054A3";
      ctx.lineWidth = 3;
      ctx.strokeRect(10, 10, 380, 180);
      ctx.fillStyle = "#FFD200";
      ctx.font = "bold 26px monospace";
      ctx.fillText(`${randomHours.toString().padStart(7, "0")} HRS`, 120, 110);
      ctx.fillStyle = "#ffffff";
      ctx.font = "bold 9px sans-serif";
      ctx.fillText("DIAL SIMULADO DE TELEMETRIA", 130, 150);
    }
    const mockPhotoUrl = canvas.toDataURL("image/png");

    const mockLog: OperationalLog = {
      id: "log_" + Math.random().toString(36).substr(2, 9),
      operatorName: randomName,
      machineType: randomType,
      regType: randomReg as "inicial" | "final",
      horometroValue: randomHours,
      dateTime: new Date(Date.now() - Math.random() * 3600000 * 24).toLocaleString("es-GT"),
      photoUrl: mockPhotoUrl,
      location: {
        lat: randomPlace.lat,
        lng: randomPlace.lng,
        formattedAddress: randomPlace.addr
      }
    };

    const updated = [mockLog, ...logs];
    setLogs(updated);
    localStorage.setItem("cooitza_machinery_logs", JSON.stringify(updated));
  };

  return (
    <>
      {!currentUser ? (
        <div className="min-h-screen flex flex-col items-center justify-center py-12 px-6 md:px-8 bg-surface">
          {/* Universal Corporate Header with Cooitzá Logo */}
          <header className="mb-6 text-center flex flex-col items-center">
            <div className="mb-4 max-w-[280px]">
              <div className="flex flex-col items-center">
                <div className="relative w-28 h-28 mb-2">
                  <div className="absolute top-0 right-0 w-20 h-20 bg-[#FFD200] rounded-full" />
                  <div className="absolute inset-0 flex items-center justify-center">
                    <span className="font-display text-3xl font-black text-[#0054A3] tracking-tighter italic">
                      COOITZÁ
                    </span>
                  </div>
                </div>
                <div className="bg-[#0054A3] px-6 py-2 w-full max-w-[320px]">
                  <span className="text-white font-sans text-[10px] font-bold whitespace-nowrap uppercase tracking-wider">
                    Cooperativa de Ahorro y Crédito
                  </span>
                </div>
              </div>
            </div>
            <p className="font-mono-label text-xs text-on-surface-variant font-bold uppercase tracking-widest">
              Bitácora de Operación v4.2.1
            </p>
          </header>

          <Login onLoginSuccess={handleLoginSuccess} />
        </div>
      ) : currentUser.role === "admin" ? (
        <AdminDashboard 
          logs={logs} 
          onDeleteLog={handleDeleteLog} 
          onAddMockLog={handleAddRandomMockLog}
          onLogout={handleLogout} 
        />
      ) : currentUser.role === "tecnico_dashboard" ? (
        <TecnicoDashboard 
          onLogout={handleLogout} 
        />
      ) : currentUser.role === "tecnico_piloto" ? (
        <TecnicoPiloto 
          currentUserFullName={currentUser.fullName}
          onLogout={handleLogout} 
        />
      ) : (
        <div className="min-h-screen flex flex-col items-center justify-center py-8 px-4 bg-surface">
          <TecnicoForm 
            currentUserFullName={currentUser.fullName} 
            onAddLog={handleAddLog} 
            onLogout={handleLogout} 
          />
        </div>
      )}
    </>
  );
}
