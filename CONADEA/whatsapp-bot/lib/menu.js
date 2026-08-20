const config = require('../config');

function textoBienvenida(nombre) {
    const primerNombre = (nombre || '').trim().split(/\s+/)[0] || 'ahí';
    return `¡Hola, ${primerNombre}! 👋 Soy *AgroIA*, tu asistente virtual de CONADEA.\n¿En qué te puedo ayudar hoy?`;
}

function textoMenu() {
    return (
        '1️⃣ Mis cursos\n' +
        '2️⃣ Mi progreso general\n' +
        '3️⃣ Consulta técnica (cultivo/hato)\n' +
        '4️⃣ Hablar con soporte\n\n' +
        'Responde con el número de la opción (o *0* para salir).'
    );
}

function textoNoRegistrado() {
    return (
        '🌱 No encontré una cuenta de CONADEA registrada con este número.\n\n' +
        'Descarga la app CONADEA y regístrate usando este mismo número de WhatsApp para poder ayudarte por aquí.'
    );
}

function textoCursos(progreso) {
    if (!progreso.cursos.length) {
        return 'Todavía no tienes cursos disponibles. Revisa la app en un rato 🌱';
    }
    const etiquetas = { aprobado: '✅ Aprobado', en_progreso: '🟡 En progreso', pendiente: '⚪ Sin empezar' };
    const lineas = progreso.cursos.map((c) => {
        let linea = `*${c.titulo}* — ${etiquetas[c.estado]} (${c.porcentaje}%)`;
        if (c.estado === 'en_progreso' && c.proxima_leccion) {
            linea += `\n   ↳ Siguiente: ${c.proxima_leccion}`;
        }
        return linea;
    });
    return '📚 *Tus cursos:*\n\n' + lineas.join('\n\n');
}

function textoResumen(progreso) {
    const r = progreso.resumen;
    return (
        '📊 *Tu progreso general*\n\n' +
        `Avance general: ${r.porcentaje_general}%\n` +
        `✅ Aprobados: ${r.aprobados}\n` +
        `🟡 En progreso: ${r.en_progreso}\n` +
        `⚪ Sin empezar: ${r.pendientes}`
    );
}

function textoPideAdjunto() {
    return (
        '🌱 Cuéntame qué está pasando con tu cultivo o tu hato.\n\n' +
        'Envíame una *foto*, una *nota de voz* o tu *ubicación*, y si quieres agrega un mensaje explicando el problema. ' +
        'Un técnico lo va a revisar y te responderá pronto.'
    );
}

function textoConsultaRecibida() {
    return '✅ ¡Recibido! Un técnico revisará tu consulta y te responderá pronto por este mismo medio.';
}

function textoSoporte() {
    return config.soporte.texto;
}

function textoOpcionInvalida() {
    return 'No entendí esa opción 🤔';
}

function textoDespedida() {
    return '¡Gracias por escribir! Cuando quieras, mándame un mensaje y volvemos a empezar. 🌱';
}

function textoErrorGenerico() {
    return 'Ocurrió un problema de mi lado, intenta de nuevo en unos minutos 🙏';
}

module.exports = {
    textoBienvenida,
    textoMenu,
    textoNoRegistrado,
    textoCursos,
    textoResumen,
    textoPideAdjunto,
    textoConsultaRecibida,
    textoSoporte,
    textoOpcionInvalida,
    textoDespedida,
    textoErrorGenerico,
};
