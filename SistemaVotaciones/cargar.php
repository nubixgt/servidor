<?php
// cargar_multiple.php - Carga múltiple de PDFs con progreso en tiempo real
require_once 'config.php';
require_once 'procesar.php';

$mensaje = '';
$tipo_mensaje = '';
$resultados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_files'])) {
    try {
        $archivos = $_FILES['pdf_files'];
        $totalArchivos = count($archivos['name']);
        
        if ($totalArchivos === 0) {
            throw new Exception('No se seleccionaron archivos');
        }
        
        $procesador = new ProcesadorCongreso();
        $exitosos = 0;
        $fallidos = 0;
        
        // Procesar cada archivo
        for ($i = 0; $i < $totalArchivos; $i++) {
            $nombreArchivo = $archivos['name'][$i];
            
            // Validar archivo individual
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) {
                $resultados[] = [
                    'nombre' => $nombreArchivo,
                    'success' => false,
                    'mensaje' => 'Error al cargar el archivo'
                ];
                $fallidos++;
                continue;
            }
            
            if ($archivos['size'][$i] > MAX_FILE_SIZE) {
                $resultados[] = [
                    'nombre' => $nombreArchivo,
                    'success' => false,
                    'mensaje' => 'Archivo demasiado grande (máximo 10MB)'
                ];
                $fallidos++;
                continue;
            }
            
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if ($extension !== 'pdf') {
                $resultados[] = [
                    'nombre' => $nombreArchivo,
                    'success' => false,
                    'mensaje' => 'Solo se permiten archivos PDF'
                ];
                $fallidos++;
                continue;
            }
            
            // Guardar archivo temporal
            $nombreUnico = uniqid('votacion_') . '.pdf';
            $rutaDestino = UPLOAD_DIR . $nombreUnico;
            
            if (!move_uploaded_file($archivos['tmp_name'][$i], $rutaDestino)) {
                $resultados[] = [
                    'nombre' => $nombreArchivo,
                    'success' => false,
                    'mensaje' => 'Error al guardar el archivo'
                ];
                $fallidos++;
                continue;
            }
            
            // Procesar el PDF
            $resultado = $procesador->procesarPDF($rutaDestino);
            
            $resultados[] = [
                'nombre' => $nombreArchivo,
                'success' => $resultado['success'],
                'mensaje' => $resultado['success'] ? $resultado['mensaje'] : $resultado['error'],
                'total_votos' => $resultado['total_votos'] ?? 0,
                'evento_id' => $resultado['evento_id'] ?? null
            ];
            
            if ($resultado['success']) {
                $exitosos++;
            } else {
                $fallidos++;
            }
        }
        
        $mensaje = "Procesamiento completado: $exitosos exitosos, $fallidos fallidos de $totalArchivos archivos";
        $tipo_mensaje = $fallidos === 0 ? 'success' : 'warning';
        
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener archivos procesados
$db = getDB();
$stmt = $db->query("
    SELECT 
        e.*,
        r.total_votos,
        r.votos_favor,
        r.votos_contra,
        r.resultado
    FROM eventos_votacion e
    LEFT JOIN resumen_eventos r ON e.id = r.evento_id
    ORDER BY e.fecha_carga DESC
    LIMIT 20
");
$archivosProcesados = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Carga de PDFs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        /* Estilos específicos para carga manteniendo consistencia */
        .upload-area {
            border: 3px dashed #cbd5e1;
            border-radius: 1.25rem;
            padding: 3rem;
            text-align: center;
            background: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .upload-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.5s;
        }
        
        .upload-area:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.2);
        }
        
        .upload-area:hover::before {
            left: 100%;
        }
        
        .upload-area.dragover {
            border-color: #667eea;
            background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
            transform: scale(1.02);
        }
        
        .upload-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .file-list {
            max-height: 350px;
            overflow-y: auto;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 1rem;
            padding: 1rem;
        }
        
        .file-item {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
            animation: slideInLeft 0.3s ease-out;
        }
        
        .file-item:hover {
            border-color: #667eea;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .file-item .file-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }
        
        .file-item .file-size {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .file-item .remove-file {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 1rem;
        }
        
        .file-item .remove-file:hover {
            background: #dc2626;
            color: white;
            transform: rotate(90deg);
        }
        
        .result-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid #e2e8f0;
            transition: all 0.3s;
            animation: fadeIn 0.5s ease-out;
        }
        
        .result-item.success {
            border-left-color: #10b981;
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, white 100%);
        }
        
        .result-item.error {
            border-left-color: #ef4444;
            background: linear-gradient(90deg, rgba(239, 68, 68, 0.05) 0%, white 100%);
        }
        
        .result-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        #progressContainer {
            animation: slideInRight 0.5s ease-out;
        }
        
        .info-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            animation: fadeIn 0.3s ease-out;
        }
        
        .info-badge.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .recent-files-list .list-group-item {
            border: none;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s;
        }
        
        .recent-files-list .list-group-item:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, white 100%);
            transform: translateX(5px);
        }
            /* Animación del Logo */
        .logo img {
            animation: logoFloat 3s ease-in-out infinite;
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            animation: logoSpin 1s ease-in-out;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-8px) scale(1.05); }
        }

        @keyframes logoSpin {
            0% { transform: rotateZ(0deg); }
            100% { transform: rotateZ(360deg); }
        }
