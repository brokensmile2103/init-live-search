<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Optional local FULLTEXT search index (opt-in, off by default).
 *
 * Vấn đề cần giải quyết: pipeline search mặc định của plugin dùng nhiều query
 * `LIKE '%term%'` tuần tự (title/excerpt/content...) — vì có dấu % ở đầu nên
 * MySQL không dùng được index, mỗi query là 1 lần quét toàn bảng wp_posts.
 * Trên site nhiều bài viết, đây là điểm nghẽn hiệu năng lớn nhất của search
 * nội bộ (không dùng Meilisearch).
 *
 * Giải pháp: đồng bộ title/excerpt/content sang MỘT BẢNG PHỤ riêng của plugin
 * (KHÔNG đụng vào wp_posts) có sẵn FULLTEXT index, rồi query bằng
 * MATCH() AGAINST() thay vì LIKE. Đồng bộ qua hook save_post / before_delete_post,
 * giống hệt pattern đã dùng cho Meilisearch ở includes/meilisearch.php.
 *
 * Tính an toàn được ưu tiên tuyệt đối vì plugin đang chạy production ở nhiều
 * site qua WP.org:
 * - Mặc định TẮT. Không ảnh hưởng gì tới site cũ cho tới khi admin tự bật.
 * - Bảng phụ được tạo LAZY — chỉ tạo đúng lúc admin lưu Settings với checkbox
 *   này được tick lần đầu (hoặc lần đầu chạy lệnh WP-CLI reindex). Site nào
 *   không bao giờ đụng tới tính năng thì DB không phát sinh thêm bảng nào cả.
 * - Tự phát hiện khả năng hỗ trợ FULLTEXT của MySQL/MariaDB (không phải server
 *   nào cũng cho phép ALTER TABLE thêm FULLTEXT) ngay tại thời điểm tạo bảng;
 *   nếu không hỗ trợ, tự động không cho bật và báo rõ trong Settings.
 * - Chỉ thực sự dùng FULLTEXT tại thời điểm search khi bảng index đã được
 *   build đầy đủ ít nhất 1 lần (`wp init-live-search fulltext-reindex`).
 *   Nếu admin bật checkbox nhưng CHƯA reindex, plugin tự động tiếp tục dùng
 *   pipeline LIKE cũ (không có chuyện search "trắng" kết quả).
 * - Nếu 1 từ khoá không có token nào đủ dài để FULLTEXT xử lý (từ quá ngắn),
 *   tự động rơi về LIKE cho riêng lần search đó — không mất kết quả.
 */

define( 'INIT_PLUGIN_SUITE_LS_FULLTEXT_SCHEMA_VERSION', '1' );

// ─────────────────────────────────────────────────────────────────────────
// Table / schema management
// ─────────────────────────────────────────────────────────────────────────

function init_plugin_suite_live_search_fulltext_table() {
    global $wpdb;
    return $wpdb->prefix . 'init_live_search_fulltext_index';
}

// Tạo bảng (nếu chưa có) qua dbDelta — chỉ cột thường, KHÔNG khai báo FULLTEXT
// ở đây. dbDelta có bug lịch sử (#26661) khi so sánh/khai báo lại FULLTEXT KEY,
// nên các FULLTEXT index được thêm riêng, thủ công, có kiểm tra tồn tại trước.
function init_plugin_suite_live_search_fulltext_create_table() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table           = init_plugin_suite_live_search_fulltext_table();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        post_id BIGINT UNSIGNED NOT NULL,
        post_type VARCHAR(20) NOT NULL DEFAULT 'post',
        title TEXT NOT NULL,
        excerpt TEXT NOT NULL,
        content LONGTEXT NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (post_id),
        KEY post_type (post_type)
    ) ENGINE=InnoDB {$charset_collate};";

    dbDelta( $sql );
}

