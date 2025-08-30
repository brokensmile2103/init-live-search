<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function () {
    $ns = INIT_PLUGIN_SUITE_LS_NAMESPACE;

    register_rest_route($ns, '/search', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_search',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/id/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_get_post_by_id',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/recent', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_recent',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/date', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_date',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/tax', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_tax_query',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/related', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_related',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/read', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_get_reading_posts',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/random', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_random',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/taxonomies', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_get_taxonomies_list',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/product', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_products',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route($ns, '/coupon', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_coupon',
        'permission_callback' => '__return_true',
    ]);
});

// Handle the main search REST endpoint, applies fallback, bigram, SEO, and ACF logic.
function init_plugin_suite_live_search_search($request) {
    $term = $request->get_param('term');
    $args = [
        'force_mode'  => $request->get_param('force_mode'),
        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
        'exclude'     => $request->get_param('exclude'),
        'no_fallback' => $request->get_param('no_fallback') ? true : false,
    ];
    return rest_ensure_response(init_plugin_suite_live_search_get_results($term, $args));
}

// Return the permalink of a post given its ID.
function init_plugin_suite_live_search_get_post_by_id($request) {
    $id = absint($request['id']);
    if (!$id || get_post_status($id) !== 'publish') {
        return rest_ensure_response([]);
    }

    return rest_ensure_response(apply_filters('init_plugin_suite_live_search_post_by_id', [
        'url' => get_permalink($id),
    ], $id));
}

// Return latest posts for `/recent` slash command.
function init_plugin_suite_live_search_recent($request) {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $per_page = !empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0
        ? (int) $options['max_results']
        : 10;

    $paged = max(1, (int) $request->get_param('page'));

    $cache_key = 'ils_recent_p' . $paged . '_' . md5(serialize($post_types) . $per_page);
    $results = wp_cache_get($cache_key, 'init_plugin_suite_live_search');

    if ($results !== false) {
        return rest_ensure_response($results);
    }

    $args = [
        'post_type'           => $post_types,
        'posts_per_page'      => $per_page,
        'paged'               => $paged,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'fields'              => 'ids',
    ];

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'recent', $request);

    $query = new WP_Query($args);

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    $results = [];

    $results = init_plugin_suite_live_search_build_result_list(
        $query->posts,
        [],
        '',
        [],
        $default_thumb
    );

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);

    return rest_ensure_response($results);
}

// Return posts by parsed date string (year, month, day).
function init_plugin_suite_live_search_date($request) {
    $value = sanitize_text_field($request->get_param('value'));
    if (!$value) return rest_ensure_response([]);

    $date_args = init_plugin_suite_live_search_parse_date_value($value);
    if (!$date_args) return rest_ensure_response([]);

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $per_page = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int) $options['max_results']
        : 10;

    $paged = max(1, (int) $request->get_param('page'));

    $cache_key = 'ils_date_p' . $paged . '_' . md5($value . serialize($post_types) . $per_page);
    $cached = wp_cache_get($cache_key, 'init_plugin_suite_live_search');
    if ($cached !== false) return rest_ensure_response($cached);

    $args = array_merge([
        'post_type'           => $post_types,
        'posts_per_page'      => $per_page,
        'paged'               => $paged,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'fields'              => 'ids',
    ], $date_args);

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'date', $request);

    $query = new WP_Query($args);

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    $results = [];

    $results = init_plugin_suite_live_search_build_result_list(
        $query->posts,
        [],
        '',
        [],
        $default_thumb
    );

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    return rest_ensure_response($results);
}

