<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Search icon/input shortcode (UPGRADED + PHPCS-safe)
add_shortcode( 'init_live_search', 'init_plugin_suite_live_search_shortcode' );
function init_plugin_suite_live_search_shortcode( $atts ) {
    $atts = shortcode_atts(
        [
            'type'         => 'icon',      // icon | input
            'placeholder'  => 'Search...',
            'label'        => '',
            'class'        => '',
            'id'           => '',
            'stroke_width' => '1',         // icon stroke-width
            'radius'       => '9999px',    // input border-radius
            // New QoL options:
            'width'        => '',          // e.g. 320px | 100% | 20rem
            'max_width'    => '',          // e.g. 480px
            'align'        => '',          // left | center | right
            'name'         => '',          // override input name (default launcher uses "ils")
            'aria_label'   => '',          // override aria-label
            'button'       => 'show',      // show | hide (input mode)
        ],
        $atts,
        'init_live_search'
    );

    // Sanitize lightweight (không đổi logic)
    $type         = $atts['type'] === 'input' ? 'input' : 'icon';
    $stroke_width = preg_replace( '/[^0-9.]/', '', $atts['stroke_width'] );
    $radius       = trim( $atts['radius'] );
    $placeholder  = $atts['placeholder'];
    $label        = $atts['label'];
    $name_attr    = trim( $atts['name'] ) !== '' ? $atts['name'] : 'ils';
    $aria_label   = trim( $atts['aria_label'] ) !== '' ? $atts['aria_label'] : ( $label ? $label : 'Open Search' );
    $show_button  = ( $atts['button'] !== 'hide' );

    // allow units: px, %, rem, em, vw, vh, ch
    $unit_ok   = '/^\s*\d+(\.\d+)?\s*(px|%|rem|em|vw|vh|ch)?\s*$/i';
    $width     = ( $atts['width']     !== '' && preg_match( $unit_ok, $atts['width'] ) )     ? $atts['width']     : '';
    $max_width = ( $atts['max_width'] !== '' && preg_match( $unit_ok, $atts['max_width'] ) ) ? $atts['max_width'] : '';

    $align = in_array( strtolower( $atts['align'] ), [ 'left', 'center', 'right' ], true ) ? strtolower( $atts['align'] ) : '';

    // Build style parts (PHPCS-safe: chỉ giữ mảng, in ra bằng esc_attr ngay tại chỗ)
    $wrap_style_parts = [];
    if ( $width     !== '' ) $wrap_style_parts[] = 'width:' . $width;
    if ( $max_width !== '' ) $wrap_style_parts[] = 'max-width:' . $max_width;
    if ( $type === 'input' && $radius !== '' && $radius !== '9999px' ) {
        $wrap_style_parts[] = 'border-radius:' . $radius;
    }
    if ( $align === 'center' ) {
        $wrap_style_parts[] = 'margin-left:auto';
        $wrap_style_parts[] = 'margin-right:auto';
    } elseif ( $align === 'right' ) {
        $wrap_style_parts[] = 'margin-left:auto';
    }

    // Compose class
    $base_class = $type === 'input' ? 'ils-input-launch' : 'ils-icon-launch';
    $classes    = trim( $base_class . ( $atts['class'] ? ' ' . $atts['class'] : '' ) );

    ob_start();

    if ( $type === 'input' ) : ?>
        <form
            <?php if ( $atts['id'] !== '' ) : ?>
                id="<?php echo esc_attr( $atts['id'] ); ?>"
            <?php endif; ?>
            class="<?php echo esc_attr( $classes ); ?>"
            role="search"
            <?php if ( ! empty( $wrap_style_parts ) ) : ?>
                style="<?php echo esc_attr( implode( ';', $wrap_style_parts ) ); ?>"
            <?php endif; ?>
        >
            <input
                name="<?php echo esc_attr( $name_attr ); ?>"
                type="search"
                placeholder="<?php echo esc_attr( $placeholder ); ?>"
                autocomplete="off"
            />
            <?php if ( $show_button ) : ?>
                <button type="submit" tabindex="-1" aria-label="<?php echo esc_attr( $aria_label ); ?>">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?php echo esc_attr( $stroke_width ); ?>" fill="none"></circle>
                        <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?php echo esc_attr( $stroke_width ); ?>"></line>
                    </svg>
                </button>
            <?php endif; ?>
        </form>
    <?php else : ?>
        <a
            <?php if ( $atts['id'] !== '' ) : ?>
                id="<?php echo esc_attr( $atts['id'] ); ?>"
            <?php endif; ?>
            href="#init-live-search"
            class="<?php echo esc_attr( $classes ); ?>"
            aria-label="<?php echo esc_attr( $aria_label ); ?>"
            <?php if ( ! empty( $wrap_style_parts ) ) : ?>
                style="<?php echo esc_attr( implode( ';', $wrap_style_parts ) ); ?>"
            <?php endif; ?>
        >
            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="<?php echo esc_attr( $stroke_width ); ?>" fill="none"></circle>
                <line x1="17" y1="17" x2="22" y2="22" stroke="currentColor" stroke-width="<?php echo esc_attr( $stroke_width ); ?>"></line>
            </svg>
            <?php if ( $label ) : ?>
                <span class="ils-icon-label"><?php echo esc_html( $label ); ?></span>
            <?php endif; ?>
        </a>
    <?php
    endif;

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
                'width'               => __( 'Width', 'init-live-search' ),
                'max_width'           => __( 'Max Width', 'init-live-search' ),
                'align'               => __( 'Align', 'init-live-search' ),
                'id_attr'             => __( 'Element ID', 'init-live-search' ),
                'aria_label'          => __( 'ARIA Label', 'init-live-search' ),
                'button_visibility'   => __( 'Search Button (input mode)', 'init-live-search' ),
                'input_name'          => __( 'Input name (input mode)', 'init-live-search' ),
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
