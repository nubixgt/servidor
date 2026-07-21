export const ROUNDS = [
  { key: 'FASHION_SHOW', label: 'Fashion Show' },
  { key: 'COREOGRAFIA', label: 'Coreografía' },
  { key: 'GALA', label: 'Gala' },
];

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
          ? { key: 'pregunta', label: 'Pregunta' }
          : { key: 'elegancia', label: 'Elegancia' },
      ];
    default:
      return [];
  }
}

export function initialScores(category) {
  const scores = {};
  ROUNDS.forEach(({ key }) => {
    const roundScores = { total: 0 };
    getRubrics(key, category).forEach((r) => {
      roundScores[r.key] = 0;
    });
    scores[key] = roundScores;
  });
  return scores;
}

// Puntaje final = promedio de los totales de las rondas ya calificadas.
// Las rondas sin calificar (total 0) no cuentan todavía en el promedio.
export function computeFinalScore(participant) {
  const totals = ROUNDS.map((r) => participant.scores[r.key].total).filter((t) => t > 0);
  if (totals.length === 0) return 0;
  return parseFloat((totals.reduce((a, b) => a + b, 0) / totals.length).toFixed(2));
}

export function roundsCompleted(participant) {
  return ROUNDS.filter((r) => participant.scores[r.key].total > 0).length;
}