// Return posts filtered by taxonomy term (e.g., category, tag).
function init_plugin_suite_live_search_tax_query($request) {
    $taxonomy = sanitize_key($request->get_param('taxonomy'));
    $term_raw = sanitize_text_field($request->get_param('term'));
    $term_input = strtolower(str_replace('+', ' ', $term_raw));

    // Chuẩn hóa taxonomy 'tag' → 'post_tag'
    if ($taxonomy === 'tag') {
        $taxonomy = 'post_tag';
    }

    if (!$taxonomy || !$term_input || !taxonomy_exists($taxonomy)) {
        return rest_ensure_response([]);
    }

    // Parse slug thành mảng
    $slugs = preg_split('/[\s,]+/', $term_input, -1, PREG_SPLIT_NO_EMPTY);
    if (empty($slugs)) {
        return rest_ensure_response([]);
    }

    // Lấy term_id từ slug
    $term_ids = array_filter(array_map(function ($slug) use ($taxonomy) {
        $term = get_term_by(is_numeric($slug) ? 'id' : 'slug', $slug, $taxonomy);
        return ($term && !is_wp_error($term)) ? (int) $term->term_id : null;
    }, $slugs));

    if (empty($term_ids)) {
        return rest_ensure_response([]);
    }

    // Lấy cấu hình
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $per_page = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int) $options['max_results']
        : 10;

    $paged = max(1, (int) $request->get_param('page'));

    // Cache
    $cache_key = 'ils_tax_' . $taxonomy . '_p' . $paged . '_' . md5(serialize($term_ids) . serialize($post_types) . $per_page);
    $results = wp_cache_get($cache_key, 'init_plugin_suite_live_search');
    if ($results !== false) {
        return rest_ensure_response($results);
    }

    // tax_query AND
    $args = [
        'post_type'           => $post_types,
        'posts_per_page'      => $per_page,
        'paged'               => $paged,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'fields'              => 'ids',
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        'tax_query'           => [[
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_ids,
            'operator' => 'AND',
        ]],
    ];

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'tax', $request);
    $query = new WP_Query($args);

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    $results = init_plugin_suite_live_search_build_result_list(
        $query->posts,
        [],
        '',
        [],
        $default_thumb
    );

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    return rest_ensure_response($results);
}

// Return related posts by analyzing title similarity.
function init_plugin_suite_live_search_related($request) {
    $raw = sanitize_text_field($request->get_param('title'));
    if (!$raw || strlen($raw) < 3) return rest_ensure_response([]);

    $clean = wp_strip_all_tags($raw);
    $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $clean = preg_replace('/\s*[-|–|—]\s*[^|–—-]+$/u', '', $clean);
    $clean = trim(preg_replace('/[^\p{L}\p{N}\s]+/u', '', $clean));
    if (strlen($clean) < 3) return rest_ensure_response([]);

    $exclude_id = absint($request->get_param('exclude'));
    $paged      = max(1, (int) $request->get_param('page'));
    $can_cache  = ($exclude_id === 0 && $paged === 1);

    $cache_key = 'ils_related_p' . $paged . '_' . md5($clean);
    $results = $can_cache ? wp_cache_get($cache_key, 'init_plugin_suite_live_search') : false;

    if ($can_cache && $results !== false) {
        return rest_ensure_response($results);
    }

    $results = init_plugin_suite_live_search_get_results($clean, [
        // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
        'exclude'    => $exclude_id,
        'paged'      => $paged,
        'lang'       => init_plugin_suite_live_search_detect_lang(),
    ]);

    if ($can_cache) {
        wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    }

    return rest_ensure_response($results);
}

// Return posts previously read, identified by `ids` param.
function init_plugin_suite_live_search_get_reading_posts($request) {
    $ids = sanitize_text_field($request->get_param('ids'));
    $ids = array_filter(array_map('absint', explode(',', $ids)));
    if (empty($ids)) return rest_ensure_response([]);

    $ids = array_slice($ids, 0, 10);

    return rest_ensure_response(init_plugin_suite_live_search_get_results('', [
        'force_ids'  => $ids,
        'post_types' => ['any'],
        'limit'      => 10,
        'lang'       => init_plugin_suite_live_search_detect_lang(),
    ]));
}

// Return a random post URL from allowed post types.
function init_plugin_suite_live_search_random($request) {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);

    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $args = [
        'post_type'           => $post_types,
        'posts_per_page'      => 1,
        'post_status'         => 'publish',
        'orderby'             => 'rand',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'random', $request);

    $query = new WP_Query($args);
    if (empty($query->posts)) return rest_ensure_response([]);

    $post_id = $query->posts[0]->ID;

    return rest_ensure_response([
        'url' => get_permalink($post_id),
    ]);
}

