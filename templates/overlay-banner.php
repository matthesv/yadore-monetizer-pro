<?php
if (is_admin()) {
    return;
}

$overlay_context = isset($overlay_context) && is_array($overlay_context)
    ? $overlay_context
    : array();

$overlay_ai_enabled = !empty($overlay_context['ai_enabled']);
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
                <?php if ($overlay_ai_enabled): ?>
                    <small>KI analysiert den Inhalt für die beste Empfehlung</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>