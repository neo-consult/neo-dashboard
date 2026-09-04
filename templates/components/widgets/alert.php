<?php
/**
 * Alert Template für Widgets
 * 
 * @var string $type
 * @var string $icon
 * @var string $message
 */
?>
<div class="alert alert-<?php echo esc_attr($type); ?> alert-sm py-2 mb-0" role="alert">
    <div class="d-flex align-items-center gap-2">
        <?php if ($icon): ?>
            <i class="<?php echo esc_attr($icon); ?>"></i>
        <?php endif; ?>
        <span class="small mb-0"><?php echo esc_html($message); ?></span>
    </div>
</div>

