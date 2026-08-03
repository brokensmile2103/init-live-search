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

// Kiểm tra CÓ ĐỦ Host/Index để lập chỉ mục hay không — KHÔNG đòi hỏi checkbox
// "Dùng Meilisearch làm nguồn tìm kiếm chính" phải bật.
//
// Lý do tách riêng khỏi is_enabled(): việc BUILD/REBUILD index (Reindex Now,
// WP-CLI, cron nền) là một hành động độc lập với việc CÓ ĐANG DÙNG Meilisearch
// để trả kết quả search hay không. Một cách dùng rất phổ biến: chuẩn bị/thử
// nghiệm index mới trong khi search live vẫn đang chạy nguồn cũ (DB/FULLTEXT),
// chỉ bật checkbox "làm nguồn chính" sau khi đã kiểm tra index mới ổn. Nếu bắt
// buộc phải bật checkbox mới cho reindex chạy thì không thể chuẩn bị trước
// được — đây chính là nguyên nhân "Reindex Now" báo lỗi "not enabled" dù
// Host/Index/Key đã điền đủ.
function init_plugin_suite_live_search_meili_is_configured( $settings = null ) {
    if ( $settings === null ) {
        $settings = init_plugin_suite_live_search_meili_get_settings();
    }

    return ! empty( $settings['host'] ) && ! empty( $settings['index'] );
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

// ─────────────────────────────────────────────────────────────────────────
// Background reindex via WP-Cron — "Reindex Now" button (Settings > Meilisearch).
//
// Khác với FULLTEXT index (bảng DB nội bộ, miễn phí, nên tự động chạy ngay
// khi bật là hợp lý), Meilisearch là SERVER BÊN NGOÀI do người dùng tự host
// hoặc trả tiền theo document/operation. Vì vậy tiến trình này CHỦ ĐỘNG
// KHÔNG tự chạy khi lưu Settings — chỉ khởi động khi admin bấm nút
// "Reindex Now", tránh phát sinh request/tiền bất ngờ ngoài ý muốn.
//
// Vẫn dùng đúng cơ chế "batch nhỏ, lặp mỗi 5 giây tới khi xong" như FULLTEXT
// để không cần WP-CLI/SSH, nhưng có thêm đếm lỗi liên tiếp: lỗi 3 lần liền
// (network, sai key, server quá tải...) thì tự dừng hẳn và báo lỗi rõ ràng,
// thay vì spam vô hạn vào server của người dùng.
// ─────────────────────────────────────────────────────────────────────────

define( 'INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK', 'init_plugin_suite_live_search_meili_cron_batch' );

add_action( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK, 'init_plugin_suite_live_search_meili_cron_run_batch' );

// Khởi động (hoặc khởi động lại từ đầu) tiến trình reindex nền.
function init_plugin_suite_live_search_meili_cron_start() {
    delete_option( 'init_plugin_suite_live_search_meili_cron_state' );
    delete_option( 'init_plugin_suite_live_search_meili_cron_skipped' );
    update_option( 'init_plugin_suite_live_search_meili_cron_last_error', '', false );

    if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK ) ) {
        wp_schedule_single_event( time(), INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK );
    }
}

function init_plugin_suite_live_search_meili_cron_finish( $total ) {
    update_option( 'init_plugin_suite_live_search_meili_indexed', current_time( 'mysql' ), false );
    update_option( 'init_plugin_suite_live_search_meili_cron_last_error', '', false );
    delete_option( 'init_plugin_suite_live_search_meili_cron_state' );
}

// Dừng hẳn tiến trình + lưu lý do lỗi để hiển thị cho admin.
function init_plugin_suite_live_search_meili_cron_stop( $error_message ) {
    update_option( 'init_plugin_suite_live_search_meili_cron_last_error', sanitize_text_field( $error_message ), false );
    delete_option( 'init_plugin_suite_live_search_meili_cron_state' );
    wp_clear_scheduled_hook( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK );
}

// "Đang chạy hay không" — dùng cho UI (Settings > Meilisearch + AJAX polling).
// Cùng lý do với init_plugin_suite_live_search_fulltext_cron_is_active():
// wp-cron.php unschedule 1 event TRƯỚC KHI gọi callback, nên chỉ dựa vào
// wp_next_scheduled() sẽ báo sai "đã dừng" trong lúc 1 batch đang thực sự
// gửi document lên Meilisearch — ảnh hưởng trực tiếp tới JS polling (dừng
// polling ngay khi thấy running=false dù chỉ là false-negative tạm thời).
function init_plugin_suite_live_search_meili_cron_is_active() {
    if ( get_transient( 'init_plugin_suite_live_search_meili_cron_lock' ) ) {
        return true;
    }

    $next = wp_next_scheduled( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK );
    if ( ! $next ) {
        return false;
    }

    $grace = (int) apply_filters( 'init_plugin_suite_live_search_meili_cron_stall_grace', 60 );

    return $next > ( time() - $grace );
}

