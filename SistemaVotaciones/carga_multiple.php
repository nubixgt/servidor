<?php
// cargar_multiple.php - Carga múltiple de PDFs con progreso en tiempo real

// CONFIGURACIÓN PHP PARA CARGA MÚLTIPLE
@ini_set('max_execution_time', 0);  // Sin límite
@set_time_limit(0);
@ini_set('max_file_uploads', 100);    // Hasta 100 archivos
@ini_set('memory_limit', '512M');     // 512MB de memoria
@ini_set('post_max_size', '500M');
@ini_set('upload_max_filesize', '50M');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carga Múltiple de PDFs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
        }
        .sidebar .logo { padding: 2rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .logo h4 { font-weight: 700; margin: 0; font-size: 1.25rem; }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.875rem 1.5rem;
            border-left: 3px solid transparent;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #2563eb;
        }
        .sidebar .nav-link i { margin-right: 0.75rem; width: 20px; }
        .upload-area {
            border: 3px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 3rem;
            text-align: center;
            background-color: white;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover { border-color: #2563eb; background-color: #f8fafc; }
        .upload-area.dragover { border-color: #2563eb; background-color: #eff6ff; }
        .page-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card { border-radius: 1rem; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .file-list {
            max-height: 300px;
            overflow-y: auto;
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .file-item {
            background: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
        }
        .file-item .file-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }
        .file-item .file-size {
            color: #64748b;
            font-size: 0.875rem;
        }
        .file-item .remove-file {
            color: #dc2626;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }
        .file-item .remove-file:hover {
            background: #fee2e2;
            border-radius: 0.25rem;
        }
        
        .progress-container {
            display: none;
            margin-top: 1rem;
        }
        .progress {
            height: 2rem;
            border-radius: 0.5rem;
        }
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .result-item {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid;
        }
        .result-item.success {
            background: #d1fae5;
            border-color: #10b981;
        }
        .result-item.error {
            background: #fee2e2;
            border-color: #ef4444;
        }
        .result-item .result-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .result-item .result-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .result-item .result-message {
            font-size: 0.875rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <div class="logo">
                    <i class="bi bi-bank2 fs-3 mb-2"></i>
                    <h4>Congreso GT</h4>
                    <small class="text-muted">Sistema de Votaciones</small>
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
                    <a class="nav-link" href="cargar.php">
                        <i class="bi bi-upload"></i> Cargar PDF
                    </a>
                    <a class="nav-link active" href="cargar_multiple.php">
                        <i class="bi bi-cloud-upload"></i> Carga Múltiple
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Carga Múltiple de Documentos PDF</h2>
                            <p class="text-muted mb-0">Procesa varios archivos PDF simultáneamente</p>
                        </div>
                        <a href="cargar.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Carga Simple
                        </a>
                    </div>
                </div>
                
                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?php echo $tipo_mensaje === 'success' ? 'check-circle' : ($tipo_mensaje === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($resultados)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clipboard-check me-2"></i>Resultados del Procesamiento
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($resultados as $res): ?>
                        <div class="result-item <?php echo $res['success'] ? 'success' : 'error'; ?>">
                            <div class="result-header">
                                <div class="result-title">
                                    <i class="bi bi-<?php echo $res['success'] ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?>"></i>
                                    <span><?php echo htmlspecialchars($res['nombre']); ?></span>
                                </div>
                                <?php if ($res['success'] && isset($res['total_votos'])): ?>
                                <span class="badge bg-primary"><?php echo $res['total_votos']; ?> votos</span>
                                <?php endif; ?>
                            </div>
                            <div class="result-message">
                                <?php echo htmlspecialchars($res['mensaje']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="bi bi-cloud-upload me-2"></i>Seleccionar Archivos
                                </h5>
                                
                                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                                    <div class="upload-area" id="uploadArea">
                                        <i class="bi bi-files fs-1 text-primary mb-3"></i>
                                        <h5>Arrastra múltiples archivos PDF aquí</h5>
                                        <p class="text-muted">o haz clic para seleccionar varios archivos a la vez</p>
                                        <input type="file" name="pdf_files[]" id="pdf_files" 
                                               accept=".pdf" class="d-none" multiple required>
                                        <button type="button" class="btn btn-primary mt-2" 
                                                onclick="document.getElementById('pdf_files').click()">
                                            <i class="bi bi-folder2-open me-2"></i>Seleccionar Archivos
                                        </button>
                                    </div>
                                    
                                    <div id="fileListContainer" class="mt-3 d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">
                                                <i class="bi bi-list-ul me-2"></i>Archivos Seleccionados: <span id="fileCount">0</span>
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearFiles">
                                                <i class="bi bi-trash me-1"></i>Limpiar Todo
                                            </button>
                                        </div>
                                        <div id="fileList" class="file-list"></div>
                                    </div>
                                    
                                    <div class="progress-container" id="progressContainer">
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                                 role="progressbar" id="progressBar" style="width: 0%">
                                                <span id="progressText">Procesando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success btn-lg w-100 mt-3" id="submitBtn" disabled>
                                        <i class="bi bi-upload me-2"></i>Procesar Todos los Archivos
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-info-circle me-2"></i>Información
                                </h5>
                                <ul class="mb-0">
                                    <li>Puedes seleccionar múltiples archivos PDF a la vez</li>
                                    <li>Tamaño máximo por archivo: 10MB</li>
                                    <li>El sistema procesará todos los archivos automáticamente</li>
                                    <li>Los eventos duplicados se actualizarán</li>
                                    <li>Verás un resumen detallado al finalizar</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Archivos Procesados Recientemente
                                </h5>
                            </div>
                            <div class="card-body p-0" style="max-height: 700px; overflow-y: auto;">
                                <?php if (empty($archivosProcesados)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p>No hay archivos procesados</p>
                                </div>
                                <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($archivosProcesados as $archivo): ?>
                                    <div class="list-group-item">
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                    <span>${file.name}</span>
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
            
            // Simular progreso (en producción, esto debería ser real vía AJAX)
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
</body>
</html>