<?php
// cargar_avanzado.php - Carga múltiple con progreso en tiempo real usando AJAX

// CONFIGURACIÓN PHP PARA CARGA MÚLTIPLE
@ini_set('max_execution_time', 0);  // Sin límite
@set_time_limit(0);
@ini_set('max_file_uploads', 100);    // Hasta 100 archivos
@ini_set('memory_limit', '512M');     // 512MB de memoria

require_once 'config.php';

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
    <title>Carga Múltiple Avanzada - <?php echo APP_NAME; ?></title>
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
        .upload-area.dragover { border-color: #2563eb; background-color: #eff6ff; transform: scale(1.02); }
        .page-header {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card { border-radius: 1rem; border: none; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        .file-list {
            max-height: 400px;
            overflow-y: auto;
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        .file-item {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s;
        }
        .file-item.processing {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .file-item.success {
            border-color: #10b981;
            background: #d1fae5;
        }
        .file-item.error {
            border-color: #ef4444;
            background: #fee2e2;
        }
        .file-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        .file-item-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            flex: 1;
        }
        .file-item-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .file-item-progress {
            margin-top: 0.5rem;
        }
        .file-item-message {
            font-size: 0.875rem;
            color: #475569;
            margin-top: 0.5rem;
        }
        
        .progress {
            height: 1.5rem;
            border-radius: 0.5rem;
            background: #e2e8f0;
        }
        .progress-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
        }
        .stats-card h3 {
            font-size: 2.5rem;
            margin: 0;
            font-weight: 700;
        }
        .stats-card p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulsing {
            animation: pulse 2s ease-in-out infinite;
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
                    <a class="nav-link active" href="cargar_avanzado.php">
                        <i class="bi bi-lightning-charge"></i> Carga Avanzada
                    </a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">
                                <i class="bi bi-lightning-charge me-2"></i>Carga Múltiple Avanzada
                            </h2>
                            <p class="text-muted mb-0">Procesamiento en tiempo real con seguimiento detallado</p>
                        </div>
                        <div>
                            <a href="cargar.php" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left me-2"></i>Carga Simple
                            </a>
                            <a href="cargar_multiple.php" class="btn btn-outline-primary">
                                <i class="bi bi-cloud-upload me-2"></i>Carga Múltiple
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Estadísticas del procesamiento -->
                <div class="row mb-4" id="statsContainer" style="display: none;">
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3 id="statTotal">0</h3>
                            <p>Total de Archivos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3 id="statProcessing">0</h3>
                            <p>Procesando</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3 id="statSuccess">0</h3>
                            <p>Exitosos</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <h3 id="statError">0</h3>
                            <p>Fallidos</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-7">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">
                                    <i class="bi bi-cloud-upload me-2"></i>Zona de Carga
                                </h5>
                                
                                <div class="upload-area" id="uploadArea">
                                    <i class="bi bi-files fs-1 text-primary mb-3"></i>
                                    <h5>Arrastra tus archivos PDF aquí</h5>
                                    <p class="text-muted">o haz clic para seleccionar múltiples archivos</p>
                                    <input type="file" id="pdf_files" accept=".pdf" class="d-none" multiple>
                                    <button type="button" class="btn btn-primary btn-lg mt-2" 
                                            onclick="document.getElementById('pdf_files').click()">
                                        <i class="bi bi-folder2-open me-2"></i>Seleccionar Archivos
                                    </button>
                                </div>
                                
                                <div id="fileListContainer" class="mt-4 d-none">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0">
                                            <i class="bi bi-list-check me-2"></i>
                                            Archivos: <span id="fileCount" class="badge bg-primary">0</span>
                                        </h6>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-danger me-2" id="clearFiles">
                                                <i class="bi bi-trash me-1"></i>Limpiar
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success" id="startProcessing" disabled>
                                                <i class="bi bi-play-fill me-1"></i>Iniciar Procesamiento
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="fileList" class="file-list"></div>
                                    
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <small class="text-muted">Progreso General</small>
                                            <small class="text-muted"><span id="overallProgress">0</span>%</small>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" id="overallProgressBar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <i class="bi bi-info-circle me-2"></i>Características
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Procesamiento en tiempo real</li>
                                            <li>Progreso individual por archivo</li>
                                            <li>Sin límite de archivos</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="mb-0">
                                            <li>Máximo 10MB por archivo</li>
                                            <li>Resultados instantáneos</li>
                                            <li>Manejo automático de errores</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-clock-history me-2"></i>Procesados Recientemente
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
        class FileProcessor {
            constructor() {
                this.files = [];
                this.fileItems = new Map();
                this.stats = { total: 0, processing: 0, success: 0, error: 0 };
                this.isProcessing = false;
                
                this.initializeElements();
                this.attachEventListeners();
            }
            
            initializeElements() {
                this.uploadArea = document.getElementById('uploadArea');
                this.fileInput = document.getElementById('pdf_files');
                this.fileListContainer = document.getElementById('fileListContainer');
                this.fileList = document.getElementById('fileList');
                this.fileCount = document.getElementById('fileCount');
                this.clearBtn = document.getElementById('clearFiles');
                this.startBtn = document.getElementById('startProcessing');
                this.statsContainer = document.getElementById('statsContainer');
                this.overallProgressBar = document.getElementById('overallProgressBar');
                this.overallProgress = document.getElementById('overallProgress');
            }
            
            attachEventListeners() {
                // Drag and drop
                this.uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    this.uploadArea.classList.add('dragover');
                });
                
                this.uploadArea.addEventListener('dragleave', () => {
                    this.uploadArea.classList.remove('dragover');
                });
                
                this.uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    this.uploadArea.classList.remove('dragover');
                    const files = Array.from(e.dataTransfer.files).filter(f => f.type === 'application/pdf');
                    this.addFiles(files);
                });
                
                // File input
                this.fileInput.addEventListener('change', (e) => {
                    const files = Array.from(e.target.files);
                    this.addFiles(files);
                });
                
                // Buttons
                this.clearBtn.addEventListener('click', () => this.clearFiles());
                this.startBtn.addEventListener('click', () => this.startProcessing());
            }
            
            addFiles(newFiles) {
                this.files = [...this.files, ...newFiles];
                this.updateUI();
            }
            
            clearFiles() {
                this.files = [];
                this.fileItems.clear();
                this.fileInput.value = '';
                this.updateUI();
            }
            
            updateUI() {
                if (this.files.length === 0) {
                    this.fileListContainer.classList.add('d-none');
                    this.statsContainer.style.display = 'none';
                    this.startBtn.disabled = true;
                    return;
                }
                
                this.fileListContainer.classList.remove('d-none');
                this.statsContainer.style.display = 'flex';
                this.fileCount.textContent = this.files.length;
                this.startBtn.disabled = this.isProcessing;
                
                this.renderFileList();
                this.updateStats();
            }
            
            renderFileList() {
                this.fileList.innerHTML = '';
                
                this.files.forEach((file, index) => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.id = `file-${index}`;
                    
                    fileItem.innerHTML = `
                        <div class="file-item-header">
                            <div class="file-item-name">
                                <i class="bi bi-file-pdf text-danger"></i>
                                <span>${file.name}</span>
                            </div>
                            <div class="file-item-status">
                                <small class="text-muted">${this.formatFileSize(file.size)}</small>
                                <span class="status-icon"></span>
                            </div>
                        </div>
                        <div class="file-item-progress d-none">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     style="width: 0%"></div>
                            </div>
                        </div>
                        <div class="file-item-message d-none"></div>
                    `;
                    
                    this.fileList.appendChild(fileItem);
                    this.fileItems.set(index, fileItem);
                });
            }
            
            async startProcessing() {
                if (this.isProcessing) return;
                
                this.isProcessing = true;
                this.startBtn.disabled = true;
                this.clearBtn.disabled = true;
                this.startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
                
                this.stats = { total: this.files.length, processing: 0, success: 0, error: 0 };
                this.updateStats();
                
                for (let i = 0; i < this.files.length; i++) {
                    await this.processFile(i, this.files[i]);
                    this.updateOverallProgress();
                }
                
                this.isProcessing = false;
                this.startBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Completado';
                this.clearBtn.disabled = false;
                
                // Mostrar notificación de resumen
                this.showSummary();
            }
            
            async processFile(index, file) {
                const fileItem = this.fileItems.get(index);
                const statusIcon = fileItem.querySelector('.status-icon');
                const progressContainer = fileItem.querySelector('.file-item-progress');
                const progressBar = progressContainer.querySelector('.progress-bar');
                const messageContainer = fileItem.querySelector('.file-item-message');
                
                // Marcar como procesando
                fileItem.classList.add('processing');
                statusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
                progressContainer.classList.remove('d-none');
                this.stats.processing++;
                this.updateStats();
                
                try {
                    const formData = new FormData();
                    formData.append('pdf_file', file);
                    
                    const response = await fetch('api_procesar.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    this.stats.processing--;
                    
                    if (result.success) {
                        // Éxito
                        fileItem.classList.remove('processing');
                        fileItem.classList.add('success');
                        statusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                        progressBar.classList.add('bg-success');
                        progressBar.style.width = '100%';
                        
                        messageContainer.classList.remove('d-none');
                        messageContainer.innerHTML = `
                            <i class="bi bi-check-circle me-2 text-success"></i>
                            ${result.mensaje} - <strong>${result.total_votos} votos procesados</strong>
                        `;
                        
                        this.stats.success++;
                    } else {
                        throw new Error(result.error || 'Error desconocido');
                    }
                } catch (error) {
                    // Error
                    fileItem.classList.remove('processing');
                    fileItem.classList.add('error');
                    statusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-danger');
                    progressBar.style.width = '100%';
                    
                    messageContainer.classList.remove('d-none');
                    messageContainer.innerHTML = `
                        <i class="bi bi-x-circle me-2 text-danger"></i>
                        <strong>Error:</strong> ${error.message}
                    `;
                    
                    this.stats.processing--;
                    this.stats.error++;
                }
                
                this.updateStats();
            }
            
            updateOverallProgress() {
                const processed = this.stats.success + this.stats.error;
                const progress = (processed / this.stats.total) * 100;
                
                this.overallProgressBar.style.width = `${progress}%`;
                this.overallProgress.textContent = Math.round(progress);
            }
            
            updateStats() {
                document.getElementById('statTotal').textContent = this.stats.total;
                document.getElementById('statProcessing').textContent = this.stats.processing;
                document.getElementById('statSuccess').textContent = this.stats.success;
                document.getElementById('statError').textContent = this.stats.error;
            }
            
            showSummary() {
                const message = `
                    Procesamiento completado:
                    ✅ ${this.stats.success} exitosos
                    ❌ ${this.stats.error} fallidos
                    📊 Total: ${this.stats.total} archivos
                `;
                
                alert(message);
                
                // Recargar lista de archivos procesados
                setTimeout(() => location.reload(), 2000);
            }
            
            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            }
        }
        
        // Inicializar el procesador
        const processor = new FileProcessor();
    </script>
</body>
</html>