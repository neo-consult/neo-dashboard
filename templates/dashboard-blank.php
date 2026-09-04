<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title id="neo-dashboard-page-title"><?php 
        // Titel wird über den registrierten Dokumenttitel-Adapter ermittelt.
        $page_title = apply_filters('neo_dashboard_page_title', '');
        
        echo esc_html($page_title);
    ?></title>

    <?php
    // In Standalone-Lösung verwenden wir nur unsere eigenen Actions
    // wp_head() wird nicht benötigt, da Assets über neo_dashboard_head ausgegeben werden
    do_action( 'neo_dashboard_head' );
    ?>
</head>
<body <?php body_class( 'neo-dashboard-standalone' ); ?>>

    <?php
    do_action( 'neo_dashboard_body_start' );
    ?>

    <?php
    do_action( 'neo_dashboard_body_content' );
    ?>

    <?php
    do_action( 'neo_dashboard_body_end' );
    ?>

    <?php
    // In Standalone-Lösung verwenden wir nur unsere eigenen Actions
    // wp_footer() wird nicht benötigt, da Assets über neo_dashboard_footer ausgegeben werden
    do_action( 'neo_dashboard_footer' );
    ?>
</body>
</html>
