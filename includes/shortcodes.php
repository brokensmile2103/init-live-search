<?php
if (!defined('ABSPATH')) exit;

add_shortcode('init_live_search', 'init_live_search_shortcode');
function init_live_search_shortcode($atts) {
    $atts = shortcode_atts([
        'type' => 'icon', // icon | input
        'placeholder' => 'Search...',
        'label' => '',
        'class' => '',
        'stroke_width' => '1', // icon stroke-width
        'radius' => '9999px', // input border-radius
    ], $atts, 'init_live_search');

    $form_style = '';
    if ($atts['type'] === 'input' && trim($atts['radius']) !== '9999px') {
        $form_style = 'style="border-radius: ' . esc_attr($atts['radius']) . ';"';
    }

    ob_start();

    if ($atts['type'] === 'input') {
        ?>
        <form class="ils-input-launch <?= esc_attr($atts['class']) ?>" role="search" <?= $form_style ?>>
            <input
                name="ils"
                type="search"
                placeholder="<?= esc_attr($atts['placeholder']) ?>"
                autocomplete="off"
            />
            <button type="submit" tabindex="-1" aria-label="Search">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?= esc_attr($atts['stroke_width']) ?>" fill="none"></circle>
                    <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?= esc_attr($atts['stroke_width']) ?>"></line>
                </svg>
            </button>
        </form>
        <?php
    } else {
        ?>
        <a href="#init-live-search" class="ils-icon-launch <?= esc_attr($atts['class']) ?>" aria-label="<?= esc_attr($atts['label'] ?: 'Open Search') ?>">
            <svg viewBox="0 0 24 24" width="20" height="20">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?= esc_attr($atts['stroke_width']) ?>" fill="none"></circle>
                <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?= esc_attr($atts['stroke_width']) ?>"></line>
            </svg>
            <?php if ($atts['label']) : ?>
                <span class="ils-icon-label"><?= esc_html($atts['label']) ?></span>
            <?php endif; ?>
        </a>
        <?php
    }

    return ob_get_clean();
}
