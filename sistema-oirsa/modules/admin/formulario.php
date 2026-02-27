<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../../login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Contrato - Oirsa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/navbar.css">
    <link rel="stylesheet" href="../../css/formulario.css">
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="container">
            <div class="form-header">
                <h1>
                    <i class="fa-solid fa-file-contract"></i>
                    Formulario de Contrato
                </h1>
                <p>Complete todos los campos obligatorios marcados con <span style="color: #d32f2f;">*</span></p>
            </div>

            <form id="contratoForm" enctype="multipart/form-data" onsubmit="validarFormulario(event)">

                <!-- Datos de la Persona a Contratar -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-user"></i>
                        Datos de la Persona a Contratar (El Contratista)
                    </h2>

                    <!-- NUEVOS CAMPOS -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numeroContrato">
                                <i class="fa-solid fa-file-contract"></i>
                                Número de Contrato <span class="required">*</span>
                            </label>
                            <input type="text" id="numeroContrato" name="numeroContrato" placeholder="Ej: 001-2026-O-M"
                                required>
                            <small style="color: #666; font-size: 12px;">Se genera automáticamente pero puede
                                editarse</small>
                        </div>

                        <div class="form-group">
                            <label for="servicios">
                                <i class="fa-solid fa-briefcase"></i>
                                Servicios <span class="required">*</span>
                            </label>
                            <select id="servicios" name="servicios" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Tecnicos">Técnicos</option>
                                <option value="Profesionales">Profesionales</option>
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
                                <option value="Incluir">Incluir</option>
                                <option value="Sumarse">Sumarse</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fondos">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                Fondos <span class="required">*</span>
                            </label>
                            <select id="fondos" name="fondos" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Apoyo a Programas Nacionales (APN)">Apoyo a Programas Nacionales (APN)
                                </option>
                                <option value="Opcion 1">Opción 1</option>
                                <option value="Opcion 2">Opción 2</option>
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
                                <option value="Armonización de Normativas">Armonización de Normativas</option>
                                <option value="Despacho Superior">Despacho Superior</option>
                                <option value="Dirección Sanidad Vegetal">Dirección Sanidad Vegetal</option>
                                <option value="Dirección Sanidad Animal">Dirección Sanidad Animal</option>
                                <option value="Inocuidad de Alimentos">Inocuidad de Alimentos</option>
                                <option value="Cuarentena Vegetal">Cuarentena Vegetal</option>
                                <option value="Trazabilidad">Trazabilidad</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fechaContrato">
                                <i class="fa-solid fa-calendar-check"></i>
                                Fecha de Contrato <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaContrato" name="fechaContrato" required>
                        </div>
                    </div>

                    <!-- Campo oculto para "Otro" en Armonización -->
                    <div class="form-group" id="armonizacionOtroContainer" style="display: none;">
                        <label for="armonizacionOtro">
                            <i class="fa-solid fa-pen"></i>
                            Especifique Cargo de Presupuesto
                        </label>
                        <input type="text" id="armonizacionOtro" name="armonizacionOtro"
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
                            required></textarea>
                        <small style="color: #666; font-size: 12px;">Este texto aparecerá en la cláusula PRIMERA del
                            contrato PDF</small>
                    </div>

                    <!-- CAMPOS EXISTENTES -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombreCompleto">
                                Nombre Completo <span class="required">*</span>
                            </label>
                            <input type="text" id="nombreCompleto" name="nombreCompleto"
                                placeholder="Ingrese el nombre completo" required>
                        </div>

                        <div class="form-group">
                            <label for="edad">
                                Edad <span class="required">*</span>
                            </label>
                            <input type="number" id="edad" name="edad" placeholder="Ingrese la edad" min="18" max="100"
                                required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="estadoCivil">
                                Estado Civil <span class="required">*</span>
                            </label>
                            <select id="estadoCivil" name="estadoCivil" required>
                                <option value="">Seleccione una opción</option>
                                <option value="Soltero">Soltero</option>
                                <option value="Casado">Casado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="profesion">
                                Profesión <span class="required">*</span>
                            </label>
                            <input type="text" id="profesion" name="profesion" placeholder="Ingrese la profesión"
                                required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="domicilio">
                            Domicilio <span class="required">*</span>
                        </label>
                        <input type="text" id="domicilio" name="domicilio" placeholder="Ingrese el domicilio completo"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="dpi">
                            Número de DPI (CUI) <span class="required">*</span>
                        </label>
                        <input type="text" id="dpi" name="dpi" placeholder="0000 00000 0000" maxlength="15"
                            oninput="formatearDPI(this)" required>
                    </div>
                </div>

                <!-- Detalles del Servicio -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-briefcase"></i>
                        Detalles del Servicio (Objeto del Contrato)
                    </h2>

                    <div class="form-group">
                        <label>
                            <i class="fa-solid fa-list-check"></i>
                            Términos de Referencias
                        </label>
                        <div class="terminos-grid">
                            <textarea name="termino1" placeholder="Término de referencia 1 (opcional)"></textarea>
                            <textarea name="termino2" placeholder="Término de referencia 2 (opcional)"></textarea>
                            <textarea name="termino3" placeholder="Término de referencia 3 (opcional)"></textarea>
                            <textarea name="termino4" placeholder="Término de referencia 4 (opcional)"></textarea>
                            <textarea name="termino5" placeholder="Término de referencia 5 (opcional)"></textarea>
                            <textarea name="termino6" placeholder="Término de referencia 6 (opcional)"></textarea>
                            <textarea name="termino7" placeholder="Término de referencia 7 (opcional)"></textarea>
                            <textarea name="termino8" placeholder="Término de referencia 8 (opcional)"></textarea>
                            <textarea name="termino9" placeholder="Término de referencia 9 (opcional)"></textarea>
                            <textarea name="termino10" placeholder="Término de referencia 10 (opcional)"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fechaInicio">
                                <i class="fa-solid fa-calendar-days"></i>
                                Fecha de Inicio del Plazo <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaInicio" name="fechaInicio" required>
                        </div>

                        <div class="form-group">
                            <label for="fechaFin">
                                <i class="fa-solid fa-calendar-check"></i>
                                Fecha de Finalización del Plazo <span class="required">*</span>
                            </label>
                            <input type="date" id="fechaFin" name="fechaFin" required>
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
                        <input type="text" id="montoTotal" name="montoTotal" placeholder="Ingrese el monto"
                            onblur="formatearMonto(this)" required>
                        <div id="montoDisplay" class="monto-display"></div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="numeroPagos">
                                <i class="fa-solid fa-hashtag"></i>
                                Número de Pagos <span class="required">*</span>
                            </label>
                            <input type="number" id="numeroPagos" name="numeroPagos"
                                placeholder="Ingrese el número de pagos" min="1" oninput="mostrarNumeroPagos(this)"
                                required>
                            <div id="pagosDisplay" class="pagos-display"></div>
                        </div>

                        <div class="form-group">
                            <label for="montoPago">
                                <i class="fa-solid fa-money-check-dollar"></i>
                                Monto por Pago Mensual <span class="required">*</span>
                            </label>
                            <input type="text" id="montoPago" name="montoPago" placeholder="Ingrese el monto por pago"
                                onblur="formatearMontoPago(this)" required>
                            <div id="montoPagoDisplay" class="monto-display"></div>
                        </div>
                    </div>
                </div>

                <!-- Adjuntar Archivos -->
                <div class="form-card">
                    <h2 class="section-title">
                        <i class="fa-solid fa-paperclip"></i>
                        Adjuntar Archivos
                    </h2>

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

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Enviar Formulario
                </button>
            </form>
        </div>
    </div>

    <script src="../../js/formulario.js"></script>
    <script src="../../js/validar_formulario.js"></script>
</body>

</html>