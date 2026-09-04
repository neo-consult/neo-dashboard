<?php
/**
 * Action-Row Template für Widgets
 *
 * @var array $actions
 * @var string $layout
 * @var string $align
 */
$layout = $layout ?? 'inline';
$align = $align ?? 'start';
$align_class = $align === 'center' ? 'justify-content-center' : ($align === 'end' ? 'justify-content-end' : 'justify-content-start');
$row_class = $layout === 'grid'
    ? 'd-grid gap-2'
    : 'd-flex flex-wrap gap-2 ' . $align_class;
?>
<div class="widget-action-row <?php echo esc_attr($row_class); ?>">
    <?php foreach ($actions as $action): ?>
        <?php echo $this->render('action-button', $action); ?>
    <?php endforeach; ?>
</div>
