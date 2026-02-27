<!-- includes/footer.php -->
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.5/dist/sweetalert2.all.min.js"></script>
    
    <!-- JavaScript Base -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Navbar Admin JS (si es administrador) -->
    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
        <script src="<?php echo SITE_URL; ?>/assets/js/navbar_admin.js"></script>
    <?php endif; ?>
    
    <!-- JavaScript Adicionales -->
    <?php if (isset($extraJS)): ?>
        <?php foreach ($extraJS as $js): ?>
            <?php if (strpos($js, 'http://') === 0 || strpos($js, 'https://') === 0): ?>
                <!-- JS Externo (CDN) -->
                <script src="<?php echo $js; ?>"></script>
            <?php else: ?>
                <!-- JS Local -->
                <script src="<?php echo SITE_URL . $js; ?>"></script>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>