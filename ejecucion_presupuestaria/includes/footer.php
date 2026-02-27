</main>

<!-- Footer -->
<footer
    style="background: var(--primary-dark); color: var(--text-light); padding: 1.5rem 2rem; text-align: center; margin-top: 2rem;">
    <p style="margin-bottom: 0.5rem;">
        <strong>Sistema de Ejecución Presupuestaria</strong> -
        Ministerio de Agricultura, Ganadería y Alimentación (MAGA)
    </p>
    <p style="font-size: 0.875rem; opacity: 0.8;">
        © <?= date('Y') ?> - Gobierno de Guatemala |
        Versión <?= APP_VERSION ?>
    </p>
</footer>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Scripts -->
<script src="assets/js/app.js"></script>

<script>
    // Toggle tema oscuro/claro
    function toggleTheme() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        const icon = document.querySelector('[onclick="toggleTheme()"] i');
        if (icon) {
            icon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    // Toggle menú móvil
    function toggleMobileMenu() {
        const navContainer = document.querySelector('.nav-container');
        const menuToggle = document.querySelector('.menu-toggle');

        if (navContainer && menuToggle) {
            navContainer.classList.toggle('open');
            menuToggle.classList.toggle('active');

            // Cambiar icono
            const icon = menuToggle.querySelector('i');
            if (navContainer.classList.contains('open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-bars';
            }
        }
    }

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', (e) => {
        const navContainer = document.querySelector('.nav-container');
        const menuToggle = document.querySelector('.menu-toggle');

        if (navContainer && navContainer.classList.contains('open')) {
            if (!navContainer.contains(e.target) && !menuToggle.contains(e.target)) {
                toggleMobileMenu();
            }
        }
    });

    // Cerrar menú al cambiar tamaño de ventana
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            const navContainer = document.querySelector('.nav-container');
            const menuToggle = document.querySelector('.menu-toggle');
            if (navContainer) navContainer.classList.remove('open');
            if (menuToggle) {
                menuToggle.classList.remove('active');
                const icon = menuToggle.querySelector('i');
                if (icon) icon.className = 'fas fa-bars';
            }
        }
    });

    // Aplicar tema guardado
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            const icon = document.querySelector('[onclick="toggleTheme()"] i');
            if (icon) {
                icon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
    });
</script>


<?php if (isset($extraScripts)): ?>
    <?= $extraScripts ?>
<?php endif; ?>
</body>

</html>