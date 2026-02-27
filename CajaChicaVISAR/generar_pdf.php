<?php
require_once 'config.php';

// Verificar que se recibió el ID
if (!isset($_GET['id'])) {
    die("ID de vale no especificado");
}

$vale_id = intval($_GET['id']);

// Obtener datos del vale
$db = getDB();
$stmt = $db->prepare("SELECT * FROM vales WHERE id = ?");
$stmt->execute([$vale_id]);
$vale = $stmt->fetch();

if (!$vale) {
    die("Vale no encontrado");
}

// Incluir librería TCPDF
require_once 'tcpdf/tcpdf.php';

// Crear nuevo PDF
$pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('Sistema de Vales VISAR');
$pdf->SetAuthor('VISAR - VICEDESPACHO');
$pdf->SetTitle('Vale de Caja Chica - ' . $vale['numero_vale']);
$pdf->SetSubject('Vale de Caja Chica');

// Eliminar header y footer por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Agregar página
$pdf->AddPage();

// Configurar fuente
$pdf->SetFont('helvetica', '', 10);

// Determinar el departamento a mostrar
$departamento_mostrar = $vale['departamento'];
if ($vale['departamento'] == 'OTROS' && !empty($vale['otros_departamento'])) {
    $departamento_mostrar = 'OTROS: ' . $vale['otros_departamento'];
}

// --- COLORES CORPORATIVOS (Basados en el diseño HTML) ---
$primary_color = '#004d80'; // Azul marino (Corporate Blue)
$accent_color = '#009933';  // Verde (Accent Green para la nota Pagos APN y Categoría)
$light_bg = '#f0f4f8';      // Fondo muy claro para secciones

// Contenido HTML del PDF (Integrando diseño de Opción 4)
$html = '
<style>
    .primary-color { color: ' . $primary_color . '; }
    .bg-primary { background-color: ' . $primary_color . '; }
    .accent-color { color: ' . $accent_color . '; }
    .border-primary { border-color: ' . $primary_color . '; }
    .light-bg { background-color: ' . $light_bg . '; }

    /* --- ENCABEZADO Y TÍTULOS --- */
    .header-info {
        font-size: 10px;
        color: #555;
        text-align: left;
        margin-bottom: 5px;
    }
    .main-title {
        font-size: 24px;
        font-weight: bold;
        text-align: right;
        color: ' . $primary_color . ';
        line-height: 1;
    }
    .note-apn {
        font-size: 11px;
        font-weight: bold;
        text-align: right;
        color: ' . $accent_color . ';
        margin-top: 2px;
    }
    .vale-line {
        height: 3px;
        background-color: ' . $primary_color . ';
        margin-top: 1px;
    }
    .divider {
        height: 1px;
        background-color: #ccc;
        margin-top: 10px;
        margin-bottom: 10px;
    }

    /* --- SECCIÓN DE DEPARTAMENTOS (Ajustado el color) --- */
    .section-box {
        border: 1px solid #ccc;
        padding: 8px;
        margin-bottom: 10px;
        background-color: #fff;
    }
    .section-title {
        font-size: 10px;
        font-weight: bold;
        color: ' . $primary_color . ';
        margin-bottom: 5px;
        text-transform: uppercase;
    }
    .department-item {
        font-size: 9px;
        padding: 3px 0;
        color: #555;
    }
    .department-item.selected {
        font-weight: bold;
        color: ' . $primary_color . ';
        background-color: ' . $light_bg . ';
        padding: 5px;
        margin: 2px 0;
        border-left: 3px solid ' . $primary_color . ';
    }

    /* --- CAMPOS DE DATOS --- */
    .field-label {
        font-size: 9px;
        font-weight: bold;
        color: #6b7280;
        margin-top: 5px;
        text-transform: uppercase;
    }
    .field-value {
        font-size: 11px;
        color: #000;
        border-bottom: 1px solid #ccc;
        padding: 3px 0;
    }
    .field-box {
        border: 1px solid #ccc;
        padding: 8px;
        background-color: #fff;
        min-height: 25px;
    }

    /* --- MONTO Y CATEGORÍA --- */
    .monto-box {
        font-size: 16px;
        font-weight: bold;
        color: #fff;
        background-color: ' . $primary_color . '; /* Fondo Azul Principal */
        padding: 12px;
        text-align: center;
        margin: 15px 0;
        border-radius: 5px;
        line-height: 1.2;
    }
    .categoria-box {
        border: 1px solid #ccc;
        padding: 10px;
        margin: 10px 0;
        background-color: #fff;
    }
    .categoria-selected {
        font-weight: bold;
        color: ' . $accent_color . ';
        font-size: 10px;
        background-color: #d4efdf;
        padding: 5px;
        border-left: 4px solid ' . $accent_color . ';
    }
    .descripcion-box {
        border: 1px solid #ccc;
        padding: 10px;
        background-color: #fff;
        min-height: 70px;
        font-size: 10px;
        line-height: 1.5;
    }
    .date-box {
        font-size: 10px;
        color: #555;
        text-align: right;
        margin-top: 5px;
    }

    /* --- SECCIÓN DE FIRMAS --- */
    .footer-section {
        margin-top: 30px;
        padding-top: 15px;
    }
    .signature-line {
        border-bottom: 2px solid ' . $primary_color . '; /* Línea fuerte azul */
        margin: 40px 10px 5px 10px; /* Más espacio arriba */
        width: 90%;
    }
    .signature-label {
        font-size: 10px;
        font-weight: bold;
        text-align: center;
        color: ' . $primary_color . ';
    }
    .signature-name-placeholder {
        font-size: 9px;
        text-align: center;
        color: #555;
        margin-top: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }
    td {
        padding: 5px;
    }
