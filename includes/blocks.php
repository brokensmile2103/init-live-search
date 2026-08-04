<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================================
// Block Editor integration
// ----------------------------------------------------------------------------
// 3 block tương ứng 1-1 với 3 shortcode đã có: [init_live_search],
// [init_live_search_related_posts], [init_live_search_related_ai]. Mỗi block
// dùng "render" trong block.json (PHP, từ WP 6.1+) trỏ tới file render.php —
// file này chỉ gọi lại đúng hàm shortcode gốc, nên KHÔNG có logic hiển thị
// nào bị lặp lại/lệch so với shortcode.
//
// Phần JS (assets/js/blocks-editor.js) là vanilla JS thuần, không build step,
// dùng ServerSideRender để preview ngay trong Block Editor.
//
// Kiến trúc này đồng bộ với Init View Count (block.json + render.php + 1 file
// JS chung cho cả 3 block).
// ============================================================================

add_filter( 'block_categories_all', 'init_plugin_suite_live_search_block_category', 10, 2 );
/**
 * Thêm 1 category riêng trong block inserter cho gọn, thay vì rơi vào "Widgets".
 *
 * @param array                   $categories     Danh sách category hiện có.
 * @param WP_Block_Editor_Context $editor_context Context hiện tại của editor (không dùng tới,
 *                                                nhưng bắt buộc phải khai báo theo đúng chữ ký
 *                                                mà hook 'block_categories_all' truyền vào).
 * @return array
 */
// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
function init_plugin_suite_live_search_block_category( $categories, $editor_context ) {
    return array_merge(
        [
            [
                'slug'  => 'init-live-search',
                'title' => __( 'Init Live Search', 'init-live-search' ),
                'icon'  => 'search',
            ],
        ],
        $categories
    );
}

add_action( 'init', 'init_plugin_suite_live_search_register_style_handles', 5 );
/**
 * Đăng ký (không enqueue) các handle CSS front-end, để block.json của cả 3
 * block có thể tham chiếu qua "style" — WordPress sẽ tự enqueue đúng lúc,
 * đúng chỗ (cả trong Block Editor lẫn ngoài front-end) khi block thực sự
 * được dùng.
 *
 * Ưu tiên chạy trước (priority 5) hàm đăng ký block bên dưới.
 *
 * @return void
 */
function init_plugin_suite_live_search_register_style_handles() {
    $options   = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $css_style = $options['css_style'] ?? 'default';

    $theme_custom_css = get_stylesheet_directory() . '/init-live-search/style.css';
    $theme_custom_url = get_stylesheet_directory_uri() . '/init-live-search/style.css';

    switch ( $css_style ) {
        case 'full':
            wp_register_style(
                'init-plugin-suite-live-search-style',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style-full.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;

        case 'topbar':
            wp_register_style(
                'init-plugin-suite-live-search-style',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style-topbar.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;

        case 'none':
            // Theme override / no default styling — only register if the
            // theme actually ships a custom stylesheet (same condition the
            // frontend wp_enqueue_scripts callback uses).
            if ( file_exists( $theme_custom_css ) ) {
                wp_register_style(
                    'init-plugin-suite-live-search-style',
                    $theme_custom_url,
                    [],
                    filemtime( $theme_custom_css )
                );
            }
            break;

        case 'default':
        default:
            wp_register_style(
                'init-plugin-suite-live-search-style',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;
    }

    wp_register_style(
        'init-live-search-related-posts',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/related-posts.css',
        [],
        INIT_PLUGIN_SUITE_LS_VERSION
    );
}

add_action( 'init', 'init_plugin_suite_live_search_register_blocks', 10 );
/**
 * Đăng ký script cho Block Editor và 3 block type.
 *
 * @return void
 */
function init_plugin_suite_live_search_register_blocks() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    wp_register_script(
        'init-live-search-blocks-editor',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/blocks-editor.js',
        [
            'wp-blocks',
            'wp-element',
            'wp-block-editor',
            'wp-components',
            'wp-i18n',
            'wp-server-side-render',
        ],
        INIT_PLUGIN_SUITE_LS_VERSION,
        true
    );

    if ( function_exists( 'wp_set_script_translations' ) ) {
        wp_set_script_translations(
            'init-live-search-blocks-editor',
            'init-live-search',
            INIT_PLUGIN_SUITE_LS_PATH . 'languages'
        );
    }

    register_block_type( INIT_PLUGIN_SUITE_LS_PATH . 'blocks/search-box' );
    register_block_type( INIT_PLUGIN_SUITE_LS_PATH . 'blocks/related-posts' );
    register_block_type( INIT_PLUGIN_SUITE_LS_PATH . 'blocks/related-ai' );
}
