<?php

declare(strict_types=1);

namespace NeoDashboard\Core\Asset;

final class WordPressDashboardClientEnvironment implements DashboardClientEnvironment
{
    public function restUrl(): string { return rest_url('neo-dashboard/v1'); }
    public function ajaxUrl(): string { return admin_url('admin-ajax.php'); }
    public function restNonce(): string { return wp_create_nonce('wp_rest'); }
    public function widgetNonce(): string { return wp_create_nonce('neo_dashboard_widget'); }

    public function strings(): array
    {
        return [
            'confirm_title' => __('Bestätigung', 'neo-dashboard-core'),
            'confirm_button' => __('Bestätigen', 'neo-dashboard-core'),
            'cancel_button' => __('Abbrechen', 'neo-dashboard-core'),
            'close_button' => __('Schließen', 'neo-dashboard-core'),
            'toast_success_title' => __('Erfolgreich', 'neo-dashboard-core'),
            'toast_error_title' => __('Fehler', 'neo-dashboard-core'),
            'toast_warning_title' => __('Warnung', 'neo-dashboard-core'),
            'toast_info_title' => __('Information', 'neo-dashboard-core'),
            'toast_close_label' => __('Schließen', 'neo-dashboard-core'),
            'widget_load_error' => __('Fehler beim Laden des Widgets.', 'neo-dashboard-core'),
            'loading' => __('Lädt…', 'neo-dashboard-core'),
        ];
    }
}
