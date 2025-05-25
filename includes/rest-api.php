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
});

function init_plugin_suite_live_search_search($request) {
    $term = $request->get_param('term');
    $args = [
        'force_mode' => $request->get_param('force_mode'),
        'exclude'    => $request->get_param('exclude'),
    ];
    return rest_ensure_response(init_plugin_suite_live_search_get_results($term, $args));
}

function init_plugin_suite_live_search_get_results($term, $args = []) {
    global $wpdb;

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
        $post_ids = array_map('absint', $args['force_ids']);
    } else {
        if (!$term || strlen($term) < 2) return [];

        $search_mode = !empty($args['force_mode'])
            ? $args['force_mode']
            : (!empty($options['search_mode']) ? $options['search_mode'] : 'title');

        $like = '%' . $wpdb->esc_like($term) . '%';
        $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));

        $cache_key = 'init_plugin_suite_live_search_' . md5($term . serialize($post_types) . $search_mode . $limit);
        $post_ids = ($paged === 1) ? wp_cache_get($cache_key, 'init_plugin_suite_live_search') : false;

        if ($post_ids === false) {
            $post_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                $wpdb, $term, $like, $post_types, $placeholders, $search_mode, 200
            );

            $enable_fallback = isset($args['enable_fallback'])
                ? (bool)$args['enable_fallback']
                : (!isset($options['enable_fallback']) || $options['enable_fallback']);

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
                    $bi_terms = init_plugin_suite_live_search_generate_bigrams($term);
                    foreach ($bi_terms as $bi_term) {
                        $like_bi = '%' . $wpdb->esc_like($bi_term) . '%';
                        $more_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                            $wpdb, $bi_term, $like_bi, $post_types, $placeholders, $search_mode, 200
                        );
                        $post_ids = array_merge($post_ids, $more_ids);
                    }
                    $post_ids = array_unique($post_ids);
                }
            }

            $post_ids = apply_filters('init_plugin_suite_live_search_post_ids', $post_ids, $term, null);
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

    foreach ($post_ids_page as $post_id) {
        if (!empty($args['exclude']) && (int)$post_id === (int)$args['exclude']) {
            continue;
        }

        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : $default_thumb;

        $post_type_slug = get_post_type($post_id);
        $post_type_obj = get_post_type_object($post_type_slug);
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type_slug;

        $category = get_the_category($post_id);
        $category_name = ($category && !is_wp_error($category)) ? $category[0]->name : '';

        $item = [
            'id'       => $post_id,
            'title'    => init_plugin_suite_live_search_highlight_keyword(esc_html(get_the_title($post_id)), $keywords),
            'url'      => get_permalink($post_id),
            'type'     => $post_type_name,
            'thumb'    => $thumb_url,
            'date'     => get_the_date('d/m/Y', $post_id),
            'category' => apply_filters('init_plugin_suite_live_search_category', $category_name, $post_id),
        ];

        $item = apply_filters('init_plugin_suite_live_search_result_item', $item, $post_id, $term, null);
        $results[] = $item;
    }

    return apply_filters('init_plugin_suite_live_search_results', $results, $post_ids, $term, null);
}

