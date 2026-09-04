<?php
/**
 * Widget Header Component
 * 
 * Einheitliche Header-Struktur für alle Widgets
 * 
 * @var string $icon Icon-Klasse (z.B. 'bi-building')
 * @var string $label Widget-Titel
 * @var array|null $actions Array von Header-Actions (optional)
 * @var string|null $class Zusätzliche CSS-Klassen (optional)
 */
if (!defined('ABSPATH')) exit;

// Variablen aus dem Widget-Array übernehmen, falls nicht direkt gesetzt
// Unterstützt sowohl direkte Variablen als auch Variablen mit 'header_' Präfix
$icon = $header_icon ?? $icon ?? 'bi-grid';
$label = $header_label ?? $label ?? '';
// WICHTIG:
// - $header_actions kann bewusst NULL sein (=> keine Actions).
// - Mit "??" würde NULL fälschlich auf ein evtl. noch gesetztes $actions aus einem vorherigen include() zurückfallen,
//   weil include() Variablen im Scope beibehält (Leak).
$actions_input = $actions ?? null;
$actions = array_key_exists('header_actions', get_defined_vars()) ? $header_actions : $actions_input;
$class = $header_class ?? $class ?? '';
$slot = $header_slot ?? null;
$badge = $header_badge ?? null;
?>
<div class="card-header neo-widget-header fw-semibold d-flex align-items-center justify-content-between gap-2 flex-wrap <?php echo esc_attr($class); ?>">
    <div class="d-flex align-items-center gap-2">
        <?php if ($icon): ?>
            <i class="<?php echo esc_attr($icon); ?>"></i>
        <?php endif; ?>
        <?php if ($label): ?>
            <span><?php echo esc_html($label); ?></span>
        <?php endif; ?>
        <?php if ($badge !== null && $badge !== ''): ?>
            <?php echo $badge; ?>
        <?php endif; ?>
    </div>
    <?php if ($slot !== null && $slot !== '' || !empty($actions)): ?>
        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto flex-grow-1 flex-lg-grow-0">
            <?php if ($slot !== null && $slot !== ''): ?>
                <?php echo $slot; ?>
            <?php endif; ?>
            <?php if (!empty($actions)): ?>
            <?php foreach ($actions as $action): ?>
                <?php
                $action_type = $action['type'] ?? 'link';
                $action_class = $action['class'] ?? 'btn-outline-secondary btn-sm';
                $action_icon = $action['icon'] ?? null;
                $action_text = $action['text'] ?? '';
                $action_href = $action['href'] ?? '#';
                $action_onclick = $action['onclick'] ?? null;
                $action_title = $action['title'] ?? null;
                $action_target = $action['target'] ?? null;
                $action_data = isset($action['data']) && is_array($action['data']) ? $action['data'] : [];
                ?>
                <?php if ($action_type === 'button'): ?>
                    <button type="button" 
                            class="btn <?php echo esc_attr($action_class); ?>"
                            <?php if ($action_onclick): ?>onclick="<?php echo esc_attr($action_onclick); ?>"<?php endif; ?>
                            <?php foreach ($action_data as $k => $v): ?>
                                <?php if ($k !== '' && $v !== null): ?>
                                    data-<?php echo esc_attr((string)$k); ?>="<?php echo esc_attr((string)$v); ?>"
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($action_title): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr($action_title); ?>"<?php endif; ?>>
                        <?php if ($action_icon): ?>
                            <i class="<?php echo esc_attr($action_icon); ?>"></i>
                        <?php endif; ?>
                        <?php if ($action_text): ?>
                            <?php echo esc_html($action_text); ?>
                        <?php endif; ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo esc_url($action_href); ?>"
                       class="btn <?php echo esc_attr($action_class); ?>"
                       <?php if ($action_target): ?>target="<?php echo esc_attr($action_target); ?>" rel="noopener"<?php endif; ?>
                       <?php foreach ($action_data as $k => $v): ?>
                           <?php if ($k !== '' && $v !== null): ?>
                               data-<?php echo esc_attr((string)$k); ?>="<?php echo esc_attr((string)$v); ?>"
                           <?php endif; ?>
                       <?php endforeach; ?>
                       <?php if ($action_title): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="<?php echo esc_attr($action_title); ?>"<?php endif; ?>>
                        <?php if ($action_icon): ?>
                            <i class="<?php echo esc_attr($action_icon); ?>"></i>
                        <?php endif; ?>
                        <?php if ($action_text): ?>
                            <?php echo esc_html($action_text); ?>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

