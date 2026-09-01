const config = require('../config');

// Letra -> nombre de día, para mostrarle al usuario (horarios_curso.dias
// guarda solo las letras, ver Database/011_horarios_curso.sql).
const NOMBRES_DIA = { L: 'lunes', M: 'martes', X: 'miércoles', J: 'jueves', V: 'viernes', S: 'sábado', D: 'domingo' };

function primerNombre(nombreCompleto) {
    return (nombreCompleto || '').trim().split(/\s+/)[0] || 'ahí';
}

function diasLegibles(dias) {
    return dias
        .split(',')
        .map((d) => NOMBRES_DIA[d] || d)
        .join(', ');
}

// El horario armado en el wizard llega como "HH:MM", pero el que viene de la
// base de datos (SELECT sobre una columna TIME) trae segundos ("HH:MM:SS").
function horaLegible(hora) {
    return hora.slice(0, 5);
}

function urlLogin() {
    return `${config.frontendBaseUrl}/login`;
}

function urlCurso(cursoId) {
    return `${config.frontendBaseUrl}/curso/${cursoId}`;
}

function textoNoRegistrado() {
    return (
        '🌱 No encontré una cuenta de CONADEA registrada con este número.\n\n' +
        `Crea tu cuenta o inicia sesión aquí, usando este mismo número de WhatsApp: ${urlLogin()}\n\n` +
        'Cuando termines, escríbeme de nuevo por aquí para configurar tu horario de estudio.'
    );
}

// Igual que textoNoRegistrado(), pero para números que están en el directorio
// de técnicos (Database/012_directorio_tecnicos.sql) y aún no tienen cuenta.
function textoNoRegistradoTecnico(nombre) {
    return (
        `🌱 Hola ${primerNombre(nombre)} de CONADEA. Te saluda AgroIA. No encontré algún usuario registrado con este número.\n\n` +
        `Crea tu cuenta o inicia sesión aquí, usando este mismo número de WhatsApp: ${urlLogin()}\n\n` +
        'Cuando termines, escríbeme de nuevo por aquí para configurar tu horario de estudio.'
    );
}

function textoBienvenida(nombreCompleto) {
    return `Hola ${primerNombre(nombreCompleto)}. Soy tu AgroIA 🌱`;
}

function textoListaCursos(cursos) {
    const lineas = cursos.map((c, i) => `${i + 1}) ${c.titulo}`).join('\n');
    return (
        '¿Para qué curso quieres configurar tu horario de estudio?\n\n' +
        `${lineas}\n\n` +
        'Responde con el *número* del curso.'
    );
}

function textoPreguntaMinutos(cursoTitulo) {
    return `Perfecto, *${cursoTitulo}*. ¿Cuántos minutos al día quieres dedicarle? (te recomendamos *15*)`;
}

function textoPreguntaHora() {
    return '¿A qué hora te gustaría estudiar? Escríbela así: *7:00 am* o *19:00*.';
}

function textoPreguntaDias() {
    return (
        '¿Qué días? Escribe las iniciales separadas por coma:\n' +
        'L=lunes, M=martes, X=miércoles, J=jueves, V=viernes, S=sábado, D=domingo\n\n' +
        'Ejemplo: *L,M,X,J,V*  (o escribe *todos* para los 7 días)'
    );
}

function textoResumenHorario({ cursoTitulo, dias, hora, duracionMinutos }) {
    return (
        `✅ Listo, así quedó tu rutina para *${cursoTitulo}*:\n\n` +
        `🕐 Hora: ${hora}\n` +
        `📅 Días: ${diasLegibles(dias)}\n` +
        `⏱️ Duración: ${duracionMinutos} minutos\n\n` +
        `Te voy a avisar por aquí 10 minutos antes de cada sesión. Escribe *horario* cuando quieras cambiar esto, o *ayuda* si necesitas soporte.`
    );
}

