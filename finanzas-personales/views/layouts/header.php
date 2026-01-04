<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Finanzas Personales'; ?></title>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
</head>
<body>


    <!-- Overlay -->
    <div class="sidebar-overlay" id="overlay" onclick="closeMenu()"></div>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                
                <a href="categorias.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-tags"></i> Categorías
                </a>

                <a href="transacciones.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'transacciones.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-exchange-alt"></i> Transacciones
                </a>
                
                <a href="deudas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'deudas.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-file-invoice-dollar"></i> Deudas
                </a>
                
                <a href="flujo_caja.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'flujo_caja.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-chart-line"></i> Flujo de Caja
                </a>
                
                <a href="costos.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'costos.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-calculator"></i> Costos
                </a>
                
                <a href="divisas.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'divisas.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-coins"></i> Divisas
                </a>

                <a href="premium.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'premium.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-crown" style="color: gold;"></i> Premium
                </a>
                
                <hr>
                
                <a href="reportes.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : ''; ?>" onclick="closeMenu()">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
                

                
            <div class="sidebar-footer">
                <a href="logout.php" class="btn btn-danger btn-sm btn-block">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
            

        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header Global -->
            <header class="top-header">
                <div class="header-left">
                    <button class="menu-toggle" onclick="toggleMenu()" aria-label="Abrir menú">
                        <i class="fas fa-bars"></i>
                    </button>
                    <!-- Optional: Breadcrumb or current section name can go here -->
                </div>
                
                <div class="header-right">
                    <a href="premium.php" class="btn-premium-nav">
                        <i class="fas fa-crown"></i> <span>Upgrade</span>
                    </a>
                    
                    <div class="user-profile-dropdown" onclick="toggleUserDropdown(event)">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="user-name-head"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></span>
                        <i class="fas fa-chevron-down text-muted" style="font-size: 0.8em; margin-left: 5px;"></i>
                        
                        <!-- Dropdown Menu -->
                        <div class="dropdown-menu" id="userDropdown">
                            <a href="perfil.php" class="dropdown-item">
                                <i class="fas fa-user-circle"></i> Mi Perfil
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-wrapper">
            
    <script>
        function toggleUserDropdown(e) {
            e.stopPropagation();
            const menu = document.getElementById('userDropdown');
            menu.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(e) {
            const menu = document.getElementById('userDropdown');
            if (menu && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });

        // JS robusto para menú móvil (click, touch, teclado, resize)
        (function(){
            var btn = document.querySelector('.menu-toggle');
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('overlay');

            if (!btn || !sidebar || !overlay) {
                console.error('Menu móvil: elementos no encontrados', {btn: !!btn, sidebar: !!sidebar, overlay: !!overlay});
                return;
            }

            function openMenu() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                btn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            }
            function closeMenu() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
            function toggleMenu(e) {
                e && e.preventDefault && e.preventDefault();
                if (sidebar.classList.contains('active')) closeMenu(); else openMenu();
            }

            // Exponer globalmente para onclick inline
            window.toggleMenu = toggleMenu;
            window.closeMenu = closeMenu;

            // conexiones
            btn.addEventListener('click', toggleMenu, {passive:false});
            btn.addEventListener('touchstart', function(e){ e.preventDefault(); toggleMenu(); }, {passive:false});
            overlay.addEventListener('click', closeMenu);
            overlay.addEventListener('touchstart', closeMenu, {passive:true});

            // cerrar con ESC
            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && sidebar.classList.contains('active')) closeMenu();
            });

            // al cambiar el tamaño por encima de breakpoint, asegurar estado cerrado
            window.addEventListener('resize', function(){
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    btn.setAttribute('aria-expanded','false');
                    document.body.style.overflow = '';
                }
            });
        })();
    </script>