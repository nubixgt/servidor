<?php
// ver_registros.php
require_once 'config/database.php';

// Verificar que el usuario esté logueado
verificarSesion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Registros - Sistema de Lotificación</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/ver_registros.css">
    <!-- DataTables CSS - SIN RESPONSIVE -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/27ecbb77de.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- Fondo animado -->
    <div class="animated-background">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="sparkle"></div>
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-shape shape-5"></div>
    </div>

    <!-- Navbar Lateral -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Contenido Principal -->
    <div class="main-content">
        <div class="container">
            <!-- Header superior -->
            <div class="top-header">
                <div class="logo-section">
                    <h1 class="page-title">Mis Registros</h1>
                </div>
                <div class="user-section">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></div>
                        <div class="user-role">Administrador</div>
                    </div>
                    <div class="user-avatar">👤</div>
                </div>
            </div>

            <!-- Contenedor de registros -->
            <div class="registros-container">
                <div class="registros-header">
                    <div class="header-info">
                        <h2>Lista de Registros</h2>
                        <p>Gestiona todos tus clientes potenciales</p>
                    </div>
                    <div class="header-actions">
                        <button id="btnExportarExcel" class="btn-export">
                            <span class="btn-icon">📥</span>
                            <span>Exportar CSV</span>
                        </button>
                        <a href="formulario.php" class="btn-nuevo">
                            <span class="btn-icon">➕</span>
                            <span>Nuevo Registro</span>
                        </a>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table id="tablaRegistros" class="display nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>Teléfono GT</th>
                                <th>Teléfono USA</th>
                                <th>Cómo se enteró</th>
                                <th>Correo</th>
                                <th>Comentario</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editar Registro</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formEditar">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="form-grid-modal">
                        <div class="form-group">
                            <label for="edit_nombre">Nombre <span class="required">*</span></label>
                            <input type="text" id="edit_nombre" name="nombre" required maxlength="100" placeholder="Ingrese el nombre">
                        </div>

                        <div class="form-group">
                            <label for="edit_apellido">Apellido <span class="required">*</span></label>
                            <input type="text" id="edit_apellido" name="apellido" required maxlength="100" placeholder="Ingrese el apellido">
                        </div>

                        <div class="form-group">
                            <label for="edit_telefono">Teléfono Guatemala</label>
                            <input type="text" id="edit_telefono" name="telefono" maxlength="15" placeholder="+502 0000-0000">
                            <small class="helper-text">Formato: +502 0000-0000</small>
                        </div>

                        <div class="form-group">
                            <label for="edit_telefono_americano">Teléfono USA</label>
                            <input type="text" id="edit_telefono_americano" name="telefono_americano" maxlength="17" placeholder="+1 000-000-0000">
                            <small class="helper-text">Formato: +1 000-000-0000</small>
                        </div>

                        <div class="form-group">
                            <label for="edit_como_se_entero">¿Cómo se enteró? <span class="required">*</span></label>
                            <select id="edit_como_se_entero" name="como_se_entero" required>
                                <option value="">Selecciona una opción</option>
                                <option value="Vallas publicitarias">Vallas publicitarias</option>
                                <option value="Redes sociales">Redes sociales</option>
                                <option value="Por amigos">Por amigos</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_correo">Correo Electrónico</label>
                            <input type="email" id="edit_correo" name="correo" maxlength="100" placeholder="ejemplo@correo.com">
                        </div>

                        <div class="form-group full-width">
                            <label for="edit_comentario">Comentario</label>
                            <textarea id="edit_comentario" name="comentario" rows="4" maxlength="500" placeholder="Comentarios adicionales..."></textarea>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-cancel" id="btnCancelar">Cancelar</button>
                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para bitácora/seguimiento -->
    <div id="modalBitacora" class="modal">
        <div class="modal-content modal-bitacora">
            <div class="modal-header">
                <h3>📋 Bitácora de Seguimiento</h3>
                <span class="close close-bitacora">&times;</span>
            </div>
            <div class="modal-body">
                <div class="bitacora-info">
                    <div class="info-cliente">
                        <h4 id="bitacora-titulo">Cliente: <span id="bitacora-nombre"></span></h4>
                        <div class="cliente-contacto">
                            <div class="contacto-item">
                                <span class="contacto-icon">📞</span>
                                <div>
                                    <strong>Teléfono GT:</strong>
                                    <span id="bitacora-telefono"></span>
                                </div>
                            </div>
                            <div class="contacto-item">
                                <span class="contacto-icon">📱</span>
                                <div>
                                    <strong>Teléfono USA:</strong>
                                    <span id="bitacora-telefono-usa"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulario para agregar nuevo seguimiento -->
                <div class="agregar-seguimiento">
                    <h4>➕ Agregar Nuevo Seguimiento</h4>
                    <form id="formAgregarSeguimiento">
                        <input type="hidden" id="seguimiento_registro_id">
                        <div class="form-group">
                            <label for="nuevo_comentario">Comentario de la llamada <span class="required">*</span></label>
                            <textarea 
                                id="nuevo_comentario" 
                                name="comentario" 
                                rows="3" 
                                required
                                maxlength="1000"
                                placeholder="Ej: No contestó, buzón de voz. Volver a llamar mañana."
                            ></textarea>
                            <small class="helper-text">Máximo 1000 caracteres</small>
                        </div>
                        <button type="submit" class="btn-save btn-block">Guardar Seguimiento</button>
                    </form>
                </div>

                <!-- Lista de seguimientos -->
                <div class="historial-seguimientos">
                    <h4>📝 Historial de Seguimientos</h4>
                    <div id="lista-seguimientos">
                        <p class="text-center text-muted">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery y DataTables - SIN RESPONSIVE -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    
    <script src="js/ver_registros.js"></script>
</body>
</html>