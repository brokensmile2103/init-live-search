<?php
defined('ABSPATH') || exit;

add_filter('init_plugin_suite_live_search_results', 'init_plugin_suite_live_search_track_query', 100, 4);

function init_plugin_suite_live_search_track_query($results, $post_ids, $term, $args) {
    $settings = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    if (empty($settings['enable_analytics'])) return $results;

    $term = trim($term);
    if ($term === '') return $results; // Không log nếu trống

    $log = [
        'query'   => sanitize_text_field($term),
        'results' => is_array($post_ids) ? count($post_ids) : 0,
        'time'    => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'source'  => $args['source'] ?? '',
    ];

    // Chunk system
    $chunk_index = absint(get_option('ils_log_chunk_index', 1));
    $chunk_key   = "ils_log_chunk_{$chunk_index}";
    $logs        = get_transient($chunk_key) ?: [];

    if (count($logs) >= 100) {
        $chunk_index++;
        update_option('ils_log_chunk_index', $chunk_index);
        $chunk_key = "ils_log_chunk_{$chunk_index}";
        $logs = [];
    }

    $logs[] = $log;
    set_transient($chunk_key, $logs, MONTH_IN_SECONDS);

    return $results;
}
