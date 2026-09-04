<?php
/**
 * Liste Template für Widgets
 * 
 * @var array $items Array von List-Items mit: icon, text, href, badge, buttons
 * @var bool $flush
 */
?>
<div class="list-group <?php echo $flush ? 'list-group-flush' : ''; ?>">
    <?php foreach ($items as $item): ?>
        <div class="list-group-item px-0 py-2 border-bottom">
            <?php if (isset($item['icon']) && $item['icon']): ?>
                <i class="<?php echo esc_attr($item['icon']); ?> me-2"></i>
            <?php endif; ?>
            <?php if (isset($item['href']) && $item['href']): ?>
                <a href="<?php echo esc_url($item['href']); ?>" class="text-decoration-none">
                    <?php echo $item['text'] ?? ''; ?>
                </a>
            <?php else: ?>
                <?php echo $item['text'] ?? ''; ?>
            <?php endif; ?>
            <?php if (isset($item['badge'])): ?>
                <span class="badge bg-secondary ms-2"><?php echo esc_html($item['badge']); ?></span>
            <?php endif; ?>
            <?php if (isset($item['buttons']) && is_array($item['buttons'])): ?>
                <div class="mt-2">
                    <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ($item['buttons'] as $btn): ?>
                            <button type="button" 
                                    class="btn btn-sm <?php echo esc_attr($btn['class'] ?? 'btn-outline-primary'); ?>"
                                    <?php if (isset($btn['onclick'])): ?>onclick="<?php echo esc_attr($btn['onclick']); ?>"<?php endif; ?>
                                    <?php if (isset($btn['data']) && is_array($btn['data'])): ?>
                                        <?php foreach ($btn['data'] as $data_key => $data_value): ?>
                                            data-<?php echo esc_attr((string) $data_key); ?>="<?php echo esc_attr((string) $data_value); ?>"
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (isset($btn['title'])): ?>
                                        title="<?php echo esc_attr($btn['title']); ?>"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-title="<?php echo esc_attr($btn['title']); ?>"
                                        aria-label="<?php echo esc_attr($btn['title']); ?>"
                                    <?php endif; ?>>
                                <?php if (isset($btn['icon'])): ?>
                                    <i class="<?php echo esc_attr($btn['icon']); ?>"></i>
                                <?php endif; ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

