<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="row g-4">
    <?php foreach ( $widgets as $widget_id => $widget ) :
        $icon  = ! empty( $widget['icon'] )  ? esc_attr( $widget['icon'] )  : 'bi-grid';
        $label = ! empty( $widget['label'] ) ? esc_html( $widget['label'] ) : '';
        $cb    = $widget['callback'] ?? null;
        $header_actions = $widget['header_actions'] ?? null;
        $header_class = $widget['header_class'] ?? '';
        $size = $widget['size'] ?? 'sm';
        $col_class = $size === 'lg' ? 'col-12' : ($size === 'md' ? 'col-12 col-lg-6' : 'col-12 col-md-6 col-xl-4');
        $load_mode = $widget['load'] ?? 'sync';
        $cache_ttl = (int) ($widget['cache_ttl'] ?? 0);
        $cache_key = 'neo_dashboard_widget_' . sanitize_key((string) $widget_id) . '_' . get_current_user_id() . '_' . get_user_locale();
        $cached_html = null;
        if ($load_mode !== 'async' && $cache_ttl > 0) {
            $cached_html = get_transient($cache_key);
        }
    ?>
        <div class="<?php echo esc_attr($col_class); ?>">
            <div class="card h-100 shadow-sm">
                <?php
                // Verwende die einheitliche Widget-Header-Komponente
                // Variablen für die Header-Komponente setzen
                $header_icon = $icon;
                $header_label = $label;
                include NEO_DASHBOARD_TEMPLATE_PATH . 'components/widgets/header.php';
                ?>
                <div class="card-body">
                    <?php if ($load_mode === 'async') : ?>
                        <div class="neo-widget-async"
                             data-widget-id="<?php echo esc_attr($widget_id); ?>">
                            <?php
                            if (!empty($widget['skeleton'])) {
                                echo $widget['skeleton'];
                            } else {
                                echo '<div class="text-muted small">' . esc_html__('Lade Widget...', 'neo-dashboard-core') . '</div>';
                            }
                            ?>
                        </div>
                    <?php else : ?>
                        <?php
                        if (is_string($cached_html) && $cached_html !== '') {
                            echo $cached_html;
                        } elseif ( is_callable( $cb ) ) {
                            ob_start();
                            try {
                                call_user_func( $cb );
                            } catch (\Throwable $e) {
                                echo '<em>' . esc_html__('Fehler beim Rendern des Widgets.', 'neo-dashboard-core') . '</em>';
                            }
                            $html = (string) ob_get_clean();
                            if ($cache_ttl > 0) {
                                set_transient($cache_key, $html, $cache_ttl);
                            }
                            echo $html;
                        } else {
                            echo '<em>' . esc_html__('Kein Callback definiert.', 'neo-dashboard-core') . '</em>';
                        }
                        ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