// Return a list of taxonomy terms with count and link.
function init_plugin_suite_live_search_get_taxonomies_list($request) {
    $taxonomy = sanitize_key($request->get_param('taxonomy'));
    if (!in_array($taxonomy, ['category', 'post_tag'])) {
        return rest_ensure_response([]);
    }

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $limit = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int)$options['max_results'] * 2
        : 20;

    $cache_key = "ils_taxonomies_{$taxonomy}_{$limit}";
    $cached = wp_cache_get($cache_key, 'init_plugin_suite_live_search');
    if ($cached !== false) {
        return rest_ensure_response($cached);
    }

    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
        'number'     => $limit,
    ]);

    if (is_wp_error($terms) || empty($terms)) {
        return rest_ensure_response([]);
    }

    $results = [];

    foreach ($terms as $term) {
        $results[] = [
            'name'  => $term->name,
            'slug'  => $term->slug,
            'count' => $term->count,
            'url'   => get_term_link($term),
        ];
    }

    $ttl = apply_filters('init_plugin_suite_live_search_taxonomy_cache_ttl', 300, $taxonomy, $limit);
    if ($ttl > 0) {
        wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', $ttl);
    }

    return rest_ensure_response($results);
}

// Fetch WooCommerce products with filters: SKU, price, stock, sale.
function init_plugin_suite_live_search_products($request) {
    if (!function_exists('wc_get_product')) {
        return rest_ensure_response([]);
    }

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $per_page = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int) $options['max_results']
        : 10;

    $paged        = max(1, (int) $request->get_param('page'));
    $term         = sanitize_text_field($request->get_param('term'));
    $on_sale      = (bool) $request->get_param('on_sale');
    $in_stock     = (bool) $request->get_param('in_stock');
    $sku          = sanitize_text_field($request->get_param('sku'));
    $min_price    = is_numeric($request->get_param('min_price')) ? floatval($request->get_param('min_price')) : null;
    $max_price    = is_numeric($request->get_param('max_price')) ? floatval($request->get_param('max_price')) : null;
    $price_order  = strtolower($request->get_param('price_order'));
    $brand        = sanitize_text_field($request->get_param('brand'));
    $attribute    = sanitize_text_field($request->get_param('attribute'));
    $variation    = sanitize_text_field($request->get_param('variation'));
    $value        = sanitize_title($request->get_param('value'));

    if ($price_order === 'sort') {
        $price_order = 'asc';
    } elseif ($price_order === 'rsort') {
        $price_order = 'desc';
    }

    $cache_key = 'ils_product_' . md5(json_encode([
        'term' => $term,
        'sku' => $sku,
        'on_sale' => $on_sale,
        'in_stock' => $in_stock,
        'min' => $min_price,
        'max' => $max_price,
        'price_order' => $price_order,
        'brand' => $brand,
        'paged' => $paged,
        'per_page' => $per_page,
        'attribute' => $attribute,
        'variation' => $variation,
        'value'     => $value,
    ]));

    $results = wp_cache_get($cache_key, 'init_plugin_suite_live_search');
    if ($results !== false) {
        return rest_ensure_response($results);
    }

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ];

    $meta_query = [];

    if (!empty($term)) {
        $args['s'] = $term;
    }

    if (!empty($sku)) {
        $meta_query[] = [
            'key'     => '_sku',
            'value'   => $sku,
            'compare' => 'LIKE',
        ];
    }

    if ($in_stock) {
        $meta_query[] = [
            'key'     => '_stock_status',
            'value'   => 'instock',
            'compare' => '=',
        ];
    }

    if ($min_price !== null && $max_price !== null) {
        $meta_query[] = [
            'key'     => '_price',
            'value'   => [$min_price, $max_price],
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        ];
    } elseif ($min_price !== null) {
        $meta_query[] = [
            'key'     => '_price',
            'value'   => $min_price,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        ];
    } elseif ($max_price !== null) {
        $meta_query[] = [
            'key'     => '_price',
            'value'   => $max_price,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        ];
    }

    $meta_query[] = [
        'key'     => '_price',
        'compare' => 'EXISTS',
    ];

    if (!empty($meta_query)) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        $args['meta_query'] = [
            'relation' => 'AND',
            ...$meta_query
        ];
    }

    if ($price_order === 'asc' || $price_order === 'desc') {
        $args['orderby'] = 'meta_value_num';
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $args['meta_key'] = '_price';
        $args['order'] = strtoupper($price_order);
    }

    if (!empty($brand)) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $args['tax_query'][] = [
            'taxonomy' => 'product_brand',
            'field'    => 'slug',
            'terms'    => $brand,
        ];
    }

    if (!empty($attribute) && !empty($value)) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $args['tax_query'][] = [
            'taxonomy' => 'pa_' . sanitize_title($attribute),
            'field'    => 'slug',
            'terms'    => $value,
        ];
    }

    if (!empty($variation) && !empty($value)) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        $args['tax_query'][] = [
            'taxonomy' => 'pa_' . sanitize_title($variation),
            'field'    => 'slug',
            'terms'    => $value,
        ];
    }

    if ($on_sale) {
        $product_ids_on_sale = wc_get_product_ids_on_sale();
        $args['post__in'] = !empty($args['post__in'])
            ? array_values(array_intersect($args['post__in'], $product_ids_on_sale))
            : $product_ids_on_sale;

        if (empty($args['post__in'])) {
            return rest_ensure_response([]);
        }
    }

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'product', $request);
    $query = new WP_Query($args);

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    $results = init_plugin_suite_live_search_build_result_list(
        $query->posts,
        [],
        '',
        [],
        $default_thumb
    );

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    return rest_ensure_response($results);
}

