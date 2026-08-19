import React, { useState } from 'react';
import { 
  Check, 
  MapPin, 
  Sliders, 
  Upload, 
  Compass, 
  Trees, 
  ArrowLeft, 
  ArrowRight, 
  Camera, 
  FileCheck2
} from 'lucide-react';
import { Parcela } from '../types';
import { GUATEMALA_DEPARTMENTS } from '../data/initialData';

interface PlotRegistrationWizardProps {
  onSaveParcel: (newParcel: Parcela) => void;
  onCancel: () => void;
}

export const PlotRegistrationWizard: React.FC<PlotRegistrationWizardProps> = ({
  onSaveParcel,
  onCancel
}) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [isCapturingGps, setIsCapturingGps] = useState(false);
  const [gpsCaptured, setGpsCaptured] = useState(false);

  // Form State
  const [formData, setFormData] = useState({
    name: '',
    producer: '',
    department: 'Alta Verapaz',
    municipality: 'Cobán',
    community: '',
    benefitedFamilies: 12,
    areaHa: 25.0,
    latitude: 15.4725,
    longitude: -90.3708,
    accuracyMeters: 3.5,
    
    // Step 2: Soil & Water
    soilTexture: 'Franco-arcilloso',
    erosionLevel: 'Moderada' as 'Leve' | 'Moderada' | 'Severa',
    slopeDegrees: 15,
    arableDepthCm: 45,
    moistureRetention: 'Alta (65%)',
    hasWaterSources: true,
    
    // Step 3: Keyline
    keylinePractice: 'Zanjas de infiltración (Swales)',
    lineSpacingMeters: 6,
    totalLengthMeters: 2800,
    associatedSpecies: ['Café Arábica', 'Inga edulis (Cuje)', 'Vetiver'],
    
    // Step 4: Photos & Technician
    technicianName: 'Ana Martínez Valdés',
    notes: '',
    photos: [] as string[]
  });

  const availableMunicipalities = GUATEMALA_DEPARTMENTS[formData.department] || ['Cabecera'];

  const handleCaptureGps = () => {
    setIsCapturingGps(true);
    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          setFormData(prev => ({
            ...prev,
            latitude: Number(pos.coords.latitude.toFixed(6)),
            longitude: Number(pos.coords.longitude.toFixed(6)),
            accuracyMeters: Number((pos.coords.accuracy || 3.0).toFixed(1))
          }));
          setIsCapturingGps(false);
          setGpsCaptured(true);
        },
        () => {
          const randomLat = 15.4000 + (Math.random() * 0.1);
          const randomLon = -90.3500 - (Math.random() * 0.1);
          setFormData(prev => ({
            ...prev,
            latitude: Number(randomLat.toFixed(6)),
            longitude: Number(randomLon.toFixed(6)),
            accuracyMeters: 2.8
          }));
          setIsCapturingGps(false);
          setGpsCaptured(true);
        },
        { timeout: 5000 }
      );
    } else {
      setIsCapturingGps(false);
      setGpsCaptured(true);
    }
  };

  const handlePhotoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      const newPhotos = Array.from(e.target.files).map((file: File) => URL.createObjectURL(file));
      setFormData(prev => ({
        ...prev,
        photos: [...prev.photos, ...newPhotos]
      }));
    }
  };

  const toggleSpecies = (species: string) => {
    setFormData(prev => {
      const exists = prev.associatedSpecies.includes(species);
      return {
        ...prev,
        associatedSpecies: exists
          ? prev.associatedSpecies.filter(s => s !== species)
          : [...prev.associatedSpecies, species]
      };
    });
  };

  const handleFinalSubmit = () => {
    const randomIdNumber = Math.floor(Math.random() * 900 + 100);
    const code = `PLT-2024-${randomIdNumber}`;
    
    const newParcel: Parcela = {
      id: code,
      code: code,
      name: formData.name || 'Finca Vista Hermosa',
      producer: formData.producer || 'Productor Comunitario',
      department: formData.department,
      municipality: formData.municipality,
      community: formData.community || 'Aldea Central',
      benefitedFamilies: Number(formData.benefitedFamilies),
      registrationDate: new Date().toISOString().split('T')[0],
      areaHa: Number(formData.areaHa),
      latitude: formData.latitude,
      longitude: formData.longitude,
      accuracyMeters: formData.accuracyMeters,
      technicianName: formData.technicianName,
      status: 'En Revisión',
      validationTag: 'Pendiente Val.',
      soilTexture: formData.soilTexture,
      erosionLevel: formData.erosionLevel,
      slopeDegrees: Number(formData.slopeDegrees),
      arableDepthCm: Number(formData.arableDepthCm),
      moistureRetention: formData.moistureRetention,
      hasWaterSources: formData.hasWaterSources,
      keylinePractice: formData.keylinePractice,
      lineSpacingMeters: Number(formData.lineSpacingMeters),
      totalLengthMeters: Number(formData.totalLengthMeters),
      associatedSpecies: formData.associatedSpecies,
      bioindicators: ['Lombrices', 'Estructura granular'],
      photos: formData.photos.length > 0 ? formData.photos : [
        'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&auto=format&fit=crop&q=80'
      ],
      notes: formData.notes
    };

    onSaveParcel(newParcel);
  };

  const steps = [
    { num: 1, title: 'Ubicación & Datos', desc: 'Identificación y GPS' },
    { num: 2, title: 'Suelo y Agua', desc: 'Textura y pendiente' },
    { num: 3, title: 'Diseño Keyline', desc: 'Líneas y prácticas' },
    { num: 4, title: 'Fotos & Cierre', desc: 'Evidencias de campo' }
  ];

  const speciesOptions = [
    'Café Arábica', 'Inga edulis (Cuje)', 'Vetiver', 'Crotalaria', 
    'Aguacate Hass', 'Cardamomo', 'Canavalia', 'Aliso', 'Pino Ocote', 'Matilisguate'
  ];

  return (
    <div className="max-w-4xl mx-auto space-y-6 animate-fadeIn pb-12">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-white tracking-tight">Registrar Nueva Parcela</h2>
          <p className="text-xs text-[#cbd5e1] mt-0.5">
            Formulario técnico de levantamiento en campo para diseño hidrológico Keyline.
          </p>
        </div>

        <button
          onClick={onCancel}
          className="text-xs text-[#cbd5e1] hover:text-white px-3.5 py-1.5 rounded-xl border border-white/15 hover:bg-white/5 transition-colors"
        >
          Cancelar
        </button>
      </div>

      {/* 4 Steps Visual Timeline */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        {steps.map((step) => {
          const isDone = currentStep > step.num;
          const isCurrent = currentStep === step.num;

          return (
            <div
              key={step.num}
              onClick={() => setCurrentStep(step.num)}
              className={`p-3 rounded-2xl border transition-all cursor-pointer ${
                isCurrent
                  ? 'glass-panel border-[#4ade80]/60 shadow-[0_0_15px_rgba(74,222,128,0.2)]'
                  : isDone
                  ? 'bg-black/30 border-[#4ade80]/30'
                  : 'bg-black/20 border-white/10 opacity-60'
              }`}
            >
              <div className="flex items-center gap-2 mb-1">
                <span
                  className={`w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold ${
                    isDone
                      ? 'bg-[#22c55e] text-white'
                      : isCurrent
                      ? 'bg-[#4ade80]/20 text-[#4ade80] border border-[#4ade80]'
                      : 'bg-white/10 text-[#94a3b8]'
                  }`}
                >
                  {isDone ? <Check className="w-3.5 h-3.5" /> : step.num}
                </span>
                <span className="text-xs font-bold text-white truncate">{step.title}</span>
              </div>
              <p className="text-[10px] text-[#94a3b8] truncate pl-8">{step.desc}</p>
            </div>
          );
        })}
      </div>

      {/* Wizard Content Card */}
      <div className="glass-panel rounded-2xl p-6 border border-white/15">
        {/* STEP 1: Ubicación & Datos Generales */}
        {currentStep === 1 && (
          <div className="space-y-5 animate-fadeIn">
            <h3 className="text-base font-bold text-white flex items-center gap-2 border-b border-white/10 pb-3">
              <MapPin className="w-5 h-5 text-[#38bdf8]" />
              <span>Paso 1: Identificación y Ubicación Georreferenciada</span>
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Nombre del Predio / Parcela</label>
                <input
                  type="text"
                  required
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="ej. Finca Buena Vista"
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white"
                />
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Nombre del Productor / Titular</label>
                <input
                  type="text"
                  required
                  value={formData.producer}
                  onChange={(e) => setFormData({ ...formData, producer: e.target.value })}
                  placeholder="ej. Juan Caal Pop"
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Departamento</label>
                <select
                  value={formData.department}
                  onChange={(e) => setFormData({ ...formData, department: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
                >
                  {Object.keys(GUATEMALA_DEPARTMENTS).map((d) => (
                    <option key={d} value={d}>{d}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Municipio</label>
                <select
                  value={formData.municipality}
                  onChange={(e) => setFormData({ ...formData, municipality: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
                >
                  {availableMunicipalities.map((m) => (
                    <option key={m} value={m}>{m}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Comunidad / Aldea</label>
                <input
                  type="text"
                  value={formData.community}
                  onChange={(e) => setFormData({ ...formData, community: e.target.value })}
                  placeholder="ej. San Juan Chamelco"
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white"
                />
              </div>
            </div>

            {/* GPS Section */}
            <div className="bg-black/30 p-4 rounded-xl border border-white/10 space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-xs font-bold text-white flex items-center gap-1.5">
                  <Compass className="w-4 h-4 text-[#4ade80]" />
                  <span>Coordenadas GPS (WGS84)</span>
                </span>

                <button
                  type="button"
                  onClick={handleCaptureGps}
                  disabled={isCapturingGps}
                  className="px-3.5 py-1.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all shadow-sm"
                >
                  <MapPin className="w-3.5 h-3.5" />
                  <span>{isCapturingGps ? 'Capturando GPS...' : gpsCaptured ? '✓ GPS Actualizado' : 'Capturar GPS Actual'}</span>
                </button>
              </div>

              <div className="grid grid-cols-3 gap-3 text-xs">
                <div>
                  <span className="text-[10px] text-[#94a3b8] uppercase font-bold block mb-1">Latitud</span>
                  <input
                    type="number"
                    step="0.000001"
                    value={formData.latitude}
                    onChange={(e) => setFormData({ ...formData, latitude: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs font-mono"
                  />
                </div>
                <div>
                  <span className="text-[10px] text-[#94a3b8] uppercase font-bold block mb-1">Longitud</span>
                  <input
                    type="number"
                    step="0.000001"
                    value={formData.longitude}
                    onChange={(e) => setFormData({ ...formData, longitude: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs font-mono"
                  />
                </div>
                <div>
                  <span className="text-[10px] text-[#94a3b8] uppercase font-bold block mb-1">Precisión (m)</span>
                  <input
                    type="number"
                    value={formData.accuracyMeters}
                    onChange={(e) => setFormData({ ...formData, accuracyMeters: Number(e.target.value) })}
                    className="w-full glass-input rounded-xl p-2 text-xs font-mono"
                  />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* STEP 2: Suelo y Agua */}
        {currentStep === 2 && (
          <div className="space-y-5 animate-fadeIn">
            <h3 className="text-base font-bold text-white flex items-center gap-2 border-b border-white/10 pb-3">
              <Trees className="w-5 h-5 text-[#facc15]" />
              <span>Paso 2: Diagnóstico Edáfico, Topografía e Hidrología</span>
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Textura del Suelo</label>
                <select
                  value={formData.soilTexture}
                  onChange={(e) => setFormData({ ...formData, soilTexture: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
                >
                  <option value="Franco">Franco</option>
                  <option value="Franco-arcilloso">Franco-arcilloso</option>
                  <option value="Franco-arenoso">Franco-arenoso</option>
                  <option value="Arcilloso">Arcilloso</option>
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Grado de Pendiente Media (°)</label>
                <input
                  type="number"
                  value={formData.slopeDegrees}
                  onChange={(e) => setFormData({ ...formData, slopeDegrees: Number(e.target.value) })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs font-mono"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Nivel de Erosión Previo</label>
                <select
                  value={formData.erosionLevel}
                  onChange={(e) => setFormData({ ...formData, erosionLevel: e.target.value as any })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
                >
                  <option value="Leve">Leve (Laminar incipiente)</option>
                  <option value="Moderada">Moderada (Regueros visibles)</option>
                  <option value="Severa">Severa (Cárcavas formadas)</option>
                </select>
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Profundidad Efectiva Arable (cm)</label>
                <input
                  type="number"
                  value={formData.arableDepthCm}
                  onChange={(e) => setFormData({ ...formData, arableDepthCm: Number(e.target.value) })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs font-mono"
                />
              </div>
            </div>
          </div>
        )}

        {/* STEP 3: Keyline */}
        {currentStep === 3 && (
          <div className="space-y-5 animate-fadeIn">
            <h3 className="text-base font-bold text-white flex items-center gap-2 border-b border-white/10 pb-3">
              <Sliders className="w-5 h-5 text-[#4ade80]" />
              <span>Paso 3: Parámetros del Diseño Hidrológico Keyline</span>
            </h3>

            <div>
              <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Práctica Keyline Seleccionada</label>
              <select
                value={formData.keylinePractice}
                onChange={(e) => setFormData({ ...formData, keylinePractice: e.target.value })}
                className="w-full glass-input rounded-xl p-2.5 text-xs text-white bg-black/80"
              >
                <option value="Zanjas de infiltración (Swales)">Zanjas de infiltración a nivel (Swales)</option>
                <option value="Terrazas de contorno">Terrazas de contorno con talud vegetado</option>
                <option value="Subsolado en curva de nivel">Subsolado en patrón Keyline</option>
                <option value="Siembra agroforestal en curva de nivel">Siembra agroforestal en curva de nivel</option>
              </select>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Espaciamiento entre Líneas (m)</label>
                <input
                  type="number"
                  value={formData.lineSpacingMeters}
                  onChange={(e) => setFormData({ ...formData, lineSpacingMeters: Number(e.target.value) })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs font-mono"
                />
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Longitud Total Proyectada (metros lineales)</label>
                <input
                  type="number"
                  value={formData.totalLengthMeters}
                  onChange={(e) => setFormData({ ...formData, totalLengthMeters: Number(e.target.value) })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs font-mono"
                />
              </div>
            </div>

            <div>
              <label className="text-xs font-medium text-[#cbd5e1] block mb-2">
                Especies Forestales y Coberturas Vegetales Asociadas
              </label>
              <div className="flex flex-wrap gap-2">
                {speciesOptions.map((sp) => {
                  const isSelected = formData.associatedSpecies.includes(sp);
                  return (
                    <button
                      key={sp}
                      type="button"
                      onClick={() => toggleSpecies(sp)}
                      className={`text-xs px-3 py-1.5 rounded-full border transition-all ${
                        isSelected
                          ? 'bg-[#22c55e] text-white border-[#4ade80] font-semibold'
                          : 'bg-black/30 border-white/10 text-[#cbd5e1] hover:text-white hover:bg-white/5'
                      }`}
                    >
                      {isSelected ? '✓ ' : '+ '}{sp}
                    </button>
                  );
                })}
              </div>
            </div>
          </div>
        )}

        {/* STEP 4: Fotos y Cierre */}
        {currentStep === 4 && (
          <div className="space-y-5 animate-fadeIn">
            <h3 className="text-base font-bold text-white flex items-center gap-2 border-b border-white/10 pb-3">
              <Camera className="w-5 h-5 text-[#38bdf8]" />
              <span>Paso 4: Registro Fotográfico y Validación</span>
            </h3>

            <div>
              <label className="text-xs font-medium text-[#cbd5e1] block mb-2">Fotografías del Levantamiento</label>
              <label className="border-2 border-dashed border-white/20 hover:border-[#4ade80] rounded-2xl p-6 flex flex-col items-center justify-center cursor-pointer bg-black/30 transition-colors">
                <Upload className="w-8 h-8 text-[#4ade80] mb-2" />
                <span className="text-xs font-bold text-white">Haz clic o arrastra fotos de campo</span>
                <span className="text-[10px] text-[#94a3b8] mt-1">Soporta JPG, PNG de alta resolución</span>
                <input
                  type="file"
                  multiple
                  accept="image/*"
                  onChange={handlePhotoUpload}
                  className="hidden"
                />
              </label>

              {formData.photos.length > 0 && (
                <div className="grid grid-cols-3 sm:grid-cols-4 gap-3 mt-3">
                  {formData.photos.map((src, idx) => (
                    <div key={idx} className="relative h-20 rounded-xl overflow-hidden border border-white/10">
                      <img src={src} alt="Field preview" className="w-full h-full object-cover" />
                    </div>
                  ))}
                </div>
              )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Técnico Responsable</label>
                <input
                  type="text"
                  value={formData.technicianName}
                  onChange={(e) => setFormData({ ...formData, technicianName: e.target.value })}
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white"
                />
              </div>

              <div>
                <label className="text-xs font-medium text-[#cbd5e1] block mb-1">Observaciones Técnicas</label>
                <textarea
                  rows={2}
                  value={formData.notes}
                  onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                  placeholder="Detalles sobre infiltración, estabilidad de taludes..."
                  className="w-full glass-input rounded-xl p-2.5 text-xs text-white resize-none"
                />
              </div>
            </div>

            <div className="p-4 bg-white/10 border border-white/15 rounded-xl flex items-center gap-3">
              <FileCheck2 className="w-6 h-6 text-[#4ade80] flex-shrink-0" />
              <div className="text-xs text-[#f1f5f9]">
                <p className="font-bold text-white">Listo para envío de ficha técnica</p>
                <p className="text-[11px] text-[#cbd5e1]">
                  La parcela se guardará en la base con estatus de revisión y geolocalización satelital.
                </p>
              </div>
            </div>
          </div>
        )}

        {/* Footer Navigation Buttons */}
        <div className="mt-8 pt-4 border-t border-white/10 flex justify-between items-center">
          <button
            type="button"
            disabled={currentStep === 1}
            onClick={() => setCurrentStep(prev => prev - 1)}
            className="flex items-center gap-1.5 px-4 py-2 bg-white/10 hover:bg-white/20 rounded-xl text-xs font-semibold text-white disabled:opacity-30 disabled:cursor-not-allowed transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>Anterior</span>
          </button>

          {currentStep < 4 ? (
            <button
              type="button"
              onClick={() => setCurrentStep(prev => prev + 1)}
              className="flex items-center gap-1.5 px-5 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-bold transition-all shadow-md"
            >
              <span>Siguiente</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          ) : (
            <button
              type="button"
              onClick={handleFinalSubmit}
              className="flex items-center gap-1.5 px-6 py-2.5 bg-[#22c55e] hover:bg-[#16a34a] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg"
            >
              <Check className="w-4 h-4" />
              <span>Finalizar y Guardar</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
};
