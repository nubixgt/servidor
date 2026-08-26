export const numeroALetras = (num) => {
    if (num === 0) return 'CERO QUETZALES EXACTOS';
    
    const partes = parseFloat(num).toFixed(2).split('.');
    const ent = parseInt(partes[0]);
    const dec = parseInt(partes[1]);
    
    let letras = convertirGrupo(ent);
    
    // Agregar moneda
    letras += ' QUETZALES';
    
    // Agregar centavos
    if (dec > 0) {
        letras += ` CON ${convertirGrupo(dec)} CENTAVOS`;
    } else {
        letras += ' EXACTOS';
    }
    
    return letras;
};

function convertirGrupo(n) {
    const unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    const decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    const diez_veinte = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];
    
    if (n === 100) return 'CIEN';
    
    let salida = '';
    
    // Miles
    if (n >= 1000) {
        const miles = Math.floor(n / 1000);
        if (miles === 1) salida += 'MIL ';
        else salida += convertirGrupo(miles) + ' MIL ';
        n %= 1000;
    }
    
    // Centenas
    if (n >= 100) {
        const c = Math.floor(n / 100);
        salida += centenas[c] + ' ';
        n %= 100;
    }
    
    // Decenas
    if (n >= 20) {
        const d = Math.floor(n / 10);
        salida += decenas[d];
        if (n % 10 > 0) salida += ' Y ';
        n %= 10;
    } else if (n >= 10) {
        salida += diez_veinte[n - 10];
        return salida;
    }
    
    // Unidades
    if (n > 0) {
        salida += unidades[n];
    }
    
    return salida.trim();
}
