// Datos de Bodegas y Almacenes VISAN - Generado desde Bodegas.xlsx
const BODEGAS_METADATA = {
  "VILLA NUEVA": {
    "id": "VILLA NUEVA",
    "nombre": "Villa Nueva",
    "etiqueta": "Villa Nueva / PMA",
    "entidad": "PMA",
    "departamento": "Guatemala",
    "municipio": "Villa Nueva",
    "tipo": "Km. 23 carretera al Pacífico, Parque Industrial Planes de Bárcenas, Eje 5, Bodega 11 12 y 13.",
    "icono": "🏢",
    "direccion": "Km. 23 carretera al Pacífico, Parque Industrial Planes de Bárcenas, Eje 5, Bodega 11 12 y 13."
  },
  "RIO HONDO": {
    "id": "RIO HONDO",
    "nombre": "Río Hondo",
    "etiqueta": "Río Hondo / PMA",
    "entidad": "PMA",
    "departamento": "Zacapa",
    "municipio": "Río Hondo",
    "tipo": "Bodega Rio Hondo PMA Km. 135.5 Rutal al Atlantico, Rio Hondo Zacapa",
    "icono": "🏭",
    "direccion": "Bodega Rio Hondo PMA Km. 135.5 Rutal al Atlantico, Rio Hondo Zacapa"
  },
  "QUETZALTENANGO 2": {
    "id": "QUETZALTENANGO 2",
    "nombre": "Quetzaltenango 2",
    "etiqueta": "Quetzaltenango 2 / PMA",
    "entidad": "PMA",
    "departamento": "Quetzaltenango",
    "municipio": "Quetzaltenango",
    "tipo": "Quetzaltenango PMA (Rosario) (Ofi-Bodegas) Complejo, Bodegas 6 y 7, final zona 8 Quetzaltenango, Calle principal Aldea Justo Rufino Barrios, Olintepeque",
    "icono": "🏬",
    "direccion": "Quetzaltenango PMA (Rosario) (Ofi-Bodegas) Complejo, Bodegas 6 y 7, final zona 8 Quetzaltenango, Calle principal Aldea Justo Rufino Barrios, Olintepeque"
  },
  "QUETZALTENANGO SILO": {
    "id": "QUETZALTENANGO SILO",
    "nombre": "Quetzaltenango Silo",
    "etiqueta": "Quetzaltenango Silo / PMA",
    "entidad": "PMA",
    "departamento": "Quetzaltenango",
    "municipio": "Quetzaltenango",
    "tipo": "37 Av. 1-51 zona 8, Quetzaltenango",
    "icono": "🏗️",
    "direccion": "37 Av. 1-51 zona 8, Quetzaltenango"
  },
  "ESTANZUELA": {
    "id": "ESTANZUELA",
    "nombre": "Estanzuela",
    "etiqueta": "Estanzuela / PMA",
    "entidad": "PMA",
    "departamento": "Zacapa",
    "municipio": "Estanzuela",
    "tipo": "Kilometro 141.5 carretera a Zacapa, Bodega La Galera, Estanzuela, Zacapa",
    "icono": "📦",
    "direccion": "Kilometro 141.5 carretera a Zacapa, Bodega La Galera, Estanzuela, Zacapa"
  },
  "FRAIJANES": {
    "id": "FRAIJANES",
    "nombre": "Fraijanes",
    "etiqueta": "Fraijanes / INDECA",
    "entidad": "INDECA",
    "departamento": "Guatemala",
    "municipio": "Fraijanes",
    "tipo": "1 Ave. 1-34 zona 2 Colonia Pavón, Fraijanes",
    "icono": "🛡️",
    "direccion": "1 Ave. 1-34 zona 2 Colonia Pavón, Fraijanes"
  },
  "CHIMALTENANGO": {
    "id": "CHIMALTENANGO",
    "nombre": "Chimaltenango",
    "etiqueta": "Chimaltenango / INDECA",
    "entidad": "INDECA",
    "departamento": "Chimaltenango",
    "municipio": "Chimaltenango",
    "tipo": "Diagonal 7, 12-133 zona 5, La Alameda, Chimaltenango",
    "icono": "🏔️",
    "direccion": "Diagonal 7, 12-133 zona 5, La Alameda, Chimaltenango"
  },
  "TACTIC": {
    "id": "TACTIC",
    "nombre": "Tactic",
    "etiqueta": "Tactic / INDECA",
    "entidad": "INDECA",
    "departamento": "Alta Verapaz",
    "municipio": "Tactic",
    "tipo": "Km.186 Ruta a Cobán CA-14 Tactíc, Alta Verapaz",
    "icono": "🌾",
    "direccion": "Km.186 Ruta a Cobán CA-14 Tactíc, Alta Verapaz"
  },
  "IPALA": {
    "id": "IPALA",
    "nombre": "Ipala",
    "etiqueta": "Ipala / INDECA",
    "entidad": "INDECA",
    "departamento": "Chiquimula",
    "municipio": "Ipala",
    "tipo": "Sector Amate Quemado, Salida al Amatío Ipala Chiquimula",
    "icono": "🏛️",
    "direccion": "Sector Amate Quemado, Salida al Amatío Ipala Chiquimula"
  },
  "AMATES": {
    "id": "AMATES",
    "nombre": "Los Amates",
    "etiqueta": "Amates / INDECA",
    "entidad": "INDECA",
    "departamento": "Izabal",
    "municipio": "Los Amates",
    "tipo": "Km. 200 Carretera al Rico, Los Amates, Izabal",
    "icono": "⚓",
    "direccion": "Km. 200 Carretera al Rico, Los Amates, Izabal"
  },
  "RETALHULEU": {
    "id": "RETALHULEU",
    "nombre": "Retalhuleu",
    "etiqueta": "Retalhuleu / INDECA",
    "entidad": "INDECA",
    "departamento": "Retalhuleu",
    "municipio": "Retalhuleu",
    "tipo": "Finca Puca Calzada Las Palmas, zona 6, Retalhuleu",
    "icono": "🌴",
    "direccion": "Finca Puca Calzada Las Palmas, zona 6, Retalhuleu"
  }
};