// Thêm 3 FULLTEXT KEY riêng lẻ (title / excerpt / content) nếu chưa có, và
// lưu kết quả khả dụng vào option để is_supported() đọc lại mà không cần
// query DB ở mỗi lần search (tránh chính vấn đề hiệu năng mình đang muốn sửa).
function init_plugin_suite_live_search_fulltext_ensure_ft_indexes() {
    global $wpdb;
    $table = init_plugin_suite_live_search_fulltext_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    if ( $table_exists !== $table ) {
        update_option( 'init_plugin_suite_live_search_fulltext_supported', 'no', false );
        return;
    }

    $needed = [
        'ft_title'   => 'title',
        'ft_excerpt' => 'excerpt',
        'ft_content' => 'content',
    ];

    // $table is derived from $wpdb->prefix only, never from user input.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $existing_keys = $wpdb->get_col( "SHOW INDEX FROM {$table} WHERE Index_type = 'FULLTEXT'", 2 );
    $existing_keys = (array) $existing_keys;

    $all_ok = true;

    foreach ( $needed as $key_name => $column ) {
        if ( in_array( $key_name, $existing_keys, true ) ) {
            continue;
        }

        // $table, $key_name, $column are all hard-coded above / derived from
        // $wpdb->prefix — never from user input.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query( "ALTER TABLE {$table} ADD FULLTEXT KEY {$key_name} ({$column})" );

        if ( ! empty( $wpdb->last_error ) ) {
            $all_ok = false;
        }
    }

    update_option( 'init_plugin_suite_live_search_fulltext_supported', $all_ok ? 'yes' : 'no', false );
}

// Không tự tạo bảng cho MỌI site chạy plugin — bảng chỉ được tạo (lazy) đúng
// lúc admin lưu Settings với checkbox "FULLTEXT Search Index" được tick lần
// đầu (xem includes/settings-page.php), hoặc khi chạy lệnh WP-CLI reindex.
// Nhờ vậy site nào không dùng tính năng này thì DB không có thêm bảng thừa.
function init_plugin_suite_live_search_fulltext_maybe_upgrade( $force = false ) {
    $installed = get_option( 'init_plugin_suite_live_search_fulltext_schema_version', '' );
    if ( ! $force && $installed === INIT_PLUGIN_SUITE_LS_FULLTEXT_SCHEMA_VERSION ) {
        return;
    }

    init_plugin_suite_live_search_fulltext_create_table();
    init_plugin_suite_live_search_fulltext_ensure_ft_indexes();

    update_option( 'init_plugin_suite_live_search_fulltext_schema_version', INIT_PLUGIN_SUITE_LS_FULLTEXT_SCHEMA_VERSION, false );
}

// Đã từng chạy capability check hay chưa (khác với is_supported() — cái này
// trả lời câu "đã biết kết quả chưa", không phải "kết quả có tốt không").
// UI dùng cái này để phân biệt "chưa thử" (bình thường) với "đã thử và thất
// bại" (mới cần khoá checkbox + báo đỏ).
function init_plugin_suite_live_search_fulltext_capability_checked() {
    return get_option( 'init_plugin_suite_live_search_fulltext_supported', '' ) !== '';
}

// "Re-check support" — link thủ công ở Settings > General khi server báo không
// hỗ trợ. Chỉ chạy khi admin chủ động bấm (capability + nonce check đầy đủ),
// KHÔNG chạy tự động lặp lại mỗi page load (tránh ALTER TABLE lặp vô ích trên
// site thật sự không hỗ trợ — đúng tinh thần "không thêm chi phí nếu không cần").
add_action( 'admin_init', 'init_plugin_suite_live_search_fulltext_handle_recheck_request' );