function textoEstadoYComandos(nombreCompleto, horarios) {
    const nombre = primerNombre(nombreCompleto);
    if (!horarios.length) {
        return `${nombre}, todavía no tienes un horario de estudio configurado. Escribe *horario* para armarlo. 🌱`;
    }

    const lineas = horarios.map(
        (h) => `• *${h.curso_titulo}* — ${horaLegible(h.hora)} (${diasLegibles(h.dias)}), ${h.activo ? 'activo' : 'pausado'}`
    );

    return (
        `${nombre}, este es tu horario de estudio:\n\n${lineas.join('\n')}\n\n` +
        'Puedes escribirme:\n' +
        '• *horario* — para configurar otro curso o cambiar la hora\n' +
        '• *pausar avisos* / *reanudar avisos*\n' +
        '• *ayuda* — para hablar con soporte'
    );
}

function textoRecordatorio({ cursoTitulo, hora, duracionMinutos, cursoId }) {
    return (
        `⏰ Hola, en 10 minutos (${horaLegible(hora)}) toca tu sesión de *${cursoTitulo}* (${duracionMinutos} min).\n\n` +
        `Abre tu curso aquí: ${urlCurso(cursoId)}\n\n` +
        'También puedes responderme:\n' +
        '• *más tarde* — te aviso de nuevo en 30 minutos\n' +
        '• *cambiar hora* — para actualizar tu horario\n' +
        '• *pausar avisos* — si no quieres más recordatorios de este curso'
    );
}

function textoPospuesto(minutos) {
    return `Listo, te vuelvo a avisar en ${minutos} minutos. 🌱`;
}

function textoPausado(cursoTitulo) {
    return `Avisos pausados para *${cursoTitulo}*. Escribe *reanudar avisos* cuando quieras que te vuelva a recordar.`;
}

function textoReanudado(cursoTitulo) {
    return `Avisos reanudados para *${cursoTitulo}*. 🌱`;
}

function textoSoporte(nombreCompleto) {
    const nombre = primerNombre(nombreCompleto);
    return `${nombre}, así puedes contactar a soporte:\n\n${config.soporte.texto}`;
}

function textoCursoInvalido() {
    return 'Ese número no corresponde a ningún curso de la lista. Intenta de nuevo.';
}

function textoMinutosInvalidos() {
    return 'Escribe solo el número de minutos, entre 5 y 180. Ejemplo: *15*';
}

function textoHoraInvalida() {
    return 'No entendí esa hora. Escríbela así: *7:00 am* o *19:00*.';
}

function textoDiasInvalidos() {
    return 'No reconocí esos días. Usa las iniciales L,M,X,J,V,S,D separadas por coma, o escribe *todos*. Ejemplo: *L,M,X,J,V*';
}

function textoSinHorarioParaComando() {
    return 'No encontré un horario configurado todavía. Escribe *horario* para crear uno.';
}

function textoDespedida(nombreCompleto) {
    const nombre = primerNombre(nombreCompleto);
    return `¡Gracias por escribir, ${nombre}! Cuando quieras, mándame un mensaje y volvemos a empezar. 🌱`;
}

function textoErrorGenerico() {
    return 'Ocurrió un problema de mi lado, intenta de nuevo en unos minutos 🙏';
}

module.exports = {
    primerNombre,
    textoNoRegistrado,
    textoNoRegistradoTecnico,
    textoBienvenida,
    textoListaCursos,
    textoPreguntaMinutos,
    textoPreguntaHora,
    textoPreguntaDias,
    textoResumenHorario,
    textoEstadoYComandos,
    textoRecordatorio,
    textoPospuesto,
    textoPausado,
    textoReanudado,
    textoSoporte,
    textoCursoInvalido,
    textoMinutosInvalidos,
    textoHoraInvalida,
    textoDiasInvalidos,
    textoSinHorarioParaComando,
    textoDespedida,
    textoErrorGenerico,
    urlCurso,
};
