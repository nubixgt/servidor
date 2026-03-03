<?php
/**
 * VIDER - MAGA Guatemala
 * Página de Historial - Registro de importaciones
 */
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireLogin(); // Proteger página - requiere autenticación

$db = Database::getInstance();
$currentPage = 'historial';

// Obtener historial de importaciones
$importaciones = $db->fetchAll("
    SELECT 
        id,
        nombre_archivo,
        usuario,
        ip_address,
        registros_totales,
        registros_importados,
        registros_duplicados,
        registros_error,
        estado,
        mensaje,
        created_at,
        completed_at
    FROM importaciones
    ORDER BY created_at DESC
    LIMIT 100
");

// Estadísticas
$stats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_importaciones,
        SUM(registros_importados) as total_importados,
        SUM(registros_duplicados) as total_duplicados,
        SUM(registros_error) as total_errores
    FROM importaciones
    WHERE estado = 'completado'
");
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'includes/header.php'; ?>
    <title>Historial | VIDER - MAGA Guatemala</title>
    <style>
        .history-page {
            padding: 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: var(--font-display);
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quick-stat {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .quick-stat:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .quick-stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.25rem;
        }

        .quick-stat-icon.green {
            background: rgba(74, 144, 217, 0.15);
            color: var(--primary);
        }

        .quick-stat-icon.blue {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .quick-stat-icon.yellow {
            background: rgba(247, 185, 40, 0.15);
            color: #f7b928;
        }

        .quick-stat-icon.red {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .quick-stat-value {
            font-family: var(--font-display);
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .quick-stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .history-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .history-header h3 {
            font-family: var(--font-display);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .history-filters {
            display: flex;
            gap: 1rem;
        }

        .history-filters select {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--surface-primary);
            color: var(--text-primary);
        }

        .history-list {
            max-height: 600px;
            overflow-y: auto;
        }

        .history-item {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 1.5rem;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--glass-border);
            transition: all 0.3s ease;
        }

        .history-item:hover {
            background: rgba(74, 144, 217, 0.05);
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .history-status {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .history-status.completado {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .history-status.procesando {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            animation: pulse 2s infinite;
        }

        .history-status.error {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .history-info h4 {
            font-family: var(--font-display);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .history-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .history-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .history-stats {
            display: flex;
            gap: 1rem;
        }

        .stat-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 60px;
        }

        .stat-badge.imported {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }

        .stat-badge.duplicates {
            background: rgba(247, 185, 40, 0.15);
            color: #f7b928;
        }

        .stat-badge.errors {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .stat-badge-value {
            font-size: 1rem;
            font-weight: 700;
        }

        .stat-badge-label {
            font-size: 0.7rem;
            font-weight: 400;
            opacity: 0.8;
        }

        .history-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: var(--surface-primary);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-family: var(--font-display);
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        /* Timeline effect */
        .timeline-dot {
            position: relative;
        }

        .timeline-dot::before {
            content: '';
            position: absolute;
            left: 22px;
            top: 45px;
            bottom: -45px;
            width: 2px;
            background: var(--glass-border);
        }

        .history-item:last-child .timeline-dot::before {
            display: none;
        }

        @media (max-width: 1024px) {
            .history-page {
                padding-top: 4rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .history-item {
                grid-template-columns: auto 1fr;
                gap: 1rem;
            }

            .history-stats,
            .history-actions {
                grid-column: 2;
                justify-content: flex-start;
            }
        }

        @media (max-width: 768px) {
            .history-page {
                padding: 1rem;
                padding-top: 4.5rem;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .quick-stat {
                padding: 1rem;
            }
            
            .quick-stat-value {
                font-size: 1.35rem;
            }

            .history-header {
                flex-direction: column;
                gap: 1rem;
            }

            .history-filters {
                width: 100%;
            }

            .history-filters select {
                flex: 1;
            }
            
            .history-item {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                padding: 1rem;
            }
            
            .history-stats {
                flex-wrap: wrap;
            }
            
            .stat-badge {
                flex: 1;
                min-width: 70px;
            }
            
            .history-meta {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
            
            .timeline-dot::before {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .history-page {
                padding: 0.75rem;
                padding-top: 4.5rem;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .quick-stat {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 0.75rem;
                text-align: left;
            }
            
            .quick-stat-icon {
                margin: 0;
            }
            
            .quick-stat-value {
                font-size: 1.15rem;
            }
            
            .history-actions {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="history-page">
                <div class="page-header stagger">
                    <h1><i class="fas fa-history"></i> Historial de Importaciones</h1>
                    <a href="importar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Importación
                    </a>
                </div>

                <!-- Quick Stats -->
                <div class="quick-stats stagger">
                    <div class="quick-stat">
                        <div class="quick-stat-icon green">
                            <i class="fas fa-file-import"></i>
                        </div>
                        <div class="quick-stat-value"><?= number_format($stats['total_importaciones'] ?? 0) ?></div>
                        <div class="quick-stat-label">Total Importaciones</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon blue">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="quick-stat-value"><?= number_format($stats['total_importados'] ?? 0) ?></div>
                        <div class="quick-stat-label">Registros Importados</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon yellow">
                            <i class="fas fa-copy"></i>
                        </div>
                        <div class="quick-stat-value"><?= number_format($stats['total_duplicados'] ?? 0) ?></div>
                        <div class="quick-stat-label">Duplicados Detectados</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="quick-stat-value"><?= number_format($stats['total_errores'] ?? 0) ?></div>
                        <div class="quick-stat-label">Errores</div>
                    </div>
                </div>

                <!-- History Container -->
                <div class="history-container stagger">
                    <div class="history-header">
                        <h3><i class="fas fa-list"></i> Registro de Actividad</h3>
                        <div class="history-filters">
                            <select id="filter-status">
                                <option value="">Todos los estados</option>
                                <option value="completado">Completado</option>
                                <option value="procesando">Procesando</option>
                                <option value="error">Error</option>
                            </select>
                            <select id="filter-period">
                                <option value="">Todo el tiempo</option>
                                <option value="today">Hoy</option>
                                <option value="week">Esta semana</option>
                                <option value="month">Este mes</option>
                            </select>
                        </div>
                    </div>

                    <div class="history-list" id="history-list">
                        <?php if (empty($importaciones)): ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>Sin importaciones</h3>
                                <p>No hay registro de importaciones todavía</p>
                                <a href="importar.php" class="btn btn-primary" style="margin-top: 1rem;">
                                    <i class="fas fa-file-import"></i> Realizar Primera Importación
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($importaciones as $import): ?>
                                <div class="history-item" data-status="<?= htmlspecialchars($import['estado']) ?>"
                                    data-date="<?= $import['created_at'] ?>">
                                    <div class="timeline-dot">
                                        <div class="history-status <?= htmlspecialchars($import['estado']) ?>">
                                            <?php
                                            $icon = match ($import['estado']) {
                                                'completado' => 'check',
                                                'procesando' => 'spinner fa-spin',
                                                'error' => 'times',
                                                default => 'question'
                                            };
                                            ?>
                                            <i class="fas fa-<?= $icon ?>"></i>
                                        </div>
                                    </div>

                                    <div class="history-info">
                                        <h4><?= htmlspecialchars($import['nombre_archivo']) ?></h4>
                                        <div class="history-meta">
                                            <span>
                                                <i class="fas fa-user"></i>
                                                <?= htmlspecialchars($import['usuario'] ?? 'Sistema') ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar"></i>
                                                <?= date('d/m/Y H:i', strtotime($import['created_at'])) ?>
                                            </span>
                                            <?php if ($import['completed_at']): ?>
                                                <span>
                                                    <i class="fas fa-clock"></i>
                                                    <?php
                                                    $start = new DateTime($import['created_at']);
                                                    $end = new DateTime($import['completed_at']);
                                                    $diff = $start->diff($end);
                                                    echo $diff->format('%i min %s seg');
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            <span>
                                                <i class="fas fa-network-wired"></i>
                                                <?= htmlspecialchars($import['ip_address'] ?? '-') ?>
                                            </span>
                                        </div>
                                        <?php if ($import['mensaje']): ?>
                                            <div class="history-message"
                                                style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                                                <i class="fas fa-info-circle"></i>
                                                <?= htmlspecialchars($import['mensaje']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="history-stats">
                                        <div class="stat-badge imported">
                                            <span
                                                class="stat-badge-value"><?= number_format($import['registros_importados'] ?? 0) ?></span>
                                            <span class="stat-badge-label">Importados</span>
                                        </div>
                                        <div class="stat-badge duplicates">
                                            <span
                                                class="stat-badge-value"><?= number_format($import['registros_duplicados'] ?? 0) ?></span>
                                            <span class="stat-badge-label">Duplicados</span>
                                        </div>
                                        <div class="stat-badge errors">
                                            <span
                                                class="stat-badge-value"><?= number_format($import['registros_error'] ?? 0) ?></span>
                                            <span class="stat-badge-label">Errores</span>
                                        </div>
                                    </div>

                                    <div class="history-actions">
                                        <button class="action-btn" title="Ver detalles"
                                            onclick="viewDetails(<?= $import['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn" title="Descargar log"
                                            onclick="downloadLog(<?= $import['id'] ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Detail Modal -->
    <div class="modal" id="detail-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-info-circle"></i> Detalles de Importación</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initMobileMenu();
            initializeFilters();
            animateItems();
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
                link.addEventListener('click', () => {
                    if (window.innerWidth <= 1024) closeSidebar();
                });
            });
            
            window.addEventListener('resize', () => {
                if (window.innerWidth > 1024) closeSidebar();
            });
        }

        function initializeFilters() {
            const statusFilter = document.getElementById('filter-status');
            const periodFilter = document.getElementById('filter-period');

            statusFilter.addEventListener('change', applyFilters);
            periodFilter.addEventListener('change', applyFilters);
        }

        function applyFilters() {
            const status = document.getElementById('filter-status').value;
            const period = document.getElementById('filter-period').value;
            const items = document.querySelectorAll('.history-item');

            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);

            items.forEach(item => {
                let show = true;

                // Status filter
                if (status && item.dataset.status !== status) {
                    show = false;
                }

                // Period filter
                if (period && show) {
                    const itemDate = new Date(item.dataset.date);

                    switch (period) {
                        case 'today':
                            show = itemDate >= today;
                            break;
                        case 'week':
                            show = itemDate >= weekAgo;
                            break;
                        case 'month':
                            show = itemDate >= monthAgo;
                            break;
                    }
                }

                item.style.display = show ? 'grid' : 'none';
            });
        }

        function animateItems() {
            document.querySelectorAll('.history-item').forEach((item, index) => {
                item.style.animation = `fadeIn 0.5s ease ${index * 0.05}s forwards`;
                item.style.opacity = '0';
            });
        }

        function viewDetails(id) {
            const modal = document.getElementById('detail-modal');
            const modalBody = document.getElementById('modal-body');

            modalBody.innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <div class="spinner" style="margin: 0 auto;"></div>
                    <p style="margin-top: 1rem;">Cargando detalles...</p>
                </div>
            `;

            modal.classList.add('active');

            // In a real implementation, fetch details from API
            setTimeout(() => {
                modalBody.innerHTML = `
                    <div style="padding: 1rem;">
                        <h4 style="margin-bottom: 1rem;">Importación #${id}</h4>
                        <p style="color: var(--text-secondary);">Los detalles completos de la importación se cargarían aquí.</p>
                        
                        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--glass-border);">
                            <h5 style="margin-bottom: 0.5rem;">Registros Procesados</h5>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                                <div style="background: rgba(34, 197, 94, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #22c55e;">1,500</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Importados</div>
                                </div>
                                <div style="background: rgba(247, 185, 40, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #f7b928;">111</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Duplicados</div>
                                </div>
                                <div style="background: rgba(239, 68, 68, 0.1); padding: 1rem; border-radius: 8px; text-align: center;">
                                    <div style="font-size: 1.5rem; font-weight: 700; color: #ef4444;">0</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">Errores</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }, 500);
        }

        function closeModal() {
            document.getElementById('detail-modal').classList.remove('active');
        }

        function downloadLog(id) {
            showToast('Descargando log de importación...', 'info');
            // In a real implementation, trigger download from API
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;

            let container = document.querySelector('.toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'toast-container';
                document.body.appendChild(container);
            }

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // Close modal on outside click
        document.getElementById('detail-modal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
    <?php include 'includes/footer.php'; ?>
</body>

</html>