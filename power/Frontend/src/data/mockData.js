export const initialAthlete = {
  id: 'ath-001',
  name: 'Atleta',
  // Vacio a proposito. Aqui decia 'Atleta Élite' y esa cadena acababa debajo
  // del nombre de CUALQUIERA que no hubiera declarado su nivel -- un atleta de
  // la beta la leyo como un sistema de niveles que no existe. El titulo real lo
  // pone `applyAuthUserToAthlete` desde `training_age_level`; sin nivel
  // declarado no se enseña nada, que es la verdad.
  roleTitle: '',
  // Nada inventado hasta que llegan los datos de verdad (prueba del 2026-10-19): aquí había un
  // 62 % de perfil (Plan enseñaba «Termina tu perfil primero» a un perfil al 100 % mientras
  // cargaba), la foto de stock de una desconocida como foto de quien no tiene una, y un
  // «Ironman 70.3 Marbella».
  avatarUrl: '',
  completionRate: null,
};
