<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Search icon shortcode
add_shortcode( 'init_live_search', 'init_plugin_suite_live_search_shortcode' );
function init_plugin_suite_live_search_shortcode( $atts ) {
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

// Related Posts shortcode
add_shortcode( 'init_live_search_related_posts', 'init_plugin_suite_live_search_related_posts_shortcode' );
function init_plugin_suite_live_search_related_posts_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'id'        => get_the_ID(),
            'count'     => 5,
            'keyword'   => '',
            'css'       => '1',
            'schema'    => '1',
            'template'  => 'default',
            'post_type' => 'post',
        ],
        $atts,
        'init_live_search_related_posts'
    );

    $post_id = intval( $atts['id'] );
    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
        return '';
    }

    $keyword = trim( $atts['keyword'] );
    if ( $keyword === '' ) {
        $raw_title = get_the_title( $post_id );
        $decoded   = html_entity_decode( $raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $clean     = wp_strip_all_tags( $decoded );
        $keyword   = trim( preg_replace( '/[^\p{L}\p{N}\s]+/u', '', $clean ) );
    }

    $related_ids = init_plugin_suite_live_search_find_related_ids(
        $keyword,
        $post_id,
        intval( $atts['count'] ),
        sanitize_key( $atts['post_type'] )
    );

    return init_plugin_suite_live_search_render_related( $related_ids, $atts );
}

// AI Related Posts shortcode
add_shortcode( 'init_live_search_related_ai', 'init_plugin_suite_live_search_related_ai_shortcode' );

function init_plugin_suite_live_search_related_ai_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'id'        => get_the_ID(),
            'count'     => 5,
            'post_type' => 'post',
            'template'  => 'default',
            'css'       => '1',
            'schema'    => '1',
        ],
        $atts,
        'init_live_search_related_ai'
    );

    $post_id = intval( $atts['id'] );
    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
        return '';
    }

    // dùng AI scoring thay vì keyword
    $related_ids = init_plugin_suite_live_search_get_related_ai_ids(
        $post_id,
        intval( $atts['count'] ),
        sanitize_key( $atts['post_type'] )
    );

    return init_plugin_suite_live_search_render_related( $related_ids, $atts );
}

// Shortcode render
function init_plugin_suite_live_search_render_related( $related_ids, $atts ) {
    if ( empty( $related_ids ) ) return '';
    $wrapper_class = count($related_ids) >= 10 ? 'ils-related-list ils-related--columns' : 'ils-related-list';

    if ( $atts['css'] !== '0' ) {
        wp_enqueue_style(
            'init-live-search-related-posts',
            INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/related-posts.css',
            [],
            INIT_PLUGIN_SUITE_LS_VERSION
        );
    }

    $template_slug = sanitize_key( $atts['template'] );
    $template_path = locate_template( "init-live-search/related-posts-{$template_slug}.php" );
    if ( ! $template_path ) {
        $template_path = INIT_PLUGIN_SUITE_LS_TEMPLATES_PATH . "related-posts-{$template_slug}.php";
        if ( ! file_exists( $template_path ) ) {
            $template_path = INIT_PLUGIN_SUITE_LS_TEMPLATES_PATH . 'related-posts-default.php';
        }
    }

    ob_start();
    include $template_path;
    return ob_get_clean();
}

// Shortcode Builder
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( $hook !== 'settings_page_init-live-search-settings' ) {
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
                'related_posts'       => __( 'Related Posts', 'init-live-search' ),
                'post_id'             => __( 'Post ID (optional)', 'init-live-search' ),
                'post_count'          => __( 'Number of Posts', 'init-live-search' ),
                'post_type'           => __( 'Post Type(s)', 'init-live-search' ),
                'keyword_override'    => __( 'Keyword (override)', 'init-live-search' ),
                'template'            => __( 'Template', 'init-live-search' ),
                'load_css'            => __( 'Load CSS', 'init-live-search' ),
                'output_schema'       => __( 'Output Schema', 'init-live-search' ),
            ],
        ]
    );

    wp_enqueue_script(
        'init-shortcode-panels',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/shortcodes.js',
        [ 'init-shortcode-builder' ],
        INIT_PLUGIN_SUITE_LS_VERSION,
        true
    );
} );
