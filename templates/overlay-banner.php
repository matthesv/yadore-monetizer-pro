<?php
if (!get_option('yadore_overlay_enabled', true) || is_admin()) {
    return;
}
?>

<div id="yadore-overlay-banner" style="display: none;">
    <div id="yadore-overlay-backdrop"></div>
    <div id="yadore-overlay-content">
        <div class="overlay-header">
            <h3>Empfehlung</h3>
            <button id="yadore-overlay-close" aria-label="Close">&times;</button>
        </div>

        <div class="overlay-body">
            <div class="overlay-loading">
                <div class="loading-spinner"></div>
                <p>Empfehlungen werden geladen...</p>
                <?php if (get_option('yadore_ai_enabled', false)): ?>
                    <small>KI analysiert den Inhalt für die beste Empfehlung</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>