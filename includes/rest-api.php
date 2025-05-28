<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('rest_api_init', function () {
    register_rest_route('initlise/v1', '/search', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_search',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/id/(?P<id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_get_post_by_id',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/recent', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_recent',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/date', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_date',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/tax', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_tax_query',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/related', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_related',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/read', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_get_reading_posts',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/random', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_random',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/taxonomies', [
        'methods' => 'GET',
        'callback' => 'init_plugin_suite_live_search_get_taxonomies_list',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('initlise/v1', '/product', [
        'methods'             => 'GET',
        'callback'            => 'init_plugin_suite_live_search_products',
        'permission_callback' => '__return_true',
    ]);
});

// Handle the main search REST endpoint, applies fallback, bigram, SEO, and ACF logic.
function init_plugin_suite_live_search_search($request) {
    $term = $request->get_param('term');
    $args = [
        'force_mode' => $request->get_param('force_mode'),
        'exclude'    => $request->get_param('exclude'),
    ];
    return rest_ensure_response(init_plugin_suite_live_search_get_results($term, $args));
}

// Execute internal search logic and return final result list.
function init_plugin_suite_live_search_get_results($term, $args = []) {
    global $wpdb;

    if (empty($args['lang'])) {
        if (function_exists('pll_current_language')) {
            $args['lang'] = pll_current_language();
        } elseif (function_exists('apply_filters')) {
            $args['lang'] = apply_filters('wpml_current_language', null);
        } else {
            $args['lang'] = get_locale();
        }
    }

    $term = sanitize_text_field($term);
    $options = get_option('init_plugin_suite_live_search_settings', []);

    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];
    if (!empty($args['post_types']) && is_array($args['post_types'])) {
        $post_types = array_map('sanitize_key', $args['post_types']);
    }

    if (empty($post_types)) return [];

    $limit = (!empty($args['limit']) && is_numeric($args['limit']))
        ? (int)$args['limit']
        : (
            (!empty($options['max_results']) && is_numeric($options['max_results']))
                ? (int)$options['max_results']
                : 10
        );

    $paged = (!empty($args['paged']) && is_numeric($args['paged']) && $args['paged'] > 1) ? (int)$args['paged'] : 1;
    $offset = ($paged - 1) * $limit;

    if (!empty($args['force_ids']) && is_array($args['force_ids'])) {
        $post_ids = array_filter(array_map('absint', $args['force_ids']), function($id) {
            return $id > 0;
        });
    } else {
        if (!$term || strlen($term) < 2) return [];

        $search_mode = !empty($args['force_mode'])
            ? $args['force_mode']
            : (!empty($options['search_mode']) ? $options['search_mode'] : 'title');

        $like = '%' . $wpdb->esc_like($term) . '%';
        $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));

        $cache_key = 'init_plugin_suite_live_search_' . md5($term . serialize($post_types) . $search_mode . $limit . $paged);
        $post_ids = ($paged === 1) ? wp_cache_get($cache_key, 'init_plugin_suite_live_search') : false;

        if ($post_ids === false) {
            $post_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                $wpdb, $term, $like, $post_types, $placeholders, $search_mode, 200
            );

            $enable_fallback = isset($args['enable_fallback'])
                ? (bool)$args['enable_fallback']
                : (!isset($options['enable_fallback']) || $options['enable_fallback']);

            $enable_fallback = apply_filters(
                'init_plugin_suite_live_search_enable_fallback',
                $enable_fallback,
                $term,
                $args
            );

            if ($enable_fallback) {
                $words = preg_split('/\s+/', trim($term));
                $cut_attempts = 0;
                while (count($post_ids) < floor($limit / 2) && count($words) > 3 && $cut_attempts < 4) {
                    array_pop($words);
                    $new_term = implode(' ', $words);
                    $like_new = '%' . $wpdb->esc_like($new_term) . '%';
                    $post_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                        $wpdb, $new_term, $like_new, $post_types, $placeholders, $search_mode, 200
                    );
                    $cut_attempts++;
                }

                if (count($post_ids) < floor($limit / 2) && str_word_count($term) >= 3) {
                    $bi_terms = array_unique(init_plugin_suite_live_search_generate_bigrams($term));
                    foreach (array_slice($bi_terms, 0, 10) as $bi_term) {
                        $like_bi = '%' . $wpdb->esc_like($bi_term) . '%';
                        $more_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                            $wpdb, $bi_term, $like_bi, $post_types, $placeholders, $search_mode, 200
                        );
                        $post_ids = array_merge($post_ids, $more_ids);
                    }
                    $post_ids = array_unique($post_ids);
                }
            }

            if (function_exists('get_field') && !empty($options['acf_search_fields'])) {
                $acf_fields = array_map('trim', explode(',', $options['acf_search_fields']));
                $acf_fields = array_filter($acf_fields, function($f) { return $f !== ''; });

                if (!empty($acf_fields)) {
                    $acf_like = '%' . $wpdb->esc_like($term) . '%';

                    $placeholders = implode(', ', array_fill(0, count($acf_fields), '%s'));

                    $acf_ids = $wpdb->get_col($wpdb->prepare(
                        "
                        SELECT pm.post_id
                        FROM {$wpdb->postmeta} pm
                        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                        WHERE pm.meta_key IN ($placeholders)
                        AND pm.meta_value LIKE %s
                        AND p.post_status = 'publish'
                        LIMIT 200
                        ",
                        ...array_merge($acf_fields, [$acf_like])
                    ));

                    $post_ids = array_unique(array_merge($post_ids, array_map('intval', $acf_ids)));
                }
            }

            $post_ids = apply_filters('init_plugin_suite_live_search_post_ids', $post_ids, $term, $args);
            $post_ids = apply_filters('init_plugin_suite_live_search_filter_lang', $post_ids, $term, $args);

            if ($paged === 1) {
                wp_cache_set($cache_key, $post_ids, 'init_plugin_suite_live_search', 300);
            }
        }
    }

    if (empty($post_ids)) return [];

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    $keywords = $term ? [$term] : [];
    if ($term && str_word_count($term) >= 3) {
        $keywords = array_merge($keywords, init_plugin_suite_live_search_generate_bigrams($term));
        $keywords = array_unique($keywords);
    }

    $results = [];
    $post_ids_page = array_slice($post_ids, $offset, $limit);

    $results = init_plugin_suite_live_search_build_result_list(
        $post_ids_page,
        $args,
        $term,
        $keywords,
        $default_thumb
    );

    return apply_filters('init_plugin_suite_live_search_results', $results, $post_ids, $term, $args);
}

