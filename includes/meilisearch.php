<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Meilisearch integration (optional, BYO server).
 *
 * Người dùng tự host Meilisearch (hoặc dùng Meilisearch Cloud) và dán
 * Host URL + Search API Key vào Settings > Meilisearch. Khi bật và cấu hình
 * đầy đủ, plugin sẽ ưu tiên dùng Meilisearch để lấy post IDs thay cho query
 * DB nội bộ; nếu request thất bại (timeout, lỗi mạng, key sai...), tự động
 * fallback về local DB search — search KHÔNG BAO GIỜ bị "chết" chỉ vì
 * Meilisearch gặp sự cố.
 *
 * Lưu ý phạm vi: khi Meilisearch xử lý thành công, các tính năng match phía
 * DB (search operators +/-, ACF field search, synonym dictionary, bigram
 * fallback) sẽ KHÔNG áp dụng lên kết quả đó — Meilisearch tự lo typo
 * tolerance/ranking theo cách riêng của nó. Các tính năng này vẫn hoạt động
 * đầy đủ khi Meilisearch tắt hoặc fallback về DB.
 */

// Lấy settings đã lưu (cache 1 lần / request qua static).
function init_plugin_suite_live_search_meili_get_settings() {
    static $settings = null;
    if ( $settings === null ) {
        $settings = get_option( INIT_PLUGIN_SUITE_LS_MEILI_OPTION, [] );
    }
    return $settings;
}

// Kiểm tra Meilisearch đã bật và có đủ config tối thiểu để search chưa.
function init_plugin_suite_live_search_meili_is_enabled( $settings = null ) {
    if ( $settings === null ) {
        $settings = init_plugin_suite_live_search_meili_get_settings();
    }

    if ( empty( $settings['enabled'] ) ) {
        return false;
    }

    return ! empty( $settings['host'] ) && ! empty( $settings['index'] ) && ! empty( $settings['search_key'] );
}

// Admin/indexing key ưu tiên lấy từ hằng số trong wp-config.php (an toàn hơn lưu DB).
// define('INIT_LIVE_SEARCH_MEILI_ADMIN_KEY', '...'); trong wp-config.php.
function init_plugin_suite_live_search_meili_get_admin_key( $settings ) {
    if ( defined( 'INIT_LIVE_SEARCH_MEILI_ADMIN_KEY' ) && INIT_LIVE_SEARCH_MEILI_ADMIN_KEY ) {
        return INIT_LIVE_SEARCH_MEILI_ADMIN_KEY;
    }
    return trim( $settings['admin_key'] ?? '' );
}

// ─────────────────────────────────────────────────────────────────────────
// SEARCH: gọi Meilisearch để lấy danh sách post IDs (đã sắp theo relevance).
// Trả về array (kể cả rỗng) khi thành công, false khi cần fallback về DB.
// ─────────────────────────────────────────────────────────────────────────
function init_plugin_suite_live_search_meili_get_post_ids( $term, $post_types, $limit, $paged, $settings, $args = [] ) {
    $host       = untrailingslashit( trim( $settings['host'] ?? '' ) );
    $index      = trim( $settings['index'] ?? '' );
    $search_key = trim( $settings['search_key'] ?? '' );

    if ( ! $host || ! $index || ! $search_key ) {
        return false;
    }

    $limit  = max( 1, (int) $limit );
    $paged  = max( 1, (int) $paged );
    $offset = ( $paged - 1 ) * $limit;

    $cache_key = 'init_plugin_suite_live_search_meili_' . md5( $term . serialize( $post_types ) . $limit . $paged );
    $cached    = wp_cache_get( $cache_key, 'init_plugin_suite_live_search' );
    if ( $cached !== false ) {
        return $cached;
    }

    $body = [
        'q'      => $term,
        'limit'  => $limit,
        'offset' => $offset,
    ];

    if ( ! empty( $post_types ) ) {
        $quoted = array_map(
            static function ( $post_type ) {
                return '"' . addslashes( $post_type ) . '"';
            },
            $post_types
        );
        $body['filter'] = 'post_type IN [' . implode( ', ', $quoted ) . ']';
    }

    $timeout_ms = isset( $settings['timeout_ms'] ) ? (int) $settings['timeout_ms'] : 3000;
    $timeout    = max( 0.5, $timeout_ms / 1000 );

    $response = wp_remote_post(
        $host . '/indexes/' . rawurlencode( $index ) . '/search',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $search_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => $timeout,
        ]
    );

    if ( is_wp_error( $response ) ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated by WP_DEBUG, intentional debug-only logging
            error_log( 'Init Live Search / Meilisearch request failed: ' . $response->get_error_message() );
        }
        return apply_filters( 'init_plugin_suite_live_search_meili_failure', false, $response, $term, $args );
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        return apply_filters( 'init_plugin_suite_live_search_meili_failure', false, $response, $term, $args );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! is_array( $data ) || ! isset( $data['hits'] ) || ! is_array( $data['hits'] ) ) {
        return false;
    }

    $post_ids = [];
    foreach ( $data['hits'] as $hit ) {
        if ( isset( $hit['id'] ) ) {
            $post_ids[] = absint( $hit['id'] );
        }
    }

    // Cache ngắn hơn DB path (Meilisearch vốn đã nhanh, ưu tiên tươi hơn là tiết kiệm request).
    wp_cache_set( $cache_key, $post_ids, 'init_plugin_suite_live_search', 120 );

    return $post_ids;
}

