<?php
/**
 * Importar Datos - VIDER
 * MAGA Guatemala
 * Solo accesible para técnicos
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin();

// Solo técnicos pueden importar datos
if (!canImport()) {
    header('Location: index.php?error=Solo los técnicos pueden cargar información al sistema');
    exit;
}

$currentPage = 'importar';
$db = Database::getInstance();

// Obtener historial de importaciones recientes
$importaciones = $db->fetchAll(
    "SELECT * FROM importaciones ORDER BY created_at DESC LIMIT 10"
);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>Importar Datos - VIDER | MAGA</title>
    <style>
        .import-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .upload-section {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem;
            margin-bottom: 2rem;
        }

        .upload-zone {
            border: 3px dashed var(--border-color);
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.3);
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--primary);
            background: rgba(74, 144, 217, 0.05);
            transform: scale(1.01);
        }

        .upload-zone.dragover {
            animation: pulse 1s infinite;
        }

        .upload-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .upload-zone h3 {
            font-family: var(--font-display);
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .upload-zone p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .file-types {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .file-type {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(74, 144, 217, 0.1);
            border-radius: 20px;
            font-size: 0.875rem;
            color: var(--primary);
        }

        .file-type i {
            font-size: 1rem;
        }

        .file-preview {
            display: none;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .file-preview.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .file-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .file-details h4 {
            margin: 0 0 0.25rem;
            color: var(--text-dark);
        }

        .file-details p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .file-actions {
            margin-left: auto;
            display: flex;
            gap: 0.5rem;
        }

        .progress-section {
            display: none;
            margin-top: 2rem;
        }

        .progress-section.show {
            display: block;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            height: 12px;
            background: var(--bg-light);
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 6px;
            width: 0%;
            transition: width 0.3s ease;
        }

        .progress-status {
            margin-top: 1rem;
            padding: 1rem;
            border-radius: 8px;
            display: none;
        }

        .progress-status.show {
            display: block;
        }

        .progress-status.success {
            background: rgba(74, 144, 217, 0.1);
            border: 1px solid var(--primary);
        }

        .progress-status.error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
        }

        .result-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .result-stat {
            text-align: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
        }

        .result-stat .number {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary);
        }

        .result-stat .label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .history-section {
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 2rem;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .history-table th {
            font-weight: 600;
            color: var(--text-dark);
            background: rgba(74, 144, 217, 0.05);
        }

        .history-table tr:hover {
            background: rgba(74, 144, 217, 0.03);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-badge.completado {
            background: rgba(74, 144, 217, 0.1);
            color: var(--primary);
        }

        .status-badge.procesando {
            background: rgba(247, 185, 40, 0.1);
            color: #c79100;
        }

        .status-badge.error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .status-badge.pendiente {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .instructions-card {
            background: linear-gradient(135deg, rgba(74, 144, 217, 0.05), rgba(212, 175, 55, 0.05));
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .instructions-card h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .instructions-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .instructions-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.5rem 0;
            color: var(--text-muted);
        }

        .instructions-list li i {
            color: var(--primary);
            margin-top: 0.25rem;
        }

        /* Responsive Styles */
        @media (max-width: 1024px) {
            .page-header {
                padding-top: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                padding-top: 4rem;
                text-align: center;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .header-subtitle {
                font-size: 0.85rem;
            }

            .import-container {
                padding: 0 0.5rem;
            }

            .upload-section {
                padding: 1.5rem;
                border-radius: 16px;
            }

            .upload-zone {
                padding: 2rem 1rem;
            }

            .upload-icon {
                font-size: 3rem;
            }

            .upload-zone h3 {
                font-size: 1.15rem;
            }

            .upload-zone p {
                font-size: 0.9rem;
            }

            .file-types {
                flex-direction: column;
                align-items: center;
                gap: 0.5rem;
            }

            .file-info {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }

            .file-actions {
                margin-left: 0;
                width: 100%;
                flex-direction: column;
            }

            .file-actions .btn {
                width: 100%;
            }

            .result-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .instructions-card {
                padding: 1rem;
            }

            .history-section {
                padding: 1rem;
                border-radius: 16px;
            }

            .history-table {
                font-size: 0.8rem;
            }

            .history-table th,
            .history-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        @media (max-width: 480px) {
            .result-stats {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }

            .result-stat {
                padding: 0.75rem 0.5rem;
            }

            .result-stat .number {
                font-size: 1.25rem;
            }

            .result-stat .label {
                font-size: 0.65rem;
            }

            /* Tabla responsive con scroll horizontal */
            .history-section {
                overflow-x: auto;
            }

            .history-table {
                min-width: 500px;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="page-header">
                <div class="header-title">
                    <h1><i class="fas fa-file-import"></i> Importar Datos</h1>
                    <p class="header-subtitle">Carga archivos Excel con información del VIDER</p>
                </div>
            </header>

            <div class="import-container stagger">
                <!-- Instrucciones -->
                <div class="instructions-card">
                    <h4><i class="fas fa-info-circle"></i> Instrucciones de Importación</h4>
                    <ul class="instructions-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>El archivo debe contener una hoja llamada <strong>"DATOS"</strong> con la información
                                principal</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Las columnas requeridas son: Departamento, Municipio, Dependencia, Programado,
                                Ejecutado, Beneficiarios</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>El sistema detecta automáticamente registros duplicados y los omite</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Tamaño máximo permitido: <strong>50 MB</strong></span>
                        </li>
                    </ul>
                </div>

                <!-- Zona de Upload -->
                <div class="upload-section">
                    <div class="upload-zone" id="uploadZone">
                        <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" hidden>
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3>Arrastra y suelta tu archivo aquí</h3>
                        <p>o haz clic para seleccionar</p>
                        <div class="file-types">
                            <span class="file-type"><i class="fas fa-file-excel"></i> .xlsx</span>
                            <span class="file-type"><i class="fas fa-file-excel"></i> .xls</span>
                            <span class="file-type"><i class="fas fa-file-csv"></i> .csv</span>
                        </div>
                    </div>

                    <!-- Vista previa del archivo -->
                    <div class="file-preview" id="filePreview">
                        <div class="file-info">
                            <div class="file-icon">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div class="file-details">
                                <h4 id="fileName">archivo.xlsx</h4>
                                <p id="fileSize">0 KB</p>
                            </div>
                            <div class="file-actions">
                                <button class="btn btn-secondary btn-sm" id="btnCancelFile">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button class="btn btn-primary btn-sm" id="btnUpload">
                                    <i class="fas fa-upload"></i> Subir Archivo
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progreso -->
                    <div class="progress-section" id="progressSection">
                        <div class="progress-header">
                            <span id="progressLabel">Subiendo archivo...</span>
                            <span id="progressPercent">0%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill"></div>
                        </div>

                        <div class="progress-status" id="progressStatus">
                            <div id="statusMessage"></div>
                            <div class="result-stats" id="resultStats"></div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Importaciones -->
                <div class="history-section">
                    <h3 style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-history" style="color: var(--primary);"></i>
                        Importaciones Recientes
                    </h3>

                    <?php if (empty($importaciones)): ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No hay importaciones registradas</p>
                        </div>
                    <?php else: ?>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Archivo</th>
                                    <th>Registros</th>
                                    <th>Importados</th>
                                    <th>Duplicados</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($importaciones as $imp): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($imp['created_at'])) ?></td>
                                        <td><?= htmlspecialchars($imp['nombre_archivo'] ?? 'N/A') ?></td>
                                        <td><?= number_format($imp['registros_totales'] ?? 0) ?></td>
                                        <td style="color: var(--primary);">
                                            <?= number_format($imp['registros_importados'] ?? 0) ?>
                                        </td>
                                        <td style="color: var(--text-muted);">
                                            <?= number_format($imp['registros_duplicados'] ?? 0) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $imp['estado'] ?>">
                                                <?php if ($imp['estado'] === 'completado'): ?>
                                                    <i class="fas fa-check-circle"></i>
                                                <?php elseif ($imp['estado'] === 'procesando'): ?>
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                <?php elseif ($imp['estado'] === 'error'): ?>
                                                    <i class="fas fa-exclamation-circle"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-clock"></i>
                                                <?php endif; ?>
                                                <?= ucfirst($imp['estado']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                initMobileMenu();

                const uploadZone = document.getElementById('uploadZone');
                const fileInput = document.getElementById('fileInput');
                const filePreview = document.getElementById('filePreview');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const btnCancelFile = document.getElementById('btnCancelFile');
                const btnUpload = document.getElementById('btnUpload');
                const progressSection = document.getElementById('progressSection');
                const progressFill = document.getElementById('progressFill');
                const progressPercent = document.getElementById('progressPercent');
                const progressLabel = document.getElementById('progressLabel');
                const progressStatus = document.getElementById('progressStatus');
                const statusMessage = document.getElementById('statusMessage');
                const resultStats = document.getElementById('resultStats');

                let selectedFile = null;

                // Click en zona de upload
                uploadZone.addEventListener('click', () => fileInput.click());

                // Drag & Drop
                uploadZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadZone.classList.add('dragover');
                });

                uploadZone.addEventListener('dragleave', () => {
                    uploadZone.classList.remove('dragover');
                });

                uploadZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadZone.classList.remove('dragover');

                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFile(files[0]);
                    }
                });

                // Selección de archivo
                fileInput.addEventListener('change', (e) => {
                    if (e.target.files.length > 0) {
                        handleFile(e.target.files[0]);
                    }
                });

                // Manejar archivo seleccionado
                function handleFile(file) {
                    const validExtensions = ['xlsx', 'xls', 'csv'];
                    const extension = file.name.split('.').pop().toLowerCase();

                    if (!validExtensions.includes(extension)) {
                        showToast('Formato no válido. Use archivos .xlsx, .xls o .csv', 'error');
                        return;
                    }

                    if (file.size > 50 * 1024 * 1024) {
                        showToast('El archivo excede el tamaño máximo de 50 MB', 'error');
                        return;
                    }

                    selectedFile = file;
                    fileName.textContent = file.name;
                    fileSize.textContent = formatFileSize(file.size);
                    filePreview.classList.add('show');
                    uploadZone.style.display = 'none';
                }

                // Cancelar archivo
                btnCancelFile.addEventListener('click', () => {
                    selectedFile = null;
                    fileInput.value = '';
                    filePreview.classList.remove('show');
                    uploadZone.style.display = 'block';
                    progressSection.classList.remove('show');
                    progressStatus.classList.remove('show');
                });

                // Subir archivo
                btnUpload.addEventListener('click', () => {
                    if (!selectedFile) return;

                    const formData = new FormData();
                    formData.append('file', selectedFile);

                    const xhr = new XMLHttpRequest();

                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percent = Math.round((e.loaded / e.total) * 100);
                            progressFill.style.width = percent + '%';
                            progressPercent.textContent = percent + '%';
                        }
                    });

                    xhr.addEventListener('load', () => {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);

                                if (response.success) {
                                    progressLabel.textContent = 'Procesando datos...';
                                    progressFill.style.width = '100%';
                                    processImport(response.import_id, response.file_path);
                                } else {
                                    showError(response.message || 'Error desconocido');
                                }
                            } catch (e) {
                                console.error('Error parsing response:', xhr.responseText);
                                showError('Error al procesar respuesta del servidor');
                            }
                        } else {
                            // Intentar obtener mensaje de error del servidor
                            try {
                                const errResponse = JSON.parse(xhr.responseText);
                                showError(errResponse.message || 'Error al subir archivo');
                            } catch (e) {
                                showError('Error al subir archivo (código: ' + xhr.status + ')');
                            }
                        }
                    });

                    xhr.addEventListener('error', () => {
                        showError('Error de conexión con el servidor');
                    });

                    progressSection.classList.add('show');
                    progressLabel.textContent = 'Subiendo archivo...';
                    btnUpload.disabled = true;

                    xhr.open('POST', 'api/upload.php');
                    xhr.send(formData);
                });

                // Procesar importación
                function processImport(importId, filePath) {
                    fetch('api/process_import.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            import_id: importId,
                            file_path: filePath
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showSuccess(data);
                            } else {
                                showError(data.message);
                            }
                        })
                        .catch(error => {
                            showError('Error al procesar importación: ' + error.message);
                        });
                }

                // Mostrar éxito
                function showSuccess(data) {
                    progressStatus.classList.add('show', 'success');
                    progressStatus.classList.remove('error');

                    statusMessage.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--primary); font-weight: 600;">
                        <i class="fas fa-check-circle"></i>
                        ¡Importación completada exitosamente!
                    </div>
                `;

                    resultStats.innerHTML = `
                    <div class="result-stat">
                        <div class="number">${formatNumber(data.total_registros)}</div>
                        <div class="label">Total</div>
                    </div>
                    <div class="result-stat">
                        <div class="number" style="color: var(--primary);">${formatNumber(data.importados)}</div>
                        <div class="label">Importados</div>
                    </div>
                    <div class="result-stat">
                        <div class="number" style="color: var(--text-muted);">${formatNumber(data.duplicados)}</div>
                        <div class="label">Duplicados</div>
                    </div>
                    <div class="result-stat">
                        <div class="number" style="color: #dc3545;">${formatNumber(data.errores)}</div>
                        <div class="label">Errores</div>
                    </div>
                `;

                    // Recargar después de 3 segundos
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                }

                // Mostrar error
                function showError(message) {
                    progressStatus.classList.add('show', 'error');
                    progressStatus.classList.remove('success');

                    statusMessage.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; color: #dc3545; font-weight: 600;">
                        <i class="fas fa-exclamation-circle"></i>
                        ${message}
                    </div>
                `;

                    resultStats.innerHTML = '';
                    btnUpload.disabled = false;
                }

                // Formatear tamaño de archivo
                function formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                }

                // Formatear número
                function formatNumber(num) {
                    return new Intl.NumberFormat('es-GT').format(num || 0);
                }

                // Toast notification
                function showToast(message, type = 'info') {
                    const toast = document.createElement('div');
                    toast.className = `toast toast-${type}`;
                    toast.innerHTML = `
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                `;
                    document.body.appendChild(toast);

                    setTimeout(() => toast.classList.add('show'), 100);
                    setTimeout(() => {
                        toast.classList.remove('show');
                        setTimeout(() => toast.remove(), 300);
                    }, 4000);
                }
            });

            // Mobile Menu Functions
            function initMobileMenu() {
                const menuToggle = document.getElementById('menu-toggle');
                const sidebar = document.getElementById('sidebar');
                const sidebarClose = document.getElementById('sidebar-close');
                const sidebarOverlay = document.getElementById('sidebar-overlay');
                if (!menuToggle || !sidebar) return;
                function openSidebar() {
                    sidebar.classList.add('open');
                    sidebarOverlay.classList.add('show');
                    menuToggle.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
                function closeSidebar() {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                    menuToggle.classList.remove('active');
                    document.body.style.overflow = '';
                }
                menuToggle.addEventListener('click', openSidebar);
                sidebarClose?.addEventListener('click', closeSidebar);
                sidebarOverlay?.addEventListener('click', closeSidebar);
                sidebar.querySelectorAll('.nav-item').forEach(link => {
                    link.addEventListener('click', () => { if (window.innerWidth <= 1024) closeSidebar(); });
                });
                window.addEventListener('resize', () => { if (window.innerWidth > 1024) closeSidebar(); });
            }
        </script>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>

</html>