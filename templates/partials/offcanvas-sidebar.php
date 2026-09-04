<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="offcanvas offcanvas-start d-md-none" id="sidebarOffcanvas" tabindex="-1">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title">Neo Dashboard</h5>
        <button type="button" 
                class="btn-close" 
                data-bs-dismiss="offcanvas" 
                data-bs-toggle="tooltip"
                data-bs-placement="left"
                data-bs-title="<?php echo esc_attr__('Menü schließen', 'neo-dashboard-core'); ?>"
                aria-label="<?php echo esc_attr__('Schließen', 'neo-dashboard-core'); ?>"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <ul class="nav nav-pills flex-column mb-0">
            <?php foreach ( $sidebar as $slug => $item ) :
                $is_active = ( $slug === $current_section ) ? ' active' : '';
                $has_children = ! empty( $item['children'] );
                $child_active = $has_children && isset( $item['children'][ $current_section ] );
                $show = $child_active ? ' show' : '';
            ?>
                <?php if ( ! empty( $item['is_group'] ) ) : ?>
                    <li class="nav-item">
                        <a class="nav-link<?php echo $is_active; ?> d-flex align-items-center justify-content-between"
                           data-bs-toggle="collapse"
                           href="#group-mobile-<?php echo esc_attr( $slug ); ?>"
                           data-tooltip-title="<?php echo esc_attr( $item['tooltip'] ?? sprintf(__('%s aufklappen/zuklappen', 'neo-dashboard-core'), $item['label']) ); ?>"
                           data-tooltip-placement="right"
                           aria-expanded="<?php echo $child_active ? 'true' : 'false'; ?>">
                            <span class="d-flex align-items-center gap-2">
                                <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                                <?php echo esc_html( $item['label'] ); ?>
                            </span>
                            <i class="bi bi-chevron-<?php echo $child_active ? 'down' : 'right'; ?> small"></i>
                        </a>
                        <ul class="collapse ps-3 list-unstyled<?php echo $show; ?>"
                            id="group-mobile-<?php echo esc_attr( $slug ); ?>">
                            <?php foreach ( $item['children'] as $child_slug => $child ) :
                                $child_cls = ( $child_slug === $current_section ) ? ' active' : '';
                            ?>
                                <li class="nav-item">
                                    <a href="<?php echo esc_url( home_url( $child['url'] ) ); ?>"
                                       class="nav-link<?php echo $child_cls; ?> d-flex align-items-center gap-2"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="right"
                                       data-bs-title="<?php echo esc_attr( $child['tooltip'] ?? sprintf(__('Zu %s', 'neo-dashboard-core'), $child['label']) ); ?>">
                                        <i class="<?php echo esc_attr( $child['icon'] ); ?>"></i>
                                        <?php echo esc_html( $child['label'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a href="<?php echo esc_url( home_url( $item['url'] ) ); ?>"
                           class="nav-link<?php echo $is_active; ?> d-flex align-items-center gap-2"
                           data-bs-toggle="tooltip"
                           data-bs-placement="right"
                           data-bs-title="<?php echo esc_attr( $item['tooltip'] ?? sprintf(__('Zu %s', 'neo-dashboard-core'), $item['label']) ); ?>">
                            <i class="<?php echo esc_attr( $item['icon'] ); ?>"></i>
                            <?php echo esc_html( $item['label'] ); ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
        <div class="border-top p-3">
            <?php echo $user_menu_html; ?>
        </div>
    </div>
</div>
