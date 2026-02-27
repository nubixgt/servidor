<?php
require_once 'config.php';

// Verificar que se recibió un ID
if (!isset($_GET['id'])) {
    die("Error: No se especificó un vale");
}

$vale_id = intval($_GET['id']);

// Obtener datos del vale
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM vales WHERE id = ?");
    $stmt->execute([$vale_id]);
    $vale = $stmt->fetch();
    
    if (!$vale) {
        die("Error: Vale no encontrado");
    }
} catch(Exception $e) {
    die("Error al obtener el vale: " . $e->getMessage());
}

// Formatear fecha en español
function formatearFechaES($fecha) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $timestamp = strtotime($fecha);
    $dia = date('d', $timestamp);
    $mes = $meses[date('n', $timestamp)];
    $anio = date('Y', $timestamp);
    
    return "$dia de $mes de $anio";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vale de Caja Chica <?php echo htmlspecialchars($vale['numero_vale']); ?> - MAGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Colores MAGA basados en el logo */
        :root {
            --maga-azul-oscuro: #1e3a5f;
            --maga-cyan: #0abde3;
            --maga-cyan-claro: #48d1ff;
            --light-bg: #f9fafb;
            --header-line-color: #c0d9ff;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, #2a4a6f 50%, #0097c7 100%);
            padding: 2rem;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .voucher-container {
            max-width: 900px;
            width: 100%;
            background-color: white;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .text-primary {
            color: var(--maga-azul-oscuro);
        }
        
        .bg-primary {
            background: linear-gradient(135deg, var(--maga-azul-oscuro) 0%, #2a4a6f 100%);
        }
        
        .bg-accent {
            background: linear-gradient(135deg, var(--maga-cyan) 0%, var(--maga-cyan-claro) 100%);
        }
        
        .border-primary {
            border-color: var(--maga-azul-oscuro);
        }

        .data-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        @media print {
            @page {
                size: letter;
                margin: 0.5cm;
            }
            
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .voucher-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
            }
            
            .print-hidden,
            .no-print {
                display: none !important;
            }
            
            /* IMPORTANTE: Mantener colores en impresión */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* Reducir padding y margin para impresión */
            .print-compact {
                padding: 0.3rem !important;
                margin: 0.2rem 0 !important;
            }
            
            .print-header {
                padding: 0.5rem !important;
            }
            
            .print-section {
                padding: 0.4rem 0.8rem !important;
                margin: 0.3rem 0 !important;
            }
            
            /* Reducir tamaño de fuentes para impresión */
            h1 {
                font-size: 1.5rem !important;
                margin: 0 !important;
            }
            
            h2 {
                font-size: 1rem !important;
                margin: 0.2rem 0 !important;
            }
            
            .print-text-lg {
                font-size: 0.9rem !important;
            }
            
            .print-text-base {
                font-size: 0.85rem !important;
            }
            
            .print-text-sm {
                font-size: 0.75rem !important;
            }
            
            .print-text-xs {
                font-size: 0.65rem !important;
            }
            
            /* Optimizar logo */
            .logo-print {
                height: 2.5rem !important;
                width: auto !important;
            }
            
            /* Reducir espaciado de grid */
            .print-grid {
                gap: 0.5rem !important;
            }
            
            /* Línea de firma más compacta */
            .signature-line-dark {
                margin: 1rem auto 0.3rem !important;
            }
            
            /* Badge de categoría más compacto */
            .category-badge {
                padding: 0.4rem 0.6rem !important;
                margin-bottom: 0.3rem !important;
            }
            
            /* Sección de monto más compacta */
            .print-monto {
                padding: 0.8rem 1rem !important;
                font-size: 1.8rem !important;
            }
            
            /* Forzar colores de fondo en impresión */
            .bg-primary,
            .bg-accent,
            .bg-gradient-to-br,
            .category-badge,
            [class*="bg-"] {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .bg-primary {
                background: linear-gradient(135deg, #1e3a5f 0%, #2a4a6f 100%) !important;
            }
            
            .bg-accent {
                background: linear-gradient(135deg, #0abde3 0%, #48d1ff 100%) !important;
            }
            
            .category-badge {
                border-left: 4px solid #0abde3 !important;
            }
            
            [class*="bg-yellow"],
            [class*="bg-green"],
            [class*="bg-red"],
            [class*="bg-blue"] {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            /* Eliminar espaciado innecesario */
            .print-no-mb {
                margin-bottom: 0 !important;
            }
            
            .observaciones-print {
                padding: 0.3rem 0.5rem !important;
                margin: 0.3rem 0 !important;
            }
        }
        
        .signature-line-dark {
            border-bottom: 2px solid var(--maga-azul-oscuro);
            height: 0.5rem;
            margin: 2rem auto 0.5rem;
        }
        
        .category-badge {
            background: linear-gradient(135deg, rgba(10, 189, 227, 0.1) 0%, rgba(72, 209, 255, 0.05) 100%);
            border-left: 4px solid var(--maga-cyan);
            padding: 0.75rem 1rem;
            border-radius: 6px;
        }
        
        .hover-lift {
            transition: all 0.3s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(10, 189, 227, 0.2);
        }
    </style>
    <script>
        function confirmarEliminacion() {
            return confirm('¿Está seguro que desea eliminar este vale? Esta acción no se puede deshacer.');
        }
    </script>
</head>
<body>

    <div class="voucher-container">
        
        <!-- SECCIÓN SUPERIOR: Logo, Información y Título -->
        <div class="p-8 pb-6 bg-gradient-to-br from-gray-50 to-white print-header">
            <div class="flex justify-between items-start flex-wrap gap-4">
                <div class="flex items-center flex-1 min-w-[300px]">
                    <!-- Logo MAGA -->
                    <img src="MagaLogo.png" alt="Logo MAGA" class="h-16 w-auto object-contain mr-6 logo-print">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 leading-tight print-text-base">Ministerio de Agricultura,<br>Ganadería y Alimentación</h2>
                        <!-- <p class="text-sm text-gray-600 mt-1 print-text-xs">VISAR - Viceministerio de Sanidad Agropecuaria</p> -->
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-4xl font-extrabold text-primary mb-2">VALE CAJA CHICA</h1>
                    <div class="w-full h-1 bg-accent rounded-full"></div>
                    <p class="text-lg font-bold text-primary mt-2 print-text-base"><?php echo htmlspecialchars($vale['numero_vale']); ?></p>
                    <p class="text-sm text-gray-500 print-text-xs">Fondos APN</p>
                </div>
            </div>
        </div>

        <!-- LÍNEA DIVISORIA PRINCIPAL -->
        <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-gray-300 to-transparent"></div>

        <!-- SECCIÓN DE DATOS DEL VALE -->
        <div class="px-8 py-6 bg-white print-section">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 print-grid">
                <!-- Nombre del Solicitante -->
                <div class="hover-lift">
                    <p class="data-label mb-2 print-text-xs">Nombre del Solicitante:</p>
                    <p class="text-lg font-semibold text-gray-900 print-text-sm"><?php echo htmlspecialchars($vale['nombre_solicitante']); ?></p>
                </div>
                
                <!-- Departamento -->
                <div class="hover-lift">
                    <p class="data-label mb-2 print-text-xs">Departamento / Unidad:</p>
                    <p class="text-lg font-semibold text-gray-900 print-text-sm">
                        <?php 
                        echo htmlspecialchars($vale['departamento']);
                        if ($vale['departamento'] === 'OTROS' && !empty($vale['otros_departamento'])) {
                            echo '<br><span class="text-sm text-gray-600 print-text-xs">' . htmlspecialchars($vale['otros_departamento']) . '</span>';
                        }
                        ?>
                    </p>
                </div>
                
                <!-- Fecha de Emisión -->
                <div class="hover-lift">
                    <p class="data-label mb-2 print-text-xs">Fecha de Emisión:</p>
                    <p class="text-lg font-semibold text-gray-900 print-text-sm"><?php echo formatearFechaES($vale['fecha_solicitud']); ?></p>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE DESCRIPCIÓN Y CATEGORÍA -->
        <div class="mb-6 border-t-2 border-gray-200 print-no-mb">
            <!-- Encabezado de la Tabla -->
            <div class="bg-primary p-4 flex justify-between items-center print-compact">
                <p class="text-sm font-bold text-white uppercase w-1/4 print-text-xs">Categoría</p>
                <p class="text-sm font-bold text-white uppercase w-3/4 print-text-xs">Descripción del Gasto</p>
            </div>
            
            <!-- Contenido -->
            <div class="p-6 bg-gray-50 print-section">
                <div class="category-badge mb-4">
                    <p class="text-sm font-semibold text-gray-600 mb-1 print-text-xs">CATEGORÍA:</p>
                    <p class="text-xl font-bold text-primary print-text-base"><?php echo htmlspecialchars($vale['categoria']); ?></p>
                </div>
                
                <div class="bg-white p-6 rounded-lg border-2 border-gray-200 print-compact">
                    <p class="text-sm font-semibold text-gray-600 mb-3 print-text-xs">DESCRIPCIÓN:</p>
                    <p class="text-base text-gray-800 leading-relaxed whitespace-pre-line print-text-sm"><?php echo htmlspecialchars($vale['descripcion']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN DE MONTOS FINALES -->
        <div class="flex justify-end px-8 mb-8 print-section">
            <div class="w-full md:w-1/2">
                <div class="bg-accent text-white p-6 flex justify-between items-center rounded-xl shadow-lg print-monto">
                    <span class="font-bold text-lg uppercase print-text-sm">Monto:</span>
                    <span class="text-4xl font-extrabold">Q <?php echo number_format($vale['monto'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- SECCIÓN DE FIRMAS -->
        <div class="px-8 mb-8 print-section">
            
            <div class="flex justify-center pt-4 print-compact">
                <div class="text-center w-full md:w-2/5">
                    <div class="signature-line-dark w-full"></div>
                    
                    <p class="text-lg font-bold text-primary mt-3 print-text-sm">Nombre y Firma de Recibido</p>
                    
                </div>
            </div>
        </div>
        
        <?php if (!empty($vale['observaciones'])): ?>
        <!-- OBSERVACIONES (si existen) -->
        <div class="px-8 mb-6 observaciones-print">
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg print-compact">
                <p class="text-sm font-semibold text-yellow-800 mb-2 print-text-xs">OBSERVACIONES:</p>
                <p class="text-sm text-yellow-900 print-text-xs"><?php echo nl2br(htmlspecialchars($vale['observaciones'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- FOOTER / NOTA LEGAL -->
        <div class="p-8 pt-6 border-t-2 border-gray-200 bg-gradient-to-br from-gray-50 to-white print-hidden">
            <div class="mb-6">
                <p class="font-semibold text-primary text-lg mb-2">Nota Importante:</p>
                <p class="text-sm text-gray-700 leading-relaxed">
                    La <strong>firma de recibido</strong> constituye la confirmación por parte del solicitante de haber recibido el monto indicado. 
                    Este vale es un documento interno y debe ser liquidado con los comprobantes fiscales originales dentro de los 5 días hábiles siguientes.
                </p>
            </div>
            
            <!-- INSTRUCCIONES DE IMPRESIÓN -->
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                <p class="font-semibold text-blue-800 text-sm mb-2">💡 Para imprimir con colores:</p>
                <ul class="text-xs text-blue-900 space-y-1 ml-4 list-disc">
                    <li><strong>Chrome/Edge:</strong> En ventana de impresión → Más opciones → Activar "Gráficos de fondo"</li>
                    <li><strong>Firefox:</strong> En ventana de impresión → Activar "Imprimir fondos"</li>
                    <li><strong>Para PDF:</strong> Selecciona "Guardar como PDF" como destino</li>
                </ul>
            </div>
            
            <div class="text-xs text-gray-500 mb-6 border-t pt-4">
                <p><strong>Generado el:</strong> <?php echo date('d/m/Y H:i:s'); ?></p>
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($vale['usuario_creador']); ?></p>
            </div>
            
            <div class="flex gap-4 justify-end flex-wrap">
                <a href="listar_vales.php" class="px-6 py-3 bg-gray-500 text-white font-bold rounded-lg shadow-lg hover:bg-gray-600 transition duration-150 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al Listado
                </a>
                
               
                <button onclick="window.print()" class="px-8 py-3 bg-primary text-white font-bold rounded-lg shadow-xl hover:bg-opacity-90 transition duration-150 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                    </svg>
                    Imprimir Vale
                </button>
            </div>
        </div>

    </div>

</body>
</html>