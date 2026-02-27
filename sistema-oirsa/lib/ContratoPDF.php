<?php
/**
 * Clase para generar PDFs de contratos
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/pdf_helpers.php';

class ContratoPDF extends TCPDF
{
    private $contrato;

    /**
     * Constructor
     * @param array $contratoData Datos del contrato desde la base de datos
     */
    public function __construct($contratoData)
    {
        parent::__construct('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $this->contrato = $contratoData;
        $this->configurar();
    }

    /**
     * Configuración inicial del PDF
     */
    private function configurar()
    {
        // Metadata
        $this->SetCreator('Sistema Oirsa - MAGA');
        $this->SetAuthor('Ministerio de Agricultura, Ganadería y Alimentación');
        $this->SetTitle('Contrato ' . $this->contrato['numero_contrato']);
        $this->SetSubject('Contrato de Servicios Técnicos');

        // Márgenes (izquierda, superior, derecha)
        $this->SetMargins(20, 40, 20);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(25);

        // Salto de página automático
        $this->SetAutoPageBreak(true, 30);

        // Fuente por defecto: Helvetica 12
        $this->SetFont('helvetica', '', 12);
    }

    /**
     * Encabezado del PDF (se repite en cada página)
     */
    public function Header()
    {
        // Logo centrado (reducido a 60mm de ancho)
        $logoPath = __DIR__ . '/../assets/images/maga_logo.png';
        if (file_exists($logoPath)) {
            // Centrar el logo: (ancho página - ancho logo) / 2
            // Página LETTER = 215.9mm, logo = 60mm, entonces X = (215.9 - 60) / 2 = 77.95
            $this->Image($logoPath, 78, 10, 60, 0, 'PNG', '', '', false, 300, '', false, false, 0);
        }
        $this->Ln(25);
    }

    /**
     * Pie de página del PDF (se repite en cada página)
     */
    public function Footer()
    {
        $this->SetY(-20);

        // Línea separadora
        $this->Line(20, $this->GetY(), 195, $this->GetY());
        $this->Ln(2);

        // Dirección y teléfono
        $this->SetFont('helvetica', '', 8);
        $this->Cell(0, 4, '7ma avenida 12-90 zona 13, edificio Monja Blanca', 0, 1, 'C');
        $this->Cell(0, 4, 'PBX: 2413 7000, extensión 7035', 0, 1, 'C');

        // Número de página
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 4, 'Página ' . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, false, 'C');
    }

    /**
     * Genera el contrato completo
     */
    public function generarContrato()
    {
        $this->AddPage();

        // Título
        $this->agregarTitulo();

        // Resetear fuente a normal antes del contenido
        $this->SetFont('helvetica', '', 12);

        // Configurar interlineado de 1.5
        $this->setCellHeightRatio(1.5);

        // Construir todo el contenido como un solo bloque HTML
        $contenidoCompleto = $this->construirContenidoCompleto();

        // Renderizar todo el contenido de una vez
        $this->writeHTMLCell(0, 0, null, null, $contenidoCompleto, 0, 1, false, true, 'J', true);

        // Firmas
        $this->agregarFirmas();
    }

    /**
     * Construye todo el contenido del contrato como un solo bloque HTML
     */
    private function construirContenidoCompleto()
    {
        // Convertir datos para la introducción
        $fechaTexto = fechaATexto($this->contrato['fecha_contrato']);
        $nombreContratista = $this->contrato['nombre_completo'];
        $edadTexto = numeroALetras($this->contrato['edad']);
        $estadoCivil = strtolower($this->contrato['estado_civil']);
        $profesion = $this->contrato['profesion'];
        $domicilio = $this->contrato['domicilio'];
        $dpiFormateado = formatearDPI($this->contrato['dpi']);
        $dpiLetras = dpiALetras($this->contrato['dpi']);

        $html = "En la ciudad de Guatemala, el día $fechaTexto, <b>NOSOTROS:</b> Por la parte <b>MAYRA LISSETE MOTA PADILLA DE HODGSON</b>, de cincuenta y cuatro años de edad, casada, guatemalteca, Médico Veterinario, del domicilio de Guatemala, departamento de Guatemala, me identifico con el Documento Personal de Identificación extendido por el Registro Nacional de las Personas de la República de Guatemala, Código Único de Identificación -CUI- número dos mil ciento noventa y ocho espacio veintisiete mil quinientos dieciséis espacio cero ciento uno (2198 27516 0101) actuó en mi calidad de <b>VICEMINISTRA DEL VICEMINISTERIO DE SANIDAD AGROPECUARIA Y REGULACIONES</b>, como lo acredito con copia de los siguientes documentos: a) Acuerdo Gubernativo numero dos (2) de mi nombramiento de treinta y uno de marzo del dos mil veinticinco (31-03-2025), b) Acta de toma de posesión de cargo número cero ochenta guion dos mil veinticinco (080-2025) de fecha dos de abril del dos mil veinticinco (2-4-2025) institución de derecho público en adelante denominado <b>\"EL MINISTERIO\"</b> o <b>\"EL CONTRATANTE\"</b>; y por otra parte <b>$nombreContratista</b>, de $edadTexto años de edad, $estadoCivil, $profesion, guatemalteco, del domicilio de $domicilio, portador del Documento Personal de Identificación -DPI- con código único de identificación -CUI- número $dpiLetras ($dpiFormateado), extendido por la República de Guatemala, Centro América; en adelante denominado <b>\"EL CONTRATISTA\"</b>. En lo sucesivo ambas partes convenimos en celebrar el presente <b>CONTRATO DE PRESTACIÓN DE SERVICIOS TECNICOS</b> que se regirá por las cláusulas siguientes: ";

        // PRIMERA
        $terminoContratacion = $this->contrato['termino_contratacion'] ?? 'SERVICIOS TECNICOS PARA ASESORAR EN TEMAS DE DESARROLLO DE MEDIOS DIGITALES ESTE EN EL MINISTERIO DE AGRICULTURA, GANADERÍA Y ALIMENTACIÓN';
        $html .= "<b>PRIMERA: OBJETO DE LA CONTRATACIÓN.</b> El Ministerio de Agricultura, Ganadería y Alimentación con la finalidad de desarrollar sus actividades de forma eficiente y sistemática para el logro de los objetivos de la Institución, considera necesaria la contratación de <b>\"$terminoContratacion\"</b>, en apoyo al funcionamiento estratégico/operativo del Ministerio de Agricultura Ganadería y Alimentación -MAGA-. ";

        // SEGUNDA
        $html .= "<b>SEGUNDA: PRINCIPALES ACTIVIDADES A REALIZAR.</b> Sus principales actividades no limitativas, serán: ";
        for ($i = 1; $i <= 10; $i++) {
            $termino = $this->contrato["termino$i"] ?? '';
            if (!empty($termino)) {
                $html .= "<b>$i)</b> $termino ";
            }
        }

        // TERCERA
        $fechaInicioTexto = fechaATexto($this->contrato['fecha_inicio']);
        $fechaFinTexto = fechaATexto($this->contrato['fecha_fin']);
        $numeroPagos = (int) $this->contrato['numero_pagos'];
        $duracionTexto = numeroALetras($numeroPagos);
        $html .= "<b>TERCERA: PLAZO E INFORMES PARA PAGO.</b> El presente contrato tendrá una duración de $duracionTexto meses, iniciando el día $fechaInicioTexto hasta el día $fechaFinTexto, el cual podrá ser prorrogado por un periodo igual a voluntad de las partes. Para el correspondiente pago, el contratista deberá presentar informes mensuales de las actividades realizadas y los resultados obtenidos a entera satisfacción, los cuales deberán estar aprobados por la Autoridad Superior donde preste sus servicios, asimismo un informe final de las actividades realizadas en el periodo de vigencia del presente contrato, al finalizar la prestación de sus servicios técnicos, aprobado por la Autoridad Superior donde preste sus servicios. ";

        // CUARTA
        $montoTotalTexto = montoATexto($this->contrato['monto_total']);
        $numeroPagos = (int) $this->contrato['numero_pagos'];
        $numeroPagosTexto = numeroALetrasMayusculas($numeroPagos);
        $numeroPagosMenos1 = $numeroPagos - 1;
        $numeroPagosMenos1Texto = numeroALetrasMayusculas($numeroPagosMenos1);
        $montoPagoTexto = montoATexto($this->contrato['monto_pago']);

        // Determinar texto de IVA dinámicamente
        $ivaAccion = strtolower($this->contrato['iva']); // "incluir" o "sumarse"
        $ivaTexto = ($ivaAccion == 'incluir') ?
            'a dicho monto debe agregarse el Impuesto al Valor Agregado –IVA-' :
            'a dicho monto debe sumarse el Impuesto al Valor Agregado –IVA-';

        $html .= "<b>CUARTA: VALOR DEL CONTRATO Y FORMA DE PAGO.</b> <b>\"EL CONTRATANTE\"</b> pagará a \"EL CONTRATISTA\" por la prestación de servicios la cantidad total de <b>$montoTotalTexto</b>, $ivaTexto, el cual se realizará mediante <b>$numeroPagosTexto</b> pagos de la forma siguiente: <b>$numeroPagosMenos1Texto</b> pagos de <b>$montoPagoTexto</b> contra entrega del informe de actividades contractuales y factura electrónica respectiva y un último pago de <b>$montoPagoTexto</b> contra entrega del informe de actividades contractuales, informe final del periodo contratado y factura electrónica respectiva. Al valor del contrato debe $ivaAccion el Impuesto al Valor Agregado –IVA- al momento de presentar la factura de manera mensual. ";

        // QUINTA
        $fondos = $this->contrato['fondos'];
        $armonizacion = $this->contrato['armonizacion'];
        if ($armonizacion == 'Otro' && !empty($this->contrato['armonizacion_otro'])) {
            $armonizacion = $this->contrato['armonizacion_otro'];
        }
        $html .= "<b>QUINTA: EROGACIONES.</b> Las erogaciones ocasionadas serán cargadas a los fondos de $fondos/$armonizacion, administrados por OIRSA, de conformidad con el artículo No. 42 (cuarenta y dos) del Reglamento de Régimen Financiero del OIRSA y artículo 34 del Convenio para la Constitución del OIRSA, aprobado por Guatemala como Estado Miembro a través del Decreto Legislativo No. 19-93, se encuentra exento del pago del Impuesto al Valor Agregado -IVA; por lo cual, El IVA será reconocido mediante una constancia de exención del IVA otorgada por OIRSA, cuando aplique el régimen tributario bajo el cual esté inscrito el Contratista ante la Superintendencia de Administración Tributaria (SAT). ";

        // SEXTA
        $html .= "<b>SEXTA: AUTORIDAD ADMINISTRATIVA.</b> El contratista desempeñará sus funciones de acuerdo con las condiciones y términos establecidos en este contrato y además, conforme a los lineamientos de efectividad que señale el Despacho Ministerial, con la supervisión directa del Ministro o Ministra del Ministerio de Agricultura, Ganadería y Alimentación –MAGA-, comprometiéndose a desempeñarlo con toda buena voluntad y disposición efectiva para lograr los mejores resultados; todo, bajo las reglas de confidencialidad, confianza y buena fe, y de acuerdo a las Normas Éticas para la Función Pública. El informe mensual detallado formará parte indispensable para la justificación de su correspondiente pago mensual. ";

        // SEPTIMA
        $html .= "<b>SEPTIMA: CESIÓN.</b> El contratista no podrá ceder este Contrato o subcontratar ninguna otra persona, sin el consentimiento previo por escrito del Contratante. ";

        // OCTAVA
        $html .= "<b>OCTAVA: EXCLUSION DE RESPONSABILIDAD LABORAL.</b> En virtud de que las causas que han dado origen a este contrato son específicas, extraordinarias y transitorias y que, debido a su naturaleza profesional, no implica ningún tipo de relación laboral con EL CONTRATISTA, exime al OIRSA de cualquier responsabilidad laboral derivada de las disposiciones legales y demás ordenamientos en materia de trabajo y seguridad social. Para los efectos de este Contrato, el OIRSA funge únicamente como administrador de los fondos a favor del MAGA, de conformidad con el artículo cuarenta y dos del Reglamento de Régimen Financiero del OIRSA. ";

        // NOVENA
        $html .= "<b>NOVENA: TÉRMINACIÓN DEL CONTRATO.</b> Sin carácter limitativo, son causas justificadas para la rescisión de un contrato las siguientes: <b>a)</b> Por el cumplimiento de las condiciones y objetivos por las cuales fue contratado; <b>b)</b> Por notificación del Contratante en razón del mal desempeño y presentación de resultados o el incumplimiento de sus funciones, requiriendo el contratante de manera inmediata la culminación de todos los trámites pendientes de ejecutar y actividades, en los cuales deberá insertar en el informe final de actividades; <b>c)</b> Incumplimiento de las obligaciones convenidas imputables al Contratista; <b>d)</b> Por la cesión del contrato a terceros sin autorización escrita del Contratante; <b>e)</b> Si el Contratista acepta nuevas asignaciones contractuales con otra institución que afecten el cumplimiento de las atribuciones designadas. La finalización del presente contrato no implica ninguna responsabilidad patronal por parte del Contratante hacia el contratista, ni tampoco para el OIRSA quien funge como administrador de los fondos. ";

        // DECIMA
        $html .= "<b>DECIMA: CAUSAS DE FUERZA MAYOR O CASO FORTUITO.</b> El Contratante no estará sujeto a liquidación por daños y perjuicios o a la resolución del Contrato por incumplimiento, en el caso y en la medida en que la demora en el incumplimiento de sus obligaciones se deba a un evento de fuerza mayor. Se entenderá por fuerza mayor un hecho o situación que esté fuera del control del Contratante, que sea imprevisible, inevitable y que no tenga como origen la negligencia o falta de cuidado de esta. Si se presenta una situación de fuerza mayor, el Contratante notificará prontamente y por escrito al Contratista sobre dicha situación y sus causas, excepto cuando reciba instrucciones en sentido contrario y por escrito del Contratante, el Contratista continuará cumpliendo las obligaciones que le imponga el Contrato en la medida en que esto le sea posible. ";

        // DÉCIMA PRIMERA
        $html .= "<b>DÉCIMA PRIMERA: CONFIDENCIALIDAD Y DERECHOS DE AUTOR.</b> La información cubierta bajo este contrato será considerada como confidencial, a menos que se estipule lo contrario. Nada de lo contenido en esta cláusula podrá ser considerado como facultativo para otorgar una licencia bajo cualquier ley de propiedad intelectual. La parte que recibe la información confidencial deberá tratarla bajo la más estricta confidencialidad y no divulgará, directa o indirectamente, a cualquier persona, firma, corporación, asociación, o entidad, bajo ninguna circunstancia, información recibida en condición de confidencial, excepto para los propósitos de este contrato. Los documentos y productos que EL CONTRATISTA elabore y genere durante el período de sus servicios, será de exclusiva propiedad del OIRSA y serán entregados en documentos y de manera electrónica. En tal sentido todos los materiales impresos, análisis, estudios, informes, gráficos, programas de computación u otros materiales preparados por EL CONTRATISTA para el Contratante, en virtud de este Contrato, serán de propiedad del MAGA. ";

        // DÉCIMA SEGUNDA
        $html .= "<b>DÉCIMA SEGUNDA: SOLUCIÓN DE DIFERENCIAS.</b> Toda diferencia que surja del presente contrato se solucionará por medio de trato directo. En caso de no obtener resultados por la vía amistosa, se resolverán mediante procedimientos de arbitraje, para lo cual nos sometemos a la jurisdicción de la Ciudad de Guatemala a través del Centro de Arbitraje y Conciliación de la Cámara de Comercio de Guatemala. ";

        // DÉCIMA TERCERA
        $html .= "<b>DÉCIMA TERCERA: ACEPTACIÓN.</b> En prueba de conformidad con los términos y cláusulas precedentes, previa lectura de estos y comprensión de los mismos, manifestamos nuestra aceptación y firmamos en todas las hojas del presente contrato, haciendo constar que el mismo queda contenido en cuatro hojas de papel bond, tamaño oficio.";

        return $html;
    }

    /**
     * Título del contrato
     */
    private function agregarTitulo()
    {
        $this->SetFont('helvetica', 'B', 12);
        // Hacer dinámico el tipo de servicio (Técnicos o Profesionales)
        $tipoServicio = strtoupper($this->contrato['servicios']); // "TECNICOS" o "PROFESIONALES"
        $this->Cell(0, 10, 'CONTRATO POR SERVICIOS ' . $tipoServicio . ' No. ' . $this->contrato['numero_contrato'], 0, 1, 'C');
        $this->Ln(5);
    }

    /**
     * Sección de firmas
     */
    private function agregarFirmas()
    {
        $this->Ln(20);

        // Líneas de firma
        $this->Cell(85, 5, '________________________________', 0, 0, 'C');
        $this->Cell(10, 5, '', 0, 0);
        $this->Cell(85, 5, '__________________________________', 0, 1, 'C');

        $this->Ln(5);

        // Nombre del contratista
        $this->Cell(85, 5, '', 0, 0, 'C');
        $this->Cell(10, 5, '', 0, 0);
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(85, 5, strtoupper($this->contrato['nombre_completo']), 0, 1, 'C');

        // Etiqueta "CONTRATISTA"
        $this->Cell(85, 5, '', 0, 0, 'C');
        $this->Cell(10, 5, '', 0, 0);
        $this->Cell(85, 5, 'CONTRATISTA', 0, 1, 'C');
    }
}
?>