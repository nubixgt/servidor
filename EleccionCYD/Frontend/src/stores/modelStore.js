import { defineStore } from 'pinia';
import participantesService from '../services/participantesService';
import calificacionesService from '../services/calificacionesService';
import { PARTICIPANT_PHOTOS } from '../data/participantPhotos';
import { ROUNDS, getRubrics } from '../utils/rubrics';

function mergeLeaderboard(state, categoria) {
  // El Backend ya entrega el leaderboard ordenado por total_final; filtrar conserva ese orden.
  return state.leaderboard
    .filter((entry) => entry.categoria === categoria)
    .map((entry) => {
      const participant = state.participants.find((p) => p.id === entry.id);
      return {
        id: entry.id,
        name: entry.nombre,
        category: entry.categoria,
        imageUrl: participant?.imageUrl,
        avatarUrl: participant?.avatarUrl,
        podiumUrl: participant?.podiumUrl,
        rondas: entry.rondas,
        totalFinal: entry.total_final,
      };
    });
}

export const useModelStore = defineStore('model', {
  state: () => ({
    participants: [],
    // Las calificaciones que ESTE jurado (el que tiene la sesión abierta) ya metió, por ronda,
    // indexadas por participante_id. No es un total compartido -- cada jurado ve solo lo suyo.
    myScores: {
      FASHION_SHOW: {},
      COREOGRAFIA: {},
      GALA: {},
    },
    // Ranking ya agregado (suma entre jurados) que devuelve el Backend.
    leaderboard: [],
    selectedParticipantId: null,
    activeRound: 'FASHION_SHOW',
    initialized: false,
    loadingLeaderboard: false,
  }),

  getters: {
    selectedModel: (state) => state.participants.find((p) => p.id === state.selectedParticipantId) || null,
    senoritas: (state) => state.participants.filter((p) => p.category === 'SENORITA'),
    jovenes: (state) => state.participants.filter((p) => p.category === 'JOVEN'),
    rankedSenoritas: (state) => mergeLeaderboard(state, 'SENORITA'),
    rankedJovenes: (state) => mergeLeaderboard(state, 'JOVEN'),

    // Cuántas de las 3 rondas ya calificó ESTE jurado para un participante.
    roundsCompletedFor: (state) => (participantId) => {
      return ROUNDS.filter((r) => !!state.myScores[r.key][participantId]).length;
    },

    myScoreFor: (state) => (participantId, roundKey) => {
      return state.myScores[roundKey]?.[participantId] || null;
    },
  },

  actions: {
    setSelectedModel(id) {
      this.selectedParticipantId = id;
    },
    setActiveRound(roundKey) {
      this.activeRound = roundKey;
    },

    // Se llama una sola vez al entrar al panel (MainLayout). Trae el listado de participantes
    // y las calificaciones propias de las 3 rondas, para que todo lo demás funcione sin recargar.
    async initialize() {
      if (this.initialized) return;
      await this.loadParticipants();
      await Promise.all(ROUNDS.map((r) => this.loadMyScores(r.key)));
      this.initialized = true;
    },

    async loadParticipants() {
      const { data } = await participantesService.getAll();
      this.participants = data.data.map((p) => {
        const photo = PARTICIPANT_PHOTOS[p.codigo];
        return {
          id: p.id,
          codigo: p.codigo,
          name: p.nombre,
          category: p.categoria,
          imageUrl: photo,
          avatarUrl: photo,
          podiumUrl: photo,
        };
      });
    },

    async loadMyScores(roundKey) {
      const { data } = await calificacionesService.misCalificaciones(roundKey);
      this.myScores[roundKey] = data.data;
    },

    async submitScore(participantId, roundKey, rubricValues) {
      const { data } = await calificacionesService.guardar(roundKey, {
        participante_id: participantId,
        ...rubricValues,
      });
      this.myScores[roundKey] = { ...this.myScores[roundKey], [participantId]: data.data };
      return data.data;
    },

    async loadLeaderboard() {
      this.loadingLeaderboard = true;
      try {
        const { data } = await calificacionesService.leaderboard();
        this.leaderboard = data.data;
      } finally {
        this.loadingLeaderboard = false;
      }
    },

    // Autocompleta con valores altos (8-10) los rubros que ESTE jurado no haya calificado todavía.
    async finalizeAllScores() {
      for (const participant of this.participants) {
        for (const round of ROUNDS) {
          if (this.myScores[round.key][participant.id]) continue;

          const rubricKeys = getRubrics(round.key, participant.category).map((r) => r.key);
          const rubricValues = {};
          rubricKeys.forEach((key) => {
            rubricValues[key] = Math.min(10, Math.round(8 + Math.random() * 2));
          });

          await this.submitScore(participant.id, round.key, rubricValues);
        }
      }
    },
  },
});