// Retrieve post IDs by specific search mode: title, tag, excerpt, etc.
function init_plugin_suite_live_search_get_post_ids_by_mode($wpdb, $term, $like, $post_types, $placeholders, $search_mode, $limit) {
    $options = get_option('init_plugin_suite_live_search_settings', []);
    $enable_seo_fields = !empty($options['seo_search_fields_enabled']);
    $seo_ids = [];

    if ($enable_seo_fields && in_array($search_mode, ['title', 'title_tag', 'title_excerpt'], true)) {
        $seo_title_keys = [
            '_yoast_wpseo_title',
            'rank_math_title',
            '_aioseo_title',
            '_genesis_title',
            '_seopress_titles_title',
        ];

        $seo_description_keys = [
            '_yoast_wpseo_metadesc',
            'rank_math_description',
            '_aioseo_description',
            '_genesis_description',
            '_seopress_titles_desc',
        ];

        $seo_meta_keys = ($search_mode === 'title_excerpt')
            ? array_merge($seo_title_keys, $seo_description_keys)
            : $seo_title_keys;

        $seo_meta_keys = apply_filters('init_plugin_suite_live_search_seo_meta_keys', $seo_meta_keys);

        $meta_placeholders = implode(', ', array_fill(0, count($seo_meta_keys), '%s'));
        $seo_like = '%' . $wpdb->esc_like($term) . '%';

        $seo_ids = $wpdb->get_col($wpdb->prepare(
            "
            SELECT DISTINCT pm.post_id
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key IN ($meta_placeholders)
            AND pm.meta_value LIKE %s
            AND p.post_status = 'publish'
            AND p.post_type IN ($placeholders)
            LIMIT %d
            ",
            ...array_merge($seo_meta_keys, [$seo_like, ...$post_types, $limit])
        ));
    }

    switch ($search_mode) {
        case 'title':
            $ids_title = $wpdb->get_col($wpdb->prepare(
                "
                SELECT ID FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                AND post_type IN ($placeholders)
                AND post_title LIKE %s
                ORDER BY LOCATE(%s, post_title), post_date DESC
                LIMIT %d
                ",
                ...array_merge($post_types, [$like, $term, $limit])
            ));

            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2], 'title');
            return init_plugin_suite_live_search_ranked_merge_weighted(
                [$ids_title, $seo_ids],
                $weights
            );

        case 'title_tag':
            $ids_title = $wpdb->get_col($wpdb->prepare(
                "
                SELECT ID FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                AND post_type IN ($placeholders)
                AND post_title LIKE %s
                ORDER BY LOCATE(%s, post_title), post_date DESC
                LIMIT %d
                ",
                ...array_merge($post_types, [$like, $term, $limit])
            ));

            $ids_tag = $wpdb->get_col($wpdb->prepare(
                "
                SELECT DISTINCT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE p.post_status = 'publish'
                AND p.post_type IN ($placeholders)
                AND tt.taxonomy = 'post_tag'
                AND t.name LIKE %s
                ORDER BY p.post_date DESC
                LIMIT %d
                ",
                ...array_merge($post_types, [$like, $limit])
            ));

            $ids_tag_extra = [];
            $words = preg_split('/\\s+/', $term);
            if (count($words) >= 1 && count($words) <= 2) {
                foreach ($words as $word) {
                    $exact_word = trim($word);
                    if ($exact_word !== '') {
                        $result = $wpdb->get_col($wpdb->prepare(
                            "
                            SELECT DISTINCT p.ID
                            FROM {$wpdb->posts} p
                            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                            INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                            WHERE p.post_status = 'publish'
                            AND p.post_type IN ($placeholders)
                            AND tt.taxonomy = 'post_tag'
                            AND t.name = %s
                            ORDER BY p.post_date DESC
                            LIMIT %d
                            ",
                            ...array_merge($post_types, [$exact_word, $limit])
                        ));
                        $ids_tag_extra = array_merge($ids_tag_extra, $result);
                    }
                }
            }

            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2, 1, 1], 'title_tag');
            return init_plugin_suite_live_search_ranked_merge_weighted(
                [$ids_title, $seo_ids, $ids_tag, $ids_tag_extra],
                $weights
            );

        case 'title_excerpt':
            $ids = $wpdb->get_col($wpdb->prepare(
                "
                SELECT ID FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                AND post_type IN ($placeholders)
                AND (post_title LIKE %s OR post_excerpt LIKE %s)
                ORDER BY LOCATE(%s, post_title), post_date DESC
                LIMIT %d
                ",
                ...array_merge($post_types, [$like, $like, $term, $limit])
            ));
            
            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2], 'title_excerpt');
            return init_plugin_suite_live_search_ranked_merge_weighted(
                [$ids, $seo_ids],
                $weights
            );

        case 'title_content':
        default:
            return $wpdb->get_col($wpdb->prepare(
                "
                SELECT ID FROM {$wpdb->posts}
                WHERE post_status = 'publish'
                AND post_type IN ($placeholders)
                AND (post_title LIKE %s OR post_excerpt LIKE %s OR post_content LIKE %s)
                ORDER BY LOCATE(%s, post_title), post_date DESC
                LIMIT %d
                ",
                ...array_merge($post_types, [$like, $like, $like, $term, $limit])
            ));
    }
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
    $options = get_option('init_plugin_suite_live_search_settings', []);
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

    $options = get_option('init_plugin_suite_live_search_settings', []);
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
    $taxonomy   = sanitize_key($request->get_param('taxonomy'));
    $term_input = sanitize_text_field($request->get_param('term'));

    if ($taxonomy === 'tag') {
        $taxonomy = 'post_tag';
    }

    if (!$taxonomy || !$term_input || !taxonomy_exists($taxonomy)) {
        return rest_ensure_response([]);
    }

    $term_obj = get_term_by(is_numeric($term_input) ? 'id' : 'slug', $term_input, $taxonomy);
    if (!$term_obj || is_wp_error($term_obj)) {
        return rest_ensure_response([]);
    }

    $term_id = $term_obj->term_id;

    $options = get_option('init_plugin_suite_live_search_settings', []);
    $post_types = !empty($options['post_types']) && is_array($options['post_types'])
        ? array_map('sanitize_key', $options['post_types'])
        : ['post'];

    $per_page = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int) $options['max_results']
        : 10;

    $paged = max(1, (int) $request->get_param('page'));

    $cache_key = 'ils_tax_' . $taxonomy . '_p' . $paged . '_' . md5($term_id . serialize($post_types) . $per_page);
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
        'no_found_rows'       => true,
        'fields'              => 'ids',
        'tax_query'           => [[
            'taxonomy' => $taxonomy,
            'field'    => 'term_id',
            'terms'    => $term_id,
        ]],
    ];

    $args = apply_filters('init_plugin_suite_live_search_query_args', $args, 'tax', $request);

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
    $options = get_option('init_plugin_suite_live_search_settings', []);

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

    $options = get_option('init_plugin_suite_live_search_settings', []);
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

    $options = get_option('init_plugin_suite_live_search_settings', []);
    $per_page = (!empty($options['max_results']) && is_numeric($options['max_results']) && $options['max_results'] > 0)
        ? (int) $options['max_results']
        : 10;

    $paged     = max(1, (int) $request->get_param('page'));
    $term      = sanitize_text_field($request->get_param('term'));
    $on_sale   = (bool) $request->get_param('on_sale');
    $in_stock  = (bool) $request->get_param('in_stock');
    $sku       = sanitize_text_field($request->get_param('sku'));
    $min_price = is_numeric($request->get_param('min_price')) ? floatval($request->get_param('min_price')) : null;
    $max_price = is_numeric($request->get_param('max_price')) ? floatval($request->get_param('max_price')) : null;

    $cache_key = 'ils_product_' . md5(json_encode([
        'term' => $term,
        'sku' => $sku,
        'on_sale' => $on_sale,
        'in_stock' => $in_stock,
        'min' => $min_price,
        'max' => $max_price,
        'paged' => $paged,
        'per_page' => $per_page
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
        $args['meta_query'] = [
            'relation' => 'AND',
            ...$meta_query
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
