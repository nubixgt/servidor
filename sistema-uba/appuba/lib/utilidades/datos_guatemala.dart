class DatosGuatemala {
  static const List<String> departamentos = [
    'Guatemala', 'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula',
    'El Progreso', 'Escuintla', 'Huehuetenango', 'Izabal', 'Jalapa',
    'Jutiapa', 'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu',
    'Sacatepéquez', 'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez',
    'Totonicapán', 'Zacapa'
  ];

  static const Map<String, List<String>> _municipiosPorDepartamento = {
    'Guatemala': [
      'Guatemala', 'Santa Catarina Pinula', 'San José Pinula', 'San José del Golfo', 
      'Palencia', 'Chinautla', 'San Pedro Ayampuc', 'Mixco', 'San Pedro Sacatepéquez', 
      'San Juan Sacatepéquez', 'San Raymundo', 'Chuarrancho', 'Fraijanes', 
      'Amatitlán', 'Villa Nueva', 'Villa Canales', 'San Miguel Petapa'
    ],
    'Alta Verapaz': [
      'Cobán', 'Santa Cruz Verapaz', 'San Cristóbal Verapaz', 'Tactic', 'Tamahú', 
      'Tucurú', 'Panzós', 'Senahú', 'San Pedro Carchá', 'San Juan Chamelco', 
      'Lanquín', 'Santa María Cahabón', 'Chisec', 'Chahal', 'Fray Bartolomé de las Casas', 
      'Santa Catalina La Tinta', 'Raxruhá'
    ],
    'Baja Verapaz': [
      'Salamá', 'San Miguel Chicaj', 'Granados', 'Santa Cruz el Chol', 'San Jerónimo', 
      'Purulhá', 'Rabinal', 'Cubulco'
    ],
    'Chimaltenango': [
      'Chimaltenango', 'San José Poaquil', 'San Martín Jilotepeque', 'Santa Apolonia', 
      'Tecpán Guatemala', 'Patzún', 'Pochuta', 'Patzicía', 'Santa Cruz Balanyá', 
      'Acatenango', 'Yepocapa', 'San Andrés Itzapa', 'Parramos', 'Zaragoza', 'El Tejar'
    ],
    'Chiquimula': [
      'Chiquimula', 'San José la Arada', 'San Juan Ermita', 'Jocotán', 'Camotán', 
      'Olopa', 'Esquipulas', 'Quetzaltepeque', 'San Jacinto', 'Ipala', 'Concepción Las Minas'
    ],
    'El Progreso': [
      'Guastatoya', 'Morazán', 'San Agustín Acasaguastlán', 'San Cristóbal Acasaguastlán', 
      'El Jícaro', 'Sansare', 'Sanarate', 'San Antonio La Paz'
    ],
    'Escuintla': [
      'Escuintla', 'Santa Lucía Cotzumalguapa', 'La Democracia', 'Siquinalá', 
      'Masagua', 'Tiquisate', 'La Gomera', 'Guanagazapa', 'San José', 'Iztapa', 
      'Palín', 'San Vicente Pacaya', 'Nueva Concepción'
    ],
    'Huehuetenango': [
      'Huehuetenango', 'Chiantla', 'Malacatancito', 'Cuilco', 'Nentón', 'San Pedro Necta', 
      'Jacaltenango', 'Soloma', 'Ixtahuacán', 'Libertad', 'San Miguel Acatán', 
      'San Rafael La Independencia', 'Todos Santos Cuchumatán', 'San Juan Atitán', 
      'Santa Eulalia', 'San Mateo Ixtatán', 'Colotenango', 'San Sebastián Huehuetenango', 
      'Tectitán', 'Concepción Huista', 'San Juan Ixcoy', 'San Antonio Huista', 
      'San Sebastián Coatán', 'Barillas', 'Aguacatán', 'San Rafael Petzal', 
      'San Gaspar Ixchil', 'Santiago Chimaltenango', 'Santa Ana Huista', 'Unión Cantinil', 'Petatán'
    ],
    'Izabal': [
      'Puerto Barrios', 'Livingston', 'El Estor', 'Morales', 'Los Amates'
    ],
    'Jalapa': [
      'Jalapa', 'San Pedro Pinula', 'San Luis Jilotepeque', 'San Manuel Chaparrón', 
      'San Carlos Alzatate', 'Monjas', 'Mataquescuintla'
    ],
    'Jutiapa': [
      'Jutiapa', 'El Progreso', 'Santa Catarina Mita', 'Agua Blanca', 'Asunción Mita', 
      'Yupiltepeque', 'Atescatempa', 'Jerez', 'El Adelanto', 'Zapotitlán', 'Comapa', 
      'Jalpatagua', 'Conguaco', 'Moyuta', 'Pasaco', 'San José Acatempa', 'Quesada'
    ],
    'Petén': [
      'Flores', 'San José', 'San Benito', 'San Andrés', 'La Libertad', 'San Francisco', 
      'Santa Ana', 'Dolores', 'San Luis', 'Sayaxché', 'Melchor de Mencos', 'Poptún', 
      'Las Cruces', 'El Chal'
    ],
    'Quetzaltenango': [
      'Quetzaltenango', 'Salcajá', 'Olintepeque', 'San Carlos Sija', 'Sibilia', 'Cabricán', 
      'Cajolá', 'San Miguel Sigüila', 'Ostuncalco', 'San Mateo', 'Concepción Chiquirichapa', 
      'San Martín Sacatepéquez', 'Almolonga', 'Cantel', 'Huitán', 'Zunil', 'Colomba', 
      'San Francisco La Unión', 'El Palmar', 'Coatepeque', 'Génova', 'Flores Costa Cuca', 
      'La Esperanza', 'Palestina de Los Altos'
    ],
    'Quiché': [
      'Santa Cruz del Quiché', 'Chiché', 'Chinique', 'Zacualpa', 'Chajul', 
      'Santo Tomás Chichicastenango', 'Patzité', 'San Antonio Ilotenango', 
      'San Pedro Jocopilas', 'Cunén', 'San Juan Cotzal', 'Joyabaj', 'Nebaj', 
      'San Andrés Sajcabajá', 'Uspantán', 'Sacapulas', 'San Bartolomé Jocotenango', 
      'Canillá', 'Chicamán', 'Ixcán', 'Pachalum'
    ],
    'Retalhuleu': [
      'Retalhuleu', 'San Sebastián', 'Santa Cruz Mulaúa', 'San Martín Zapotitlán', 
      'San Felipe', 'San Andrés Villa Seca', 'Champerico', 'Nuevo San Carlos', 'El Asintal'
    ],
    'Sacatepéquez': [
      'Antigua Guatemala', 'Jocotenango', 'Pastores', 'Sumpango', 'Santo Domingo Xenacoj', 
      'Santiago Sacatepéquez', 'San Bartolomé Milpas Altas', 'San Lucas Sacatepéquez', 
      'Santa Lucía Milpas Altas', 'Magdalena Milpas Altas', 'Santa María de Jesús', 
      'Ciudad Vieja', 'San Miguel Dueñas', 'Alotenango', 'San Antonio Aguas Calientes', 
      'Santa Catarina Barahona'
    ],
    'San Marcos': [
      'San Marcos', 'San Pedro Sacatepéquez', 'San Antonio Sacatepéquez', 'Comitancillo', 
      'San Miguel Ixtahuacán', 'Concepción Tutuapa', 'Tacaná', 'Sibinal', 'San José Ojetenam', 
      'San Cristóbal Cucho', 'Esquipulas Palo Gordo', 'Río Blanco', 'San Lorenzo', 
      'Tejutla', 'San Rafael Pie de la Cuesta', 'Nuevo Progreso', 'El Tumbador', 
      'El Rodeo', 'Malacatán', 'Catarina', 'Ayutla', 'Ocós', 'San Pablo', 'El Quetzal', 
      'La Reforma', 'Pajapita', 'Ixchiguán', 'San José Ojetenán', 'San Lorenzo', 
      'Tajumulco', 'La Blanca'
    ],
    'Santa Rosa': [
      'Cuilapa', 'Barberena', 'Santa Rosa de Lima', 'Casillas', 'San Rafael las Flores', 
      'Oratorio', 'San Juan Tecuaco', 'Chiquimulilla', 'Taxisco', 'Santa María Ixhuatán', 
      'Guazacapán', 'Santa Cruz Naranjo', 'Pueblo Nuevo Viñas', 'Nueva Santa Rosa'
    ],
    'Sololá': [
      'Sololá', 'San José Chacayá', 'Santa María Visitación', 'Santa Lucía Utatlán', 
      'Nahualá', 'Santa Catarina Ixtahuacán', 'Santa Clara la Laguna', 'Concepción', 
      'San Andrés Semetabaj', 'Panajachel', 'Santa Catarina Palopó', 'San Antonio Palopó', 
      'San Lucas Tolimán', 'Santa Cruz la Laguna', 'San Pablo la Laguna', 'San Marcos la Laguna', 
      'San Juan la Laguna', 'San Pedro la Laguna', 'Santiago Atitlán'
    ],
    'Suchitepéquez': [
      'Mazatenango', 'Cuyotenango', 'San Francisco Zapotitlán', 'San Bernardino', 
      'San José el Idolo', 'Santo Domingo Suchitepéquez', 'San Lorenzo', 'Samayac', 
      'San Pablo Jocopilas', 'San Antonio Suchitepéquez', 'San Miguel Panán', 
      'Chicacao', 'Patulul', 'Santa Bárbara', 'San Juan Bautista', 'Santo Tomás La Unión', 
      'Zunilito', 'Pueblo Nuevo', 'Río Bravo', 'San José La Máquina'
    ],
    'Totonicapán': [
      'Totonicapán', 'San Cristóbal Totonicapán', 'San Francisco El Alto', 
      'San Andrés Xecul', 'Momostenango', 'Santa María Chiquimula', 
      'Santa Lucía La Reforma', 'San Bartolo'
    ],
    'Zacapa': [
      'Zacapa', 'Estanzuela', 'Río Hondo', 'Gualán', 'Teculután', 'Usumatlán', 
      'Cabañas', 'San Diego', 'La Unión', 'Huité', 'San Jorge'
    ],
  };

  static List<String> obtenerMunicipios(String departamento) {
    return _municipiosPorDepartamento[departamento] ?? ['Seleccione un departamento'];
  }
}
