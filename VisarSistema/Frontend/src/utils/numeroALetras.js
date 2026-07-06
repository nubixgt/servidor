export function numeroALetras(num) {
    const unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
    const decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
    const especiales = ['DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE'];
    const centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

    if (num === 0) return 'CERO QUETZALES EXACTOS';

    let entero = Math.floor(num);
    const decimal = Math.round((num - entero) * 100);

    let letras = '';

    // Miles
    if (entero >= 1000) {
        const miles = Math.floor(entero / 1000);
        if (miles === 1) {
            letras += 'MIL ';
        } else {
            letras += convertirGrupo(miles) + ' MIL ';
        }
        entero = entero % 1000;
    }

    // Centenas, decenas y unidades
    if (entero > 0) {
        letras += convertirGrupo(entero);
    }

    letras += ' QUETZALES';

    if (decimal > 0) {
        letras += ' CON ' + convertirGrupo(decimal) + ' CENTAVOS';
    } else {
        letras += ' EXACTOS';
    }

    return letras.trim();

    function convertirGrupo(n) {
        if (n === 0) return '';
        if (n === 100) return 'CIEN';

        let resultado = '';

        // Centenas
        if (n >= 100) {
            resultado += centenas[Math.floor(n / 100)] + ' ';
            n = n % 100;
        }

        // Decenas y unidades
        if (n >= 10 && n < 20) {
            resultado += especiales[n - 10];
        } else {
            if (n >= 20) {
                resultado += decenas[Math.floor(n / 10)];
                if (n % 10 > 0) {
                    resultado += ' Y ' + unidades[n % 10];
                }
            } else if (n > 0) {
                resultado += unidades[n];
            }
        }

        return resultado.trim();
    }
}
