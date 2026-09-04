<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<nav class="navbar sticky-top shadow" id="neo-navbar">
    <div class="container-fluid">
        <a class="navbar-brand me-0 d-flex align-items-center" href="<?php echo esc_url( home_url( '/neo-dashboard/' ) ); ?>">
            <?php 
            $base = plugin_dir_url(NEO_DASHBOARD_PLUGIN_FILE);
            $logo_exists = file_exists(plugin_dir_path(NEO_DASHBOARD_PLUGIN_FILE) . 'assets/images/logo.png');
            if ($logo_exists): 
            ?>
                <img src="<?php echo $base; ?>assets/images/logo.png" alt="Neo Dashboard" height="32" class="me-2">
            <?php endif; ?>
            <span>Neo Dashboard</span>
        </a>
        <div class="d-flex align-items-center ms-auto">
            <!-- Sprachauswahl -->
            <div class="dropdown me-2">
                <button id="language-toggle-navbar" 
                        class="btn btn-outline-secondary" 
                        type="button"
                        data-bs-toggle="dropdown"
                        data-bs-auto-close="true"
                        aria-expanded="false"
                        data-tooltip-title="<?php echo esc_attr__('Sprache auswählen', 'neo-dashboard-core'); ?>"
                        data-tooltip-placement="bottom">
                    <span id="language-flag">🇩🇪</span>
                    <span id="language-code" class="d-none d-md-inline ms-1">DE</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="language-dropdown">
                    <!-- Wird dynamisch gefüllt -->
                </ul>
            </div>
            
            <!-- Theme-Toggle -->
            <button id="theme-toggle-navbar" 
                    class="btn btn-outline-secondary me-2" 
                    data-bs-toggle="tooltip"
                    data-bs-placement="bottom"
                    data-bs-title="<?php echo esc_attr__('Theme wechseln (Hell/Dunkel)', 'neo-dashboard-core'); ?>">
                🌙
            </button>
            <button class="navbar-toggler d-md-none border-0 ms-2" 
                    type="button"
                    data-bs-toggle="offcanvas" 
                    data-bs-target="#sidebarOffcanvas"
                    data-tooltip-title="<?php echo esc_attr__('Menü öffnen', 'neo-dashboard-core'); ?>"
                    data-tooltip-placement="bottom"
                    aria-label="<?php echo esc_attr__('Menü öffnen', 'neo-dashboard-core'); ?>">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="d-none d-md-block">
                <?php echo $user_menu_html; ?>
            </div>
        </div>
    </div>
</nav>