function init_plugin_suite_live_search_get_post_ids_by_mode($wpdb, $term, $like, $post_types, $placeholders, $search_mode, $limit) {
    switch ($search_mode) {
        case 'title':
            return $wpdb->get_col($wpdb->prepare(
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

        case 'title_excerpt':
            return $wpdb->get_col($wpdb->prepare(
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

function init_plugin_suite_live_search_generate_bigrams($term) {
    $words = preg_split('/\s+/', $term);
    $bigrams = [];
    for ($i = 0; $i < count($words) - 1; $i++) {
        $bigrams[] = $words[$i] . ' ' . $words[$i + 1];
    }
    return $bigrams;
}

function init_plugin_suite_live_search_highlight_keyword($text, $keywords) {
    if (empty($text) || empty($keywords)) return $text;

    $remove_accents = function($str) {
        static $patterns = null, $replacements = null;
        if (!$patterns) {
            $patterns = [
                '/[áàảãạăắằẳẵặâấầẩẫậ]/u', '/[ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ]/u',
                '/[éèẻẽẹêếềểễệ]/u', '/[ÉÈẺẼẸÊẾỀỂỄỆ]/u',
                '/[íìỉĩị]/u', '/[ÍÌỈĨỊ]/u',
                '/[óòỏõọôốồổỗộơớờởỡợ]/u', '/[ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ]/u',
                '/[úùủũụưứừửữự]/u', '/[ÚÙỦŨỤƯỨỪỬỮỰ]/u',
                '/[ýỳỷỹỵ]/u', '/[ÝỲỶỸỴ]/u',
                '/[đ]/u', '/[Đ]/u'
            ];
            $replacements = [
                'a','A','e','E','i','I','o','O','u','U','y','Y','d','D'
            ];
        }
        return preg_replace($patterns, $replacements, $str);
    };

    $original_text = $text;
    $text_no_diacritics = $remove_accents(mb_strtolower($text));
    if (is_string($keywords)) {
        $keywords = [$keywords];
    }

    $highlights = [];

    foreach ($keywords as $keyword) {
        $keyword_no_diacritics = $remove_accents(mb_strtolower($keyword));
        if (empty($keyword_no_diacritics)) continue;

        $len = mb_strlen($keyword_no_diacritics);
        $offset = 0;

        while (true) {
            $pos = mb_stripos($text_no_diacritics, $keyword_no_diacritics, $offset);
            if ($pos === false) break;

            $overlap = false;
            foreach ($highlights as $h) {
                if (!($pos + $len <= $h['start'] || $pos >= $h['end'])) {
                    $overlap = true;
                    break;
                }
            }
            if (!$overlap) {
                $highlights[] = ['start' => $pos, 'end' => $pos + $len];
            }
            $offset = $pos + $len;
        }
    }

    if (empty($highlights)) return $text;

    usort($highlights, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    $result = '';
    $last_pos = 0;
    foreach ($highlights as $hl) {
        $result .= mb_substr($original_text, $last_pos, $hl['start'] - $last_pos);
        $result .= '<mark>' . mb_substr($original_text, $hl['start'], $hl['end'] - $hl['start']) . '</mark>';
        $last_pos = $hl['end'];
    }
    $result .= mb_substr($original_text, $last_pos);

    return $result;
}

function init_plugin_suite_live_search_get_post_by_id($request) {
    $id = absint($request['id']);
    if (!$id || get_post_status($id) !== 'publish') {
        return rest_ensure_response([]);
    }

    return rest_ensure_response([
        'url' => get_permalink($id),
    ]);
}

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

    foreach ($query->posts as $post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : $default_thumb;

        $post_type_slug = get_post_type($post_id);
        $post_type_obj = get_post_type_object($post_type_slug);
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type_slug;

        $category = get_the_category($post_id);
        $category_name = ($category && !is_wp_error($category)) ? $category[0]->name : '';

        $results[] = [
            'title'    => get_the_title($post_id),
            'url'      => get_permalink($post_id),
            'type'     => $post_type_name,
            'thumb'    => $thumb_url,
            'date'     => get_the_date('d/m/Y', $post_id),
            'category' => apply_filters('init_plugin_suite_live_search_category', $category_name, $post_id),
        ];
    }

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);

    return rest_ensure_response($results);
}

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

    foreach ($query->posts as $post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : $default_thumb;

        $post_type_slug = get_post_type($post_id);
        $post_type_obj = get_post_type_object($post_type_slug);
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type_slug;

        $category = get_the_category($post_id);
        $category_name = ($category && !is_wp_error($category)) ? $category[0]->name : '';

        $results[] = [
            'title'    => get_the_title($post_id),
            'url'      => get_permalink($post_id),
            'type'     => $post_type_name,
            'thumb'    => $thumb_url,
            'date'     => get_the_date('d/m/Y', $post_id),
            'category' => apply_filters('init_plugin_suite_live_search_category', $category_name, $post_id),
        ];
    }

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    return rest_ensure_response($results);
}

function init_plugin_suite_live_search_parse_date_value($value) {
    if (preg_match('/^\d{4}$/', $value)) {
        return ['year' => (int) $value];
    }

    if (preg_match('/^(\d{4})\/(\d{1,2})$/', $value, $matches)) {
        return ['year' => (int) $matches[1], 'month' => (int) $matches[2]];
    }

    if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $value, $matches)) {
        return [
            'year'  => (int) $matches[1],
            'month' => (int) $matches[2],
            'day'   => (int) $matches[3]
        ];
    }

    return false;
}

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

    foreach ($query->posts as $post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : $default_thumb;

        $post_type_slug = get_post_type($post_id);
        $post_type_obj  = get_post_type_object($post_type_slug);
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type_slug;

        $terms = get_the_terms($post_id, $taxonomy);
        $term_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';

        $results[] = [
            'title'    => get_the_title($post_id),
            'url'      => get_permalink($post_id),
            'type'     => $post_type_name,
            'thumb'    => $thumb_url,
            'date'     => get_the_date('d/m/Y', $post_id),
            'category' => apply_filters('init_plugin_suite_live_search_category', $term_name, $post_id),
        ];
    }

    wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    return rest_ensure_response($results);
}

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
    ]);

    if ($can_cache) {
        wp_cache_set($cache_key, $results, 'init_plugin_suite_live_search', 300);
    }

    return rest_ensure_response($results);
}

function init_plugin_suite_live_search_get_reading_posts($request) {
    $ids = sanitize_text_field($request->get_param('ids'));
    $ids = array_filter(array_map('absint', explode(',', $ids)));
    if (empty($ids)) return rest_ensure_response([]);

    $ids = array_slice($ids, 0, 10);

    return rest_ensure_response(init_plugin_suite_live_search_get_results('', [
        'force_ids'  => $ids,
        'post_types' => ['any'],
        'limit'      => 10,
    ]));
}

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

    $query = new WP_Query($args);
    if (empty($query->posts)) return rest_ensure_response([]);

    $post_id = $query->posts[0]->ID;

    return rest_ensure_response([
        'url' => get_permalink($post_id),
    ]);
}

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