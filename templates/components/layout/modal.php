<?php
if (!defined('ABSPATH')) exit;

$id = $args['id'] ?? 'modal-' . uniqid();
$title = $args['title'] ?? '';
$title_icon = $args['title_icon'] ?? '';
$content = $args['content'] ?? '';
$callback = $args['callback'] ?? null;
$size = $args['size'] ?? '';
$scrollable = $args['scrollable'] ?? false;
$backdrop = $args['backdrop'] ?? true;
$keyboard = $args['keyboard'] ?? true;
$footer_buttons = $args['footer_buttons'] ?? [];
$header_border = $args['header_border'] ?? false;
$footer_border = $args['footer_border'] ?? false;
$header_class = $args['header_class'] ?? '';
$body_class = $args['body_class'] ?? '';
$footer_class = $args['footer_class'] ?? '';
$close_aria_label = $args['close_aria_label'] ?? __('Schließen', 'neo-dashboard-core');

$size_class = $size ? 'modal-' . $size : '';
$scrollable_class = $scrollable ? 'modal-dialog-scrollable' : '';
$header_border_class = $header_border ? 'border-bottom' : '';
$footer_border_class = $footer_border ? 'border-top' : '';

$modal_attrs = [];
if (!$backdrop) $modal_attrs[] = 'data-bs-backdrop="static"';
if (!$keyboard) $modal_attrs[] = 'data-bs-keyboard="false"';
?>

<div class="modal fade" id="<?php echo esc_attr($id); ?>" tabindex="-1" <?php echo implode(' ', $modal_attrs); ?>>
    <div class="modal-dialog <?php echo esc_attr($size_class); ?> <?php echo esc_attr($scrollable_class); ?>">
        <div class="modal-content">
            <?php if ($title || $title_icon): ?>
            <div class="modal-header <?php echo esc_attr($header_border_class); ?> <?php echo esc_attr($header_class); ?>">
                <h5 class="modal-title <?php echo $title_icon ? 'd-flex align-items-center gap-2' : ''; ?>" id="<?php echo esc_attr($id); ?>Title">
                    <?php if ($title_icon): ?>
                        <i class="<?php echo esc_attr($title_icon); ?>"></i>
                    <?php endif; ?>
                    <?php if ($title): ?>
                        <span><?php echo esc_html($title); ?></span>
                    <?php endif; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo esc_attr($close_aria_label); ?>"></button>
            </div>
            <?php endif; ?>
            
            <div class="modal-body <?php echo esc_attr($body_class); ?>">
                <?php
                if (is_callable($callback)) {
                    call_user_func($callback);
                } else {
                    echo $content;
                }
                ?>
            </div>
            
            <?php if (!empty($footer_buttons)): ?>
            <div class="modal-footer <?php echo esc_attr($footer_border_class); ?> <?php echo esc_attr($footer_class); ?>">
                <?php foreach ($footer_buttons as $button): ?>
                    <button type="button" 
                            class="btn <?php echo esc_attr($button['class'] ?? 'btn-secondary'); ?> <?php echo esc_attr($button['size'] ?? ''); ?>"
                            <?php if (isset($button['dismiss']) && $button['dismiss']): ?>data-bs-dismiss="modal"<?php endif; ?>
                            <?php if (isset($button['onclick'])): ?>onclick="<?php echo esc_attr($button['onclick']); ?>"<?php endif; ?>
                            <?php if (isset($button['id'])): ?>id="<?php echo esc_attr($button['id']); ?>"<?php endif; ?>>
                        <?php if (isset($button['icon'])): ?>
                            <i class="<?php echo esc_attr($button['icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo esc_html($button['text'] ?? ''); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>