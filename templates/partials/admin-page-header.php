<?php
/**
 * Modernized admin page header.
 *
 * Expected $page_header array structure:
 * - slug: optional identifier for CSS hooks
 * - eyebrow: optional small text label
 * - title: required heading text
 * - icon: optional dashicon class
 * - subtitle: optional descriptive copy
 * - version: optional version string without leading "v"
 * - actions: array of action definitions with label, url, icon, type, attrs
 * - meta: array of status summaries with label, value/value_html, description/description_html, icon, state
 *
 * @package YadoreMonetizerPro
 */

if (!isset($page_header) || !is_array($page_header)) {
    return;
}

$slug = isset($page_header['slug']) ? sanitize_html_class($page_header['slug']) : '';
$eyebrow = isset($page_header['eyebrow']) ? $page_header['eyebrow'] : '';
$title = isset($page_header['title']) ? $page_header['title'] : '';
$icon = isset($page_header['icon']) ? $page_header['icon'] : '';
$subtitle = isset($page_header['subtitle']) ? $page_header['subtitle'] : '';
$version = isset($page_header['version']) ? $page_header['version'] : '';
$actions = isset($page_header['actions']) && is_array($page_header['actions']) ? $page_header['actions'] : array();
$meta_items = isset($page_header['meta']) && is_array($page_header['meta']) ? $page_header['meta'] : array();
$show_surface = empty($page_header['bare']);
?>
<header class="yadore-hero<?php echo $show_surface ? '' : ' yadore-hero--flat'; ?>"<?php echo $slug ? ' data-page="' . esc_attr($slug) . '"' : ''; ?>>
    <div class="yadore-hero__primary">
        <?php if (!empty($eyebrow)) : ?>
            <p class="yadore-hero__eyebrow"><?php echo esc_html($eyebrow); ?></p>
        <?php endif; ?>

        <div class="yadore-hero__headline">
            <?php if (!empty($icon)) : ?>
                <span class="yadore-hero__icon dashicons <?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
            <?php endif; ?>
            <?php if (!empty($title)) : ?>
                <h1 class="yadore-hero__title">
                    <?php echo esc_html($title); ?>
                    <?php if (!empty($version)) : ?>
                        <span class="yadore-hero__badge">v<?php echo esc_html($version); ?></span>
                    <?php endif; ?>
                </h1>
            <?php endif; ?>
        </div>

        <?php if (!empty($subtitle)) : ?>
            <p class="yadore-hero__lead"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>

        <?php if (!empty($actions)) : ?>
            <div class="yadore-hero__actions" role="group" aria-label="<?php esc_attr_e('Schnellaktionen', 'yadore-monetizer'); ?>">
                <?php foreach ($actions as $action) :
                    if (empty($action['label'])) {
                        continue;
                    }

                    $action_label = $action['label'];
                    $action_icon = isset($action['icon']) ? $action['icon'] : '';
                    $action_type = isset($action['type']) ? $action['type'] : 'primary';
                    $action_url = isset($action['url']) ? $action['url'] : '';
                    $action_tag = (!empty($action['tag']) && in_array($action['tag'], array('button', 'a'), true)) ? $action['tag'] : ($action_url ? 'a' : 'button');
                    $action_attrs = isset($action['attrs']) && is_array($action['attrs']) ? $action['attrs'] : array();
                    $classes = array('button');

                    switch ($action_type) {
                        case 'secondary':
                            $classes[] = 'button-secondary';
                            break;
                        case 'ghost':
                            $classes[] = 'button-ghost';
                            break;
                        case 'link':
                            $classes[] = 'button-link';
                            break;
                        default:
                            $classes[] = 'button-primary';
                            break;
                    }

                    $classes[] = 'yadore-hero__action';
                    $attribute_string = '';

                    if (!empty($action_url)) {
                        $action_attrs['href'] = $action_url;
                    }

                    if (!empty($action['target'])) {
                        $action_attrs['target'] = $action['target'];
                    }

                    if (!empty($action['id'])) {
                        $action_attrs['id'] = $action['id'];
                    }

                    if (!empty($action['rel'])) {
                        $action_attrs['rel'] = $action['rel'];
                    }

                    $action_attrs['class'] = implode(' ', array_map('sanitize_html_class', $classes));

                    foreach ($action_attrs as $attr_key => $attr_value) {
                        if ($attr_value === '') {
                            continue;
                        }

                        $attribute_string .= sprintf(' %1$s="%2$s"', esc_attr($attr_key), esc_attr($attr_value));
                    }
                    ?>
                    <<?php echo $action_tag; ?><?php echo $attribute_string; ?>>
                        <?php if (!empty($action_icon)) : ?>
                            <span class="dashicons <?php echo esc_attr($action_icon); ?>" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($action_label); ?></span>
                    </<?php echo $action_tag; ?>>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($meta_items)) : ?>
        <div class="yadore-hero__meta" role="list">
            <?php foreach ($meta_items as $meta) :
                if (empty($meta['label']) && empty($meta['value']) && empty($meta['value_html'])) {
                    continue;
                }

                $meta_icon = isset($meta['icon']) ? $meta['icon'] : '';
                $meta_state = isset($meta['state']) ? ' meta-state-' . sanitize_html_class($meta['state']) : '';
                $meta_value = isset($meta['value']) ? esc_html($meta['value']) : '';
                $meta_value_html = isset($meta['value_html']) ? wp_kses($meta['value_html'], array(
                    'span' => array('class' => array(), 'id' => array(), 'aria-hidden' => array(), 'role' => array()),
                    'strong' => array('class' => array()),
                )) : '';
                $meta_description = isset($meta['description']) ? esc_html($meta['description']) : '';
                $meta_description_html = isset($meta['description_html']) ? wp_kses_post($meta['description_html']) : '';
                ?>
                <div class="yadore-hero__meta-card<?php echo $meta_state; ?>" role="listitem">
                    <?php if (!empty($meta_icon)) : ?>
                        <span class="yadore-hero__meta-icon dashicons <?php echo esc_attr($meta_icon); ?>" aria-hidden="true"></span>
                    <?php endif; ?>
                    <div class="yadore-hero__meta-body">
                        <span class="yadore-hero__meta-label"><?php echo esc_html($meta['label']); ?></span>
                        <span class="yadore-hero__meta-value">
                            <?php echo $meta_value_html ? $meta_value_html : $meta_value; ?>
                        </span>
                        <?php if ($meta_description_html) : ?>
                            <span class="yadore-hero__meta-description"><?php echo $meta_description_html; ?></span>
                        <?php elseif ($meta_description) : ?>
                            <span class="yadore-hero__meta-description"><?php echo $meta_description; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
