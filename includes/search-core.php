<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Execute internal search logic and return final result list.
function init_plugin_suite_live_search_get_results($term, $args = []) {
    global $wpdb;

    $args['lang'] = $args['lang'] ?? init_plugin_suite_live_search_detect_lang();
    $term = sanitize_text_field($term);
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);

    $post_types = init_plugin_suite_live_search_resolve_post_types($options, $args);
    if (empty($post_types)) return [];

    $limit = init_plugin_suite_live_search_resolve_limit($options, $args);
    $paged = max(1, (int)($args['paged'] ?? 1));
    $offset = ($paged - 1) * $limit;

    if (!empty($args['force_ids'])) {
        $post_ids = array_filter(array_map('absint', (array)$args['force_ids']));
    } else {
        if (!$term || strlen($term) < 2) return [];

        $search_mode = $args['force_mode'] ?? ($options['search_mode'] ?? 'title');
        $like = '%' . $wpdb->esc_like($term) . '%';
        $placeholders = implode(', ', array_fill(0, count($post_types), '%s'));

        $cache_key = 'init_plugin_suite_live_search_' . md5($term . serialize($post_types) . $search_mode . $limit . $paged);
        $post_ids = ($paged === 1) ? wp_cache_get($cache_key, 'init_plugin_suite_live_search') : false;

        if ($post_ids === false) {
            $post_ids = init_plugin_suite_live_search_resolve_post_ids(
                $term, $like, $post_types, $placeholders, $search_mode, $limit, $paged, $options, $args
            );
            if ($paged === 1) {
                wp_cache_set($cache_key, $post_ids, 'init_plugin_suite_live_search', 300);
            }
        }
    }

    if (empty($post_ids)) return [];

    if ( ! empty( $args['exclude'] ) ) {
        $exclude = array_map( 'absint', (array) $args['exclude'] );
        $post_ids = array_values( array_diff( $post_ids, $exclude ) );
    }

    [$keywords, $default_thumb] = init_plugin_suite_live_search_prepare_keywords_and_thumb($term);

    return apply_filters(
        'init_plugin_suite_live_search_results',
        init_plugin_suite_live_search_build_result_list(
            array_slice($post_ids, $offset, $limit),
            $args,
            $term,
            $keywords,
            $default_thumb
        ),
        $post_ids,
        $term,
        $args
    );
}

// Fallback search by splitting query into single words and matching titles exactly
function init_plugin_suite_live_search_fallback_single_words($wpdb, $term, $post_types, $placeholders, $search_mode, $limit) {
    $words = array_filter(preg_split('/\s+/', $term));
    if (empty($words)) return [];

    $all_results = [];
    $weights = [];

    foreach ($words as $word) {
        $title_ids = init_plugin_suite_live_search_get_ids_by_title_exact_word(
            $wpdb, $word, $post_types, $placeholders, $limit
        );

        $seo_ids = init_plugin_suite_live_search_get_seo_ids_by_word(
            $wpdb, $word, $post_types, $placeholders, $limit
        );

        $merged = array_merge($title_ids, $seo_ids);
        $all_results[] = $merged;
        $weights[] = 1;
    }

    return init_plugin_suite_live_search_ranked_merge_weighted($all_results, $weights);
}

