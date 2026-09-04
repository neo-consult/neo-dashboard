<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="card shadow-sm" aria-labelledby="neo-dashboard-not-found-title">
    <div class="card-body text-center p-5">
        <i class="bi bi-signpost-split text-muted d-block mb-3" style="font-size: 3rem" aria-hidden="true"></i>
        <h1 id="neo-dashboard-not-found-title" class="h3 mb-3">
            <?php esc_html_e('Bereich nicht gefunden', 'neo-dashboard-core'); ?>
        </h1>
        <p class="text-muted mb-4">
            <?php esc_html_e('Der aufgerufene Dashboard-Bereich existiert nicht oder steht Ihnen nicht zur Verfügung.', 'neo-dashboard-core'); ?>
        </p>
        <a class="btn btn-primary" href="<?php echo esc_url(home_url('/neo-dashboard/')); ?>">
            <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>
            <?php esc_html_e('Zum Dashboard', 'neo-dashboard-core'); ?>
        </a>
    </div>
</section>
