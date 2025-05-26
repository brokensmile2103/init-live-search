<?php
/**
 * Plugin Name: Init Live Search
 * Plugin URI: https://inithtml.com/plugin/init-live-search/
 * Description: A fast, lightweight, and smart live search modal built with Vanilla JS and powered by the WordPress REST API.
 * Version: 1.5.3
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
define('INIT_PLUGIN_SUITE_LS_VERSION',        '1.5.3');
define('INIT_PLUGIN_SUITE_LS_SLUG',           'init-live-search');
define('INIT_PLUGIN_SUITE_LS_OPTION',         'init_plugin_suite_live_search_settings');
define('INIT_PLUGIN_SUITE_LS_NAMESPACE',      'initlise/v1');

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

    // Enqueue CSS
    if (!isset($options['enqueue_css']) || $options['enqueue_css']) {
        wp_enqueue_style(
            'init-plugin-suite-live-search-style',
            INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'css/style.css',
            [],
            INIT_PLUGIN_SUITE_LS_VERSION
        );
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
        'popular'    => __('Show popular posts (if available)', 'init-live-search'),
        'related'    => __('Find posts related to current page title', 'init-live-search'),
        'read'       => __('Show posts you recently read (local only)', 'init-live-search'),
        'fav'        => __('Show your favorited posts', 'init-live-search'),
        'fav_clear'  => __('Clear all favorite posts', 'init-live-search'),
    ];

    if (!empty($options['post_types']) && in_array('product', $options['post_types'], true)) {
        $woo_commands = [
            'product'  => __('Search all products', 'init-live-search'),
            'on-sale'  => __('Show only products on sale', 'init-live-search'),
            'stock'    => __('Show only in-stock products', 'init-live-search'),
            'sku'      => __('Search product by SKU', 'init-live-search'),
            'price'    => __('Filter products by price range', 'init-live-search'),
        ];
        $commands += $woo_commands;
    }

    $commands += [
        'random'     => __('Open a random post', 'init-live-search'),
        'category'   => __('Filter by category', 'init-live-search'),
        'tag'        => __('Filter by tag', 'init-live-search'),
        'categories' => __('Show list of categories', 'init-live-search'),
        'tags'       => __('Show list of tags', 'init-live-search'),
        'date'       => __('Filter by date (Y, Y/m, or Y/m/d)', 'init-live-search'),
        'id'         => __('Go to post by ID', 'init-live-search'),
        'clear'      => __('Clear local cache', 'init-live-search'),
        'reset'      => __('Reset search field', 'init-live-search'),
    ];

    $enable_slash = !isset($options['enable_slash']) || $options['enable_slash'];
    $use_cache = !isset($options['use_cache']) || $options['use_cache'];
    $enable_voice = !isset($options['enable_voice']) || $options['enable_voice'];

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
        'i18n' => [
            'placeholder'           => __('Type to search...', 'init-live-search'),
            'no_results'            => __('No results found.', 'init-live-search'),
            'error'                 => __('Error loading results.', 'init-live-search'),
            'all'                   => __('All', 'init-live-search'),
            'cache_cleared'         => __('Cache cleared successfully.', 'init-live-search'),
            'fav_cleared'           => __('Favorite list has been cleared.', 'init-live-search'),
            'cache_failed'          => __('Failed to clear cache.', 'init-live-search'),
            'quick_search'          => __('Quick search', 'init-live-search'),
            'on_sale'               => __('Sale', 'init-live-search'),
            'out_of_stock'          => __('Sold out', 'init-live-search'),
            'supported_commands'    => __('Supported Commands:', 'init-live-search'),
            'popular_not_supported' => __('Popular feature is not supported. Please install Init View Count plugin.', 'init-live-search'),
        ],
        'commands' => $commands,
    ]);
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'init_live_search_add_settings_link');

function init_live_search_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=init-live-search-settings') . '">' . __('Settings', 'init-live-search') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Includes
if (is_dir(INIT_PLUGIN_SUITE_LS_INCLUDES_PATH)) {
    // Load required logic: REST API and settings page
    foreach (['rest-api.php', 'settings-page.php'] as $file) {
        $path = INIT_PLUGIN_SUITE_LS_INCLUDES_PATH . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }

    if (is_admin()) {
        $admin_path = INIT_PLUGIN_SUITE_LS_INCLUDES_PATH . 'ajax.php';
        if (file_exists($admin_path)) {
            require_once $admin_path;
        }
    }
}