// Resolve post IDs based on search term, fallback, and ACF fields
function init_plugin_suite_live_search_resolve_post_ids($term, $like, $post_types, $placeholders, $search_mode, $limit, $paged, $options, $args) {
    global $wpdb;
    $internal_limit = min($limit * 3, 300);

    // Apply + / - operator logic only if enabled
    $enable_ops = !empty($options['enable_search_operators']);
    if ( $enable_ops ) {
        // Parse + / - operators into must_include / must_exclude
        $words = preg_split('/\s+/', trim($term));
        
        $must_have = [];
        $must_not_have = [];
        $normal = [];

        foreach ($words as $w) {
            if (strpos($w, '+') === 0) {
                $word = substr($w, 1);
                if (strlen($word) >= 2) {
                    $must_have[] = $word;
                }
            } elseif (strpos($w, '-') === 0) {
                $word = substr($w, 1);
                if (strlen($word) >= 2) {
                    $must_not_have[] = $word;
                }
            } else {
                if (strlen($w) >= 2) {
                    $normal[] = $w;
                }
            }
        }

        $must_have = array_slice($must_have, 0, 5);
        $must_not_have = array_slice($must_not_have, 0, 5);

        // Rebuild sanitized term for normal search
        $term = implode(' ', array_merge($must_have, $normal));
        $like = '%' . $wpdb->esc_like($term) . '%';

        // Store operator filters in $args for later filtering (if needed)
        $args['operator_must'] = $must_have;
        $args['operator_must_not'] = $must_not_have;
    }

    $post_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
        $wpdb, $term, $like, $post_types, $placeholders, $search_mode, $internal_limit
    );

    // Expand search with synonyms if result count is low
    $enable_synonym = !isset($options['enable_synonym']) || $options['enable_synonym'];
    if ($enable_synonym && count($post_ids) < floor($limit / 2)) {
        $expanded_terms = init_plugin_suite_live_search_expand_with_synonyms($term);

        $synonym_ids_list = [];
        $synonym_weights = [];

        foreach ($expanded_terms as $expanded_term) {
            if ($expanded_term === $term) continue;

            $like_syn = '%' . $wpdb->esc_like($expanded_term) . '%';
            $synonym_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                $wpdb, $expanded_term, $like_syn, $post_types, $placeholders, $search_mode, $internal_limit
            );

            if (!empty($synonym_ids)) {
                $synonym_ids_list[] = $synonym_ids;
                $synonym_weights[] = 1; // Lower weight than primary term
            }
        }

        if (!empty($synonym_ids_list)) {
            $post_ids = init_plugin_suite_live_search_ranked_merge_weighted(
                array_merge([ $post_ids ], $synonym_ids_list),
                array_merge([ 2 ], $synonym_weights)
            );
        }
    }

    $enable_fallback = isset($args['enable_fallback'])
        ? (bool) $args['enable_fallback']
        : (!isset($options['enable_fallback']) || $options['enable_fallback']);

    $enable_fallback = apply_filters('init_plugin_suite_live_search_enable_fallback', $enable_fallback, $term, $args);

    if ($enable_fallback) {
        $words = preg_split('/\s+/', trim($term));
        $cut_attempts = 0;
        while (count($post_ids) < floor($limit / 2) && count($words) > 3 && $cut_attempts < 4) {
            array_pop($words);
            $short_term = implode(' ', $words);
            $like_short = '%' . $wpdb->esc_like($short_term) . '%';
            $post_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                $wpdb, $short_term, $like_short, $post_types, $placeholders, $search_mode, $internal_limit
            );
            $cut_attempts++;
        }

        if (count($post_ids) < floor($limit / 2) && str_word_count($term) >= 3) {
            $bi_terms = array_unique(init_plugin_suite_live_search_generate_bigrams($term));
            foreach (array_slice($bi_terms, 0, 10) as $bi_term) {
                $like_bi = '%' . $wpdb->esc_like($bi_term) . '%';
                $more_ids = init_plugin_suite_live_search_get_post_ids_by_mode(
                    $wpdb, $bi_term, $like_bi, $post_types, $placeholders, $search_mode, $internal_limit
                );
                $post_ids = array_merge($post_ids, $more_ids);
            }
            $post_ids = array_unique($post_ids);
        }

        $should_apply_single_word_fallback = true;

        if (!empty($args['no_fallback'])) {
            $should_apply_single_word_fallback = false;
        }

        if (!empty($options['cross_sites'])) {
            $should_apply_single_word_fallback = false;
        }

        if (count($post_ids) < $limit && in_array($search_mode, ['title', 'title_tag'], true) && $should_apply_single_word_fallback) {
            $words = array_filter(preg_split('/\s+/', $term));
            $word_count = count($words);

            $extra_ids = init_plugin_suite_live_search_fallback_single_words(
                $wpdb, $term, $post_types, $placeholders, $search_mode, $limit
            );

            $extra_weight = max(1, min(5, $word_count));

            $post_ids = init_plugin_suite_live_search_ranked_merge_weighted([$post_ids, $extra_ids], [2, $extra_weight]);
        }

        $post_ids = array_unique($post_ids);
    }

    // ACF field search
    if (function_exists('get_field') && !empty($options['acf_search_fields'])) {
        $acf_fields = array_filter(array_map('trim', explode(',', $options['acf_search_fields'])));
        if (!empty($acf_fields)) {
            $acf_like = '%' . $wpdb->esc_like($term) . '%';
            $acf_placeholders = implode(', ', array_fill(0, count($acf_fields), '%s'));
            $acf_ids = $wpdb->get_col($wpdb->prepare(
                "
                SELECT pm.post_id
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key IN ($acf_placeholders)
                AND pm.meta_value LIKE %s
                AND p.post_status = 'publish'
                LIMIT $internal_limit
                ",
                ...array_merge($acf_fields, [$acf_like])
            ));
            $post_ids = array_unique(array_merge($post_ids, array_map('intval', $acf_ids)));
        }
    }

    // Filter out must-not-have posts if applicable
    if ( $enable_ops && !empty($args['operator_must_not']) ) {
        $post_ids = array_filter($post_ids, function($post_id) use ($args) {
            $content = get_post_field('post_title', $post_id);
            foreach ($args['operator_must_not'] as $bad) {
                if (stripos($content, $bad) !== false) return false;
            }
            return true;
        });
    }

    $post_ids = apply_filters('init_plugin_suite_live_search_post_ids', $post_ids, $term, $args);
    return apply_filters('init_plugin_suite_live_search_filter_lang', $post_ids, $term, $args);
}

