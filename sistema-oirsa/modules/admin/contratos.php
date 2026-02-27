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
    <title>Contratos - Oirsa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/navbar.css">
    <link rel="stylesheet" href="../../css/contratos.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- jQuery (requerido por DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="container">
            <div class="page-header">
                <h1>
                    <i class="fa-solid fa-file-contract"></i>
                    Gestión de Contratos
                </h1>
                <p>Visualiza, edita y gestiona todos los contratos registrados en el sistema</p>
            </div>

            <div class="table-card">
                <div class="table-header">
                    <h2>
                        <i class="fa-solid fa-list"></i>
                        Todos los Contratos
                    </h2>
                </div>

                <div class="table-container">
                    <table id="contratosTable" class="display" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre Completo</th>
                                <th>Número de Contrato</th>
                                <th>Servicio</th>
                                <th>Fecha de Contrato</th>
                                <th>Fecha Inicio</th>
                                <th>Fecha Fin</th>
                                <th>Monto Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los datos se cargarán dinámicamente con DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para visualizar contrato -->
    <div id="modalVerContrato" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fa-solid fa-eye"></i>
                    Detalles del Contrato
                </h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- El contenido se cargará dinámicamente -->
            </div>
        </div>
    </div>

    <script src="../../js/contratos.js?v=<?php echo time(); ?>"></script>
</body>

</html>