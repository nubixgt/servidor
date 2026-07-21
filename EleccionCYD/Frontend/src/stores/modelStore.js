import { defineStore } from 'pinia';
import { INITIAL_PARTICIPANTS } from '../data/participants';
import { ROUNDS, getRubrics, computeFinalScore } from '../utils/rubrics';

const STORAGE_KEY = 'eleccioncyd_participants';

// El nombre, foto y categoría de cada participante SIEMPRE vienen del código (participants.js),
// para que cualquier corrección (foto nueva, nombre corregido, etc.) se refleje de inmediato
// sin depender de que el jurado borre el localStorage de su navegador.
// Solo las calificaciones (scores) ya guardadas se preservan, buscadas por id.
function loadModels() {
  let saved = [];
  try {
    saved = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
  } catch {
    saved = [];
  }
  const savedScoresById = Object.fromEntries(saved.map((m) => [m.id, m.scores]));
  return INITIAL_PARTICIPANTS.map((participant) => ({
    ...participant,
    scores: savedScoresById[participant.id] || participant.scores,
  }));
}

export const useModelStore = defineStore('model', {
  state: () => ({
    models: loadModels(),
    selectedModelId: null,
    activeRound: 'FASHION_SHOW',
  }),
  getters: {
    selectedModel: (state) => state.models.find((m) => m.id === state.selectedModelId) || null,
    senoritas: (state) => state.models.filter((m) => m.category === 'SENORITA'),
    jovenes: (state) => state.models.filter((m) => m.category === 'JOVEN'),
    rankedSenoritas: (state) =>
      [...state.models.filter((m) => m.category === 'SENORITA')].sort(
        (a, b) => computeFinalScore(b) - computeFinalScore(a)
      ),
    rankedJovenes: (state) =>
      [...state.models.filter((m) => m.category === 'JOVEN')].sort(
        (a, b) => computeFinalScore(b) - computeFinalScore(a)
      ),
  },
  actions: {
    setSelectedModel(id) {
      this.selectedModelId = id;
    },
    setActiveRound(roundKey) {
      this.activeRound = roundKey;
    },
    updateRoundScores(modelId, roundKey, roundScores) {
      const model = this.models.find((m) => m.id === modelId);
      if (model) {
        model.scores[roundKey] = roundScores;
        this.saveModels();
      }
    },
    saveModels() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.models));
    },
    finalizeAllScores() {
      this.models.forEach((m) => {
        ROUNDS.forEach(({ key }) => {
          const round = m.scores[key];
          if (round.total === 0) {
            const rubricKeys = getRubrics(key, m.category).map((r) => r.key);
            let sum = 0;
            rubricKeys.forEach((rk) => {
              const val = parseFloat((8.5 + Math.random() * 1.5).toFixed(1));
              round[rk] = val;
              sum += val;
            });
            round.total = parseFloat((sum / rubricKeys.length).toFixed(2));
          }
        });
      });
      this.saveModels();
    },
  },
});