// Retrieve post IDs by specific search mode: title, tag, excerpt, etc.
function init_plugin_suite_live_search_get_post_ids_by_mode($wpdb, $term, $like, $post_types, $placeholders, $search_mode, $limit) {
    $seo_ids = init_plugin_suite_live_search_get_seo_ids($wpdb, $term, $like, $post_types, $placeholders, $search_mode, $limit);

    switch ($search_mode) {
        case 'title':
            $ids_title = init_plugin_suite_live_search_get_ids_by_title($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2], 'title');
            return init_plugin_suite_live_search_ranked_merge_weighted([$ids_title, $seo_ids], $weights);

        case 'title_tag':
            $ids_title = init_plugin_suite_live_search_get_ids_by_title($wpdb, $term, $like, $post_types, $placeholders, $limit);
            [$ids_tag, $ids_tag_exact] = init_plugin_suite_live_search_get_ids_by_tag($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2, 1, 1], 'title_tag');
            return init_plugin_suite_live_search_ranked_merge_weighted([$ids_title, $seo_ids, $ids_tag, $ids_tag_exact], $weights);

        case 'title_excerpt':
            $ids_title = init_plugin_suite_live_search_get_ids_by_title($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $ids_excerpt = init_plugin_suite_live_search_get_ids_by_excerpt($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2, 1], 'title_excerpt');
            return init_plugin_suite_live_search_ranked_merge_weighted([$ids_title, $seo_ids, $ids_excerpt], $weights);

        case 'title_content':
        default:
            $ids_title = init_plugin_suite_live_search_get_ids_by_title($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $ids_excerpt = init_plugin_suite_live_search_get_ids_by_excerpt($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $ids_content = init_plugin_suite_live_search_get_ids_by_content($wpdb, $term, $like, $post_types, $placeholders, $limit);
            $weights = apply_filters('init_plugin_suite_live_search_weights', [3, 2, 1], 'title_content');
            return init_plugin_suite_live_search_ranked_merge_weighted([$ids_title, $ids_excerpt, $ids_content], $weights);
    }
}

// Get post IDs where the title matches the search term
function init_plugin_suite_live_search_get_ids_by_title($wpdb, $term, $like, $post_types, $placeholders, $limit) {
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
}

