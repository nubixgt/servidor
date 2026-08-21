module.exports = {
    port: process.env.PORT || 3000,

    // Debe coincidir exactamente con Backend/config/whatsapp.php
    apiKey: 'CONADEA-Wsp2026#AsistenteAgroIA',

    // Backend PHP de CONADEA — igual que ApiConfig.baseUrl en la app Flutter,
    // SIN /api/v1: el .htaccess reescribe cualquier ruta bajo Backend/ hacia
    // el entry point real, y el Router espera la ruta pública "limpia"
    // (ej. /asistente/usuario), no la ruta física del archivo PHP.
    backendBaseUrl: 'https://m.nubix.gt/CONADEA/Backend',

    soporte: {
        texto: '🆘 Puedes hablar con soporte MAGA AgroIA al +502 0000-0000, de lunes a viernes de 8:00 a 16:00.\n\nTambién puedes escribir tu duda aquí mismo y te ayudamos apenas podamos.'
    },

    // Minutos de inactividad tras los que se olvida en qué paso del menú
    // se quedó una conversación (limpieza de memoria, no persiste a disco).
    inactividadMinutos: 30,
};
