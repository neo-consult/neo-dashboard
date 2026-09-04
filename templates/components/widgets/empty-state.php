<?php
/**
 * Empty-State Template für Widgets
 * 
 * @var string $icon
 * @var string $message
 * @var array|null $action
 */
?>
<div class="text-center py-4">
    <?php if ($icon): ?>
        <i class="<?php echo esc_attr($icon); ?> text-muted" style="font-size: 2rem;"></i>
    <?php endif; ?>
    <p class="text-muted small mb-3 mt-2"><?php echo esc_html($message); ?></p>
    <?php if ($action && isset($action['href'])): ?>
        <a href="<?php echo esc_url($action['href']); ?>" class="btn btn-sm btn-outline-primary">
            <?php if (isset($action['icon'])): ?>
                <i class="<?php echo esc_attr($action['icon']); ?>"></i>
            <?php endif; ?>
            <?php echo esc_html($action['text'] ?? 'Aktion'); ?>
        </a>
    <?php endif; ?>
</div>