const BODEGAS_RESUMEN_CONVENIOS = {
  "convenios": [
    {
      "key": "c02_2026",
      "codigo": "02-2026",
      "nombre": "INSAN",
      "titulo": "Convenio 02-2026 (INSAN)",
      "color": "#2563eb",
      "badgeBg": "#dbeafe",
      "badgeColor": "#1d4ed8",
      "icono": "🌾"
    },
    {
      "key": "c03_2026",
      "codigo": "03-2026",
      "nombre": "RESERVA ESTRATEGICA",
      "titulo": "Convenio 03-2026 (Reserva Estratégica)",
      "color": "#059669",
      "badgeBg": "#d1fae5",
      "badgeColor": "#047857",
      "icono": "🛡️"
    },
    {
      "key": "c04_2026",
      "codigo": "04-2026",
      "nombre": "ALIMENTOS POR ACCIONES",
      "titulo": "Convenio 04-2026 (Alimentos Por Acciones)",
      "color": "#d97706",
      "badgeBg": "#fef3c7",
      "badgeColor": "#b45309",
      "icono": "🤝"
    },
    {
      "key": "c05_2026",
      "codigo": "05-2026",
      "nombre": "NDA | MJ | MT | MC",
      "titulo": "Convenio 05-2026 (NDA - MJ - MT - MC)",
      "color": "#7c3aed",
      "badgeBg": "#ede9fe",
      "badgeColor": "#6d28d9",
      "icono": "👶"
    }
  ],
  "filas": [
    {
      "bodega": "VILLA NUEVA / PMA",
      "bodega_id": "VILLA NUEVA",
      "nombre": "Villa Nueva",
      "entidad": "PMA",
      "departamento": "Guatemala",
      "c02_2026": 4360,
      "c03_2026": 0,
      "c04_2026": 19171,
      "c05_2026": 0,
      "total": 23531
    },
    {
      "bodega": "RIO HONDO / PMA",
      "bodega_id": "RIO HONDO",
      "nombre": "Río Hondo",
      "entidad": "PMA",
      "departamento": "Zacapa",
      "c02_2026": 17850,
      "c03_2026": 0,
      "c04_2026": 0,
      "c05_2026": 2623,
      "total": 20473
    },
    {
      "bodega": "QUETZALTENANGO 2 / PMA",
      "bodega_id": "QUETZALTENANGO 2",
      "nombre": "Quetzaltenango 2",
      "entidad": "PMA",
      "departamento": "Quetzaltenango",
      "c02_2026": 3860,
      "c03_2026": 0,
      "c04_2026": 11636,
      "c05_2026": 0,
      "total": 15496
    },
    {
      "bodega": "QUETZALTENANGO SILO / PMA",
      "bodega_id": "QUETZALTENANGO SILO",
      "nombre": "Quetzaltenango Silo",
      "entidad": "PMA",
      "departamento": "Quetzaltenango",
      "c02_2026": 0,
      "c03_2026": 0,
      "c04_2026": 9528,
      "c05_2026": 0,
      "total": 9528
    },
    {
      "bodega": "FRAIJANES / INDECA",
      "bodega_id": "FRAIJANES",
      "nombre": "Fraijanes",
      "entidad": "INDECA",
      "departamento": "Guatemala",
      "c02_2026": 0,
      "c03_2026": 19035,
      "c04_2026": 0,
      "c05_2026": 0,
      "total": 19035
    },
    {
      "bodega": "CHIMALTENANGO / INDECA",
      "bodega_id": "CHIMALTENANGO",
      "nombre": "Chimaltenango",
      "entidad": "INDECA",
      "departamento": "Chimaltenango",
      "c02_2026": 0,
      "c03_2026": 950,
      "c04_2026": 0,
      "c05_2026": 0,
      "total": 950
    },
    {
      "bodega": "ESTANZUELA / PMA",
      "bodega_id": "ESTANZUELA",
      "nombre": "Estanzuela",
      "entidad": "PMA",
      "departamento": "Zacapa",
      "c02_2026": 559,
      "c03_2026": 0,
      "c04_2026": 453,
      "c05_2026": 0,
      "total": 1012
    },
    {
      "bodega": "TACTIC / INDECA",
      "bodega_id": "TACTIC",
      "nombre": "Tactic",
      "entidad": "INDECA",
      "departamento": "Alta Verapaz",
      "c02_2026": 0,
      "c03_2026": 0,
      "c04_2026": 0,
      "c05_2026": 0,
      "total": 0
    },
    {
      "bodega": "IPALA / INDECA",
      "bodega_id": "IPALA",
      "nombre": "Ipala",
      "entidad": "INDECA",
      "departamento": "Chiquimula",
      "c02_2026": 0,
      "c03_2026": 0,
      "c04_2026": 0,
      "c05_2026": 0,
      "total": 0
    },
    {
      "bodega": "AMATES / INDECA",
      "bodega_id": "AMATES",
      "nombre": "Los Amates",
      "entidad": "INDECA",
      "departamento": "Izabal",
      "c02_2026": 0,
      "c03_2026": 0,
      "c04_2026": 0,
      "c05_2026": 0,
      "total": 0
    }
  ],
  "totales": {
    "c02_2026": 26629,
    "c03_2026": 19985,
    "c04_2026": 40788,
    "c05_2026": 2623,
    "total_general": 90025
  }
};