// ─────────────────────────────────────────────────────────────────────────
// Gửi 1 lô document lên Meilisearch, TỰ ĐỘNG chia nhỏ và gửi lại nếu server
// (hoặc 1 reverse proxy phía trước, vd Nginx client_max_body_size) trả về
// HTTP 413 Payload Too Large.
//
// Lý do cần: kích thước JSON của 1 batch phụ thuộc vào NỘI DUNG bài viết
// (title/excerpt/content), không phải chỉ số lượng bài. Cùng batch_size=200
// nhưng site có bài viết dài/nhiều ảnh embed base64... có thể tạo payload
// vài chục MB trong khi site khác chỉ vài trăm KB. Một giới hạn batch_size
// cố định vì vậy không thể vừa an toàn cho mọi site — nên thay vì chỉ báo
// lỗi 413 và dừng hẳn (3 lần liên tiếp chắc chắn fail giống hệt nhau vì
// nguyên nhân không phải tạm thời), hàm này tự chia đôi batch và thử lại,
// hội tụ rất nhanh (vd 200 → 100 → 50 → 25 → 13 → 7 → 4 → 2 → 1 chỉ mất tối
// đa 8 lượt gọi) về kích thước server chấp nhận được.
//
// Nếu THẬM CHÍ 1 document đơn lẻ vẫn bị 413 (post quá dài để tự nó vượt giới
// hạn payload), document đó được BỎ QUA (không chặn cả tiến trình reindex vì
// 1 bài quá khổ) và ID được ghi nhận lại để báo cho admin biết.
//
// @return array{sent:int,skipped:int[],error:string} error rỗng nghĩa là
//         không có lỗi "cứng" (network/auth/5xx...) — chỉ có thể có vài ID
//         bị skip vì quá khổ, không tính là lỗi để dừng tiến trình.
function init_plugin_suite_live_search_meili_send_documents( $endpoint, $admin_key, $documents, $timeout = 15, $depth = 0 ) {
    if ( empty( $documents ) ) {
        return [
            'sent'    => 0,
            'skipped' => [],
            'error'   => '',
        ];
    }

    $response = wp_remote_post(
        $endpoint,
        [
            'headers' => [
                'Authorization' => 'Bearer ' . $admin_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $documents ),
            'timeout' => $timeout,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return [
            'sent'    => 0,
            'skipped' => [],
            'error'   => $response->get_error_message(),
        ];
    }

    $code = wp_remote_retrieve_response_code( $response );

    if ( $code >= 200 && $code < 300 ) {
        return [
            'sent'    => count( $documents ),
            'skipped' => [],
            'error'   => '',
        ];
    }

    if ( 413 === $code && count( $documents ) > 1 && $depth < 10 ) {
        $mid    = (int) ceil( count( $documents ) / 2 );
        $first  = array_slice( $documents, 0, $mid );
        $second = array_slice( $documents, $mid );

        $result_a = init_plugin_suite_live_search_meili_send_documents( $endpoint, $admin_key, $first, $timeout, $depth + 1 );
        $result_b = init_plugin_suite_live_search_meili_send_documents( $endpoint, $admin_key, $second, $timeout, $depth + 1 );

        return [
            'sent'    => $result_a['sent'] + $result_b['sent'],
            'skipped' => array_merge( $result_a['skipped'], $result_b['skipped'] ),
            'error'   => '' !== $result_a['error'] ? $result_a['error'] : $result_b['error'],
        ];
    }

    if ( 413 === $code ) {
        // Không chia nhỏ được nữa (chỉ còn 1 document, hoặc đã chạm giới hạn
        // đệ quy) mà vẫn 413 — bỏ qua đúng document này, không chặn cả batch.
        return [
            'sent'    => 0,
            'skipped' => [ isset( $documents[0]['id'] ) ? (int) $documents[0]['id'] : 0 ],
            'error'   => '',
        ];
    }

    return [
        'sent'    => 0,
        'skipped' => [],
        /* translators: %d: HTTP status code */
        'error'   => sprintf( __( 'HTTP error %d from Meilisearch.', 'init-live-search' ), $code ),
    ];
}

function init_plugin_suite_live_search_meili_cron_run_batch() {
    // Chặn 2 lượt chạy chồng nhau (không bắt buộc để đúng tuyệt đối — chỉ để
    // tránh gửi trùng document lãng phí trong trường hợp hiếm gặp).
    if ( get_transient( 'init_plugin_suite_live_search_meili_cron_lock' ) ) {
        return;
    }
    // TTL 60s: cùng lý do với FULLTEXT — batch lớn/host chậm có thể mất hơn
    // 30 giây, transient hết hạn giữa chừng sẽ khiến cron_is_active() báo sai.
    set_transient( 'init_plugin_suite_live_search_meili_cron_lock', 1, 60 );

    $settings = init_plugin_suite_live_search_meili_get_settings();

    $admin_key = init_plugin_suite_live_search_meili_get_admin_key( $settings );
    $host      = untrailingslashit( trim( $settings['host'] ?? '' ) );
    $index     = trim( $settings['index'] ?? '' );

    if ( ! $admin_key || ! $host || ! $index ) {
        init_plugin_suite_live_search_meili_cron_stop( __( 'Missing Admin/Indexing Key, Host, or Index.', 'init-live-search' ) );
        delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
        return;
    }

    $options       = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $allowed_types = apply_filters(
        'init_plugin_suite_live_search_post_types',
        ! empty( $options['post_types'] ) ? (array) $options['post_types'] : [ 'post' ],
        $options,
        []
    );

    $batch_size = (int) apply_filters( 'init_plugin_suite_live_search_meili_cron_batch_size', 200 );

    $state = get_option( 'init_plugin_suite_live_search_meili_cron_state', false );
    if ( ! is_array( $state ) || empty( $state['paged'] ) ) {
        $state = [ 'paged' => 1, 'total' => 0, 'errors' => 0 ];
    }

    $query = new WP_Query( [
        'post_type'      => $allowed_types,
        'post_status'    => 'publish',
        'posts_per_page' => $batch_size,
        'paged'          => $state['paged'],
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ] );

    // Hết bài để xử lý — hoàn tất.
    if ( empty( $query->posts ) ) {
        init_plugin_suite_live_search_meili_cron_finish( $state['total'] );
        delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
        return;
    }

    $documents = [];
    foreach ( $query->posts as $post ) {
        $documents[] = init_plugin_suite_live_search_meili_build_document( $post );
    }

    $result = init_plugin_suite_live_search_meili_send_documents(
        $host . '/indexes/' . rawurlencode( $index ) . '/documents',
        $admin_key,
        $documents,
        15
    );

    if ( '' !== $result['error'] ) {
        $state['errors'] = ( $state['errors'] ?? 0 ) + 1;

        // 3 lỗi liên tiếp -> dừng hẳn, không spam vô hạn vào server của người dùng.
        // (413 không rơi vào nhánh này nữa — send_documents() đã tự chia nhỏ
        // và xử lý 413 ở mức document, xem hàm đó để biết chi tiết.)
        if ( $state['errors'] >= 3 ) {
            init_plugin_suite_live_search_meili_cron_stop( $result['error'] );
            delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
            return;
        }

        // Thử lại đúng batch này sau 5 giây (không tăng $state['paged']).
        update_option( 'init_plugin_suite_live_search_meili_cron_state', $state, false );

        if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK );
        }

        delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
        return;
    }

    $state['errors'] = 0;
    $state['total'] += $result['sent'];

    if ( ! empty( $result['skipped'] ) ) {
        $existing_skipped = get_option( 'init_plugin_suite_live_search_meili_cron_skipped', [] );
        $existing_skipped = is_array( $existing_skipped ) ? $existing_skipped : [];
        $state['skipped'] = array_values( array_unique( array_merge( $existing_skipped, $result['skipped'] ) ) );
        update_option( 'init_plugin_suite_live_search_meili_cron_skipped', $state['skipped'], false );
    }

    if ( count( $query->posts ) === $batch_size ) {
        // Còn bài viết chưa xử lý — hẹn đợt kế tiếp sau 5 giây. Schedule
        // TRƯỚC khi xoá lock để tránh khoảnh khắc cả 2 tín hiệu cùng "trống"
        // (xem giải thích ở init_plugin_suite_live_search_fulltext_cron_run_batch()).
        $state['paged']++;
        update_option( 'init_plugin_suite_live_search_meili_cron_state', $state, false );

        if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, INIT_PLUGIN_SUITE_LS_MEILI_CRON_HOOK );
        }

        delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
    } else {
        init_plugin_suite_live_search_meili_cron_finish( $state['total'] );
        delete_transient( 'init_plugin_suite_live_search_meili_cron_lock' );
    }
}
