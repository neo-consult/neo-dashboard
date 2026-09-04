<?php
/**
 * Statistik-Item Template für Widgets
 * 
 * @var string $icon
 * @var string $label
 * @var int|string $value
 * @var string $value_color
 * @var array|null $action
 */
?>
<div class="widget-stat-item d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <?php if ($icon): ?>
            <span class="widget-stat-icon text-<?php echo esc_attr($icon_color ?? 'secondary'); ?>">
                <i class="<?php echo esc_attr($icon); ?>"></i>
            </span>
        <?php endif; ?>
        <span class="widget-stat-label small"><?php echo esc_html($label); ?></span>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill bg-<?php echo esc_attr($value_color); ?> widget-stat-value"><?php echo esc_html($value); ?></span>
        <?php if ($action && isset($action['href'])): ?>
            <?php $action_title = $action['title'] ?? $label; ?>
            <a href="<?php echo esc_url($action['href']); ?>" 
               class="btn btn-sm widget-stat-action <?php echo esc_attr($action['class'] ?? 'btn-outline-secondary'); ?>"
               <?php if ($action_title): ?>
                   data-bs-toggle="tooltip"
                   data-bs-placement="top"
                   data-bs-title="<?php echo esc_attr($action_title); ?>"
                   aria-label="<?php echo esc_attr($action_title); ?>"
               <?php endif; ?>>
                <?php if (isset($action['icon'])): ?>
                    <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                <?php endif; ?>
            </a>
        <?php endif; ?>
    </div>
</div>