const BODEGAS_BALANCE = [
  {
    "programa": "ASISTENCIA ALIMENTARIA",
    "convenio": "02-2026 / INSAN",
    "total_convenio": 593102,
    "recibidas": 354500,
    "despachadas": 327871,
    "disponibles": 29252,
    "pendiente_pma": 238602
  },
  {
    "programa": "ASISTENCIA ALIMENTARIA",
    "convenio": "05-2026 / NDA - MC - MJ - MT",
    "total_convenio": 28037,
    "recibidas": 28037,
    "despachadas": 25414,
    "disponibles": 2623,
    "pendiente_pma": 0
  },
  {
    "programa": "ALIMENTOS POR ACCIONES",
    "convenio": "04-2026 / Alimentos Por Acciones",
    "total_convenio": 392648,
    "recibidas": 269377,
    "despachadas": 228589,
    "disponibles": 40788,
    "pendiente_pma": 123271
  },
  {
    "programa": "RESERVA ESTRATEGICA",
    "convenio": "03-2026 / Reserva Estrategica",
    "total_convenio": 106610,
    "recibidas": 83250,
    "despachadas": 63265,
    "disponibles": 19985,
    "pendiente_pma": 23360
  }
];

const BODEGAS_INVENTARIO = [
  {
    "id": "VILLA NUEVA_002-2026_2",
    "bodega": "VILLA NUEVA",
    "departamento": "Guatemala",
    "convenio": "002-2026",
    "programa": "INSAN",
    "arroz_5lb": 4360,
    "frijol_5lb": 8720,
    "frijol_10lb": 0,
    "azucar_500g": 8720,
    "mezcla_900g": 4360,
    "harina_soya_900g": 4360,
    "sal_460g": 0,
    "sal_500g": 4360,
    "aceite_800ml": 4360,
    "avena_1kg": 4360,
    "harina_maiz_5lb": 8720,
    "maiz_blanco_25lb": 0,
    "maiz_25lb": 0,
    "disponible_raciones": 4360
  },
  {
    "id": "RIO HONDO_002-2026_3",
    "bodega": "RIO HONDO",
    "departamento": "Zacapa",
    "convenio": "002-2026",
    "programa": "INSAN",
    "arroz_5lb": 17850,
    "frijol_5lb": 35700,
    "frijol_10lb": 0,
    "azucar_500g": 35700,
    "mezcla_900g": 17850,
    "harina_soya_900g": 17850,
    "sal_460g": 17850,
    "sal_500g": 0,
    "aceite_800ml": 17850,
    "avena_1kg": 17850,
    "harina_maiz_5lb": 34354,
    "maiz_blanco_25lb": 673,
    "maiz_25lb": 673,
    "disponible_raciones": 17850
  },
  {
    "id": "QUETZALTENANGO 2_002-2026_4",
    "bodega": "QUETZALTENANGO 2",
    "departamento": "Quetzaltenango",
    "convenio": "002-2026",
    "programa": "INSAN",
    "arroz_5lb": 27000,
    "frijol_5lb": 54000,
    "frijol_10lb": 0,
    "azucar_500g": 7720,
    "mezcla_900g": 27000,
    "harina_soya_900g": 27000,
    "sal_460g": 23140,
    "sal_500g": 3860,
    "aceite_800ml": 27000,
    "avena_1kg": 27000,
    "harina_maiz_5lb": 48374,
    "maiz_blanco_25lb": 2813,
    "maiz_25lb": 2813,
    "disponible_raciones": 3860
  },
  {
    "id": "ESTANZUELA_002-2026_5",
    "bodega": "ESTANZUELA",
    "departamento": "Zacapa",
    "convenio": "002-2026",
    "programa": "INSAN",
    "arroz_5lb": 559,
    "frijol_5lb": 1118,
    "frijol_10lb": 0,
    "azucar_500g": 1118,
    "mezcla_900g": 559,
    "harina_soya_900g": 559,
    "sal_460g": 559,
    "sal_500g": 0,
    "aceite_800ml": 559,
    "avena_1kg": 559,
    "harina_maiz_5lb": 1118,
    "maiz_blanco_25lb": 0,
    "maiz_25lb": 0,
    "disponible_raciones": 559
  },
  {
    "id": "FRAIJANES_003-2026_6",
    "bodega": "FRAIJANES",
    "departamento": "Guatemala",
    "convenio": "003-2026",
    "programa": "RESERVA ESTRATEGICA",
    "arroz_5lb": 19035,
    "frijol_5lb": 57105,
    "frijol_10lb": 0,
    "azucar_500g": 0,
    "mezcla_900g": 0,
    "harina_soya_900g": 0,
    "sal_460g": 0,
    "sal_500g": 0,
    "aceite_800ml": 0,
    "avena_1kg": 0,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 38070,
    "maiz_25lb": 38070,
    "disponible_raciones": 19035
  },
  {
    "id": "CHIMALTENANGO_003-2026_7",
    "bodega": "CHIMALTENANGO",
    "departamento": "Chimaltenango",
    "convenio": "003-2026",
    "programa": "RESERVA ESTRATEGICA",
    "arroz_5lb": 950,
    "frijol_5lb": 2848,
    "frijol_10lb": 0,
    "azucar_500g": 0,
    "mezcla_900g": 0,
    "harina_soya_900g": 0,
    "sal_460g": 0,
    "sal_500g": 0,
    "aceite_800ml": 0,
    "avena_1kg": 0,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 1900,
    "maiz_25lb": 1900,
    "disponible_raciones": 950
  },
  {
    "id": "VILLA NUEVA_004-2026_8",
    "bodega": "VILLA NUEVA",
    "departamento": "Guatemala",
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES",
    "arroz_5lb": 19171,
    "frijol_5lb": 5282,
    "frijol_10lb": 16530,
    "azucar_500g": 38342,
    "mezcla_900g": 19171,
    "harina_soya_900g": 19171,
    "sal_460g": 12799,
    "sal_500g": 6372,
    "aceite_800ml": 19171,
    "avena_1kg": 19171,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 19171,
    "maiz_25lb": 19171,
    "disponible_raciones": 19171
  },
  {
    "id": "RIO HONDO_004-2026_9",
    "bodega": "RIO HONDO",
    "departamento": "Zacapa",
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES",
    "arroz_5lb": 0,
    "frijol_5lb": 0,
    "frijol_10lb": 0,
    "azucar_500g": 0,
    "mezcla_900g": 0,
    "harina_soya_900g": 0,
    "sal_460g": 0,
    "sal_500g": 0,
    "aceite_800ml": 0,
    "avena_1kg": 0,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 0,
    "maiz_25lb": 0,
    "disponible_raciones": 0
  },
  {
    "id": "ESTANZUELA_004-2026_10",
    "bodega": "ESTANZUELA",
    "departamento": "Zacapa",
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES",
    "arroz_5lb": 453,
    "frijol_5lb": 906,
    "frijol_10lb": 0,
    "azucar_500g": 906,
    "mezcla_900g": 453,
    "harina_soya_900g": 453,
    "sal_460g": 453,
    "sal_500g": 0,
    "aceite_800ml": 453,
    "avena_1kg": 453,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 453,
    "maiz_25lb": 453,
    "disponible_raciones": 453
  },
  {
    "id": "QUETZALTENANGO 2_004-2026_11",
    "bodega": "QUETZALTENANGO 2",
    "departamento": "Quetzaltenango",
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES",
    "arroz_5lb": 11636,
    "frijol_5lb": 23272,
    "frijol_10lb": 0,
    "azucar_500g": 23272,
    "mezcla_900g": 11636,
    "harina_soya_900g": 11636,
    "sal_460g": 11095,
    "sal_500g": 541,
    "aceite_800ml": 11636,
    "avena_1kg": 11636,
    "harina_maiz_5lb": 1086,
    "maiz_blanco_25lb": 11093,
    "maiz_25lb": 11093,
    "disponible_raciones": 11636
  },
  {
    "id": "QUETZALTENANGO SILO_004-2026_12",
    "bodega": "QUETZALTENANGO SILO",
    "departamento": "Quetzaltenango",
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES",
    "arroz_5lb": 9528,
    "frijol_5lb": 19056,
    "frijol_10lb": 0,
    "azucar_500g": 19056,
    "mezcla_900g": 9528,
    "harina_soya_900g": 9528,
    "sal_460g": 9528,
    "sal_500g": 0,
    "aceite_800ml": 9528,
    "avena_1kg": 9528,
    "harina_maiz_5lb": 0,
    "maiz_blanco_25lb": 9528,
    "maiz_25lb": 9528,
    "disponible_raciones": 9528
  },
  {
    "id": "RIO HONDO_005-2026_13",
    "bodega": "RIO HONDO",
    "departamento": "Zacapa",
    "convenio": "005-2026",
    "programa": "NDA - MJ - MT - MC",
    "arroz_5lb": 10492,
    "frijol_5lb": 15738,
    "frijol_10lb": 0,
    "azucar_500g": 13115,
    "mezcla_900g": 10492,
    "harina_soya_900g": 10492,
    "sal_460g": 0,
    "sal_500g": 0,
    "aceite_800ml": 5246,
    "avena_1kg": 7869,
    "harina_maiz_5lb": 13115,
    "maiz_blanco_25lb": 0,
    "maiz_25lb": 0,
    "disponible_raciones": 2623
  }
];