</style>
</head>
<body>
    <!-- Botón Menú Móvil -->
    <button class="mobile-menu-btn d-lg-none" id="mobileMenuBtn" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay para cerrar menú -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 sidebar" id="sidebar">
                <div class="logo text-center">
                    <img src="logo-congreso.jpg" alt="Congreso de Guatemala" style="max-width: 120px; height: auto; margin-bottom: 1rem;">
                    <h5 class="mb-1">Congreso de la República</h5>
                    <small class="text-muted d-block">Sistema de Votaciones</small>
                </div>
                <nav class="nav flex-column mt-4">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                    <a class="nav-link" href="eventos.php">
                        <i class="bi bi-calendar-event"></i> Eventos
                    </a>
                    <a class="nav-link" href="congresistas.php">
                        <i class="bi bi-people"></i> Congresistas
                    </a>
                    <a class="nav-link" href="bloques.php">
                        <i class="bi bi-diagram-3"></i> Bloques
                    </a>
                    <a class="nav-link" href="estadisticas.php">
                        <i class="bi bi-bar-chart"></i> Estadísticas
                    </a>
                    <a class="nav-link active" href="cargar.php">
                        <i class="bi bi-upload"></i> Cargar PDF
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2 class="mb-1">
                        <i class="bi bi-cloud-upload me-2"></i>Cargar Documentos PDF
                    </h2>
                    <p class="text-muted mb-0">
                        Sube archivos de votaciones para procesamiento automático
                    </p>
                </div>
                
                <!-- Mensajes -->
                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                    <strong><?php echo $mensaje; ?></strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Resultados Detallados -->
                <?php if (!empty($resultados)): ?>
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">
                            <i class="bi bi-list-check me-2"></i>Resultados del Procesamiento
                        </h5>
                        <div class="results-container">
                            <?php foreach ($resultados as $resultado): ?>
                            <div class="result-item <?php echo $resultado['success'] ? 'success' : 'error'; ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <i class="bi bi-<?php echo $resultado['success'] ? 'check-circle text-success' : 'x-circle text-danger'; ?> me-2"></i>
                                            <?php echo htmlspecialchars($resultado['nombre']); ?>
                                        </h6>
                                        <p class="mb-0 small text-muted">
                                            <?php echo htmlspecialchars($resultado['mensaje']); ?>
                                        </p>
                                    </div>
                                    <?php if ($resultado['success'] && isset($resultado['total_votos'])): ?>
                                    <div class="ms-3">
                                        <span class="badge bg-primary">
                                            <?php echo $resultado['total_votos']; ?> votos
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Formulario de Carga -->
                <div class="row g-3">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                                    <!-- Área de carga -->
                                    <div class="upload-area" id="uploadArea" onclick="document.getElementById('pdf_files').click()">
                                        <i class="bi bi-cloud-arrow-up upload-icon"></i>
                                        <h4 class="mb-2">Arrastra archivos PDF aquí</h4>
                                        <p class="text-muted mb-3">o haz clic para seleccionar archivos</p>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <span class="info-badge info">
                                                <i class="bi bi-file-pdf"></i>
                                                Solo PDF
                                            </span>
                                            <span class="info-badge info">
                                                <i class="bi bi-files"></i>
                                                Múltiples archivos
                                            </span>
                                            <span class="info-badge info">
                                                <i class="bi bi-hdd"></i>
                                                Máx. 10MB
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <input type="file" 
                                           id="pdf_files" 
                                           name="pdf_files[]" 
                                           accept=".pdf,application/pdf" 
                                           multiple 
                                           style="display: none;">
                                    
                                    <!-- Lista de archivos seleccionados -->
                                    <div id="fileListContainer" class="mt-3 d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="bi bi-files me-2"></i>
                                                Archivos seleccionados (<span id="fileCount">0</span>)
                                            </h5>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearFiles">
                                                <i class="bi bi-trash me-1"></i>Limpiar
                                            </button>
                                        </div>
                                        <div class="file-list" id="fileList"></div>
                                    </div>
                                    
                                    <!-- Barra de progreso -->
                                    <div id="progressContainer" style="display: none;" class="mt-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="mb-3" id="progressText">Procesando archivos...</h6>
                                                <div class="progress" style="height: 25px;">
                                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                                         role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Botones de acción -->
                                    <div class="mt-3 d-flex gap-2">
                                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg flex-grow-1" disabled>
                                            <i class="bi bi-upload me-2"></i>Procesar Archivos
                                        </button>
                                        <a href="eventos.php" class="btn btn-outline-secondary btn-lg">
                                            <i class="bi bi-arrow-left"></i>
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Información -->
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    <i class="bi bi-info-circle me-2"></i>Información Importante
                                </h5>
                                <ul class="mb-0">
                                    <li>Puedes seleccionar 20 PDF a la vez</li>
                                    <li>Tamaño máximo por archivo: 10MB</li>
                                    <li>El sistema procesará todos los archivos automáticamente</li>
                                    <li>Los eventos duplicados se actualizarán</li>
                                    <li>Verás un resumen detallado al finalizar</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Archivos Procesados Recientemente -->
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="mb-3">
                                    <i class="bi bi-clock-history me-2"></i>Procesados Recientemente
                                </h5>
                                <div class="recent-files-list" style="max-height: 700px; overflow-y: auto;">
                                    <?php if (empty($archivosProcesados)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-inbox"></i>
                                        <p class="mt-3 mb-0 text-muted">No hay archivos procesados</p>
                                    </div>
                                    <?php else: ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($archivosProcesados as $archivo): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1">
                                                        <i class="bi bi-file-pdf text-danger me-2"></i>
                                                        Evento #<?php echo htmlspecialchars($archivo['numero_evento']); ?>
                                                    </h6>
                                                    <p class="mb-1 small text-muted">
                                                        <?php echo htmlspecialchars(substr($archivo['titulo'], 0, 60)) . '...'; ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar3 me-1"></i>
                                                        <?php echo formatearFecha($archivo['fecha_carga']); ?>
                                                    </small>
                                                </div>
                                                <div class="ms-3 text-end">
                                                    <?php if ($archivo['resultado']): ?>
                                                    <span class="badge bg-<?php echo $archivo['resultado'] === 'APROBADO' ? 'success' : 'danger'; ?>">
                                                        <?php echo $archivo['resultado']; ?>
                                                    </span>
                                                    <br>
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?php echo $archivo['total_votos']; ?> votos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript para menú móvil responsive
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (menuBtn && sidebar && overlay) {
                // Abrir/cerrar menú
                menuBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                    
                    // Cambiar ícono
                    const icon = this.querySelector('i');
                    if (sidebar.classList.contains('show')) {
                        icon.className = 'bi bi-x';
                        document.body.style.overflow = 'hidden';
                    } else {
                        icon.className = 'bi bi-list';
                        document.body.style.overflow = '';
                    }
                });
                
                // Cerrar al hacer clic en overlay
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                    menuBtn.querySelector('i').className = 'bi bi-list';
                    document.body.style.overflow = '';
                });
                
                // Cerrar al hacer clic en un link
                const navLinks = sidebar.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth < 992) {
                            sidebar.classList.remove('show');
                            overlay.classList.remove('show');
                            menuBtn.querySelector('i').className = 'bi bi-list';
                            document.body.style.overflow = '';
                        }
                    });
                });
                
                // Cerrar con ESC
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                        menuBtn.querySelector('i').className = 'bi bi-list';
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('pdf_files');
        const fileListContainer = document.getElementById('fileListContainer');
        const fileList = document.getElementById('fileList');
        const fileCount = document.getElementById('fileCount');
        const submitBtn = document.getElementById('submitBtn');
        const clearFilesBtn = document.getElementById('clearFiles');
        const progressContainer = document.getElementById('progressContainer');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        let selectedFiles = [];
        
        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
            if (files.length > 0) {
                addFiles(files);
            }
        });
        
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            addFiles(files);
        });
        
        clearFilesBtn.addEventListener('click', () => {
            selectedFiles = [];
            fileInput.value = '';
            updateFileList();
        });
        
        function addFiles(files) {
            selectedFiles = [...selectedFiles, ...files];
            updateFileList();
        }
        
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFileList();
        }
        
        function updateFileList() {
            if (selectedFiles.length === 0) {
                fileListContainer.classList.add('d-none');
                submitBtn.disabled = true;
                return;
            }
            
            fileListContainer.classList.remove('d-none');
            submitBtn.disabled = false;
            
            fileCount.textContent = selectedFiles.length;
            
            fileList.innerHTML = '';
            selectedFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item';
                
                const fileName = document.createElement('div');
                fileName.className = 'file-name';
                fileName.innerHTML = `
                    <i class="bi bi-file-pdf text-danger"></i>
                    <span><strong>${file.name}</strong></span>
                `;
                
                const fileSize = document.createElement('div');
                fileSize.className = 'file-size';
                fileSize.textContent = formatFileSize(file.size);
                
                const removeBtn = document.createElement('div');
                removeBtn.className = 'remove-file';
                removeBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
                removeBtn.onclick = () => removeFile(index);
                
                fileItem.appendChild(fileName);
                fileItem.appendChild(fileSize);
                fileItem.appendChild(removeBtn);
                fileList.appendChild(fileItem);
            });
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        // Form submission con indicador de progreso
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
            progressContainer.style.display = 'block';
            
            // Simular progreso
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    progressText.textContent = `Procesando archivos... ${progress}%`;
                } else {
                    clearInterval(interval);
                    progressText.textContent = 'Finalizando...';
                }
            }, 200);
        });
    </script>
    <script>
        document.querySelectorAll('a:not([target="_blank"]):not([href^="#"])').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href.includes('#') || this.classList.contains('no-transition')) return;
                e.preventDefault();
                const href = this.href;
                document.body.style.opacity = '0';
                document.body.style.transition = 'opacity 0.4s ease-in-out';
                setTimeout(() => { window.location.href = href; }, 400);
            });
        });
        window.addEventListener('pageshow', function() { document.body.style.opacity = '1'; });
    </script>
</body>
</html>