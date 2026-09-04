<?php
/**
 * KPI-Grid Template für Widgets
 *
 * @var array $items Array von Stat-Items
 * @var int $columns Anzahl der Spalten (2 oder 3)
 */
$columns = (int) ($columns ?? 2);
$col_class = $columns === 3 ? 'col-12 col-lg-4' : 'col-12 col-md-6';
?>
<div class="row g-2 widget-stat-grid">
    <?php foreach ($items as $item): ?>
        <div class="<?php echo esc_attr($col_class); ?>">
            <?php
            echo $this->render('stat-item', $item);
            ?>
        </div>
    <?php endforeach; ?>
</div>