function init_plugin_suite_live_search_fulltext_handle_recheck_request() {
    if ( ! isset( $_GET['init_ls_recheck_fulltext'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    check_admin_referer( 'init_ls_recheck_fulltext' );

    init_plugin_suite_live_search_fulltext_maybe_upgrade( true );

    wp_safe_redirect( remove_query_arg( [ 'init_ls_recheck_fulltext', '_wpnonce' ] ) );
    exit;
}

// ─────────────────────────────────────────────────────────────────────────
// Capability / enablement checks
// ─────────────────────────────────────────────────────────────────────────

// Server có tạo được FULLTEXT index trên bảng phụ của plugin hay không.
function init_plugin_suite_live_search_fulltext_is_supported() {
    return get_option( 'init_plugin_suite_live_search_fulltext_supported', '' ) === 'yes';
}

// Điều kiện ĐẦY ĐỦ để pipeline search thực sự dùng FULLTEXT thay cho LIKE:
// admin đã bật + server hỗ trợ + đã build index ít nhất 1 lần qua WP-CLI.
function init_plugin_suite_live_search_fulltext_is_enabled() {
    static $enabled = null;
    if ( $enabled !== null ) {
        return $enabled;
    }

    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    if ( empty( $options['use_fulltext_index'] ) ) {
        return $enabled = false;
    }

    if ( ! init_plugin_suite_live_search_fulltext_is_supported() ) {
        return $enabled = false;
    }

    if ( ! get_option( 'init_plugin_suite_live_search_fulltext_indexed', '' ) ) {
        return $enabled = false;
    }

    return $enabled = true;
}

// Danh sách post type nên được lập chỉ mục — mọi public post type, trừ các
// type nội bộ của WP không nên/không cần search.
function init_plugin_suite_live_search_fulltext_get_indexable_post_types() {
    $blocklist = apply_filters( 'init_plugin_suite_live_search_fulltext_excluded_post_types', [
        'revision', 'nav_menu_item', 'custom_css', 'customize_changeset',
        'oembed_cache', 'user_request', 'wp_block', 'wp_template',
        'wp_template_part', 'wp_global_styles', 'wp_navigation', 'attachment',
    ] );

    $types = get_post_types( [ 'public' => true ], 'names' );
    $types = array_values( array_diff( $types, $blocklist ) );

    return apply_filters( 'init_plugin_suite_live_search_fulltext_post_types', $types );
}

// Server-side min token length cho InnoDB FULLTEXT (từ ngắn hơn giá trị này
// bị MySQL âm thầm bỏ qua khi index/search — mặc định InnoDB là 3 ký tự).
// Cache 1 ngày vì đây là cấu hình server, gần như không đổi giữa các request.
function init_plugin_suite_live_search_fulltext_get_min_token_size() {
    $cached = get_transient( 'init_plugin_suite_live_search_fulltext_min_token' );
    if ( $cached !== false ) {
        return (int) $cached;
    }

    global $wpdb;
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $row = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_ft_min_token_size'" );
    $value = ( $row && isset( $row->Value ) ) ? (int) $row->Value : 3;

    // Không tin tưởng giá trị bất thường (0 hoặc quá lớn) — dùng mặc định an toàn.
    if ( $value < 1 || $value > 10 ) {
        $value = 3;
    }

    set_transient( 'init_plugin_suite_live_search_fulltext_min_token', $value, DAY_IN_SECONDS );
    return $value;
}

// ─────────────────────────────────────────────────────────────────────────
// Query builder: chuyển 1 chuỗi search thành cú pháp BOOLEAN MODE của MySQL.
// Trả về '' nếu không còn từ nào đủ điều kiện (caller nên fallback về LIKE).
// ─────────────────────────────────────────────────────────────────────────

function init_plugin_suite_live_search_fulltext_build_boolean_query( $term, $min_token_size ) {
    $words  = preg_split( '/\s+/u', trim( (string) $term ) );
    $tokens = [];

    foreach ( (array) $words as $word ) {
        // Bỏ các ký tự có ý nghĩa đặc biệt trong BOOLEAN MODE để tránh vỡ cú
        // pháp câu query (+ - > < ( ) ~ * " @).
        $clean = preg_replace( '/[+\-><\(\)~*"@]+/u', '', $word );
        $clean = trim( $clean );

        if ( $clean === '' || mb_strlen( $clean ) < $min_token_size ) {
            continue;
        }

        // '+' bắt buộc phải có từ này (AND theo từng từ), '*' cho phép match
        // theo tiền tố (gõ dở "thu" vẫn ra "thuê", giữ đúng trải nghiệm live-search).
        $tokens[] = '+' . $clean . '*';
    }

    if ( empty( $tokens ) ) {
        return '';
    }

    return implode( ' ', $tokens );
}

// ─────────────────────────────────────────────────────────────────────────
// FULLTEXT lookups — cùng chữ ký "trả false = chưa xử lý được, để caller tự
// fallback về LIKE" như cách meilisearch.php báo hiệu fallback.
// ─────────────────────────────────────────────────────────────────────────

function init_plugin_suite_live_search_fulltext_get_ids_by_title( $wpdb, $term, $post_types, $limit ) {
    return init_plugin_suite_live_search_fulltext_match( $wpdb, 'title', $term, $post_types, $limit );
}

function init_plugin_suite_live_search_fulltext_get_ids_by_excerpt( $wpdb, $term, $post_types, $limit ) {
    return init_plugin_suite_live_search_fulltext_match( $wpdb, 'excerpt', $term, $post_types, $limit );
}

function init_plugin_suite_live_search_fulltext_get_ids_by_content( $wpdb, $term, $post_types, $limit ) {
    return init_plugin_suite_live_search_fulltext_match( $wpdb, 'content', $term, $post_types, $limit );
}

function init_plugin_suite_live_search_fulltext_match( $wpdb, $column, $term, $post_types, $limit ) {
    if ( ! init_plugin_suite_live_search_fulltext_is_enabled() ) {
        return false;
    }

    if ( empty( $post_types ) ) {
        return false;
    }

    $min_token = init_plugin_suite_live_search_fulltext_get_min_token_size();
    $query     = init_plugin_suite_live_search_fulltext_build_boolean_query( $term, $min_token );

    if ( $query === '' ) {
        return false;
    }

    $table        = init_plugin_suite_live_search_fulltext_table();
    $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

    // Whitelist cột hard-coded ở 3 hàm gọi bên trên — không nhận trực tiếp từ
    // input người dùng, an toàn để nội suy tên cột vào SQL.
    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $ids = $wpdb->get_col( $wpdb->prepare(
        "
        SELECT post_id FROM {$table}
        WHERE post_type IN ($placeholders)
        AND MATCH({$column}) AGAINST (%s IN BOOLEAN MODE)
        ORDER BY MATCH({$column}) AGAINST (%s IN BOOLEAN MODE) DESC
        LIMIT %d
        ",
        ...array_merge( $post_types, [ $query, $query, (int) $limit ] )
    ) );
    // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    if ( ! is_array( $ids ) ) {
        return false;
    }

    return array_map( 'absint', $ids );
}

// ─────────────────────────────────────────────────────────────────────────
// Đồng bộ dữ liệu: upsert khi publish, xoá khi unpublish/trash/xoá hẳn.
// Đồng bộ luôn diễn ra ngay khi admin bật checkbox (không cần chờ "indexed"
// flag) để những bài viết mới không bị bỏ sót cho tới lần reindex kế tiếp.
// ─────────────────────────────────────────────────────────────────────────

function init_plugin_suite_live_search_fulltext_upsert_row( $post ) {
    global $wpdb;

    if ( ! ( $post instanceof WP_Post ) ) {
        $post = get_post( $post );
    }
    if ( ! $post ) {
        return;
    }

    $table = init_plugin_suite_live_search_fulltext_table();

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->replace(
        $table,
        [
            'post_id'    => $post->ID,
            'post_type'  => $post->post_type,
            'title'      => (string) $post->post_title,
            'excerpt'    => (string) $post->post_excerpt,
            'content'    => wp_strip_all_tags( strip_shortcodes( (string) $post->post_content ) ),
            'updated_at' => current_time( 'mysql' ),
        ],
        [ '%d', '%s', '%s', '%s', '%s', '%s' ]
    );
}

function init_plugin_suite_live_search_fulltext_delete_row( $post_id ) {
    global $wpdb;
    $table = init_plugin_suite_live_search_fulltext_table();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->delete( $table, [ 'post_id' => absint( $post_id ) ], [ '%d' ] );
}

function init_plugin_suite_live_search_fulltext_sync_post( $post_id, $post, $update ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    if ( empty( $options['use_fulltext_index'] ) ) {
        return;
    }

    if ( ! init_plugin_suite_live_search_fulltext_is_supported() ) {
        return;
    }

    if ( ! in_array( $post->post_type, init_plugin_suite_live_search_fulltext_get_indexable_post_types(), true ) ) {
        return;
    }

    if ( 'publish' === $post->post_status ) {
        init_plugin_suite_live_search_fulltext_upsert_row( $post );
    } else {
        init_plugin_suite_live_search_fulltext_delete_row( $post->ID );
    }
}
add_action( 'save_post', 'init_plugin_suite_live_search_fulltext_sync_post', 20, 3 );

function init_plugin_suite_live_search_fulltext_handle_delete( $post_id ) {
    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    if ( empty( $options['use_fulltext_index'] ) ) {
        return;
    }

    if ( ! init_plugin_suite_live_search_fulltext_is_supported() ) {
        return;
    }

    init_plugin_suite_live_search_fulltext_delete_row( $post_id );
}
add_action( 'before_delete_post', 'init_plugin_suite_live_search_fulltext_handle_delete' );

// ─────────────────────────────────────────────────────────────────────────
// Background auto-reindex via WP-Cron.
//
// Không phải ai cũng SSH được để chạy `wp init-live-search fulltext-reindex`,
// nên ngay khi admin bật "FULLTEXT Search Index" ở Settings > General, plugin
// tự lên lịch xử lý theo từng đợt vài trăm bài, lặp lại mỗi 5 giây cho tới
// khi hết bài viết — không cần WP-CLI, không cần admin làm gì thêm.
//
// Lưu ý: đây vẫn là WP-Cron "giả lập" (pseudo-cron) — chỉ thực sự chạy khi có
// request tới site (page view, hoặc real system cron gọi wp-cron.php nếu site
// đã cấu hình DISABLE_WP_CRON). Trên site gần như không có traffic, tiến trình
// có thể chạy chậm hơn 5 giây/đợt — lệnh WP-CLI vẫn luôn có sẵn để chạy tay,
// đẩy nhanh bất cứ lúc nào.
// ─────────────────────────────────────────────────────────────────────────

define( 'INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK', 'init_plugin_suite_live_search_fulltext_cron_batch' );

// Mỗi lần settings được lưu (kể cả lần đầu option được tạo), kiểm tra xem có
// cần khởi động (hoặc nối lại) tiến trình lập chỉ mục nền hay không.
add_action( 'update_option_' . INIT_PLUGIN_SUITE_LS_OPTION, 'init_plugin_suite_live_search_fulltext_maybe_start_cron', 10, 2 );
add_action( 'add_option_' . INIT_PLUGIN_SUITE_LS_OPTION, 'init_plugin_suite_live_search_fulltext_maybe_start_cron_on_add', 10, 2 );

function init_plugin_suite_live_search_fulltext_maybe_start_cron_on_add( $option, $value ) {
    init_plugin_suite_live_search_fulltext_maybe_start_cron( [], $value );
}

function init_plugin_suite_live_search_fulltext_maybe_start_cron( $old_value, $value ) {
    if ( empty( $value['use_fulltext_index'] ) ) {
        return;
    }

    if ( ! init_plugin_suite_live_search_fulltext_is_supported() ) {
        return;
    }

    // Đã build xong ít nhất 1 lần rồi — các hook save_post/before_delete_post
    // lo phần đồng bộ tiếp theo, không cần chạy lại full reindex.
    if ( get_option( 'init_plugin_suite_live_search_fulltext_indexed', '' ) ) {
        return;
    }

    // Đã có 1 tiến trình đang chạy (hoặc đang chờ tới lượt) — không xếp lịch trùng.
    if ( wp_next_scheduled( INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK ) ) {
        return;
    }

    delete_option( 'init_plugin_suite_live_search_fulltext_cron_state' );
    wp_schedule_single_event( time(), INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK );
}

add_action( INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK, 'init_plugin_suite_live_search_fulltext_cron_run_batch' );

// ─────────────────────────────────────────────────────────────────────────
// "Đang chạy hay không" — dùng cho UI (Settings > General).
//
// CHỈ dựa vào wp_next_scheduled() là không đủ: khi wp-cron.php tới giờ chạy
// 1 event, nó UNSCHEDULE event đó TRƯỚC KHI gọi callback (để callback tự
// schedule lại lượt kế tiếp nếu cần). Nghĩa là trong suốt thời gian 1 batch
// đang thực sự xử lý (WP_Query + upsert cho tới 300 bài, có thể mất vài giây
// trên host chậm), wp_next_scheduled() trả về false dù cron KHÔNG hề đứng
// yên — chỉ là đang ở giữa 2 lượt. Nếu admin load lại trang đúng lúc đó, họ
// sẽ thấy báo lỗi "không chạy" gây hiểu lầm dù mọi thứ vẫn ổn (F5 lại sau đó
// thì đã xong hoặc đã lên lịch lại, nên trông như "tự khỏi").
//
// Vì vậy: coi là "đang chạy" khi (a) đang thực sự xử lý 1 batch (transient
// lock còn hiệu lực), HOẶC (b) có lịch trong "grace window" gần đây (chưa
// hoặc mới vừa tới giờ, đang chờ pseudo-cron nhận request tiếp theo để chạy).
// Chỉ khi ĐÃ QUÁ HẠN LÂU mà event vẫn còn nằm trong hàng đợi — nghĩa là
// pseudo-cron chưa từng có cơ hội chạy nó — mới thực sự coi là "không chạy"
// và hiển thị nút "Run Now".
function init_plugin_suite_live_search_fulltext_cron_is_active() {
    if ( get_transient( 'init_plugin_suite_live_search_fulltext_cron_lock' ) ) {
        return true;
    }

    $next = wp_next_scheduled( INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK );
    if ( ! $next ) {
        return false;
    }

    $grace = (int) apply_filters( 'init_plugin_suite_live_search_fulltext_cron_stall_grace', 60 );

    return $next > ( time() - $grace );
}

// "Run Now" — link thủ công ở Settings > General, chạy trực tiếp trong chính
// request admin đó (KHÔNG qua WP-Cron/pseudo-cron), dành cho site tắt hẳn
// WP-Cron (define('DISABLE_WP_CRON', true)) hoặc pseudo-cron vì lý do gì đó
// không tự chạy được. Gọi lặp lại cùng 1 hàm xử lý batch mà WP-Cron vẫn dùng
// (an toàn để gọi liên tiếp — hàm tự giải phóng lock ngay khi return), trong
// tối đa ~20 giây hoặc tới khi xong, rồi để phần còn lại (nếu có) tiếp tục
// qua WP-Cron như bình thường.
add_action( 'admin_init', 'init_plugin_suite_live_search_fulltext_handle_force_run_request' );

function init_plugin_suite_live_search_fulltext_handle_force_run_request() {
    if ( ! isset( $_GET['init_ls_force_fulltext'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    check_admin_referer( 'init_ls_force_fulltext' );

    if ( init_plugin_suite_live_search_fulltext_is_supported() ) {
        // Tự giới hạn bằng deadline theo wall-clock bên dưới (không dùng
        // set_time_limit() — hàm này bị nhiều host chặn/hạn chế và không
        // đáng tin cậy giữa các môi trường, WP.org cũng khuyến cáo tránh dùng).

        $deadline   = microtime( true ) + 20;
        $iterations = 0;

        do {
            init_plugin_suite_live_search_fulltext_cron_run_batch();
            $iterations++;
        } while (
            $iterations < 100
            && microtime( true ) < $deadline
            && ! get_option( 'init_plugin_suite_live_search_fulltext_indexed', '' )
            && wp_next_scheduled( INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK )
        );
    }

    wp_safe_redirect( remove_query_arg( [ 'init_ls_force_fulltext', '_wpnonce' ] ) );
    exit;
}

function init_plugin_suite_live_search_fulltext_cron_run_batch() {
    // Chặn 2 lượt cron chạy chồng nhau (2 request gần như đồng thời cùng spawn
    // cron). Không bắt buộc để đúng — REPLACE INTO vốn đã idempotent theo
    // post_id — chỉ là tối ưu, tránh xử lý trùng lãng phí.
    if ( get_transient( 'init_plugin_suite_live_search_fulltext_cron_lock' ) ) {
        return;
    }
    // TTL 60s (không phải 30s): batch tới 300 bài trên host chậm/nội dung
    // nặng có thể mất hơn 30 giây để xử lý xong, nên transient hết hạn giữa
    // chừng sẽ khiến is_active() trả về sai (báo "không chạy" trong khi vẫn
    // đang chạy). Chỉ là cận trên an toàn — không ảnh hưởng gì tới việc
    // release lock (đã tự giải phóng ngay khi hàm return) hay batch_size.
    set_transient( 'init_plugin_suite_live_search_fulltext_cron_lock', 1, 60 );

    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    if ( empty( $options['use_fulltext_index'] ) || ! init_plugin_suite_live_search_fulltext_is_supported() ) {
        // Option đã bị tắt lại, hoặc server hết hỗ trợ giữa chừng — dừng hẳn.
        delete_option( 'init_plugin_suite_live_search_fulltext_cron_state' );
        delete_transient( 'init_plugin_suite_live_search_fulltext_cron_lock' );
        return;
    }

    global $wpdb;
    $table      = init_plugin_suite_live_search_fulltext_table();
    $batch_size = (int) apply_filters( 'init_plugin_suite_live_search_fulltext_cron_batch_size', 300 );
    $post_types = init_plugin_suite_live_search_fulltext_get_indexable_post_types();

    $state = get_option( 'init_plugin_suite_live_search_fulltext_cron_state', false );
    if ( ! is_array( $state ) || empty( $state['paged'] ) ) {
        // Lượt đầu tiên của 1 lần build mới — dọn sạch bảng để tránh sót dữ
        // liệu cũ (bài đã unpublish trong khi tính năng chưa từng bật, v.v.).
        // $table is derived from $wpdb->prefix only, never from user input.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query( "TRUNCATE TABLE {$table}" );
        $state = [ 'paged' => 1, 'total' => 0 ];
    }

    $query = new WP_Query( [
        'post_type'      => $post_types,
        'post_status'    => 'publish',
        'posts_per_page' => $batch_size,
        'paged'          => $state['paged'],
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ] );

    foreach ( $query->posts as $post ) {
        init_plugin_suite_live_search_fulltext_upsert_row( $post );
    }

    $state['total'] += count( $query->posts );

    if ( count( $query->posts ) === $batch_size ) {
        // Còn bài viết chưa xử lý — hẹn đợt kế tiếp sau 5 giây. Schedule
        // TRƯỚC khi xoá lock (không phải sau) để không có khoảnh khắc nào
        // cả 2 tín hiệu (lock + wp_next_scheduled) cùng "trống" — is_active()
        // nhờ vậy luôn thấy ít nhất 1 tín hiệu đúng, kể cả khi UI đọc đúng
        // lúc giao nhau giữa 2 lượt batch.
        $state['paged']++;
        update_option( 'init_plugin_suite_live_search_fulltext_cron_state', $state, false );

        if ( ! wp_next_scheduled( INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK ) ) {
            wp_schedule_single_event( time() + 5, INIT_PLUGIN_SUITE_LS_FULLTEXT_CRON_HOOK );
        }

        delete_transient( 'init_plugin_suite_live_search_fulltext_cron_lock' );
    } else {
        // Hết bài viết để xử lý — hoàn tất.
        update_option( 'init_plugin_suite_live_search_fulltext_indexed', current_time( 'mysql' ), false );
        delete_option( 'init_plugin_suite_live_search_fulltext_cron_state' );
        delete_transient( 'init_plugin_suite_live_search_fulltext_cron_lock' );
    }
}