// Get post IDs where the excerpt matches the search term
function init_plugin_suite_live_search_get_ids_by_excerpt($wpdb, $term, $like, $post_types, $placeholders, $limit) {
    return $wpdb->get_col($wpdb->prepare(
        "
        SELECT ID FROM {$wpdb->posts}
        WHERE post_status = 'publish'
        AND post_type IN ($placeholders)
        AND post_excerpt LIKE %s
        ORDER BY post_date DESC
        LIMIT %d
        ",
        ...array_merge($post_types, [$like, $limit])
    ));
}

// Get post IDs where the content matches the search term
function init_plugin_suite_live_search_get_ids_by_content($wpdb, $term, $like, $post_types, $placeholders, $limit) {
    return $wpdb->get_col($wpdb->prepare(
        "
        SELECT ID FROM {$wpdb->posts}
        WHERE post_status = 'publish'
        AND post_type IN ($placeholders)
        AND post_content LIKE %s
        ORDER BY post_date DESC
        LIMIT %d
        ",
        ...array_merge($post_types, [$like, $limit])
    ));
}

// Get post IDs where the tag name partially or exactly matches the search term
function init_plugin_suite_live_search_get_ids_by_tag($wpdb, $term, $like, $post_types, $placeholders, $limit) {
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

    $ids_tag_exact = [];
    $words = preg_split('/\\s+/', $term);
    if (count($words) >= 1 && count($words) <= 2) {
        foreach ($words as $word) {
            $exact = trim($word);
            if ($exact !== '') {
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
                    ...array_merge($post_types, [$exact, $limit])
                ));
                $ids_tag_exact = array_merge($ids_tag_exact, $result);
            }
        }
    }

    return [$ids_tag, $ids_tag_exact];
}

// Get post IDs where SEO metadata (title/description) matches the search term
function init_plugin_suite_live_search_get_seo_ids($wpdb, $term, $like, $post_types, $placeholders, $search_mode, $limit) {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    if (empty($options['seo_search_fields_enabled'])) return [];
    if (!in_array($search_mode, ['title', 'title_tag', 'title_excerpt'], true)) return [];

    $seo_title_keys = ['_yoast_wpseo_title', 'rank_math_title', '_aioseo_title', '_genesis_title', '_seopress_titles_title'];
    $seo_desc_keys = ['_yoast_wpseo_metadesc', 'rank_math_description', '_aioseo_description', '_genesis_description', '_seopress_titles_desc'];

    $keys = ($search_mode === 'title_excerpt') ? array_merge($seo_title_keys, $seo_desc_keys) : $seo_title_keys;
    $keys = apply_filters('init_plugin_suite_live_search_seo_meta_keys', $keys);
    $placeholders_meta = implode(', ', array_fill(0, count($keys), '%s'));
    $seo_like = '%' . $wpdb->esc_like($term) . '%';

    return $wpdb->get_col($wpdb->prepare(
        "
        SELECT DISTINCT pm.post_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key IN ($placeholders_meta)
        AND pm.meta_value LIKE %s
        AND p.post_status = 'publish'
        AND p.post_type IN ($placeholders)
        LIMIT %d
        ",
        ...array_merge($keys, [$seo_like, ...$post_types, $limit])
    ));
}

// Get post IDs where the title matches an exact word using REGEXP
function init_plugin_suite_live_search_get_ids_by_title_exact_word($wpdb, $word, $post_types, $placeholders, $limit) {
    $escaped = preg_quote($word, '/');
    $regexp = '\\b' . $escaped . '\\b';

    return $wpdb->get_col($wpdb->prepare(
        "
        SELECT ID FROM {$wpdb->posts}
        WHERE post_status = 'publish'
        AND post_type IN ($placeholders)
        AND post_title REGEXP %s
        ORDER BY post_date DESC
        LIMIT %d
        ",
        ...array_merge($post_types, [$regexp, $limit])
    ));
}