// Test connection nhanh — dùng cho nút "Test Connection" ở trang settings.
// Cố tình dùng endpoint /search (action "search") thay vì GET /indexes/{index}
// (action "indexes.get") vì Search API Key theo khuyến nghị chỉ có quyền search —
// dùng đúng endpoint mà key đó thực sự có quyền gọi, tránh 403 giả (đủ quyền
// search thật nhưng vẫn bị từ chối vì test sai endpoint).
function init_plugin_suite_live_search_meili_test_connection( $settings ) {
    $host       = untrailingslashit( trim( $settings['host'] ?? '' ) );
    $index      = trim( $settings['index'] ?? '' );
    $search_key = trim( $settings['search_key'] ?? '' );

    if ( ! $host || ! $index || ! $search_key ) {
        return new WP_Error( 'missing_config', __( 'Please fill in Host, Index, and Search Key before testing.', 'init-live-search' ) );
    }

    $response = wp_remote_post(
        $host . '/indexes/' . rawurlencode( $index ) . '/search',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $search_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( [ 'q' => '', 'limit' => 1 ] ),
            'timeout' => 5,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    $code = wp_remote_retrieve_response_code( $response );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_Error(
            'meili_http_error',
            sprintf(
                /* translators: %d: HTTP status code */
                __( 'Meilisearch returned HTTP error %d. Please check your Host / Index / Key.', 'init-live-search' ),
                $code
            )
        );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    return [
        'estimatedTotalHits' => $data['estimatedTotalHits'] ?? null,
        'processingTimeMs'   => $data['processingTimeMs'] ?? null,
    ];
}

// ─────────────────────────────────────────────────────────────────────────
// INDEXING: build document + đồng bộ khi save/xóa post.
// ─────────────────────────────────────────────────────────────────────────
function init_plugin_suite_live_search_meili_build_document( $post ) {
    $categories = ( 'post' === $post->post_type ) ? wp_get_post_categories( $post->ID, [ 'fields' => 'names' ] ) : [];

    return [
        'id'             => $post->ID,
        'title'          => get_the_title( $post ),
        'excerpt'        => wp_strip_all_tags( get_the_excerpt( $post ) ),
        'content'        => wp_strip_all_tags( strip_shortcodes( $post->post_content ) ),
        'url'            => get_permalink( $post ),
        'thumbnail'      => get_the_post_thumbnail_url( $post, 'medium' ) ?: '',
        'post_type'      => $post->post_type,
        'categories'     => is_array( $categories ) ? $categories : [],
        'date'           => get_the_date( 'Y-m-d', $post ),
        'date_timestamp' => strtotime( $post->post_date ),
    ];
}

function init_plugin_suite_live_search_meili_push_document( $post, $settings ) {
    $admin_key = init_plugin_suite_live_search_meili_get_admin_key( $settings );
    $host      = untrailingslashit( trim( $settings['host'] ?? '' ) );
    $index     = trim( $settings['index'] ?? '' );

    if ( ! $admin_key || ! $host || ! $index ) {
        return;
    }

    $document = init_plugin_suite_live_search_meili_build_document( $post );

    wp_remote_post(
        $host . '/indexes/' . rawurlencode( $index ) . '/documents',
        [
            'headers'  => [
                'Authorization' => 'Bearer ' . $admin_key,
                'Content-Type'  => 'application/json',
            ],
            'body'     => wp_json_encode( [ $document ] ),
            'timeout'  => 5,
            'blocking' => false, // Không làm chậm thao tác lưu bài trong wp-admin.
        ]
    );
}

function init_plugin_suite_live_search_meili_remove_document( $post_id, $settings ) {
    $admin_key = init_plugin_suite_live_search_meili_get_admin_key( $settings );
    $host      = untrailingslashit( trim( $settings['host'] ?? '' ) );
    $index     = trim( $settings['index'] ?? '' );

    if ( ! $admin_key || ! $host || ! $index ) {
        return;
    }

    wp_remote_request(
        $host . '/indexes/' . rawurlencode( $index ) . '/documents/' . absint( $post_id ),
        [
            'method'   => 'DELETE',
            'headers'  => [ 'Authorization' => 'Bearer ' . $admin_key ],
            'timeout'  => 5,
            'blocking' => false,
        ]
    );
}

// Đồng bộ khi post được lưu (publish → index, unpublish/trash → gỡ khỏi index).
function init_plugin_suite_live_search_meili_sync_post( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $settings = init_plugin_suite_live_search_meili_get_settings();
    if ( ! init_plugin_suite_live_search_meili_is_enabled( $settings ) ) {
        return;
    }

    $options       = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $allowed_types = apply_filters(
        'init_plugin_suite_live_search_post_types',
        $options['post_types'] ?? [ 'post' ],
        $options,
        []
    );
    if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
        return;
    }

    if ( 'publish' === $post->post_status ) {
        init_plugin_suite_live_search_meili_push_document( $post, $settings );
    } elseif ( in_array( $post->post_status, [ 'trash', 'draft', 'pending', 'private', 'future' ], true ) ) {
        init_plugin_suite_live_search_meili_remove_document( $post->ID, $settings );
    }
}
add_action( 'save_post', 'init_plugin_suite_live_search_meili_sync_post', 20, 3 );

// Xóa vĩnh viễn khỏi index khi post bị xóa hẳn (kể cả không qua trash).
function init_plugin_suite_live_search_meili_handle_delete( $post_id ) {
    $settings = init_plugin_suite_live_search_meili_get_settings();
    if ( ! init_plugin_suite_live_search_meili_is_enabled( $settings ) ) {
        return;
    }
    init_plugin_suite_live_search_meili_remove_document( $post_id, $settings );
}
add_action( 'before_delete_post', 'init_plugin_suite_live_search_meili_handle_delete' );
