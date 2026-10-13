// Datos de ejemplo de la Etapa 1 (frontend sin backend).
// Misma forma que devolverá el API (/overview) para cambiar la fuente después sin tocar las vistas.
const BASE = import.meta.env.BASE_URL

export const league={id:1,name:"LIGA PULSE",season:"Apertura 2026",timezone:"America/Guatemala"};
export const teams=[
  {id:1,name:"Aurora FC",short_name:"AUR",city:"Ciudad de Guatemala",stadium:"Estadio del Sol",coach:"Rodrigo Méndez",primary_color:"#c6ff3b",secondary_color:"#11171b",founded_year:1964,logo_url:`${BASE}pulse/equipos/1.svg`},
  {id:2,name:"Atlético Volcán",short_name:"VOL",city:"Antigua Guatemala",stadium:"Estadio La Cumbre",coach:"Diego Salazar",primary_color:"#a797ff",secondary_color:"#11171b",founded_year:1982,logo_url:`${BASE}pulse/equipos/2.svg`},
  {id:3,name:"Deportivo Costa",short_name:"COS",city:"Escuintla",stadium:"Estadio del Pacífico",coach:"Marco Rivas",primary_color:"#4dd9ed",secondary_color:"#11171b",founded_year:1978,logo_url:`${BASE}pulse/equipos/3.svg`},
  {id:4,name:"Unión Capital",short_name:"UNI",city:"Mixco",stadium:"Arena Capital",coach:"Luis Contreras",primary_color:"#ff8066",secondary_color:"#11171b",founded_year:1995,logo_url:`${BASE}pulse/equipos/4.svg`}];
