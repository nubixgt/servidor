import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  User, 
  Phone, 
  Mail, 
  MapPin, 
  Map, 
  Send, 
  Heart, 
  CheckCircle, 
  Download, 
  Printer, 
  ArrowLeft, 
  Trash2, 
  Edit, 
  Search, 
  FileSpreadsheet, 
  Plus, 
  X, 
  FileText, 
  LayoutDashboard, 
  Sparkles,
  Database,
  Grid,
  Check,
  AlertCircle
} from 'lucide-react';
import { Registration, ScreenType } from './types';

// Predefined lists
const DIRECTIONS = [
  'SANIDAD ANIMAL',
  'SANIDAD VEGETAL',
  'INOCUIDAD',
  'FITOZOOGENÉTICA',
  'DIPESCA',
  'UDAFA',
  'RRHH',
  'VICEDESPACHO'
];

const MOCK_REGISTRATIONS: Registration[] = [
  {
    id: 'mock-1',
    fullName: 'Carlos Enrique Fuentes Rivera',
    phone: '58412039',
    email: 'carlos.fuentes@maga.gob.gt',
    direction: 'SANIDAD ANIMAL',
    department: 'Guatemala',
    dateCreated: '24/06/2026 10:15',
    code: 'MAGA-PADRE-2026-9481'
  },
  {
    id: 'mock-2',
    fullName: 'José Mario Alvarez Castillo',
    phone: '41203948',
    email: 'jose.alvarez@gmail.com',
    direction: 'VICEDESPACHO',
    department: 'Sacatepéquez',
    dateCreated: '24/06/2026 11:30',
    code: 'MAGA-PADRE-2026-1029'
  },
  {
    id: 'mock-3',
    fullName: 'Ramiro Antonio Morales Arriola',
    phone: '30291827',
    email: 'ramiro_morales@gmail.com',
    direction: 'SANIDAD VEGETAL',
    department: 'Chimaltenango',
    dateCreated: '24/06/2026 12:05',
    code: 'MAGA-PADRE-2026-4739'
  },
  {
    id: 'mock-4',
    fullName: 'Luis Francisco Ortiz Estrada',
    phone: '55443322',
    email: 'lortiz@maga.gob.gt',
    direction: 'INOCUIDAD',
    department: 'Escuintla',
    dateCreated: '24/06/2026 13:40',
    code: 'MAGA-PADRE-2026-8821'
  },
  {
    id: 'mock-5',
    fullName: 'Manuel de Jesús Galdámez',
    phone: '47881122',
    email: 'manuel.galdamez@outlook.com',
    direction: 'RRHH',
    department: 'Alta Verapaz',
    dateCreated: '24/06/2026 14:02',
    code: 'MAGA-PADRE-2026-2938'
  }
];