// Get post IDs from SEO metadata where word matches exactly
function init_plugin_suite_live_search_get_seo_ids_by_word($wpdb, $word, $post_types, $placeholders, $limit) {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    if (empty($options['seo_search_fields_enabled'])) return [];

    $search_mode = $options['search_mode'] ?? 'title';
    if (!in_array($search_mode, ['title', 'title_tag'], true)) return [];

    $seo_title_keys = ['_yoast_wpseo_title', 'rank_math_title', '_aioseo_title', '_genesis_title', '_seopress_titles_title'];
    $keys = apply_filters('init_plugin_suite_live_search_seo_meta_keys', $seo_title_keys);
    $placeholders_meta = implode(', ', array_fill(0, count($keys), '%s'));
    $escaped = '\\b' . preg_quote($word, '/') . '\\b';

    return $wpdb->get_col($wpdb->prepare(
        "
        SELECT DISTINCT pm.post_id
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key IN ($placeholders_meta)
        AND pm.meta_value REGEXP %s
        AND p.post_status = 'publish'
        AND p.post_type IN ($placeholders)
        LIMIT %d
        ",
        ...array_merge($keys, [$escaped, ...$post_types, $limit])
    ));
}

// Expand a search term by including its synonyms.
function init_plugin_suite_live_search_expand_with_synonyms($term) {
    $term = trim(mb_strtolower($term));
    if ($term === '') return [$term];

    // Get custom synonyms from user input
    $user_map = [];
    $raw = get_option(INIT_PLUGIN_SUITE_LS_SYNONYM_OPTION, '{}');
    $decoded = is_string($raw) ? json_decode($raw, true) : (is_array($raw) ? $raw : []);

    if (is_array($decoded)) {
        foreach ($decoded as $key => $syns) {
            $key = trim(mb_strtolower($key));
            if (!is_array($syns)) continue;
            $user_map[$key] = array_values(array_filter(array_map('trim', $syns)));
        }
    }

    // Get predefined dictionaries and merge with user synonyms
    $predefined_map = init_plugin_suite_live_search_get_predefined_synonyms();
    $synonym_map = array_merge_recursive($predefined_map, $user_map);

    // Remove duplicates from merged arrays
    foreach ($synonym_map as $key => $values) {
        if (is_array($values)) {
            $synonym_map[$key] = array_unique($values);
        }
    }

    $synonym_map = apply_filters('init_plugin_suite_live_search_synonym_map', $synonym_map);

    $expanded = [$term];

    // Direct lookup
    if (!empty($synonym_map[$term])) {
        $expanded = array_merge($expanded, $synonym_map[$term]);
    }

    // Multi-word term lookup (check each word)
    $words = preg_split('/\s+/', $term);
    if (count($words) > 1) {
        foreach ($words as $word) {
            $word = trim(mb_strtolower($word));
            if (strlen($word) >= 3 && !empty($synonym_map[$word])) {
                $expanded = array_merge($expanded, $synonym_map[$word]);
            }
        }
    }

    return array_unique($expanded);
}

// Find related post IDs based on a keyword and exclude a specific post.
function init_plugin_suite_live_search_find_related_ids( $keyword, $exclude_id, $limit = 5, $post_type = '' ) {
    global $wpdb;

    // Làm sạch y chang REST
    $keyword = sanitize_text_field( $keyword );
    if ( strlen( $keyword ) < 3 ) return [];

    $keyword = wp_strip_all_tags( $keyword );
    $keyword = html_entity_decode( $keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $keyword = preg_replace( '/\s*[-|–|—]\s*[^|–—-]+$/u', '', $keyword );
    $keyword = trim( preg_replace( '/[^\p{L}\p{N}\s]+/u', '', $keyword ) );
    if ( strlen( $keyword ) < 3 ) return [];

    $args = [
        'exclude'    => $exclude_id,
        'paged'      => 1,
        'lang'       => init_plugin_suite_live_search_detect_lang(),
        'post_type'  => $post_type,
        'force_mode' => 'title',
    ];

    $raw_results = init_plugin_suite_live_search_get_results( $keyword, $args );

    if ( empty( $raw_results ) || ! is_array( $raw_results ) ) {
        return [];
    }

    $post_ids = array_column( $raw_results, 'id' );
    return array_slice( $post_ids, 0, $limit );
}
