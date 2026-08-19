// Catálogos del sistema KeylineGT (departamentos, estados, tipos...).
// Réplica de Diseno/src/constants.js — si el backend expone estos catálogos
// dinámicamente en el futuro, este archivo puede sustituirse por una llamada a la API.
export const DEPARTAMENTOS = [
    'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula', 'El Progreso',
    'Escuintla', 'Guatemala', 'Huehuetenango', 'Izabal', 'Jalapa', 'Jutiapa',
    'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu', 'Sacatepéquez',
    'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez', 'Totonicapán', 'Zacapa',
];

export const ESTADOS_PROCESO = ['Levantamiento', 'Diseño', 'Implementado', 'Pendiente'];
export const ESTADOS_VALIDACION = ['Pendiente de revisión', 'Validado', 'Requiere corrección'];
export const USOS_ACTUALES = ['Agrícola', 'Ganadero', 'Agroforestal', 'Forestal', 'Mixto', 'Otro'];
export const NIVELES_AGUA = ['Alta', 'Media', 'Baja', 'Estacional'];
export const RIESGO_EROSION = ['Alto', 'Medio', 'Bajo'];
export const SI_NO_VACIO = ['', 'No', 'Sí'];
export const TENENCIA_TIERRA = ['Propia', 'Arrendada', 'Comunal', 'Prestada', 'Otro'];
export const FUENTE_AGUA = ['Río o quebrada', 'Pozo', 'Nacimiento', 'Lluvia (temporal)', 'Sistema municipal', 'Otro'];
export const ROLES = ['tecnico', 'supervisor', 'administrador'];

export const ROLE_LABELS = {
    tecnico: 'Técnico de campo',
    supervisor: 'Supervisor regional',
    administrador: 'Administrador',
};

export const ESTADO_COLORS = {
    Levantamiento: 'bg-sky-100 text-sky-700',
    'Diseño': 'bg-amber-100 text-amber-700',
    Implementado: 'bg-emerald-100 text-emerald-700',
    Pendiente: 'bg-rose-100 text-rose-700',
};

export const VALIDACION_COLORS = {
    'Pendiente de revisión': 'bg-amber-100 text-amber-700',
    Validado: 'bg-emerald-100 text-emerald-700',
    'Requiere corrección': 'bg-rose-100 text-rose-700',
};
