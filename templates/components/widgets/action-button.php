<?php
/**
 * Action-Button Template für Widgets
 * 
 * @var string $href
 * @var string $text
 * @var string|null $icon
 * @var string $class
 * @var string|null $id
 * @var string|null $style
 * @var string|null $onclick
 * @var string|null $title
 */
?>
<a href="<?php echo esc_url($href); ?>" 
   class="btn btn-sm <?php echo esc_attr($class); ?>"
   <?php if ($id): ?>id="<?php echo esc_attr($id); ?>"<?php endif; ?>
   <?php if ($style): ?>style="<?php echo esc_attr($style); ?>"<?php endif; ?>
   <?php if ($onclick): ?>onclick="<?php echo esc_attr($onclick); ?>"<?php endif; ?>
   <?php if (isset($data) && is_array($data)): ?>
       <?php foreach ($data as $data_key => $data_value): ?>
           data-<?php echo esc_attr((string) $data_key); ?>="<?php echo esc_attr((string) $data_value); ?>"
       <?php endforeach; ?>
   <?php endif; ?>
   <?php if ($title): ?>
       data-bs-toggle="tooltip"
       data-bs-placement="top"
       data-bs-title="<?php echo esc_attr($title); ?>"
   <?php endif; ?>>
    <?php if ($icon): ?>
        <i class="<?php echo esc_attr($icon); ?>"></i>
    <?php endif; ?>
    <?php echo esc_html($text); ?>
</a>