// Fetch coupon
function init_plugin_suite_live_search_coupon( $request ) {
    if ( ! class_exists( 'WC_Coupon' ) ) {
        return rest_ensure_response( [] );
    }

    $options  = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $per_page = ( ! empty( $options['max_results'] ) && is_numeric( $options['max_results'] ) && $options['max_results'] > 0 )
        ? (int) $options['max_results']
        : 10;

    $paged     = max( 1, (int) $request->get_param( 'page' ) );
    $cache_key = 'ils_coupon_p' . $paged . '_' . $per_page;
    $results   = wp_cache_get( $cache_key, 'init_plugin_suite_live_search' );

    if ( false !== $results ) {
        return rest_ensure_response( $results );
    }

    $posts = get_posts( [
        'post_type'      => 'shop_coupon',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ] );

    $results = [];

    foreach ( $posts as $post_id ) {
        try {
            $coupon = new WC_Coupon( (int) $post_id );

            $expiry = $coupon->get_date_expires();
            if ( $expiry && $expiry->getTimestamp() < time() ) {
                continue;
            }

            $limit = (int) $coupon->get_usage_limit();
            $used  = (int) $coupon->get_usage_count();
            if ( $limit > 0 && $used >= $limit ) {
                continue;
            }

            $code = (string) $coupon->get_code();
            $desc = (string) $coupon->get_description();

            if ( '' === $desc ) {
                $type   = (string) $coupon->get_discount_type();
                $amount = (float) $coupon->get_amount();

                if ( in_array( $type, [ 'percent', 'percent_product' ], true ) ) {
                    // translators: %s is the discount percentage (e.g. 20 for 20%)
                    $desc = sprintf( __( 'Save %s%%', 'init-live-search' ), wc_format_coupon_amount( $amount ) );
                } else {
                    // translators: %s is the monetary discount amount (e.g. $10)
                    $desc = sprintf( __( 'Save %s', 'init-live-search' ), wc_price( $amount ) );
                }
            }

            $meta = [];
            if ( $limit > 0 ) {
                // translators: %d is the number of remaining uses
                $meta[] = sprintf( __( 'Remaining: %d uses', 'init-live-search' ), max( 0, $limit - $used ) );
            } else {
                $meta[] = __( 'Unlimited uses', 'init-live-search' );
            }

            if ( $expiry ) {
                // translators: %s is the expiration date
                $meta[] = sprintf( __( 'Expires on: %s', 'init-live-search' ), $expiry->date_i18n( function_exists( 'wc_date_format' ) ? wc_date_format() : get_option( 'date_format' ) ) );
            } else {
                $meta[] = __( 'No expiration', 'init-live-search' );
            }

            $results[] = [
                'type'  => 'coupon',
                'title' => strtoupper( $code ),
                'desc'  => $desc,
                'meta'  => $meta,
                'copy'  => $code,
            ];
        } catch ( \Throwable $t ) {
            continue; // Không log gì cả, bỏ qua coupon lỗi
        }
    }

    wp_cache_set( $cache_key, $results, 'init_plugin_suite_live_search', 300 );
    return rest_ensure_response( $results );
}
