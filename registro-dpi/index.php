<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Registro de DPI - VISAN 10910</title>

    <!-- Google Fonts - Space Grotesk + Inter -->
    <link
        href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <div class="container">
        <!-- Ultra Modern Header -->
        <header class="header">
            <div class="header-left">
                <div class="logo-wrapper">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="header-title">
                    <h1>Sistema de Registro de DPI</h1>
                    <p>Planillas VISAN 10910</p>
                </div>
            </div>
            <button class="header-btn">
                <i class="fas fa-database"></i>
                <span>Base de Datos DPI</span>
            </button>
        </header>

        <!-- Stats Section -->
        <section class="stats-section">
            <div class="section-label">Estadísticas Generales</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <span class="stat-badge">
                            <i class="fas fa-arrow-up"></i> 100%
                        </span>
                    </div>
                    <div class="stat-value" id="totalRegistros">0</div>
                    <div class="stat-label">Total de Registros</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="stat-badge">
                            <i class="fas fa-check"></i> <span id="porcentajeRegistrados">0%</span>
                        </span>
                    </div>
                    <div class="stat-value" id="registradosCount">0</div>
                    <div class="stat-label">DPI Físico Registrado</div>
                </div>

                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                        <span class="stat-badge danger">
                            <i class="fas fa-exclamation"></i> <span id="porcentajePendientes">100%</span>
                        </span>
                    </div>
                    <div class="stat-value" id="sinRegistrarCount">0</div>
                    <div class="stat-label">Pendientes de Registro</div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <h2>Registros de DPI</h2>
                <p>Administra y verifica el estado físico de los documentos de identificación personal</p>
            </div>

            <div class="content-body">
                <!-- DataTable -->
                <table id="tablaDPI" class="display nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fila</th>
                            <th>Nombre Completo</th>
                            <th>Número de DPI</th>
                            <th>Comunidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los datos se cargan dinámicamente vía AJAX -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom JS -->
    <script src="js/main.js"></script>
</body>

</html>