const BODEGAS_CONTENIDO = [
  {
    "producto": "Arroz",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Frijol Negro",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 2,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Avena en Hojuela",
    "presentacion": "Bolsa de 1 kg",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Azucar",
    "presentacion": "Bolsa de 500 gramos",
    "cantidad": 2,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Aceite Vegetal",
    "presentacion": "Botella 800 ml",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Sal Yodada",
    "presentacion": "Bolsa de 460 gramos",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Sal Yodada",
    "presentacion": "Bolsa de 500 gramos",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Harina de Maiz Nixtamalizada o Maíz Blanco",
    "presentacion": "Bolsa de 25 libras",
    "cantidad": 2,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Mezcla de Harina de Maiz y Soya Fortificada",
    "presentacion": "Bolsa de 900 gramos",
    "cantidad": 1,
    "convenio": "002-2026",
    "programa": "INSAN"
  },
  {
    "producto": "Arroz",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 1,
    "convenio": "003-2026",
    "programa": "RESERVA ESTRATEGICA"
  },
  {
    "producto": "Frijol Negro",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 3,
    "convenio": "003-2026",
    "programa": "RESERVA ESTRATEGICA"
  },
  {
    "producto": "Maiz Blanco",
    "presentacion": "Bolsa de 25 libras",
    "cantidad": 2,
    "convenio": "003-2026",
    "programa": "RESERVA ESTRATEGICA"
  },
  {
    "producto": "Arroz",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Frijol Negro",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 2,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Frijol Negro",
    "presentacion": "Bolsa de 10 libras",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Avena en Hojuela",
    "presentacion": "Bolsa de 1 kg",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Azucar",
    "presentacion": "Bolsa de 500 gramos",
    "cantidad": 2,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Aceite Vegetal",
    "presentacion": "Botella 800 ml",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Sal Yodada",
    "presentacion": "Bolsa de 460 gramos",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Sal Yodada",
    "presentacion": "Bolsa de 500 gramos",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Harina de Maiz Nixtamalizada o Maíz Blanco",
    "presentacion": "Bolsa de 25 libras",
    "cantidad": 2,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Mezcla de Harina de Maiz y Soya Fortificada",
    "presentacion": "Bolsa de 900 gramos",
    "cantidad": 1,
    "convenio": "004-2026",
    "programa": "ALIMENTOS POR ACCIONES"
  },
  {
    "producto": "Arroz",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 4,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Frijol Negro",
    "presentacion": "Bolsa de 05 libras",
    "cantidad": 6,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Avena en Hojuela",
    "presentacion": "Bolsa de 1 kg",
    "cantidad": 3,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Azucar",
    "presentacion": "Bolsa de 500 gramos",
    "cantidad": 5,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Aceite Vegetal",
    "presentacion": "Botella 800 ml",
    "cantidad": 2,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Harina de Maiz Nixtamalizada o Maíz Blanco",
    "presentacion": "Bolsa de 25 libras",
    "cantidad": 5,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  },
  {
    "producto": "Mezcla de Harina de Maiz y Soya Fortificada",
    "presentacion": "Bolsa de 900 gramos",
    "cantidad": 4,
    "convenio": "005-2026",
    "programa": "NDA - MJ - MC - MT"
  }
];
