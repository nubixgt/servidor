<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Verificar que se recibió el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: contratos.php');
    exit();
}

require_once '../../config/database.php';

try {
    $conn = getConnection();
    $id = $_GET['id'];

    // Obtener datos del contrato
    $sql = "SELECT * FROM contratos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contrato) {
        header('Location: contratos.php');
        exit();
    }

    // Obtener archivos adjuntos
    $sqlArchivos = "SELECT tipo_archivo, nombre_archivo, ruta_archivo 
                    FROM contrato_archivos 
                    WHERE contrato_id = ?";
    $stmtArchivos = $conn->prepare($sqlArchivos);
    $stmtArchivos->execute([$id]);
    $archivos = $stmtArchivos->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    header('Location: contratos.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Contrato - Oirsa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/navbar.css">
    <link rel="stylesheet" href="../../css/formulario.css">
    <link rel="stylesheet" href="../../css/contratos.css">
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="container">
            <div class="form-header">
                <h1>
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar Contrato
                </h1>
                <p>Contrato: <strong>
                        <?php echo htmlspecialchars($contrato['numero_contrato']); ?>
                    </strong></p>
                <p style="margin-top: 10px;">
                    <a href="contratos.php" style="color: #1A73E8; text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Volver a Contratos
                    </a>
                </p>
            </div>

            <form id="editarContratoForm" enctype="multipart/form-data" onsubmit="validarFormulario(event)">
                <input type="hidden" name="id" value="<?php echo $contrato['id']; ?>">

                <!-- Datos de la Persona a Contratar -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-user"></i>
                        Datos de la Persona a Contratar (El Contratista)
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numeroContrato">
                                <i class="fa-solid fa-file-contract"></i>
                                Número de Contrato <span class="required">*</span>
                            </label>
                            <input type="text" id="numeroContrato" name="numeroContrato"
                                value="<?php echo htmlspecialchars($contrato['numero_contrato']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="servicios">
                                <i class="fa-solid fa-briefcase"></i>
                                Servicios <span class="required">*</span>
                            </label>
                            <select id="servicios" name="servicios" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Tecnicos" <?php echo ($contrato['servicios'] == 'Tecnicos') ? 'selected' : ''; ?>>Técnicos</option>
                                <option value="Profesionales" <?php echo ($contrato['servicios'] == 'Profesionales') ? 'selected' : ''; ?>>Profesionales</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="iva">
                                <i class="fa-solid fa-percent"></i>
                                IVA <span class="required">*</span>
                            </label>
                            <select id="iva" name="iva" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Incluir" <?php echo ($contrato['iva'] == 'Incluir') ? 'selected' : ''; ?>>
                                    Incluir</option>
                                <option value="Sumarse" <?php echo ($contrato['iva'] == 'Sumarse') ? 'selected' : ''; ?>>
                                    Sumarse</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fondos">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                Fondos <span class="required">*</span>
                            </label>
                            <select id="fondos" name="fondos" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Apoyo a Programas Nacionales (APN)" <?php echo ($contrato['fondos'] == 'Apoyo a Programas Nacionales (APN)') ? 'selected' : ''; ?>>
                                    Apoyo a Programas Nacionales (APN)
                                </option>
                                <option value="Opcion 1" <?php echo ($contrato['fondos'] == 'Opcion 1') ? 'selected' : ''; ?>>
                                    Opción 1</option>
                                <option value="Opcion 2" <?php echo ($contrato['fondos'] == 'Opcion 2') ? 'selected' : ''; ?>>
                                    Opción 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="armonizacion">
                                <i class="fa-solid fa-scale-balanced"></i>
                                Cargo de Presupuesto <span class="required">*</span>
                            </label>
                            <select id="armonizacion" name="armonizacion" onchange="toggleArmonizacionOtro()" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Armonización de Normativas" <?php echo ($contrato['armonizacion'] == 'Armonización de Normativas') ? 'selected' : ''; ?>>
                                    Armonización de Normativas</option>
                                <option value="Despacho Superior" <?php echo ($contrato['armonizacion'] == 'Despacho Superior') ? 'selected' : ''; ?>>Despacho Superior</option>
                                <option value="Dirección Sanidad Vegetal" <?php echo ($contrato['armonizacion'] == 'Dirección Sanidad Vegetal') ? 'selected' : ''; ?>>
                                    Dirección Sanidad Vegetal</option>
                                <option value="Dirección Sanidad Animal" <?php echo ($contrato['armonizacion'] == 'Dirección Sanidad Animal') ? 'selected' : ''; ?>>
                                    Dirección Sanidad Animal</option>
                                <option value="Inocuidad de Alimentos" <?php echo ($contrato['armonizacion'] == 'Inocuidad de Alimentos') ? 'selected' : ''; ?>>Inocuidad de Alimentos</option>
                                <option value="Cuarentena Vegetal" <?php echo ($contrato['armonizacion'] == 'Cuarentena Vegetal') ? 'selected' : ''; ?>>Cuarentena Vegetal</option>
                                <option value="Trazabilidad" <?php echo ($contrato['armonizacion'] == 'Trazabilidad') ? 'selected' : ''; ?>>Trazabilidad</option>
                                <option value="Otro" <?php echo ($contrato['armonizacion'] == 'Otro') ? 'selected' : ''; ?>>
                                    Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fechaContrato">
                                <i class="fa-solid fa-calendar-check"></i>
                                Fecha de Contrato <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaContrato" name="fechaContrato"
                                value="<?php echo $contrato['fecha_contrato']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group" id="armonizacionOtroContainer"
                        style="display: <?php echo ($contrato['armonizacion'] == 'Otro') ? 'block' : 'none'; ?>;">
                        <label for="armonizacionOtro">
                            <i class="fa-solid fa-pen"></i>
                            Especifique Cargo de Presupuesto
                        </label>
                        <input type="text" id="armonizacionOtro" name="armonizacionOtro"
                            value="<?php echo htmlspecialchars($contrato['armonizacion_otro'] ?? ''); ?>"
                            placeholder="Escriba el cargo de presupuesto personalizado">
                    </div>

                    <!-- Nuevo campo: Término de Contratación -->
                    <div class="form-group">
                        <label for="terminoContratacion">
                            <i class="fa-solid fa-file-contract"></i>
                            Término de Contratación <span class="required">*</span>
                        </label>
                        <textarea id="terminoContratacion" name="terminoContratacion" rows="3"
                            placeholder="Ej: SERVICIOS TECNICOS PARA ASESORAR EN TEMAS DE DESARROLLO DE MEDIOS DIGITALES ESTE EN EL MINISTERIO DE AGRICULTURA, GANADERÍA Y ALIMENTACIÓN"
                            required><?php echo htmlspecialchars($contrato['termino_contratacion'] ?? ''); ?></textarea>
                        <small style="color: #666; font-size: 12px;">Este texto aparecerá en la cláusula PRIMERA del
                            contrato PDF</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombreCompleto">
                                Nombre Completo <span class="required">*</span>
                            </label>
                            <input type="text" id="nombreCompleto" name="nombreCompleto"
                                value="<?php echo htmlspecialchars($contrato['nombre_completo']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="edad">
                                Edad <span class="required">*</span>
                            </label>
                            <input type="number" id="edad" name="edad" min="18" max="100"
                                value="<?php echo $contrato['edad']; ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="estadoCivil">
                                Estado Civil <span class="required">*</span>
                            </label>
                            <select id="estadoCivil" name="estadoCivil" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Soltero" <?php echo ($contrato['estado_civil'] == 'Soltero') ? 'selected' : ''; ?>>Soltero</option>
                                <option value="Casado" <?php echo ($contrato['estado_civil'] == 'Casado') ? 'selected' : ''; ?>>Casado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="profesion">
                                Profesión <span class="required">*</span>
                            </label>
                            <input type="text" id="profesion" name="profesion"
                                value="<?php echo htmlspecialchars($contrato['profesion']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="domicilio">
                            Domicilio <span class="required">*</span>
                        </label>
                        <input type="text" id="domicilio" name="domicilio"
                            value="<?php echo htmlspecialchars($contrato['domicilio']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="dpi">
                            Número de DPI (CUI) <span class="required">*</span>
                        </label>
                        <input type="text" id="dpi" name="dpi" maxlength="15"
                            value="<?php echo htmlspecialchars($contrato['dpi']); ?>" oninput="formatearDPI(this)"
                            required>
                    </div>
                </div>

                <!-- Fechas del Contrato -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-calendar-days"></i>
                        Fechas del Contrato
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fechaInicio">
                                <i class="fa-solid fa-calendar-days"></i>
                                Fecha de Inicio del Plazo <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaInicio" name="fechaInicio"
                                value="<?php echo $contrato['fecha_inicio']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="fechaFin">
                                <i class="fa-solid fa-calendar-check"></i>
                                Fecha de Finalización del Plazo <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaFin" name="fechaFin"
                                value="<?php echo $contrato['fecha_fin']; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Datos Financieros -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        Datos Financieros (Honorarios)
                    </h2>

                    <div class="form-group">
                        <label for="montoTotal">
                            <i class="fa-solid fa-sack-dollar"></i>
                            Monto Total del Contrato <span class="required">*</span>
                        </label>
                        <input type="text" id="montoTotal" name="montoTotal"
                            value="<?php echo $contrato['monto_total']; ?>" onblur="formatearMonto(this)" required>
                        <div id="montoDisplay" class="monto-display"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numeroPagos">
                                <i class="fa-solid fa-hashtag"></i>
                                Número de Pagos <span class="required">*</span>
                            </label>
                            <input type="number" id="numeroPagos" name="numeroPagos" min="1"
                                value="<?php echo $contrato['numero_pagos']; ?>" oninput="mostrarNumeroPagos(this)"
                                required>
                            <div id="pagosDisplay" class="pagos-display"></div>
                        </div>

                        <div class="form-group">
                            <label for="montoPago">
                                <i class="fa-solid fa-money-check-dollar"></i>
                                Monto por Pago Mensual <span class="required">*</span>
                            </label>
                            <input type="text" id="montoPago" name="montoPago"
                                value="<?php echo $contrato['monto_pago']; ?>" onblur="formatearMontoPago(this)"
                                required>
                            <div id="montoPagoDisplay" class="monto-display"></div>
                        </div>
                    </div>
                </div>

                <!-- Términos de Referencia -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-list-check"></i>
                        Términos de Referencia
                    </h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        <i class="fa-solid fa-info-circle"></i>
                        Complete los términos de referencia del contrato. Puede dejar en blanco los que no apliquen.
                    </p>

                    <div class="form-group">
                        <label for="termino1">
                            <i class="fa-solid fa-list"></i>
                            Término 1
                        </label>
                        <textarea id="termino1" name="termino1" rows="3"
                            placeholder="Escriba el primer término de referencia"><?php echo htmlspecialchars($contrato['termino1'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino2">
                            <i class="fa-solid fa-list"></i>
                            Término 2
                        </label>
                        <textarea id="termino2" name="termino2" rows="3"
                            placeholder="Escriba el segundo término de referencia"><?php echo htmlspecialchars($contrato['termino2'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino3">
                            <i class="fa-solid fa-list"></i>
                            Término 3
                        </label>
                        <textarea id="termino3" name="termino3" rows="3"
                            placeholder="Escriba el tercer término de referencia"><?php echo htmlspecialchars($contrato['termino3'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino4">
                            <i class="fa-solid fa-list"></i>
                            Término 4
                        </label>
                        <textarea id="termino4" name="termino4" rows="3"
                            placeholder="Escriba el cuarto término de referencia"><?php echo htmlspecialchars($contrato['termino4'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino5">
                            <i class="fa-solid fa-list"></i>
                            Término 5
                        </label>
                        <textarea id="termino5" name="termino5" rows="3"
                            placeholder="Escriba el quinto término de referencia"><?php echo htmlspecialchars($contrato['termino5'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino6">
                            <i class="fa-solid fa-list"></i>
                            Término 6
                        </label>
                        <textarea id="termino6" name="termino6" rows="3"
                            placeholder="Escriba el sexto término de referencia"><?php echo htmlspecialchars($contrato['termino6'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino7">
                            <i class="fa-solid fa-list"></i>
                            Término 7
                        </label>
                        <textarea id="termino7" name="termino7" rows="3"
                            placeholder="Escriba el séptimo término de referencia"><?php echo htmlspecialchars($contrato['termino7'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino8">
                            <i class="fa-solid fa-list"></i>
                            Término 8
                        </label>
                        <textarea id="termino8" name="termino8" rows="3"
                            placeholder="Escriba el octavo término de referencia"><?php echo htmlspecialchars($contrato['termino8'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino9">
                            <i class="fa-solid fa-list"></i>
                            Término 9
                        </label>
                        <textarea id="termino9" name="termino9" rows="3"
                            placeholder="Escriba el noveno término de referencia"><?php echo htmlspecialchars($contrato['termino9'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="termino10">
                            <i class="fa-solid fa-list"></i>
                            Término 10
                        </label>
                        <textarea id="termino10" name="termino10" rows="3"
                            placeholder="Escriba el décimo término de referencia"><?php echo htmlspecialchars($contrato['termino10'] ?? ''); ?></textarea>
                    </div>
                </div>

                <?php if (!empty($archivos)): ?>
                    <!-- Archivos Adjuntos Actuales -->
                    <div class="form-card">
                        <h2 class="section-title">
                            <i class="fa-solid fa-paperclip"></i>
                            Archivos Adjuntos Actuales
                        </h2>
                        <p style="color: #666; margin-bottom: 20px;">
                            <i class="fa-solid fa-info-circle"></i>
                            Archivos actuales del contrato. Para reemplazar un archivo, suba uno nuevo en la sección
                            siguiente.
                        </p>

                        <div class="archivos-grid">
                            <?php
                            $nombresArchivos = [
                                'cv' => 'Currículum Vitae (CV)',
                                'titulo' => 'Título Profesional',
                                'colegiadoActivo' => 'Colegiado Activo',
                                'cuentaBanco' => 'Cuenta de Banco',
                                'dpiArchivo' => 'DPI',
                                'otro' => 'Otro'
                            ];

                            foreach ($archivos as $archivo):
                                $extension = pathinfo($archivo['nombre_archivo'], PATHINFO_EXTENSION);
                                $esPDF = strtolower($extension) === 'pdf';
                                $esImagen = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                ?>
                                <div class="archivo-item">
                                    <div class="archivo-icon">
                                        <?php if ($esPDF): ?>
                                            <i class="fa-solid fa-file-pdf" style="color: #ef4444; font-size: 2.5rem;"></i>
                                        <?php elseif ($esImagen): ?>
                                            <i class="fa-solid fa-file-image" style="color: #10b981; font-size: 2.5rem;"></i>
                                        <?php else: ?>
                                            <i class="fa-solid fa-file" style="color: #667eea; font-size: 2.5rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="archivo-info">
                                        <p class="archivo-nombre">
                                            <?php echo $nombresArchivos[$archivo['tipo_archivo']] ?? $archivo['tipo_archivo']; ?>
                                        </p>
                                        <p class="archivo-file"><?php echo htmlspecialchars($archivo['nombre_archivo']); ?></p>
                                    </div>
                                    <a href="/Oirsa/<?php echo str_replace('../', '', htmlspecialchars($archivo['ruta_archivo'])); ?>"
                                        target="_blank" class="btn-ver-archivo" title="Ver archivo">
                                        <i class="fa-solid fa-eye"></i> Ver
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reemplazar o Agregar Archivos -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-upload"></i>
                        Reemplazar o Agregar Archivos
                    </h2>
                    <p style="color: #666; margin-bottom: 20px;">
                        <i class="fa-solid fa-info-circle"></i>
                        Al subir un archivo nuevo, se reemplazará automáticamente el archivo existente del mismo tipo.
                    </p>

                    <div class="file-upload-grid">
                        <div class="file-upload-item">
                            <label for="cv">
                                <i class="fa-solid fa-file-pdf"></i>
                                CV <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="cv" name="cv" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>

                        <div class="file-upload-item">
                            <label for="titulo">
                                <i class="fa-solid fa-graduation-cap"></i>
                                Título <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="titulo" name="titulo" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>

                        <div class="file-upload-item">
                            <label for="colegiadoActivo">
                                <i class="fa-solid fa-id-card"></i>
                                Colegiado Activo <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="colegiadoActivo" name="colegiadoActivo" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>

                        <div class="file-upload-item">
                            <label for="cuentaBanco">
                                <i class="fa-solid fa-building-columns"></i>
                                Cuenta de Banco <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="cuentaBanco" name="cuentaBanco" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>

                        <div class="file-upload-item">
                            <label for="dpiArchivo">
                                <i class="fa-solid fa-id-card-clip"></i>
                                DPI <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="dpiArchivo" name="dpiArchivo" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>

                        <div class="file-upload-item">
                            <label for="otro">
                                <i class="fa-solid fa-file"></i>
                                Otro <span class="optional-badge">Opcional</span>
                            </label>
                            <input type="file" id="otro" name="otro" accept=".pdf,image/*"
                                onchange="previsualizarArchivo(this)">
                        </div>
                    </div>
                </div>

                <div class="form-card" style="background: #fff7ed; border-left: 4px solid #f59e0b;">
                    <p style="margin: 0; color: #92400e;">
                        <i class="fa-solid fa-info-circle"></i>
                        <strong>Nota:</strong> Puede editar todos los campos del contrato. Los archivos existentes
                        pueden
                        ser eliminados o reemplazados subiendo nuevos archivos.
                    </p>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-save"></i>
                        Guardar Cambios
                    </button>
                    <button type="button" class="btn-cancel" onclick="confirmarCancelar()">
                        <i class="fa-solid fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../js/formulario.js?v=<?php echo time(); ?>"></script>
    <script src="../../js/editar_contrato.js?v=<?php echo time(); ?>"></script>
</body>

</html>