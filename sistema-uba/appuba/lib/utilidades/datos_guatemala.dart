class DatosGuatemala {
  static const List<String> departamentos = [
    'Guatemala', 'Alta Verapaz', 'Baja Verapaz', 'Chimaltenango', 'Chiquimula',
    'El Progreso', 'Escuintla', 'Huehuetenango', 'Izabal', 'Jalapa',
    'Jutiapa', 'Petén', 'Quetzaltenango', 'Quiché', 'Retalhuleu',
    'Sacatepéquez', 'San Marcos', 'Santa Rosa', 'Sololá', 'Suchitepéquez',
    'Totonicapán', 'Zacapa'
  ];

  static List<String> obtenerMunicipios(String departamento) {
    // Simplified list for now
    switch (departamento) {
      case 'Guatemala':
        return ['Guatemala', 'Mixco', 'Villa Nueva', 'Santa Catarina Pinula'];
      default:
        return ['$departamento Centro', 'Municipio 2', 'Municipio 3'];
    }
  }
}
