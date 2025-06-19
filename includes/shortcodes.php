<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'init_live_search', 'init_live_search_shortcode' );
function init_live_search_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'type'          => 'icon', // icon | input
            'placeholder'   => 'Search...',
            'label'         => '',
            'class'         => '',
            'stroke_width'  => '1', // icon stroke-width
            'radius'        => '9999px', // input border-radius
        ],
        $atts,
        'init_live_search'
    );

    $form_style = '';
    if ( 'input' === $atts['type'] && trim( $atts['radius'] ) !== '9999px' ) {
        $form_style = 'style="border-radius: ' . esc_attr( $atts['radius'] ) . ';"';
    }

    ob_start();

    if ( 'input' === $atts['type'] ) {
        ?>
        <form class="ils-input-launch <?php echo esc_attr( $atts['class'] ); ?>" role="search" <?php echo esc_attr($form_style); ?>>
            <input
                name="ils"
                type="search"
                placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                autocomplete="off"
            />
            <button type="submit" tabindex="-1" aria-label="Search">
                <svg viewBox="0 0 24 24" width="20" height="20">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?php echo esc_attr( $atts['stroke_width'] ); ?>" fill="none"></circle>
                    <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?php echo esc_attr( $atts['stroke_width'] ); ?>"></line>
                </svg>
            </button>
        </form>
        <?php
    } else {
        ?>
        <a href="#init-live-search" class="ils-icon-launch <?php echo esc_attr( $atts['class'] ); ?>" aria-label="<?php echo esc_attr( $atts['label'] ? $atts['label'] : 'Open Search' ); ?>">
            <svg viewBox="0 0 24 24" width="20" height="20">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?php echo esc_attr( $atts['stroke_width'] ); ?>" fill="none"></circle>
                <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?php echo esc_attr( $atts['stroke_width'] ); ?>"></line>
            </svg>
            <?php if ( $atts['label'] ) : ?>
                <span class="ils-icon-label"><?php echo esc_html( $atts['label'] ); ?></span>
            <?php endif; ?>
        </a>
        <?php
    }

    return ob_get_clean();
}

add_action( 'admin_enqueue_scripts', function () {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    wp_enqueue_script(
        'init-shortcode-builder',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/init-shortcode-builder.js',
        [],
        INIT_PLUGIN_SUITE_LS_VERSION,
        true
    );

    wp_localize_script(
        'init-shortcode-builder',
        'InitShortcodeBuilder',
        [
            'i18n' => [
                'copy'                => __( 'Copy', 'init-live-search' ),
                'copied'              => __( 'Copied!', 'init-live-search' ),
                'close'               => __( 'Close', 'init-live-search' ),
                'shortcode_preview'   => __( 'Shortcode Preview', 'init-live-search' ),
                'shortcode_builder'   => __( 'Shortcode Builder', 'init-live-search' ),
                'init_live_search'    => __( 'Init Live Search', 'init-live-search' ),
                'type'                => __( 'Type', 'init-live-search' ),
                'placeholder'         => __( 'Placeholder (input mode)', 'init-live-search' ),
                'placeholder_default' => __( 'Search...', 'init-live-search' ),
                'label'               => __( 'Label (icon mode)', 'init-live-search' ),
                'custom_class'        => __( 'Custom CSS class', 'init-live-search' ),
                'stroke_width'        => __( 'Stroke Width', 'init-live-search' ),
                'radius'              => __( 'Border Radius (input mode)', 'init-live-search' ),
            ],
        ]
    );
} );
