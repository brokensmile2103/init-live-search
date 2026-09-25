<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('init_plugin_suite_live_search_results', 'init_plugin_suite_live_search_track_query', 100, 4);

function init_plugin_suite_live_search_track_query($results, $post_ids, $term, $args) {
    $settings = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    if (empty($settings['enable_analytics'])) return $results;

    $term = trim($term);
    if ($term === '') return $results; // Không log nếu trống

    // Chỉ ghi nhận lượt tìm kiếm THẬT của người dùng (REST /search, trang đầu).
    // Trước 2.0.1, filter này bắt cả Related Posts, redirect 404, Abilities API...
    // -> mỗi lượt xem bài viết đều ghi 1 lần vào DB và làm sai số liệu thống kê.
    $is_user_search = is_array( $args )
        && isset( $args['context'] ) && 'search' === $args['context']
        && empty( $args['force_ids'] )
        && (int) ( $args['paged'] ?? 1 ) <= 1;

    if ( ! apply_filters( 'init_plugin_suite_live_search_should_track', $is_user_search, $term, $args ) ) {
        return $results;
    }

    $log = [
        'query'   => sanitize_text_field($term),
        'results' => is_array($post_ids) ? count($post_ids) : 0,
        'time'    => current_time('mysql'),
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