export default function App() {
  // Navigation & Data state
  const [screen, setScreen] = useState<ScreenType>('form');
  const [registrations, setRegistrations] = useState<Registration[]>([]);
  const [currentRegistration, setCurrentRegistration] = useState<Registration | null>(null);
  
  // Form input states
  const [fullName, setFullName] = useState('');
  const [phone, setPhone] = useState('');
  const [email, setEmail] = useState('');
  const [direction, setDirection] = useState('');
  const [department, setDepartment] = useState('');
  
  // Validation / Feedback states
  const [formErrors, setFormErrors] = useState<{ [key: string]: string }>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [heartCount, setHeartCount] = useState(0);
  const [heartAnimating, setHeartAnimating] = useState(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  // Administration dashboard search & edit states
  const [searchTerm, setSearchTerm] = useState('');
  const [adminDirFilter, setAdminDirFilter] = useState('');
  const [adminDeptFilter, setAdminDeptFilter] = useState('');
  const [editingReg, setEditingReg] = useState<Registration | null>(null);
  const [isEditingModalOpen, setIsEditingModalOpen] = useState(false);
  const [isAddModalOpen, setIsAddModalOpen] = useState(false);
  
  // New Admin manual record states
  const [newAdminName, setNewAdminName] = useState('');
  const [newAdminPhone, setNewAdminPhone] = useState('');
  const [newAdminEmail, setNewAdminEmail] = useState('');
  const [newAdminDir, setNewAdminDir] = useState('');
  const [newAdminDept, setNewAdminDept] = useState('');
  const [adminFormErrors, setAdminFormErrors] = useState<{ [key: string]: string }>({});

  // Loading existing registrations or seeding them on mount
  useEffect(() => {
    const stored = localStorage.getItem('maga_padre_registrations');
    if (stored) {
      try {
        setRegistrations(JSON.parse(stored));
      } catch (e) {
        console.error('Error parsing stored registrations, using empty');
        setRegistrations([]);
      }
    } else {
      // Seed initial beautiful mock data so dashboard is not empty
      localStorage.setItem('maga_padre_registrations', JSON.stringify(MOCK_REGISTRATIONS));
      setRegistrations(MOCK_REGISTRATIONS);
    }

    // Heart count from localStorage
    const storedHearts = localStorage.getItem('maga_padre_hearts');
    if (storedHearts) {
      setHeartCount(parseInt(storedHearts, 10));
    }
  }, []);

  // Save to localStorage whenever registration changes
  const updateLocalStorage = (updatedList: Registration[]) => {
    setRegistrations(updatedList);
    localStorage.setItem('maga_padre_registrations', JSON.stringify(updatedList));
  };

  const showToast = (message: string) => {
    setToastMessage(message);
    setTimeout(() => {
      setToastMessage(null);
    }, 3500);
  };

  // Hearts toggle / clicker
  const handleHeartClick = () => {
    setHeartAnimating(true);
    const newCount = heartCount + 1;
    setHeartCount(newCount);
    localStorage.setItem('maga_padre_hearts', newCount.toString());
    setTimeout(() => setHeartAnimating(false), 600);
    showToast('¡Has enviado amor a los padres del MAGA! ❤️');
  };

  // Helper date formatter
  const getFormattedNow = () => {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
  };

  // Generate 4-digit unique code helper
  const generateUniqueCode = () => {
    const rand = Math.floor(1000 + Math.random() * 9000);
    return `MAGA-PADRE-2026-${rand}`;
  };

  // Validation function
  const validateForm = (nameVal: string, phoneVal: string, emailVal: string, dirVal: string, deptVal: string) => {
    const errors: { [key: string]: string } = {};
    if (!nameVal.trim()) {
      errors.fullName = 'El nombre completo es obligatorio';
    } else if (nameVal.trim().length < 4) {
      errors.fullName = 'El nombre debe tener al menos 4 caracteres';
    }

    const cleanPhone = phoneVal.replace(/\D/g, '');
    if (!phoneVal.trim()) {
      errors.phone = 'El teléfono es obligatorio';
    } else if (cleanPhone.length < 8) {
      errors.phone = 'El teléfono debe contener al menos 8 dígitos';
    }

    if (!emailVal.trim()) {
      errors.email = 'El correo electrónico es obligatorio';
    } else {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailVal.trim())) {
        errors.email = 'El formato de correo no es válido';
      }
    }

    if (!dirVal) {
      errors.direction = 'Debe seleccionar una dirección';
    }

    if (!deptVal.trim()) {
      errors.department = 'El departamento es obligatorio';
    }

    return errors;
  };

  // Submit main registration
  const handleSubmitRegistration = (e: React.FormEvent) => {
    e.preventDefault();
    const errors = validateForm(fullName, phone, email, direction, department);
    setFormErrors(errors);

    if (Object.keys(errors).length > 0) {
      showToast('Por favor, corrija los errores en el formulario.');
      return;
    }

    setIsSubmitting(true);
    
    // Simulate API registration call for realistic premium feel
    setTimeout(() => {
      const newReg: Registration = {
        id: 'reg-' + Date.now(),
        fullName: fullName.trim(),
        phone: phone.trim(),
        email: email.trim().toLowerCase(),
        direction,
        department,
        dateCreated: getFormattedNow(),
        code: generateUniqueCode()
      };

      const updated = [newReg, ...registrations];
      updateLocalStorage(updated);
      setCurrentRegistration(newReg);
      setIsSubmitting(false);
      setScreen('success');
      
      // Clear inputs
      setFullName('');
      setPhone('');
      setEmail('');
      setDirection('');
      setDepartment('');
      showToast('¡Registro exitoso! Tu ticket de participación está listo.');
    }, 1200);
  };

  // Custom simulation for downloading
  const handleDownloadSimulation = () => {
    if (!currentRegistration) return;
    showToast('Generando ticket PDF de participación...');
    
    setTimeout(() => {
      // Create element and download representation
      const fileContent = `
=============================================
     TICKET DE PARTICIPACIÓN - DÍA DEL PADRE
   MINISTERIO DE AGRICULTURA Y ALIMENTACIÓN
=============================================
Código: ${currentRegistration.code}
Nombre: ${currentRegistration.fullName}
Teléfono: ${currentRegistration.phone}
Correo: ${currentRegistration.email}
Dirección: ${currentRegistration.direction}
Departamento: ${currentRegistration.department}
Fecha Registro: ${currentRegistration.dateCreated}
---------------------------------------------
¡Gracias por ser un padre ejemplar y trabajar
por el desarrollo del campo guatemalteco!
=============================================
`;
      const blob = new Blob([fileContent], { type: 'text/plain' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `Ticket_MAGA_${currentRegistration.fullName.replace(/\s+/g, '_')}.txt`;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      showToast('¡Descarga iniciada con éxito!');
    }, 1000);
  };

  // Simple browser trigger print
  const handlePrint = () => {
    window.print();
  };

  // Administration actions
  const handleDeleteRegistration = (id: string, name: string) => {
    if (window.confirm(`¿Está seguro de que desea eliminar el registro de ${name}?`)) {
      const filtered = registrations.filter(r => r.id !== id);
      updateLocalStorage(filtered);
      showToast('Registro eliminado exitosamente.');
    }
  };

  // Open Edit Modal
  const handleOpenEdit = (reg: Registration) => {
    setEditingReg({ ...reg });
    setIsEditingModalOpen(true);
  };

  // Save edit registration
  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!editingReg) return;

    const errors = validateForm(
      editingReg.fullName, 
      editingReg.phone, 
      editingReg.email, 
      editingReg.direction, 
      editingReg.department
    );

    if (Object.keys(errors).length > 0) {
      showToast('Por favor, complete correctamente todos los campos del formulario.');
      return;
    }

    const updated = registrations.map(r => r.id === editingReg.id ? editingReg : r);
    updateLocalStorage(updated);
    setIsEditingModalOpen(false);
    setEditingReg(null);
    showToast('Registro actualizado correctamente.');
  };

  // Admin manually create new dad registration
  const handleCreateAdminRecord = (e: React.FormEvent) => {
    e.preventDefault();
    const errors = validateForm(newAdminName, newAdminPhone, newAdminEmail, newAdminDir, newAdminDept);
    setAdminFormErrors(errors);

    if (Object.keys(errors).length > 0) {
      showToast('Verifique los campos con errores.');
      return;
    }

    const newReg: Registration = {
      id: 'reg-' + Date.now(),
      fullName: newAdminName.trim(),
      phone: newAdminPhone.trim(),
      email: newAdminEmail.trim().toLowerCase(),
      direction: newAdminDir,
      department: newAdminDept,
      dateCreated: getFormattedNow(),
      code: generateUniqueCode()
    };

    updateLocalStorage([newReg, ...registrations]);
    setIsAddModalOpen(false);
    
    // Reset manual fields
    setNewAdminName('');
    setNewAdminPhone('');
    setNewAdminEmail('');
    setNewAdminDir('');
    setNewAdminDept('');
    setAdminFormErrors({});
    
    showToast('Padre agregado manualmente de forma exitosa.');
  };

  // Export to CSV helper
  const handleExportCSV = () => {
    if (registrations.length === 0) {
      showToast('No hay registros para exportar.');
      return;
    }

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // UTF-8 BOM for Spanish characters
    csvContent += "Codigo,Nombre Completo,Telefono,Correo,Direccion,Departamento,Fecha Registro\n";
    
    registrations.forEach(r => {
      const row = [
        `"${r.code}"`,
        `"${r.fullName.replace(/"/g, '""')}"`,
        `"${r.phone}"`,
        `"${r.email}"`,
        `"${r.direction.replace(/"/g, '""')}"`,
        `"${r.department}"`,
        `"${r.dateCreated}"`
      ].join(",");
      csvContent += row + "\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.href = encodedUri;
    link.download = `Participantes_Dia_Del_Padre_MAGA_${new Date().toISOString().slice(0,10)}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast('Registros exportados a CSV exitosamente.');
  };

  // Restore/Re-seed mock data
  const handleResetData = () => {
    if (window.confirm('¿Está seguro de que desea restaurar los datos de prueba? Esto sobrescribirá los datos actuales.')) {
      updateLocalStorage(MOCK_REGISTRATIONS);
      showToast('Datos de prueba restaurados.');
    }
  };

  // Dynamic list of unique departments from all registered dads
  const uniqueDepartments = Array.from(new Set(registrations.map(r => r.department).filter(Boolean))).sort();

  // Filter registrations for table
  const filteredRegistrations = registrations.filter(r => {
    const matchesSearch = 
      r.fullName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      r.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      r.phone.includes(searchTerm) ||
      r.code.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesDir = adminDirFilter ? r.direction === adminDirFilter : true;
    const matchesDept = adminDeptFilter ? r.department === adminDeptFilter : true;

    return matchesSearch && matchesDir && matchesDept;
  });

  // Calculate statistics for the dashboard
  const statsTotal = registrations.length;
  
  // Count by direction
  const directionCounts = registrations.reduce((acc, curr) => {
    acc[curr.direction] = (acc[curr.direction] || 0) + 1;
    return acc;
  }, {} as { [key: string]: number });

  // Get direction with most participants
  const topDirection = Object.entries(directionCounts).sort((a, b) => (b[1] as number) - (a[1] as number))[0]?.[0] || 'Ninguna';

  // Count by department
  const departmentCounts = registrations.reduce((acc, curr) => {
    acc[curr.department] = (acc[curr.department] || 0) + 1;
    return acc;
  }, {} as { [key: string]: number });

  const sortedDepts = Object.entries(departmentCounts).sort((a, b) => (b[1] as number) - (a[1] as number)).slice(0, 3);

  return (
    <div className="min-h-screen bg-[#f4f7f9] text-gray-800 flex flex-col font-sans selection:bg-maga-light-blue selection:text-white print:bg-white print:text-black">
      
      {/* Toast Notification */}
      <AnimatePresence>
        {toastMessage && (
          <motion.div 
            initial={{ opacity: 0, y: -50, scale: 0.9 }}
            animate={{ opacity: 1, y: 16, scale: 1 }}
            exit={{ opacity: 0, y: -20, scale: 0.95 }}
            className="fixed top-4 left-1/2 -translate-x-1/2 z-50 bg-maga-blue text-white py-3 px-6 rounded-xl shadow-2xl flex items-center gap-3 border border-white/10 max-w-md w-[90%] md:w-auto"
          >
            <Sparkles className="w-5 h-5 text-amber-400 flex-shrink-0 animate-pulse" />
            <span className="text-sm font-medium">{toastMessage}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Navigation bar / Floating switch for admins */}
      <div className="bg-white/80 backdrop-blur-md border-b border-gray-200/50 sticky top-0 z-40 py-2.5 px-4 md:px-12 print:hidden">
        <div className="max-w-6xl mx-auto flex justify-between items-center">
          <div className="flex items-center gap-2">
            <span className="flex h-2.5 w-2.5 relative">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
            <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">
              Sistema de Registro MAGA 2026
            </span>
          </div>

          <div className="flex items-center gap-3">
            {screen !== 'form' && (
              <button 
                onClick={() => setScreen('form')}
                className="text-xs font-medium text-maga-blue hover:text-maga-light-blue px-3 py-1.5 rounded-lg hover:bg-gray-100 flex items-center gap-1 transition-all duration-150"
              >
                <ArrowLeft className="w-3.5 h-3.5" />
                Ir al Registro
              </button>
            )}

            {screen !== 'admin' ? (
              <button
                onClick={() => setScreen('admin')}
                className="text-xs font-medium bg-maga-blue hover:bg-maga-light-blue text-white px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-all duration-150 shadow-sm"
              >
                <LayoutDashboard className="w-3.5 h-3.5" />
                Panel Admin
              </button>
            ) : (
              <button
                onClick={() => setScreen('form')}
                className="text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg border border-emerald-200/50 flex items-center gap-1.5 transition-all duration-150"
              >
                <Plus className="w-3.5 h-3.5" />
                Registrar Padre
              </button>
            )}
          </div>
        </div>
      </div>

      {/* Main Content Areas based on selected screen state */}
      <AnimatePresence mode="wait">
        
        {/* SCREEN 1: REGISTRATION FORM */}
        {screen === 'form' && (
          <motion.div
            key="registration-form"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="flex-grow flex flex-col justify-between"
          >
            {/* Header section displaying the beautiful official unified banner */}
            <header className="bg-[#f4f7f9] px-4 pt-6 pb-2 md:px-12 relative overflow-hidden print:bg-white">
              <div className="max-w-3xl mx-auto relative bg-white rounded-3xl overflow-hidden shadow-sm border border-gray-100">
                {/* Unified full banner image */}
                <div className="relative w-full aspect-[1200/540] md:aspect-[1200/480] overflow-hidden select-none">
                  <img 
                    alt="Banner Oficial Día del Padre MAGA 2026" 
                    className="w-full h-full object-cover md:object-fill" 
                    src="https://lh3.googleusercontent.com/aida-public/AB6AXuCxH4F8yOyY2HFdZAIahTcLf3-V2btihOF-3LKujXyi7esjqlvU3bqg-YbMeOB0xbESveekj6FUqsHjmcCnxjyqsMCysAVMxDolrJbnzFu2hlmKL7YeoquXFmzqpvPJf95uBnMDztH9ylp7m13_30Vzw2ltxD1eQ3kt7q85hUtcUdCtDit_-Wi4Z2mK5ltisERfNBp3gGwZD3kXkbkwMZBglFD3VZX6yoDdGCYOnTyESEfnmelPnZaZNAEk1LBW-Et-Vnh-tXYZgIQ" 
                  />

                  {/* Absolute positioned interactive heart overlay on the banner heart */}
                  <div className="absolute left-[39.5%] md:left-[41%] top-[45%] md:top-[44%] -translate-x-1/2 -translate-y-1/2">
                    <button 
                      onClick={handleHeartClick}
                      className="group flex flex-col items-center justify-center relative cursor-pointer focus:outline-none"
                      title="¡Envía un corazón de felicitación!"
                    >
                      <motion.div
                        animate={heartAnimating ? { scale: [1, 1.4, 1.2, 1.5, 1] } : {}}
                        transition={{ duration: 0.6 }}
                      >
                        <Heart 
                          className={`w-6 h-6 md:w-9 md:h-9 transition-all duration-200 ${
                            heartCount > 0 
                              ? 'fill-rose-500 text-rose-500 scale-110 drop-shadow-[0_2px_8px_rgba(244,63,94,0.4)]' 
                              : 'text-maga-light-blue hover:text-rose-500 hover:fill-rose-100 hover:scale-110'
                          }`}
                          strokeWidth={2}
                        />
                      </motion.div>
                      {heartCount > 0 && (
                        <span className="absolute -bottom-3 bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow-md scale-90 md:scale-100">
                          {heartCount}
                        </span>
                      )}
                    </button>
                  </div>
                </div>
              </div>
            </header>

            {/* Registration Form Card Section */}
            <main className="px-4 pb-16 -mt-8 relative z-20">
              <div className="max-w-3xl mx-auto bg-white rounded-2xl p-6 md:p-12 shadow-[0_15px_40px_-15px_rgba(0,40,85,0.12)] border border-gray-100">
                
                {/* Form Header */}
                <div className="flex flex-col items-center mb-8 text-center" data-purpose="form-header">
                  <div className="w-14 h-14 border border-maga-blue/30 rounded-full flex items-center justify-center mb-4 bg-maga-bg-gray">
                    <FileText className="w-7 h-7 text-maga-blue" />
                  </div>
                  <h4 className="text-maga-blue text-2xl font-bold tracking-tight">Formulario de participación</h4>
                  <p className="text-gray-500 mt-2 text-sm max-w-lg">
                    Completa tus datos para participar en la celebración del Día del Padre, organizada por el Viceministerio de VISAR.
                  </p>
                  <div className="w-full border-b border-gray-100 mt-6"></div>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmitRegistration} className="space-y-6 max-w-2xl mx-auto" data-purpose="registration-form">
                  
                  {/* Field: Nombre Completo */}
                  <div className="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div className="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                      <User className="w-5 h-5 text-maga-blue" />
                    </div>
                    <div className="flex-grow">
                      <label className="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" htmlFor="full_name">
                        <User className="w-4 h-4 text-maga-blue md:hidden" />
                        Nombre Completo <span className="text-rose-500">*</span>
                      </label>
                      <input 
                        type="text"
                        id="full_name"
                        value={fullName}
                        onChange={(e) => {
                          setFullName(e.target.value);
                          if (formErrors.fullName) setFormErrors(prev => ({ ...prev, fullName: '' }));
                        }}
                        className={`w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20 ${
                          formErrors.fullName ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue'
                        }`}
                        placeholder="Ingresa tu nombre completo"
                      />
                      {formErrors.fullName && (
                        <p className="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                          <AlertCircle className="w-3 h-3" />
                          {formErrors.fullName}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Field: Teléfono */}
                  <div className="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div className="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                      <Phone className="w-5 h-5 text-maga-blue" />
                    </div>
                    <div className="flex-grow">
                      <label className="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" htmlFor="phone">
                        <Phone className="w-4 h-4 text-maga-blue md:hidden" />
                        Teléfono <span className="text-rose-500">*</span>
                      </label>
                      <input 
                        type="tel"
                        id="phone"
                        value={phone}
                        onChange={(e) => {
                          setPhone(e.target.value);
                          if (formErrors.phone) setFormErrors(prev => ({ ...prev, phone: '' }));
                        }}
                        className={`w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20 ${
                          formErrors.phone ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue'
                        }`}
                        placeholder="Ingresa tu número de teléfono"
                      />
                      {formErrors.phone && (
                        <p className="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                          <AlertCircle className="w-3 h-3" />
                          {formErrors.phone}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Field: Correo */}
                  <div className="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div className="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                      <Mail className="w-5 h-5 text-maga-blue" />
                    </div>
                    <div className="flex-grow">
                      <label className="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" htmlFor="email">
                        <Mail className="w-4 h-4 text-maga-blue md:hidden" />
                        Correo Electrónico <span className="text-rose-500">*</span>
                      </label>
                      <input 
                        type="email"
                        id="email"
                        value={email}
                        onChange={(e) => {
                          setEmail(e.target.value);
                          if (formErrors.email) setFormErrors(prev => ({ ...prev, email: '' }));
                        }}
                        className={`w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20 ${
                          formErrors.email ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue'
                        }`}
                        placeholder="Ingresa tu correo electrónico"
                      />
                      {formErrors.email && (
                        <p className="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                          <AlertCircle className="w-3 h-3" />
                          {formErrors.email}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Field: Dirección */}
                  <div className="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div className="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                      <MapPin className="w-5 h-5 text-maga-blue" />
                    </div>
                    <div className="flex-grow">
                      <label className="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" htmlFor="direction">
                        <MapPin className="w-4 h-4 text-maga-blue md:hidden" />
                        Dirección a la que pertenece <span className="text-rose-500">*</span>
                      </label>
                      <div className="relative">
                        <select 
                          id="direction"
                          value={direction}
                          onChange={(e) => {
                            setDirection(e.target.value);
                            if (formErrors.direction) setFormErrors(prev => ({ ...prev, direction: '' }));
                          }}
                          className={`w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-700 focus:ring-2 focus:ring-maga-blue/20 appearance-none cursor-pointer ${
                            formErrors.direction ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue'
                          }`}
                        >
                          <option value="">Selecciona tu dirección</option>
                          {DIRECTIONS.map(dir => (
                            <option key={dir} value={dir}>{dir}</option>
                          ))}
                        </select>
                        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                          <svg className="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />
                          </svg>
                        </div>
                      </div>
                      {formErrors.direction && (
                        <p className="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                          <AlertCircle className="w-3 h-3" />
                          {formErrors.direction}
                        </p>
                      )}
                    </div>
                  </div>

                  {/* Field: Departamento */}
                  <div className="flex flex-col md:flex-row md:items-end gap-3 md:gap-4">
                    <div className="hidden md:flex w-11 h-11 border border-maga-blue items-center justify-center rounded-full flex-shrink-0 bg-maga-bg-gray">
                      <Map className="w-5 h-5 text-maga-blue" />
                    </div>
                    <div className="flex-grow">
                      <label className="block text-maga-blue font-bold text-sm mb-1.5 flex items-center gap-1.5" htmlFor="department">
                        <Map className="w-4 h-4 text-maga-blue md:hidden" />
                        Departamento al que pertenece <span className="text-rose-500">*</span>
                      </label>
                      <input 
                        type="text"
                        id="department"
                        value={department}
                        onChange={(e) => {
                          setDepartment(e.target.value);
                          if (formErrors.department) setFormErrors(prev => ({ ...prev, department: '' }));
                        }}
                        className={`w-full border rounded-xl py-3 px-4 bg-gray-50/50 transition-all outline-none text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-maga-blue/20 ${
                          formErrors.department ? 'border-rose-400 focus:border-rose-500' : 'border-gray-200 focus:border-maga-blue'
                        }`}
                        placeholder="Ingresa tu departamento"
                      />
                      {formErrors.department && (
                        <p className="text-rose-500 text-xs mt-1.5 flex items-center gap-1">
                          <AlertCircle className="w-3 h-3" />
                          {formErrors.department}
                        </p>
                      )}
                    </div>
                  </div>

                  <p className="text-center text-xs text-gray-400 pt-2">* Campos obligatorios</p>

                  {/* Submit Button */}
                  <div className="pt-4 flex justify-center">
                    <button 
                      type="submit"
                      disabled={isSubmitting}
                      className="w-full sm:w-auto bg-maga-blue hover:bg-maga-light-blue disabled:bg-maga-blue/60 text-white font-bold py-3.5 px-12 rounded-xl flex items-center justify-center gap-3 transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-maga-blue/40 cursor-pointer"
                    >
                      {isSubmitting ? (
                        <>
                          <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Registrando...
                        </>
                      ) : (
                        <>
                          <Send className="w-5 h-5 transform rotate-45 -mt-0.5" />
                          Enviar formulario
                        </>
                      )}
                    </button>
                  </div>

                </form>
              </div>
            </main>

            {/* Footer identical to layout */}
            <footer className="bg-maga-blue text-white py-12 px-4 md:px-12 mt-auto">
              <div className="max-w-6xl mx-auto flex flex-col md:flex-row justify-between items-center gap-8">
                
                {/* Footer Logo Area */}
                <div className="w-full md:w-1/2 flex items-center justify-center md:justify-start" data-purpose="footer-branding">
                  <div className="border border-white/20 rounded-xl p-5 w-full max-w-sm flex items-center gap-4 bg-white/[0.02] backdrop-blur-sm">
                    <div className="border border-white/30 p-2 rounded-lg bg-white/5">
                      <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5"></path>
                      </svg>
                    </div>
                    <div className="h-10 w-px bg-white/20"></div>
                    <div>
                      <p className="text-xs uppercase tracking-widest font-extrabold text-white/90 leading-tight">Ministerio de Agricultura</p>
                      <p className="text-[10px] text-white/50 font-medium">República de Guatemala</p>
                    </div>
                  </div>
                </div>

                {/* Footer Message */}
                <div className="w-full md:w-1/2 flex items-center justify-center md:justify-end gap-5" data-purpose="footer-slogan">
                  <div className="w-14 h-14 border border-white/20 rounded-full flex items-center justify-center flex-shrink-0 bg-white/5">
                    <Heart className="w-7 h-7 text-white fill-white/10" />
                  </div>
                  <p className="text-md md:text-lg font-medium leading-tight">
                    Trabajamos por el desarrollo del campo<br />
                    <span className="font-bold text-amber-300">y la seguridad alimentaria</span><br />
                    de Guatemala.
                  </p>
                </div>
              </div>
            </footer>
          </motion.div>
        )}

        {/* SCREEN 2: SUCCESS ENTRY CONFIRMATION TICKET */}
        {screen === 'success' && currentRegistration && (
          <motion.div
            key="success-ticket"
            initial={{ opacity: 0, scale: 0.98 }}
            animate={{ opacity: 1, scale: 1 }}
            exit={{ opacity: 0, scale: 0.98 }}
            className="flex-grow flex flex-col justify-center items-center py-10 px-4 max-w-4xl mx-auto w-full"
          >
            <div className="text-center mb-8 print:hidden">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-emerald-50 rounded-full border-4 border-emerald-500/20 text-emerald-600 mb-4 animate-bounce">
                <Check className="w-8 h-8" strokeWidth={3} />
              </div>
              <h1 className="text-3xl font-extrabold text-maga-blue">¡Registro Confirmado!</h1>
              <p className="text-gray-500 mt-2 text-sm max-w-md">
                Tu participación ha sido registrada correctamente. Guarda o imprime tu pase de entrada oficial para el evento.
              </p>
            </div>

            {/* HIGH-FIDELITY TICKET COMPONENT */}
            <div className="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden relative print:border-none print:shadow-none print:my-0">
              
              {/* Ticket Top Banner */}
              <div className="bg-maga-blue text-white px-6 py-4 flex justify-between items-center relative overflow-hidden">
                <div className="absolute top-0 right-0 w-32 h-32 bg-maga-light-blue/20 rounded-full blur-2xl"></div>
                <div>
                  <p className="text-[10px] uppercase tracking-widest font-bold text-white/60">Pase Oficial de Evento</p>
                  <h4 className="font-extrabold text-lg tracking-tight">Día del Padre MAGA 2026</h4>
                </div>
                <div className="bg-amber-400 text-maga-blue text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider animate-pulse">
                  VIP ACCESO
                </div>
              </div>

              {/* Ticket Main Info Block */}
              <div className="p-6 md:p-8 space-y-6">
                
                {/* Visual Registration Code */}
                <div className="text-center bg-gray-50/80 rounded-2xl p-4 border border-gray-100">
                  <p className="text-xs text-gray-400 font-medium uppercase tracking-wider">Código Único de Registro</p>
                  <p className="text-2xl md:text-3xl font-mono font-black text-maga-blue mt-1 tracking-tight">
                    {currentRegistration.code}
                  </p>
                </div>

                {/* Grid Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                  <div>
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Nombre del Participante</label>
                    <span className="font-bold text-gray-800 text-base">{currentRegistration.fullName}</span>
                  </div>

                  <div>
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Teléfono</label>
                    <span className="font-semibold text-gray-800">{currentRegistration.phone}</span>
                  </div>

                  <div>
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Correo Electrónico</label>
                    <span className="font-semibold text-gray-700 truncate block">{currentRegistration.email}</span>
                  </div>

                  <div>
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Fecha de Registro</label>
                    <span className="font-semibold text-gray-800">{currentRegistration.dateCreated}</span>
                  </div>

                  <div className="md:col-span-2 border-t border-gray-100 pt-3">
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Dirección</label>
                    <span className="font-bold text-maga-light-blue text-sm">{currentRegistration.direction}</span>
                  </div>

                  <div className="md:col-span-2">
                    <label className="text-xs text-gray-400 font-semibold block uppercase">Departamento</label>
                    <span className="font-bold text-maga-blue text-sm">{currentRegistration.department}</span>
                  </div>
                </div>

                {/* Separation line representing ticket perforations */}
                <div className="relative py-4">
                  <div className="absolute left-0 right-0 top-1/2 -translate-y-1/2 border-t-2 border-dashed border-gray-200"></div>
                  {/* Left Circle punchout */}
                  <div className="absolute -left-[35px] top-1/2 -translate-y-1/2 w-6 h-6 bg-[#f4f7f9] border border-gray-200 rounded-full print:hidden"></div>
                  {/* Right Circle punchout */}
                  <div className="absolute -right-[35px] top-1/2 -translate-y-1/2 w-6 h-6 bg-[#f4f7f9] border border-gray-200 rounded-full print:hidden"></div>
                </div>

                {/* Bottom Ticket / QR simulation & validation footer */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-6">
                  <div>
                    <p className="text-xs font-semibold text-gray-400 uppercase tracking-widest">Información de Entrada</p>
                    <p className="text-xs text-gray-500 mt-1 max-w-[280px]">
                      Presente este código en la entrada principal. Organizado por el Viceministerio de VISAR del Ministerio de Agricultura de Guatemala.
                    </p>
                  </div>

                  {/* Dynamic simulated QR code */}
                  <div className="bg-white p-3 border border-gray-200 rounded-xl shadow-sm flex-shrink-0 flex items-center justify-center">
                    <svg className="w-24 h-24 text-maga-blue" viewBox="0 0 100 100">
                      <rect width="100" height="100" fill="white" />
                      {/* Top Left Finder Pattern */}
                      <rect x="5" y="5" width="25" height="25" fill="currentColor" />
                      <rect x="10" y="10" width="15" height="15" fill="white" />
                      <rect x="13" y="13" width="9" height="9" fill="currentColor" />
                      
                      {/* Top Right Finder Pattern */}
                      <rect x="70" y="5" width="25" height="25" fill="currentColor" />
                      <rect x="75" y="10" width="15" height="15" fill="white" />
                      <rect x="78" y="13" width="9" height="9" fill="currentColor" />

                      {/* Bottom Left Finder Pattern */}
                      <rect x="5" y="70" width="25" height="25" fill="currentColor" />
                      <rect x="10" y="75" width="15" height="15" fill="white" />
                      <rect x="13" y="78" width="9" height="9" fill="currentColor" />

                      {/* Random aesthetic barcode dots in the middle */}
                      <rect x="40" y="10" width="5" height="5" fill="currentColor" />
                      <rect x="50" y="5" width="5" height="10" fill="currentColor" />
                      <rect x="60" y="15" width="5" height="5" fill="currentColor" />
                      <rect x="45" y="25" width="5" height="15" fill="currentColor" />
                      <rect x="40" y="45" width="15" height="5" fill="currentColor" />
                      <rect x="10" y="40" width="10" height="5" fill="currentColor" />
                      <rect x="25" y="45" width="5" height="5" fill="currentColor" />
                      <rect x="5" y="55" width="5" height="5" fill="currentColor" />

                      <rect x="40" y="70" width="5" height="15" fill="currentColor" />
                      <rect x="50" y="80" width="10" height="5" fill="currentColor" />
                      <rect x="65" y="75" width="5" height="5" fill="currentColor" />
                      <rect x="80" y="40" width="15" height="10" fill="currentColor" />
                      <rect x="75" y="55" width="5" height="5" fill="currentColor" />
                      <rect x="90" y="60" width="5" height="15" fill="currentColor" />
                      
                      {/* Interactive little center heart */}
                      <path d="M 50 50 L 53 47 A 2 2 0 0 1 57 51 L 50 58 L 43 51 A 2 2 0 0 1 47 47 Z" fill="#f43f5e" />
                    </svg>
                  </div>
                </div>

                {/* Decorative Serial code at the absolute bottom of the card */}
                <div className="pt-2 border-t border-gray-100 flex items-center justify-between text-[9px] text-gray-400 font-mono">
                  <span>MAGA SECURE SYSTEMS</span>
                  <span>IP-STAMP: {Date.now().toString().slice(-8)}</span>
                </div>
              </div>

            </div>

            {/* Screen 2 Buttons actions */}
            <div className="mt-8 flex flex-wrap gap-4 justify-center print:hidden">
              <button
                onClick={() => setScreen('form')}
                className="bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 font-semibold py-2.5 px-6 rounded-xl flex items-center gap-2 transition shadow-sm cursor-pointer"
              >
                <ArrowLeft className="w-4 h-4" />
                Registrar otro Padre
              </button>

              <button
                onClick={handlePrint}
                className="bg-maga-blue hover:bg-maga-light-blue text-white font-semibold py-2.5 px-6 rounded-xl flex items-center gap-2 transition shadow-md cursor-pointer"
              >
                <Printer className="w-4 h-4" />
                Imprimir Ticket
              </button>

              <button
                onClick={handleDownloadSimulation}
                className="bg-[#0f172a] hover:bg-[#1e293b] text-white font-semibold py-2.5 px-6 rounded-xl flex items-center gap-2 transition shadow-md cursor-pointer"
              >
                <Download className="w-4 h-4" />
                Guardar Ticket
              </button>

              <button
                onClick={() => setScreen('admin')}
                className="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-6 rounded-xl flex items-center gap-2 transition shadow-md cursor-pointer"
              >
                <LayoutDashboard className="w-4 h-4" />
                Ver Todos los Registros
              </button>
            </div>
          </motion.div>
        )}

        {/* SCREEN 3: ADMINISTRATIVE PANEL */}
        {screen === 'admin' && (
          <motion.div
            key="admin-panel"
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: 15 }}
            className="flex-grow max-w-6xl mx-auto w-full px-4 py-8"
          >
            {/* Admin Header */}
            <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
              <div>
                <div className="flex items-center gap-2 text-maga-light-blue font-bold text-sm uppercase tracking-wider">
                  <Database className="w-4 h-4" />
                  Módulo de Gestión y Estadísticas
                </div>
                <h1 className="text-3xl font-black text-maga-blue mt-1">Registros Día del Padre 2026</h1>
                <p className="text-gray-500 text-sm mt-1">
                  Control de participantes inscritos para la celebración de las direcciones de VISAR y Despacho Superior.
                </p>
              </div>

              <div className="flex flex-wrap gap-2.5">
                <button
                  onClick={handleResetData}
                  className="bg-white hover:bg-gray-100 text-gray-700 font-medium py-2 px-4 rounded-xl border border-gray-200 text-sm flex items-center gap-2 transition shadow-sm cursor-pointer"
                >
                  <Sparkles className="w-4 h-4 text-amber-500" />
                  Restaurar Prueba
                </button>

                <button
                  onClick={handleExportCSV}
                  className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl text-sm flex items-center gap-2 transition shadow-md cursor-pointer"
                >
                  <FileSpreadsheet className="w-4 h-4" />
                  Exportar a CSV
                </button>

                <button
                  onClick={() => setIsAddModalOpen(true)}
                  className="bg-maga-blue hover:bg-maga-light-blue text-white font-bold py-2 px-4 rounded-xl text-sm flex items-center gap-2 transition shadow-md cursor-pointer"
                >
                  <Plus className="w-4 h-4" />
                  Agregar Registro
                </button>
              </div>
            </div>

            {/* Statistics Dashboard Banner */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              {/* Stat 1 */}
              <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                  <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Registrados</span>
                  <span className="text-4xl font-black text-maga-blue mt-1 block">{statsTotal}</span>
                  <span className="text-xs text-gray-500 mt-1 block">Padres ingresados en el sistema</span>
                </div>
                <div className="w-14 h-14 bg-maga-bg-gray rounded-2xl flex items-center justify-center text-maga-blue border border-maga-blue/10">
                  <User className="w-7 h-7" />
                </div>
              </div>

              {/* Stat 2 */}
              <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div>
                  <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Mayor Participación</span>
                  <span className="text-xl font-extrabold text-maga-light-blue mt-2 block truncate max-w-[180px]">
                    {topDirection}
                  </span>
                  <span className="text-xs text-gray-500 mt-1 block">Dirección con más inscritos</span>
                </div>
                <div className="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600 border border-amber-200/50">
                  <Grid className="w-7 h-7" />
                </div>
              </div>

              {/* Stat 3 */}
              <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                <span className="text-xs font-semibold text-gray-400 uppercase tracking-wider block mb-2.5">Top Departamentos</span>
                <div className="space-y-2">
                  {sortedDepts.length > 0 ? (
                    sortedDepts.map(([dept, count]) => {
                      const pct = statsTotal > 0 ? ((count as number) / statsTotal) * 100 : 0;
                      return (
                        <div key={dept} className="text-xs">
                          <div className="flex justify-between font-semibold text-gray-700 mb-0.5">
                            <span>{dept}</span>
                            <span>{count} ({Math.round(pct)}%)</span>
                          </div>
                          <div className="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div className="bg-maga-light-blue h-full" style={{ width: `${pct}%` }}></div>
                          </div>
                        </div>
                      );
                    })
                  ) : (
                    <p className="text-xs text-gray-400 font-medium py-2">Sin datos disponibles</p>
                  )}
                </div>
              </div>
            </div>

            {/* Advanced Filters and Search row */}
            <div className="bg-white p-4 rounded-2xl border border-gray-200/80 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between mb-6">
              
              {/* Search */}
              <div className="relative w-full md:w-1/3">
                <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input 
                  type="text"
                  placeholder="Buscar por nombre, código o teléfono..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="w-full pl-10 pr-4 py-2 bg-gray-50/50 border border-gray-200 rounded-xl text-sm outline-none focus:bg-white focus:border-maga-blue transition-all"
                />
                {searchTerm && (
                  <button 
                    onClick={() => setSearchTerm('')} 
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                )}
              </div>

              {/* Direction Filter */}
              <div className="w-full md:w-1/4">
                <select
                  value={adminDirFilter}
                  onChange={(e) => setAdminDirFilter(e.target.value)}
                  className="w-full py-2 px-3 bg-gray-50/50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all"
                >
                  <option value="">Filtrar por Dirección (Todas)</option>
                  {DIRECTIONS.map(dir => (
                    <option key={dir} value={dir}>{dir}</option>
                  ))}
                </select>
              </div>

              {/* Department Filter */}
              <div className="w-full md:w-1/4">
                <select
                  value={adminDeptFilter}
                  onChange={(e) => setAdminDeptFilter(e.target.value)}
                  className="w-full py-2 px-3 bg-gray-50/50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:bg-white focus:border-maga-blue transition-all"
                >
                  <option value="">Filtrar por Departamento (Todos)</option>
                  {uniqueDepartments.map(dept => (
                    <option key={dept} value={dept}>{dept}</option>
                  ))}
                </select>
              </div>

              {/* Clear filters trigger */}
              {(searchTerm || adminDirFilter || adminDeptFilter) && (
                <button
                  onClick={() => {
                    setSearchTerm('');
                    setAdminDirFilter('');
                    setAdminDeptFilter('');
                  }}
                  className="text-xs font-bold text-rose-500 bg-rose-50 hover:bg-rose-100 py-2 px-4 rounded-xl transition"
                >
                  Limpiar Filtros
                </button>
              )}
            </div>

            {/* MAIN RECORDS TABLE */}
            <div className="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-sm">
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-maga-bg-gray border-b border-gray-200 text-xs font-bold text-maga-blue uppercase tracking-wider">
                      <th className="py-4 px-6">Código / Registro</th>
                      <th className="py-4 px-6">Nombre Completo</th>
                      <th className="py-4 px-6">Datos de Contacto</th>
                      <th className="py-4 px-6">Dirección / Depto</th>
                      <th className="py-4 px-6 text-center">Acciones</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-gray-100 text-sm">
                    {filteredRegistrations.length > 0 ? (
                      filteredRegistrations.map((reg) => (
                        <tr key={reg.id} className="hover:bg-gray-50/50 transition">
                          
                          {/* Code & timestamp */}
                          <td className="py-4 px-6">
                            <span className="font-mono text-xs font-extrabold text-maga-blue bg-maga-bg-gray px-2 py-1 rounded">
                              {reg.code}
                            </span>
                            <span className="block text-[11px] text-gray-400 mt-1">{reg.dateCreated}</span>
                          </td>

                          {/* Full Name */}
                          <td className="py-4 px-6 font-bold text-gray-800">
                            {reg.fullName}
                          </td>

                          {/* Contact */}
                          <td className="py-4 px-6">
                            <div className="flex items-center gap-1.5 text-gray-700 font-medium">
                              <Phone className="w-3.5 h-3.5 text-gray-400" />
                              <span>{reg.phone}</span>
                            </div>
                            <div className="flex items-center gap-1.5 text-gray-500 text-xs mt-1">
                              <Mail className="w-3.5 h-3.5 text-gray-400" />
                              <span>{reg.email}</span>
                            </div>
                          </td>

                          {/* Dept & Direction */}
                          <td className="py-4 px-6 max-w-[220px]">
                            <div className="text-xs font-bold text-maga-light-blue truncate" title={reg.direction}>
                              {reg.direction}
                            </div>
                            <div className="text-xs text-gray-400 mt-1 flex items-center gap-1">
                              <Map className="w-3 h-3 text-gray-400" />
                              {reg.department}
                            </div>
                          </td>

                          {/* Action triggers */}
                          <td className="py-4 px-6">
                            <div className="flex items-center justify-center gap-2">
                              <button
                                onClick={() => {
                                  setCurrentRegistration(reg);
                                  setScreen('success');
                                }}
                                className="p-1.5 hover:bg-gray-100 rounded text-maga-blue"
                                title="Ver ticket oficial"
                              >
                                <FileText className="w-4 h-4" />
                              </button>

                              <button
                                onClick={() => handleOpenEdit(reg)}
                                className="p-1.5 hover:bg-gray-100 rounded text-amber-600"
                                title="Editar registro"
                              >
                                <Edit className="w-4 h-4" />
                              </button>

                              <button
                                onClick={() => handleDeleteRegistration(reg.id, reg.fullName)}
                                className="p-1.5 hover:bg-rose-50 rounded text-rose-600"
                                title="Eliminar registro"
                              >
                                <Trash2 className="w-4 h-4" />
                              </button>
                            </div>
                          </td>

                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan={5} className="py-12 text-center text-gray-400">
                          <AlertCircle className="w-8 h-8 text-gray-300 mx-auto mb-2" />
                          <p className="font-semibold text-gray-500">No se encontraron registros</p>
                          <p className="text-xs mt-1">Intente cambiar el término de búsqueda o filtros.</p>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </div>

              {/* Table footer count */}
              <div className="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500 font-semibold">
                <span>Mostrando {filteredRegistrations.length} de {registrations.length} registros</span>
                <span>Filtro activo: {adminDirFilter || adminDeptFilter || searchTerm ? 'Sí' : 'No'}</span>
              </div>
            </div>

          </motion.div>
        )}

      </AnimatePresence>

      {/* POPUP MODAL: EDIT REGISTRATION */}
      <AnimatePresence>
        {isEditingModalOpen && editingReg && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsEditingModalOpen(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            ></motion.div>

            {/* Modal Body */}
            <motion.div
              initial={{ scale: 0.95, y: 15, opacity: 0 }}
              animate={{ scale: 1, y: 0, opacity: 1 }}
              exit={{ scale: 0.95, y: 15, opacity: 0 }}
              className="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 shadow-2xl relative z-10 border border-gray-100"
            >
              <button 
                onClick={() => setIsEditingModalOpen(false)}
                className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1 transition"
              >
                <X className="w-4 h-4" />
              </button>

              <h3 className="text-xl font-bold text-maga-blue mb-2 flex items-center gap-2">
                <Edit className="w-5 h-5 text-maga-light-blue" />
                Editar Registro del Padre
              </h3>
              <p className="text-xs text-gray-400 font-semibold uppercase tracking-wide font-mono mb-6 bg-maga-bg-gray inline-block px-2 py-0.5 rounded">
                Código: {editingReg.code}
              </p>

              <form onSubmit={handleSaveEdit} className="space-y-4 text-sm">
                
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Nombre Completo</label>
                  <input 
                    type="text"
                    value={editingReg.fullName}
                    onChange={(e) => setEditingReg(prev => prev ? ({ ...prev, fullName: e.target.value }) : null)}
                    className="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none transition-all font-semibold text-gray-800"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Teléfono</label>
                  <input 
                    type="tel"
                    value={editingReg.phone}
                    onChange={(e) => setEditingReg(prev => prev ? ({ ...prev, phone: e.target.value }) : null)}
                    className="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none transition-all font-semibold text-gray-800"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Correo Electrónico</label>
                  <input 
                    type="email"
                    value={editingReg.email}
                    onChange={(e) => setEditingReg(prev => prev ? ({ ...prev, email: e.target.value }) : null)}
                    className="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none transition-all font-semibold text-gray-800"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Dirección de Origen</label>
                  <select
                    value={editingReg.direction}
                    onChange={(e) => setEditingReg(prev => prev ? ({ ...prev, direction: e.target.value }) : null)}
                    className="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none transition-all font-semibold text-gray-800 cursor-pointer"
                  >
                    {DIRECTIONS.map(dir => (
                      <option key={dir} value={dir}>{dir}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Departamento</label>
                  <input
                    type="text"
                    value={editingReg.department}
                    onChange={(e) => setEditingReg(prev => prev ? ({ ...prev, department: e.target.value }) : null)}
                    className="w-full border border-gray-200 rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white focus:border-maga-blue outline-none transition-all font-semibold text-gray-800"
                    placeholder="Escriba el departamento"
                  />
                </div>

                <div className="flex gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setIsEditingModalOpen(false)}
                    className="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    className="w-1/2 bg-maga-blue hover:bg-maga-light-blue text-white py-2.5 rounded-xl font-bold transition shadow-md"
                  >
                    Guardar Cambios
                  </button>
                </div>

              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* POPUP MODAL: ADD MANUAL REGISTRATION */}
      <AnimatePresence>
        {isAddModalOpen && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <motion.div 
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setIsAddModalOpen(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
            ></motion.div>

            {/* Modal Body */}
            <motion.div
              initial={{ scale: 0.95, y: 15, opacity: 0 }}
              animate={{ scale: 1, y: 0, opacity: 1 }}
              exit={{ scale: 0.95, y: 15, opacity: 0 }}
              className="bg-white rounded-3xl w-full max-w-lg p-6 md:p-8 shadow-2xl relative z-10 border border-gray-100"
            >
              <button 
                onClick={() => setIsAddModalOpen(false)}
                className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-1 transition"
              >
                <X className="w-4 h-4" />
              </button>

              <h3 className="text-xl font-bold text-maga-blue mb-4 flex items-center gap-2">
                <Plus className="w-5 h-5 text-emerald-600" />
                Registrar Nuevo Padre (Manual)
              </h3>

              <form onSubmit={handleCreateAdminRecord} className="space-y-4 text-sm">
                
                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Nombre Completo *</label>
                  <input 
                    type="text"
                    value={newAdminName}
                    onChange={(e) => {
                      setNewAdminName(e.target.value);
                      if (adminFormErrors.fullName) setAdminFormErrors(prev => ({ ...prev, fullName: '' }));
                    }}
                    className={`w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none transition-all ${
                      adminFormErrors.fullName ? 'border-rose-400' : 'border-gray-200 focus:border-maga-blue'
                    }`}
                    placeholder="Escriba el nombre completo"
                  />
                  {adminFormErrors.fullName && <span className="text-rose-500 text-[11px] mt-1 block">{adminFormErrors.fullName}</span>}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Teléfono *</label>
                  <input 
                    type="tel"
                    value={newAdminPhone}
                    onChange={(e) => {
                      setNewAdminPhone(e.target.value);
                      if (adminFormErrors.phone) setAdminFormErrors(prev => ({ ...prev, phone: '' }));
                    }}
                    className={`w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none transition-all ${
                      adminFormErrors.phone ? 'border-rose-400' : 'border-gray-200 focus:border-maga-blue'
                    }`}
                    placeholder="Ej. 55224411"
                  />
                  {adminFormErrors.phone && <span className="text-rose-500 text-[11px] mt-1 block">{adminFormErrors.phone}</span>}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Correo Electrónico *</label>
                  <input 
                    type="email"
                    value={newAdminEmail}
                    onChange={(e) => {
                      setNewAdminEmail(e.target.value);
                      if (adminFormErrors.email) setAdminFormErrors(prev => ({ ...prev, email: '' }));
                    }}
                    className={`w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none transition-all ${
                      adminFormErrors.email ? 'border-rose-400' : 'border-gray-200 focus:border-maga-blue'
                    }`}
                    placeholder="correo@ejemplo.com"
                  />
                  {adminFormErrors.email && <span className="text-rose-500 text-[11px] mt-1 block">{adminFormErrors.email}</span>}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Dirección de Origen *</label>
                  <select
                    value={newAdminDir}
                    onChange={(e) => {
                      setNewAdminDir(e.target.value);
                      if (adminFormErrors.direction) setAdminFormErrors(prev => ({ ...prev, direction: '' }));
                    }}
                    className={`w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none transition-all cursor-pointer ${
                      adminFormErrors.direction ? 'border-rose-400' : 'border-gray-200 focus:border-maga-blue'
                    }`}
                  >
                    <option value="">Selecciona una dirección</option>
                    {DIRECTIONS.map(dir => (
                      <option key={dir} value={dir}>{dir}</option>
                    ))}
                  </select>
                  {adminFormErrors.direction && <span className="text-rose-500 text-[11px] mt-1 block">{adminFormErrors.direction}</span>}
                </div>

                <div>
                  <label className="block text-xs font-bold text-gray-700 mb-1">Departamento *</label>
                  <input
                    type="text"
                    value={newAdminDept}
                    onChange={(e) => {
                      setNewAdminDept(e.target.value);
                      if (adminFormErrors.department) setAdminFormErrors(prev => ({ ...prev, department: '' }));
                    }}
                    className={`w-full border rounded-xl py-2.5 px-3 bg-gray-50 focus:bg-white outline-none transition-all ${
                      adminFormErrors.department ? 'border-rose-400' : 'border-gray-200 focus:border-maga-blue'
                    }`}
                    placeholder="Escriba el departamento"
                  />
                  {adminFormErrors.department && <span className="text-rose-500 text-[11px] mt-1 block">{adminFormErrors.department}</span>}
                </div>

                <div className="flex gap-3 pt-4">
                  <button
                    type="button"
                    onClick={() => setIsAddModalOpen(false)}
                    className="w-1/2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-xl font-bold transition"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    className="w-1/2 bg-maga-blue hover:bg-maga-light-blue text-white py-2.5 rounded-xl font-bold transition shadow-md"
                  >
                    Guardar Registro
                  </button>
                </div>

              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Special styles for Print View Optimization */}
      <style>{`
        @media print {
          body {
            background-color: white !important;
            color: black !important;
          }
          header, footer, nav, button, .print\\:hidden, select, input, .fixed {
            display: none !important;
          }
          .print\\:border-none {
            border: none !important;
          }
          .print\\:shadow-none {
            box-shadow: none !important;
          }
          .print\\:my-0 {
            margin: 0 !important;
            padding: 0 !important;
          }
          main {
            margin-top: 0 !important;
            padding: 0 !important;
          }
        }
      `}</style>
    </div>
  );
}