export const players=[
  {id:1,team_id:1,full_name:"Andrés Castillo",jersey_number:1,position:"POR",birth_date:"1996-02-11",nationality:"Guatemala",height_cm:184,weight_kg:80,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/1.svg`},
  {id:2,team_id:1,full_name:"Mateo Ríos",jersey_number:4,position:"DEF",birth_date:"1997-02-12",nationality:"Guatemala",height_cm:183,weight_kg:78.5,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/2.svg`},
  {id:3,team_id:1,full_name:"Daniel Estrada",jersey_number:8,position:"MED",birth_date:"1998-02-13",nationality:"Guatemala",height_cm:182,weight_kg:77,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/3.svg`},
  {id:4,team_id:1,full_name:"Gabriel Fuentes",jersey_number:10,position:"DEL",birth_date:"1999-02-14",nationality:"Guatemala",height_cm:181,weight_kg:75.5,preferred_foot:"Izquierdo",photo_url:`${BASE}pulse/jugadores/4.svg`},
  {id:5,team_id:1,full_name:"Nicolás Vega",jersey_number:9,position:"DEL",birth_date:"2000-02-15",nationality:"Guatemala",height_cm:180,weight_kg:74,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/5.svg`},
  {id:6,team_id:2,full_name:"Sebastián López",jersey_number:1,position:"POR",birth_date:"1996-03-11",nationality:"Guatemala",height_cm:184,weight_kg:80,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/6.svg`},
  {id:7,team_id:2,full_name:"Diego Molina",jersey_number:4,position:"DEF",birth_date:"1997-03-12",nationality:"Guatemala",height_cm:183,weight_kg:78.5,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/7.svg`},
  {id:8,team_id:2,full_name:"Javier Cruz",jersey_number:8,position:"MED",birth_date:"1998-03-13",nationality:"Guatemala",height_cm:182,weight_kg:77,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/8.svg`},
  {id:9,team_id:2,full_name:"Emiliano Reyes",jersey_number:10,position:"DEL",birth_date:"1999-03-14",nationality:"Guatemala",height_cm:181,weight_kg:75.5,preferred_foot:"Izquierdo",photo_url:`${BASE}pulse/jugadores/9.svg`},
  {id:10,team_id:2,full_name:"Alejandro Paz",jersey_number:9,position:"DEL",birth_date:"2000-03-15",nationality:"Guatemala",height_cm:180,weight_kg:74,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/10.svg`},
  {id:11,team_id:3,full_name:"Luis Cabrera",jersey_number:1,position:"POR",birth_date:"1996-04-11",nationality:"Guatemala",height_cm:184,weight_kg:80,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/11.svg`},
  {id:12,team_id:3,full_name:"Pablo Méndez",jersey_number:4,position:"DEF",birth_date:"1997-04-12",nationality:"Guatemala",height_cm:183,weight_kg:78.5,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/12.svg`},
  {id:13,team_id:3,full_name:"Santiago León",jersey_number:8,position:"MED",birth_date:"1998-04-13",nationality:"Guatemala",height_cm:182,weight_kg:77,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/13.svg`},
  {id:14,team_id:3,full_name:"Adrián Morales",jersey_number:10,position:"DEL",birth_date:"1999-04-14",nationality:"Guatemala",height_cm:181,weight_kg:75.5,preferred_foot:"Izquierdo",photo_url:`${BASE}pulse/jugadores/14.svg`},
  {id:15,team_id:3,full_name:"Carlos Duarte",jersey_number:9,position:"DEL",birth_date:"2000-04-15",nationality:"Guatemala",height_cm:180,weight_kg:74,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/15.svg`},
  {id:16,team_id:4,full_name:"José Aguilar",jersey_number:1,position:"POR",birth_date:"1996-05-11",nationality:"Guatemala",height_cm:184,weight_kg:80,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/16.svg`},
  {id:17,team_id:4,full_name:"David Castañeda",jersey_number:4,position:"DEF",birth_date:"1997-05-12",nationality:"Guatemala",height_cm:183,weight_kg:78.5,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/17.svg`},
  {id:18,team_id:4,full_name:"Miguel Herrera",jersey_number:8,position:"MED",birth_date:"1998-05-13",nationality:"Guatemala",height_cm:182,weight_kg:77,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/18.svg`},
  {id:19,team_id:4,full_name:"Fernando Solís",jersey_number:10,position:"DEL",birth_date:"1999-05-14",nationality:"Guatemala",height_cm:181,weight_kg:75.5,preferred_foot:"Izquierdo",photo_url:`${BASE}pulse/jugadores/19.svg`},
  {id:20,team_id:4,full_name:"Óscar Ramírez",jersey_number:9,position:"DEL",birth_date:"2000-05-15",nationality:"Guatemala",height_cm:180,weight_kg:74,preferred_foot:"Derecho",photo_url:`${BASE}pulse/jugadores/20.svg`}];
export const matches=[
  {id:1,home_team_id:1,away_team_id:2,kickoff_at:"2026-09-20 21:00:00",round_number:1,status:"finished"},
  {id:2,home_team_id:3,away_team_id:4,kickoff_at:"2026-09-20 23:00:00",round_number:1,status:"finished"},
  {id:3,home_team_id:1,away_team_id:3,kickoff_at:"2026-09-27 21:00:00",round_number:2,status:"finished"},
  {id:4,home_team_id:2,away_team_id:4,kickoff_at:"2026-09-27 23:00:00",round_number:2,status:"finished"},
  {id:5,home_team_id:4,away_team_id:1,kickoff_at:"2026-10-04 21:00:00",round_number:3,status:"finished"},
  {id:6,home_team_id:2,away_team_id:3,kickoff_at:"2026-10-04 23:00:00",round_number:3,status:"finished"},
  {id:7,home_team_id:2,away_team_id:1,kickoff_at:"2026-10-18 21:00:00",round_number:4,status:"scheduled"},
  {id:8,home_team_id:4,away_team_id:3,kickoff_at:"2026-10-18 23:00:00",round_number:4,status:"scheduled"},
  {id:9,home_team_id:3,away_team_id:1,kickoff_at:"2026-10-25 21:00:00",round_number:5,status:"scheduled"},
  {id:10,home_team_id:4,away_team_id:2,kickoff_at:"2026-10-25 23:00:00",round_number:5,status:"scheduled"}];
export const events=[
  {id:1,match_id:1,team_id:1,player_id:4,assist_player_id:3,type:"goal",minute:12},
  {id:2,match_id:1,team_id:2,player_id:9,assist_player_id:8,type:"goal",minute:35},
  {id:3,match_id:1,team_id:1,player_id:4,assist_player_id:5,type:"goal",minute:68},
  {id:4,match_id:2,team_id:3,player_id:14,assist_player_id:13,type:"goal",minute:21},
  {id:5,match_id:2,team_id:4,player_id:19,assist_player_id:18,type:"goal",minute:71},
  {id:6,match_id:3,team_id:1,player_id:4,assist_player_id:3,type:"goal",minute:8},
  {id:7,match_id:3,team_id:1,player_id:5,assist_player_id:4,type:"goal",minute:41},
  {id:8,match_id:3,team_id:3,player_id:14,assist_player_id:13,type:"goal",minute:57},
  {id:9,match_id:3,team_id:1,player_id:4,assist_player_id:5,type:"goal",minute:86},
  {id:10,match_id:4,team_id:2,player_id:9,assist_player_id:8,type:"goal",minute:31},
  {id:11,match_id:4,team_id:2,player_id:10,assist_player_id:9,type:"goal",minute:78},
  {id:12,match_id:5,team_id:1,player_id:4,assist_player_id:3,type:"goal",minute:22},
  {id:13,match_id:5,team_id:4,player_id:19,assist_player_id:18,type:"goal",minute:66},
  {id:14,match_id:6,team_id:3,player_id:14,assist_player_id:13,type:"goal",minute:14},
  {id:15,match_id:6,team_id:2,player_id:9,assist_player_id:8,type:"goal",minute:38},
  {id:16,match_id:6,team_id:2,player_id:9,assist_player_id:10,type:"goal",minute:80},
  {id:17,match_id:1,team_id:1,player_id:2,assist_player_id:null,type:"yellow",minute:39},
  {id:18,match_id:1,team_id:2,player_id:7,assist_player_id:null,type:"yellow",minute:74},
  {id:19,match_id:2,team_id:3,player_id:12,assist_player_id:null,type:"yellow",minute:23},
  {id:20,match_id:2,team_id:4,player_id:17,assist_player_id:null,type:"yellow",minute:55},
  {id:21,match_id:3,team_id:3,player_id:13,assist_player_id:null,type:"yellow",minute:62},
  {id:22,match_id:4,team_id:4,player_id:18,assist_player_id:null,type:"yellow",minute:19},
  {id:23,match_id:5,team_id:1,player_id:2,assist_player_id:null,type:"yellow",minute:83},
  {id:24,match_id:6,team_id:2,player_id:7,assist_player_id:null,type:"yellow",minute:51},
  {id:25,match_id:4,team_id:4,player_id:17,assist_player_id:null,type:"red",minute:89}];
