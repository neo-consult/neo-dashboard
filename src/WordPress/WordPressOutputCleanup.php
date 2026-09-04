<?php
declare(strict_types=1);

namespace NeoDashboard\Core\WordPress;

final class WordPressOutputCleanup
{
    public function registerHooks(): void
    {
        add_action('init', [$this, 'removeEmojiAssets']);
    }

    public function removeEmojiAssets(): void
    {
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
}
