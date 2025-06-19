<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Xác định tab đang chọn
$current_tab = 'general';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only reading $_GET['tab'] to load admin UI tab, no action performed
if (isset($_GET['tab'])) {
    $current_tab = sanitize_key(wp_unslash($_GET['tab']));
}

// Register các setting group riêng biệt
add_action('admin_init', function () {
    register_setting(
        INIT_PLUGIN_SUITE_LS_GROUP_GENERAL,
        INIT_PLUGIN_SUITE_LS_OPTION,
        'init_plugin_suite_live_search_sanitize_settings'
    );

    register_setting(
        INIT_PLUGIN_SUITE_LS_GROUP_SYNONYMS,
        INIT_PLUGIN_SUITE_LS_SYNONYM_OPTION,
        'init_plugin_suite_live_search_sanitize_synonyms'
    );
});

// Tạo menu trong admin
add_action('admin_menu', function () {
    add_options_page(
        __('Init Live Search Settings', 'init-live-search'),
        __('Init Live Search', 'init-live-search'),
        'manage_options',
        'init-live-search-settings',
        'init_plugin_suite_live_search_render_settings_page'
    );
});

// Render trang settings
function init_plugin_suite_live_search_render_settings_page() {
    global $current_tab;

    $tabs = [
        'general'   => __('General Settings', 'init-live-search'),
        'synonyms'  => __('Synonyms', 'init-live-search'),
        'analytics' => __('Analytics', 'init-live-search'),
    ];

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Init Live Search Settings', 'init-live-search') . '</h1>';

    echo '<nav class="nav-tab-wrapper">';
    foreach ($tabs as $slug => $label) {
        $active = ($current_tab === $slug) ? ' nav-tab-active' : '';
        printf(
            '<a href="%s" class="nav-tab%s">%s</a>',
            esc_url(add_query_arg(['page' => 'init-live-search-settings', 'tab' => $slug], admin_url('options-general.php'))),
            esc_attr($active),
            esc_html($label)
        );
    }
    echo '</nav>';

    $tab_file = INIT_PLUGIN_SUITE_LS_INCLUDES_PATH . 'settings/' . $current_tab . '.php';
    if (file_exists($tab_file)) {
        include $tab_file;
    } else {
        echo '<p>' . esc_html__('Invalid tab.', 'init-live-search') . '</p>';
    }

    echo '</div>';
}

// Sanitize: GENERAL settings
function init_plugin_suite_live_search_sanitize_settings($input) {
    $output = [];

    $output['post_types'] = array_map('sanitize_key', $input['post_types'] ?? ['post']);
    if (empty($output['post_types'])) {
        $output['post_types'] = ['post'];
    }

    $output['debounce'] = max(100, min(3000, absint($input['debounce'] ?? 500)));
    $output['trigger_triple_click'] = !empty($input['trigger_triple_click']) ? '1' : '0';
    $output['trigger_ctrl_slash'] = !empty($input['trigger_ctrl_slash']) ? '1' : '0';
    $output['trigger_input_focus'] = !empty($input['trigger_input_focus']) ? '1' : '0';

    $allowed_default_commands = ['none', 'default', 'related', 'auto'];
    if (defined('INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION')) {
        $allowed_default_commands[] = 'popular';
        $allowed_default_commands[] = 'trending';
    }
    if (defined('INIT_PLUGIN_SUITE_RP_VERSION')) {
        $allowed_default_commands[] = 'read';
    }

    $output['default_command'] = in_array($input['default_command'] ?? 'none', $allowed_default_commands, true)
        ? $input['default_command']
        : 'none';

    $output['enable_slash'] = !empty($input['enable_slash']) ? '1' : '0';
    $output['max_results'] = min(100, max(1, absint($input['max_results'] ?? 10)));

    $allowed_modes = ['title', 'title_excerpt', 'title_content', 'title_tag'];
    $output['search_mode'] = in_array($input['search_mode'], $allowed_modes, true) ? $input['search_mode'] : 'title';

    $output['acf_search_fields'] = sanitize_text_field($input['acf_search_fields'] ?? '');
    $output['seo_search_fields_enabled'] = !empty($input['seo_search_fields_enabled']) ? '1' : '0';
    $output['show_excerpt'] = !empty($input['show_excerpt']) ? '1' : '0';
    $output['enable_synonym'] = !empty($input['enable_synonym']) ? '1' : '0';
    $output['enable_fallback'] = !empty($input['enable_fallback']) ? '1' : '0';
    $output['enable_analytics'] = !empty($input['enable_analytics']) ? '1' : '0';

    $output['css_style'] = in_array($input['css_style'], ['default', 'full', 'topbar', 'none'], true)
        ? $input['css_style']
        : 'default';

    $output['use_cache'] = !empty($input['use_cache']) ? '1' : '0';
    $output['enable_voice'] = !empty($input['enable_voice']) ? '1' : '0';
    $output['max_select_word'] = max(0, min(20, absint($input['max_select_word'] ?? 8)));
    $output['default_utm'] = esc_url_raw($input['default_utm'] ?? '');
    $output['suggested_keywords'] = sanitize_text_field($input['suggested_keywords'] ?? '');

    $clean_sites = [];
    $raw_lines = explode("\n", $input['cross_sites'] ?? '');
    foreach ($raw_lines as $line) {
        $line = trim($line);
        if (strpos($line, '|') !== false) {
            [$label, $url] = explode('|', $line, 2);
            $label = sanitize_text_field($label);
            $url = esc_url_raw(trim($url));
            if ($label && $url) {
                $clean_sites[] = $label . '|' . $url;
            }
        }
    }
    $output['cross_sites'] = implode("\n", $clean_sites);

    return $output;
}

// Sanitize: SYNONYMS
function init_plugin_suite_live_search_sanitize_synonyms($raw) {
    $clean = trim($raw);
    if ($clean === '') return '{}';

    json_decode($clean);
    return (json_last_error() === JSON_ERROR_NONE) ? $clean : '{}';
}

// Enqueue scripts
add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if ($hook_suffix !== 'settings_page_init-live-search-settings') return;
    wp_enqueue_script(
        'init_plugin_suite_live_search_admin',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/admin.js',
        [],
        INIT_PLUGIN_SUITE_LS_VERSION,
        true
    );
    wp_localize_script('init_plugin_suite_live_search_admin', 'init_plugin_suite_live_search_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('init_live_search_admin_nonce')
    ]);
});
