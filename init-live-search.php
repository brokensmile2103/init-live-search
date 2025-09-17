<?php
/**
 * Plugin Name: Init Live Search
 * Plugin URI: https://inithtml.com/plugin/init-live-search/
 * Description: A fast, lightweight, and extensible live search modal for WordPress. Built with Vanilla JS and powered by the REST API.
 * Version: 1.8.4
 * Author: Init HTML
 * Author URI: https://inithtml.com/
 * Text Domain: init-live-search
 * Domain Path: /languages
 * Requires at least: 5.2
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// Main Constants
define('INIT_PLUGIN_SUITE_LS_VERSION',                '1.8.4');
define('INIT_PLUGIN_SUITE_LS_SLUG',                   'init-live-search');
define('INIT_PLUGIN_SUITE_LS_GROUP_GENERAL',          'init_live_search_group_general');
define('INIT_PLUGIN_SUITE_LS_OPTION',                 'init_plugin_suite_live_search_settings');
define('INIT_PLUGIN_SUITE_LS_PREDEFINED_DICT_OPTION', 'init_live_search_predifined_dict');
define('INIT_PLUGIN_SUITE_LS_GROUP_SYNONYMS',         'init_live_search_group_synonyms');
define('INIT_PLUGIN_SUITE_LS_SYNONYM_OPTION',         'init_plugin_suite_live_search_custom_synonyms');
define('INIT_PLUGIN_SUITE_LS_NAMESPACE',              'initlise/v1');

define('INIT_PLUGIN_SUITE_LS_URL',            plugin_dir_url(__FILE__));
define('INIT_PLUGIN_SUITE_LS_PATH',           plugin_dir_path(__FILE__));
define('INIT_PLUGIN_SUITE_LS_ASSETS_URL',     INIT_PLUGIN_SUITE_LS_URL . 'assets/');
define('INIT_PLUGIN_SUITE_LS_ASSETS_PATH',    INIT_PLUGIN_SUITE_LS_PATH . 'assets/');
define('INIT_PLUGIN_SUITE_LS_TEMPLATES_PATH', INIT_PLUGIN_SUITE_LS_PATH . 'templates/');
define('INIT_PLUGIN_SUITE_LS_INCLUDES_PATH',  INIT_PLUGIN_SUITE_LS_PATH . 'includes/');

// Frontend Scripts & Styles
add_action('wp_enqueue_scripts', function () {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);

    $trigger = [
        'triple_click' => !isset($options['trigger_triple_click']) || $options['trigger_triple_click'],
        'ctrl_slash'   => !isset($options['trigger_ctrl_slash']) || $options['trigger_ctrl_slash'],
        'input_focus'  => !isset($options['trigger_input_focus']) || $options['trigger_input_focus'],
    ];

    // If all triggers are disabled, do not enqueue
    if (!$trigger['triple_click'] && !$trigger['ctrl_slash'] && !$trigger['input_focus']) {
        return;
    }

    $css_style = $options['css_style'] ?? 'default';

    // Check for theme override file
    $theme_custom_css = get_stylesheet_directory() . '/init-live-search/style.css';
    $theme_custom_url = get_stylesheet_directory_uri() . '/init-live-search/style.css';

    switch ($css_style) {
        case 'default':
            wp_enqueue_style(
                'init-plugin-suite-live-search-style',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;

        case 'full':
            wp_enqueue_style(
                'init-plugin-suite-live-search-style-full',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style-full.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;

        case 'topbar':
            wp_enqueue_style(
                'init-plugin-suite-live-search-style-topbar',
                INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style-topbar.css',
                [],
                INIT_PLUGIN_SUITE_LS_VERSION
            );
            break;

        case 'none':
        default:
            if (file_exists($theme_custom_css)) {
                wp_enqueue_style(
                    'init-plugin-suite-live-search-style-custom',
                    $theme_custom_url,
                    [],
                    filemtime($theme_custom_css)
                );
            }
            break;
    }

    // Enqueue JS
    wp_enqueue_script(
        'init-plugin-suite-live-search-script',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/script.js',
        [],
        INIT_PLUGIN_SUITE_LS_VERSION,
        true
    );

    // Debounce
    $debounce = !empty($options['debounce']) && is_numeric($options['debounce']) && $options['debounce'] > 0
        ? (int) $options['debounce']
        : 500;
    $debounce = apply_filters('init_plugin_suite_live_search_debounce', $debounce);

    // Suggested keywords
    $suggested = isset($options['suggested_keywords']) && is_string($options['suggested_keywords'])
        ? array_values(array_filter(array_map(fn($s) => trim(wp_strip_all_tags($s)), explode(',', $options['suggested_keywords']))))
        : [];

    // Localized strings for commands
    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $product_enabled = in_array('product', $post_types, true);

    $commands = [
        'recent'     => __('Show latest posts', 'init-live-search'),
    ];

    if (defined('INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION')) {
        $commands['popular']  = __('Show most viewed posts (all time)', 'init-live-search');
        $commands['trending'] = __('Show trending posts right now', 'init-live-search');
        $commands['day']      = __('Show most viewed posts today', 'init-live-search');
        $commands['week']     = __('Show most viewed posts this week', 'init-live-search');
        $commands['month']    = __('Show most viewed posts this month', 'init-live-search');
    }

    if (defined('INIT_PLUGIN_SUITE_RP_VERSION')) {
        $commands['read'] = __('Show posts you recently read (local only)', 'init-live-search');
    }

    $commands['related']   = __('Find posts related to the current page', 'init-live-search');
    $commands['fav']       = __('Show your favorited posts', 'init-live-search');
    $commands['fav_clear'] = __('Clear all favorite posts', 'init-live-search');

    if (!empty($options['post_types']) && in_array('product', $options['post_types'], true)) {
        $woo_commands = [
            'product'   => __('Search all products', 'init-live-search'),
            'on-sale'   => __('Show only products on sale', 'init-live-search'),
            'stock'     => __('Show only in-stock products', 'init-live-search'),
            'sku'       => __('Search product by SKU', 'init-live-search'),
            'brand'     => __('Filter products by brand slug', 'init-live-search'),
            'price'     => __('Filter products by price range', 'init-live-search'),
            'attribute' => __('Filter products by attribute (e.g. color)', 'init-live-search'),
            'variation' => __('Filter products by variation value', 'init-live-search'),
            'coupon'    => __('Show all available coupons', 'init-live-search'),
        ];
        $commands += $woo_commands;
    }

    $commands += [
        'random'        => __('Open a random post', 'init-live-search'),
        'category'      => __('Filter by category', 'init-live-search'),
        'tag'           => __('Filter by tag', 'init-live-search'),
        'categories'    => __('Show list of categories', 'init-live-search'),
        'tags'          => __('Show list of tags', 'init-live-search'),
        'date'          => __('Filter by date (Y, Y/m, or Y/m/d)', 'init-live-search'),
        'id'            => __('Go to post by ID', 'init-live-search'),
        'history'       => __('Show past search keywords', 'init-live-search'),
        'history_clear' => __('Clear past search keywords', 'init-live-search'),
        'clear'         => __('Clear local cache', 'init-live-search'),
        'reset'         => __('Reset search field', 'init-live-search'),
    ];

    $commands = apply_filters('init_plugin_suite_live_search_commands', $commands, $options);

    $enable_slash = !isset($options['enable_slash']) || $options['enable_slash'];

    $default_command = '';
    if ($enable_slash) {
        $raw_default_command = $options['default_command'] ?? 'none';

        if ($raw_default_command === 'default') {
            $default_command = '/recent';
        } elseif ($raw_default_command === 'related') {
            $default_command = '/related';
        } elseif ($raw_default_command === 'popular') {
            $default_command = '/popular';
        } elseif ($raw_default_command === 'trending') {
            $default_command = '/trending';
        } elseif ($raw_default_command === 'read') {
            $default_command = '/read';
        } elseif ($raw_default_command === 'auto') {
            if (is_single()) {
                $default_command = '/related';
            } elseif (is_category()) {
                $term = get_queried_object();
                $default_command = $term ? '/category ' . $term->slug : '/recent';
            } elseif (is_tag()) {
                $term = get_queried_object();
                $default_command = $term ? '/tag ' . $term->slug : '/recent';
            } elseif (is_search()) {
                $default_command = get_search_query();
            } elseif (function_exists('is_shop') && (is_shop() || is_product_category())) {
                $default_command = '/product';
            } else {
                $default_command = '/recent';
            }
        }
    }

    $use_cache = !isset($options['use_cache']) || $options['use_cache'];
    $enable_voice = !isset($options['enable_voice']) || $options['enable_voice'];

    $cross_sites = [];
    if (!empty($options['cross_sites'])) {
        foreach (explode("\n", $options['cross_sites']) as $line) {
            $line = trim($line);
            if (strpos($line, '|') !== false) {
                [$label, $url] = explode('|', $line, 2);
                $label = sanitize_text_field($label);
                $url = esc_url_raw(trim($url));
                if ($label && $url) {
                    $cross_sites[] = [
                        'label' => $label,
                        'url'   => rtrim($url, '/'),
                    ];
                }
            }
        }
    }

    // Final localization
    wp_localize_script('init-plugin-suite-live-search-script', 'InitPluginSuiteLiveSearch', [
        'api'                => esc_url_raw(rest_url(INIT_PLUGIN_SUITE_LS_NAMESPACE . '/search')),
        'debounce'           => $debounce,
        'nonce'              => wp_create_nonce('wp_rest'),
        'suggested'          => $suggested,
        'use_cache'          => $use_cache,
        'enable_voice'       => $enable_voice,
        'voice_auto_restart' => false,
        'voice_auto_stop'    => true,
        'max_select_word'    => (int) ($options['max_select_word'] ?? 8),
        'enable_slash'       => $enable_slash,
        'utm'                => isset($options['default_utm']) ? sanitize_text_field($options['default_utm']) : '',
        'trigger'            => $trigger,
        'post_id'            => is_singular() ? get_the_ID() : 0,
        'default_thumb'      => INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg',
        'search_page'        => home_url('/'),
        'i18n' => [
            'placeholder'           => __('Type to search...', 'init-live-search'),
            'no_results'            => __('No results found.', 'init-live-search'),
            'error'                 => __('Error loading results.', 'init-live-search'),
            'all'                   => __('All', 'init-live-search'),
            'cache_cleared'         => __('Cache cleared successfully.', 'init-live-search'),
            'fav_cleared'           => __('Favorite list has been cleared.', 'init-live-search'),
            'cache_failed'          => __('Failed to clear cache.', 'init-live-search'),
            'quick_search'          => __('Quick search', 'init-live-search'),
            'views'                 => __('views', 'init-live-search'),
            'on_sale'               => __('Sale', 'init-live-search'),
            'out_of_stock'          => __('Sold out', 'init-live-search'),
            'supported_commands'    => __('Supported Commands:', 'init-live-search'),
            'no_history'            => __('You haven\'t searched for anything yet.', 'init-live-search'),
            'history_cleared'       => __('Past search keywords have been cleared.', 'init-live-search'),
        ],
        'commands'          => $commands,
        'default_command'   => $enable_slash ? $default_command : '',
        'cross_sites'       => $cross_sites,
    ]);
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'init_plugin_suite_live_search_add_settings_link');
// Add a "Settings" link to the plugin row in the Plugins admin screen
function init_plugin_suite_live_search_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=init-live-search-settings') . '">' . __('Settings', 'init-live-search') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Includes
if (is_dir(INIT_PLUGIN_SUITE_LS_INCLUDES_PATH)) {
    // Load internal modules (utils first, then main logic)
    foreach (['search-core.php', 'related-ai.php', 'utils.php', 'predefined-dictionaries.php', 'rest-api.php', 'settings-page.php', 'tracking.php', 'shortcodes.php', 'hooks.php'] as $file) {
        $path = INIT_PLUGIN_SUITE_LS_INCLUDES_PATH . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }

    // Load admin-specific logic
    if (is_admin()) {
        $admin_path = INIT_PLUGIN_SUITE_LS_INCLUDES_PATH . 'ajax.php';
        if (file_exists($admin_path)) {
            require_once $admin_path;
        }
    }
}
