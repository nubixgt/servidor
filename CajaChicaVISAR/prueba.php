<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vale de Caja Chica APN - Opción 4</title>
    <!-- Incluyendo Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Definición de colores principales basada en el logo */
        :root {
            --primary-color: #004d80; /* Azul marino oscuro, muy corporativo */
            --accent-color: #009933; /* Verde para acentos */
            --light-bg: #f9fafb; /* Fondo muy claro */
            --header-line-color: #c0d9ff; /* Línea divisoria azul muy clara */
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-bg);
            padding: 2rem;
            display: flex;
            justify-content: center;
        }

        .voucher-container {
            max-width: 800px;
            width: 100%;
            background-color: white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            padding: 0; /* Quitamos padding aquí para que el contenido ocupe el borde */
        }

        .text-primary {
            color: var(--primary-color);
        }
        .bg-primary {
            background-color: var(--primary-color);
        }
        .border-primary {
            border-color: var(--primary-color);
        }

        /* Estilo para las etiquetas de datos (simulando la sección Cliente de la factura) */
        .data-label {
            font-size: 0.75rem; /* text-xs */
            font-weight: 600;
            color: #6b7280; /* gray-500 */
            text-transform: uppercase;
        }
        
        /* Estilos específicos para impresión */
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .voucher-container {
                box-shadow: none;
                border: none;
                margin: 0;
                max-width: 100%;
                padding: 0;
            }
            .print-hidden {
                display: none;
            }
        }
        
        /* Estilo para la línea de separación en las firmas */
        .signature-line-dark {
            border-bottom: 2px solid var(--primary-color);
            height: 0.5rem;
            /* Hemos quitado el 70% fijo de width para controlarlo con w-full en el HTML */
            margin: 2rem auto 0.5rem;
        }
    </style>
</head>
<body>

    <div class="voucher-container">
        
        <!-- SECCIÓN SUPERIOR: Logo, Información de la Empresa y Título del Documento -->
        <div class="p-8 pb-4">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <!-- Logo de la Empresa -->
                    <img src="MagaLogo.png" alt="Logo APN" class="h-14 w-auto object-contain mr-6">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Ministerio de Agricultura, Ganadería y Alimentación</h2>
                        <p class="text-sm text-gray-600">Dirección: Oficina Central, Edificio 5, Nivel 2</p>
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-4xl font-extrabold text-primary mb-1">VALE CAJA CHICA</h1>
                    <div class="w-full h-1 bg-primary"></div>
                    <p class="text-sm font-semibold text-accent mt-1">"Pagos APN"</p>
                </div>
            </div>
        </div>

        <!-- LÍNEA DIVISORIA PRINCIPAL (Similar a la de la factura) -->
        <div class="w-full h-px bg-gray-300 mb-6"></div>

        <!-- SECCIÓN DE DATOS DEL VALE (Simulando la sección Cliente/Fecha de la factura) -->
        <div class="px-8 mb-8 grid grid-cols-1 md:grid-cols-3 gap-y-4 gap-x-8">
            <!-- Nombre -->
            <div>
                <p class="data-label">Nombre del Solicitante:</p>
                <p class="text-lg font-semibold text-gray-900">Juan Pérez López</p>
            </div>
            <!-- Dirección/Proyecto -->
            <div>
                <p class="data-label">Dirección / Unidad:</p>
                <p class="text-lg font-semibold text-gray-900">Dirección de Planificación</p>
            </div>
            <!-- Fecha -->
            <div>
                <p class="data-label">Fecha de Emisión:</p>
                <p class="text-lg font-semibold text-gray-900">28 de Noviembre de 2025</p>
            </div>
        </div>

        <!-- SECCIÓN DE DESCRIPCIÓN Y MONTO (Simulando la tabla de ítems de la factura) -->
        <div class="mb-8 border-t border-b border-gray-300">
            <!-- Encabezado de la Tabla -->
            <div class="bg-primary p-3 flex justify-between items-center">
                <p class="text-sm font-bold text-white uppercase w-1/4">Categoría</p>
                <p class="text-sm font-bold text-white uppercase w-3/4">Descripción</p>
            </div>
            
            <!-- Fila de Datos 1 (Descripción Principal) -->
            <div class="p-3 flex justify-between border-b border-gray-200 bg-gray-50">
                <p class="text-md font-medium text-gray-800 w-1/4">Materiales de Oficina</p>
                <p class="text-md text-gray-700 w-3/4">
                    Compra de cartuchos de tinta negra y a color para impresora HP, y resmas de papel bond tamaño carta para el departamento de Administración.
                </p>
            </div>
            
            <!-- Fila de Datos 2 (Para agregar más detalles si se necesitan) -->
             <div class="p-3 flex justify-between border-b border-gray-200">
                <p class="text-md font-medium text-gray-800 w-1/4">Viáticos</p>
                <p class="text-md text-gray-700 w-3/4">
                    Pago de transporte local para entrega de documentos oficiales en la capital.
                </p>
            </div>
        </div>
        
        <!-- SECCIÓN DE MONTOS FINALES -->
        <div class="flex justify-end px-8 mb-10">
            <div class="w-full md:w-1/2">
                <!-- MONTO TOTAL DESTACADO -->
                <div class="bg-primary text-white p-4 flex justify-between items-center rounded-tr-lg rounded-bl-lg">
                    <span class="font-bold text-lg uppercase">MONTO ENTREGADO:</span>
                    <span class="text-3xl font-extrabold">Q 750.00</span>
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN DE FIRMAS (Modificada a un solo espacio) -->
        <div class="px-8 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mb-8">Firma de Recepción</h2>
            <div class="flex justify-center pt-8">
                <!-- Única Firma de Recibido, centrada y con ancho definido -->
                <div class="text-center w-full md:w-2/5">
                    <div class="signature-line-dark w-full"></div>
                    <p class="text-lg font-bold text-primary mt-2">Firma de Recibido</p>
                    <p class="text-sm font-medium text-gray-600">(Nombre Completo y Documento de Identidad)</p>
                </div>
            </div>
        </div>

        <!-- FOOTER / NOTA LEGAL -->
        <div class="p-8 pt-4 border-t border-gray-200 bg-gray-50 print-hidden">
            <div class="text-sm text-gray-600 mb-6">
                <p class="font-semibold text-primary text-lg">Nota Importante:</p>
                <p class="text-xs">La **Firma de recibido** constituye la confirmación por parte del solicitante de haber recibido el monto indicado. Este vale es un documento interno y debe ser liquidado con los comprobantes fiscales originales.</p>
            </div>
            
            <div class="flex justify-end">
                <button onclick="window.print()" class="px-8 py-3 bg-primary text-white font-bold rounded-lg shadow-xl hover:bg-opacity-90 transition duration-150 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path></svg>
                    Imprimir Vale
                </button>
            </div>
        </div>

    </div>
</body>
</html>