</style>

<!-- TABLA DE ENCABEZADO (2 COLUMNAS) -->
<table>
    <tr>
        <td style="width: 50%; vertical-align: top;">
            <div class="header-info">VISAR - VICEDESPACHO</div>
            <div class="header-info">Ministerio de Agricultura, Ganadería y Alimentación</div>
        </td>
        <td style="width: 50%; vertical-align: top;">
            <div class="main-title">VALE CAJA CHICA</div>
            <div class="vale-line"></div>
            <div class="note-apn">"Pagos APN"</div>
        </td>
    </tr>
</table>
<div class="divider"></div>

<!-- VALE NÚMERO Y FECHA -->
<table>
    <tr>
        <td style="width: 50%;">
            <div class="field-label">Vale No.</div>
            <div class="field-value primary-color" style="font-size: 14px; font-weight: bold;">' . htmlspecialchars($vale['numero_vale']) . '</div>
        </td>
        <td style="width: 50%; text-align: right;">
             <div class="field-label" style="text-align: right;">Fecha de Solicitud</div>
            <div class="field-value" style="text-align: right;">' . date('d/m/Y', strtotime($vale['fecha_solicitud'])) . '</div>
        </td>
    </tr>
</table>


<div class="field-label" style="margin-top: 15px;">NOMBRE DEL SOLICITANTE:</div>
<div class="field-box">' . htmlspecialchars($vale['nombre_solicitante']) . '</div>

<div class="monto-box">
    MONTO ENTREGADO: Q. ' . number_format($vale['monto'], 2) . '
</div>

<!-- Mantenemos la sección de Departamentos, pero con colores actualizados -->
<div class="section-box light-bg">
    <div class="section-title">Departamento Solicitante</div>
    <div style="font-size: 9px; line-height: 1.4;">
        <div class="department-item' . ($vale['departamento'] == 'DESPACHO SUPERIOR' ? ' selected' : '') . '">□ DESPACHO SUPERIOR</div>
        <div class="department-item' . ($vale['departamento'] == 'VEGETAL' ? ' selected' : '') . '">□ VEGETAL</div>
        <div class="department-item' . ($vale['departamento'] == 'AMD' ? ' selected' : '') . '">□ AMD</div>
        <div class="department-item' . ($vale['departamento'] == 'INOCUIDAD ALIMENTARIA' ? ' selected' : '') . '">□ INOCUIDAD ALIMENTARIA</div>
        <div class="department-item' . ($vale['departamento'] == 'UDAG-VISAR' ? ' selected' : '') . '">□ UDAG-VISAR</div>
        <div class="department-item' . ($vale['departamento'] == 'OTROS' ? ' selected' : '') . '">
            □ OTROS: ' . ($vale['departamento'] == 'OTROS' && !empty($vale['otros_departamento']) ? htmlspecialchars($vale['otros_departamento']) : '___________________________') . '
        </div>
    </div>
</div>


<div class="categoria-box">
    <div class="section-title">Categoría</div>
    <div class="categoria-selected">✓ ' . htmlspecialchars($vale['categoria']) . '</div>
    <div style="margin-top: 8px; font-size: 9px; color: #555;">
        <!-- Lista de otras categorías -->
         ' . ($vale['categoria'] != 'ALIMENTOS PERSONALES' ? '□ ALIMENTOS PERSONALES ' : '') . '
         ' . ($vale['categoria'] != 'INSUMOS' ? '□ INSUMOS ' : '') . '
         ' . ($vale['categoria'] != 'EQUIPO' ? '□ EQUIPO ' : '') . '
         ' . ($vale['categoria'] != 'LIBRERIA' ? '□ LIBRERIA ' : '') . '
         ' . ($vale['categoria'] != 'MATERIALES DE CONSTRUCCION' ? '□ MATERIALES DE CONSTRUCCIÓN' : '') . '
    </div>
</div>

<div class="field-label">DESCRIPCIÓN:</div>
<div class="descripcion-box">' . nl2br(htmlspecialchars($vale['descripcion'])) . '</div>


<div class="footer-section">
    <div style="font-size: 11px; font-weight: bold; color: ' . $primary_color . '; text-align: center; margin-bottom: 10px;">
        APROBACIÓN Y RECEPCIÓN
    </div>
    <table>
        <tr>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div class="signature-label">FIRMA DEL SOLICITANTE</div>
                <div class="signature-name-placeholder">Nombre: ______________________________</div>
            </td>
            <td style="width: 50%; text-align: center;">
                <div class="signature-line"></div>
                <div class="signature-label">AUTORIZADO POR</div>
                <div class="signature-name-placeholder">Nombre: ______________________________</div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 20px; font-size: 8px; color: #999; text-align: center;">
    Generado el: ' . date('d/m/Y H:i:s') . ' | Sistema de Vales de Caja Chica v1.0
</div>
';

// Escribir HTML en el PDF
$pdf->writeHTML($html, true, false, true, false, '');

// Cerrar y generar PDF
$filename = 'Vale_' . $vale['numero_vale'] . '.pdf';
$pdf->Output($filename, 'I'); // 'I' para mostrar en navegador, 'D' para descargar
?>