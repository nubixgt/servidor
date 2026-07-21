export const ROUNDS = [
  { key: 'FASHION_SHOW', label: 'Fashion Show' },
  { key: 'COREOGRAFIA', label: 'Coreografía' },
  { key: 'GALA', label: 'Gala' },
];

// Las "key" de cada rubro coinciden exactamente con las columnas de la tabla de esa ronda en el Backend
// (Backend/src/Utils/RondaConfig.php) -- si cambia un nombre aquí, cambia allá también.
export function getRubrics(roundKey, category) {
  switch (roundKey) {
    case 'FASHION_SHOW':
      return [
        { key: 'originalidad', label: 'Originalidad' },
        { key: 'presentacion', label: 'Presentación' },
        { key: 'coordinacion', label: 'Coordinación' },
      ];
    case 'COREOGRAFIA':
      return [
        { key: 'coordinacion', label: 'Coordinación' },
        { key: 'ritmo', label: 'Ritmo' },
        { key: 'desplazamiento', label: 'Desplazamiento' },
      ];
    case 'GALA':
      return [
        { key: 'modelaje', label: 'Modelaje' },
        { key: 'seguridad', label: 'Seguridad' },
        category === 'SENORITA'
          ? { key: 'pregunta_o_elegancia', label: 'Pregunta' }
          : { key: 'pregunta_o_elegancia', label: 'Elegancia' },
      ];
    default:
      return [];
  }
}
