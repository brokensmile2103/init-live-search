<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Generate bi-grams from search term.
function init_plugin_suite_live_search_generate_bigrams($term) {
    $words = preg_split('/\s+/', $term);
    $bigrams = [];
    for ($i = 0; $i < count($words) - 1; $i++) {
        $bigrams[] = $words[$i] . ' ' . $words[$i + 1];
    }
    return $bigrams;
}

// Merge arrays of post IDs using custom weights to prioritize sources.
function init_plugin_suite_live_search_ranked_merge_weighted(array $arrays, array $weights = []) {
    $non_empty = array_filter($arrays, function($arr) {
        return is_array($arr) && !empty($arr);
    });

    if (count($non_empty) <= 1) {
        return array_unique(array_merge(...$non_empty));
    }

    $score_map = [];

    foreach ($arrays as $i => $arr) {
        $weight = $weights[$i] ?? 1;
        foreach ((array)$arr as $id) {
            $score_map[$id] = ($score_map[$id] ?? 0) + $weight;
        }
    }

    arsort($score_map);
    return array_keys($score_map);
}

/**
 * Highlight matching keywords in a string using <mark> tags.
 * - Diacritic-insensitive (supports Vietnamese)
 * - XSS-safe: escapes all output, only <mark> tags are raw HTML
 * - Handles overlapping/adjacent ranges by merging them
 *
 * @param string $text
 * @param string|string[] $keywords
 * @return string HTML-escaped string with <mark> tags
 */
function init_plugin_suite_live_search_highlight_keyword($text, $keywords) {
    if (empty($text) || empty($keywords)) return esc_html($text);

    static $patterns = null, $replacements = null;
    if (!$patterns) {
        $patterns = [
            '/[áàảãạăắằẳẵặâấầẩẫậ]/u', '/[ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ]/u',
            '/[éèẻẽẹêếềểễệ]/u',        '/[ÉÈẺẼẸÊẾỀỂỄỆ]/u',
            '/[íìỉĩị]/u',               '/[ÍÌỈĨỊ]/u',
            '/[óòỏõọôốồổỗộơớờởỡợ]/u',  '/[ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ]/u',
            '/[úùủũụưứừửữự]/u',         '/[ÚÙỦŨỤƯỨỪỬỮỰ]/u',
            '/[ýỳỷỹỵ]/u',               '/[ÝỲỶỸỴ]/u',
            '/[đ]/u',                    '/[Đ]/u',
        ];
        $replacements = ['a','A','e','E','i','I','o','O','u','U','y','Y','d','D'];
    }

    $remove_accents = fn($str) => preg_replace($patterns, $replacements, $str);

    $text_normalized = $remove_accents(mb_strtolower($text));

    if (is_string($keywords)) {
        $keywords = [$keywords];
    }

    // --- Collect all match ranges ---
    $ranges = [];
    foreach ($keywords as $keyword) {
        $kw_normalized = $remove_accents(mb_strtolower($keyword));
        if (empty($kw_normalized)) continue;

        $kw_len = mb_strlen($kw_normalized);
        $offset = 0;
        while (($pos = mb_stripos($text_normalized, $kw_normalized, $offset)) !== false) {
            $ranges[] = [$pos, $pos + $kw_len];
            $offset = $pos + 1; // +1 thay vì +$kw_len để không bỏ sót overlapping keywords
        }
    }

    if (empty($ranges)) return esc_html($text);

    // --- Sort + merge overlapping/adjacent ranges ---
    usort($ranges, fn($a, $b) => $a[0] <=> $b[0]);

    $merged = [];
    [$cur_start, $cur_end] = $ranges[0];
    foreach (array_slice($ranges, 1) as [$start, $end]) {
        if ($start <= $cur_end) {
            // Overlap hoặc adjacent → merge
            $cur_end = max($cur_end, $end);
        } else {
            $merged[] = [$cur_start, $cur_end];
            [$cur_start, $cur_end] = [$start, $end];
        }
    }
    $merged[] = [$cur_start, $cur_end];

    // --- Build result, escape từng segment ---
    $result   = '';
    $last_pos = 0;
    foreach ($merged as [$start, $end]) {
        $result .= esc_html(mb_substr($text, $last_pos, $start - $last_pos));
        $result .= '<mark>' . esc_html(mb_substr($text, $start, $end - $start)) . '</mark>';
        $last_pos = $end;
    }
    $result .= esc_html(mb_substr($text, $last_pos));

    return $result;
}

// Detect current language via Polylang, WPML, or locale fallback.
function init_plugin_suite_live_search_detect_lang() {
    if (function_exists('pll_current_language')) {
        return pll_current_language();
    } elseif (function_exists('apply_filters')) {
        return apply_filters('wpml_current_language', null);
    }
    return get_locale();
}

// Parse a `Y`, `Y/m`, or `Y/m/d` formatted string into query args.
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

// Retrieve WooCommerce product data: price, stock, cart URL.
function init_plugin_suite_live_search_get_product_data($post_id) {
    if (!function_exists('wc_get_product')) return [];

    $product = wc_get_product($post_id);
    if (!$product) return [];

    $data = [
        'price'         => $product->get_price_html(),
        'regular_price' => wc_price($product->get_regular_price()),
        'on_sale'       => $product->is_on_sale(),
        'stock_status'  => $product->get_stock_status(),
        'add_to_cart_url' => $product->add_to_cart_url(),
    ];

    return $data;
}

// Build result item for a post: title, thumb, category, etc.
function init_plugin_suite_live_search_build_result_item($post_id, $term = '', $keywords = [], $default_thumb = '', $args = []) {
    $thumb_id  = get_post_thumbnail_id($post_id);
    $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'thumbnail') : '';

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);

    if (
        empty($thumb_id)
        && !empty($options['first_image_fallback'])
    ) {
        $post_content = get_post_field('post_content', $post_id);

        if (!empty($post_content)) {

            // Try extracting attachment ID from wp-image-{ID} class first
            if (
                preg_match('/wp-image-([0-9]+)/i', $post_content, $id_matches)
            ) {
                $attachment_id = absint($id_matches[1]);

                if ($attachment_id) {
                    $attachment_thumb = wp_get_attachment_image_url(
                        $attachment_id,
                        'thumbnail'
                    );

                    if (!empty($attachment_thumb)) {
                        $thumb_url = $attachment_thumb;
                    }
                }
            }

            // Fallback to raw image src if attachment lookup fails
            if (
                empty($thumb_url)
                && preg_match(
                    '/<img[^>]+src=["\']([^"\']+)["\']/i',
                    $post_content,
                    $src_matches
                )
            ) {
                $candidate_thumb = esc_url_raw($src_matches[1]);

                $site_host  = wp_parse_url(home_url(), PHP_URL_HOST);
                $image_host = wp_parse_url($candidate_thumb, PHP_URL_HOST);

                $allowed = (
                    empty($image_host)
                    || $image_host === $site_host
                    || str_ends_with($image_host, '.' . $site_host)
                );

                $allowed = apply_filters(
                    'init_plugin_suite_live_search_allow_fallback_image_host',
                    $allowed,
                    $image_host,
                    $candidate_thumb,
                    $post_id
                );

                if ($allowed) {
                    $thumb_url = $candidate_thumb;
                }
            }
        }
    }

    if (empty($thumb_url)) {
        $thumb_url = $default_thumb;
    }

    $post_type_slug = get_post_type($post_id);
    $post_type_obj  = get_post_type_object($post_type_slug);
    $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post_type_slug;

    $taxonomy = apply_filters('init_plugin_suite_live_search_category_taxonomy', 'category', $post_id);
    $category = get_the_terms($post_id, $taxonomy);
    $category_name = ($category && !is_wp_error($category)) ? $category[0]->name : '';

    $title = get_the_title($post_id);
    if (!empty($keywords)) {
        $title = init_plugin_suite_live_search_highlight_keyword(esc_html($title), $keywords);
    }

    $item = [
        'id'        => $post_id,
        'title'     => $title,
        'url'       => get_permalink($post_id),
        'type'      => $post_type_name,
        'post_type' => $post_type_slug,
        'thumb'     => $thumb_url,
        'date'      => get_the_date('', $post_id),
        'category'  => apply_filters('init_plugin_suite_live_search_category', $category_name, $post_id),
    ];

    if ($post_type_slug === 'product') {
        $item = array_merge($item, init_plugin_suite_live_search_get_product_data($post_id));
    }

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $show_excerpt = apply_filters('init_live_search_show_excerpt', !isset($options['show_excerpt']) || $options['show_excerpt']);

    if ($show_excerpt) {
        $raw_content = get_post_field('post_excerpt', $post_id) ?: get_post_field('post_content', $post_id);
        $clean_text = wp_strip_all_tags($raw_content);

        if (!empty($keywords)) {
            foreach ($keywords as $keyword) {
                if (stripos($clean_text, $keyword) !== false) {
                    $item['excerpt'] = init_plugin_suite_live_search_extract_snippet($clean_text, $keyword);
                    break;
                }
            }
        }

        if (empty($item['excerpt'])) {
            $fallback = get_the_excerpt($post_id);
            $fallback = wp_strip_all_tags($fallback);
            $item['excerpt'] = wp_trim_words($fallback, 15, '...');
        }

        if (!empty($item['excerpt']) && !empty($keywords)) {
            $item['excerpt'] = init_plugin_suite_live_search_highlight_keyword($item['excerpt'], $keywords);
        }
    }

    return apply_filters('init_plugin_suite_live_search_result_item', $item, $post_id, $term, $args);
}

// Build full list of results from post IDs.
function init_plugin_suite_live_search_build_result_list($post_ids, $args = [], $term = '', $keywords = [], $default_thumb = '') {
    if (!is_array($post_ids)) return [];

    $exclude = !empty($args['exclude']) ? (int)$args['exclude'] : null;
    $results = [];

    foreach ($post_ids as $post_id) {
        if ($exclude && $post_id === $exclude) continue;
        $results[] = init_plugin_suite_live_search_build_result_item($post_id, $term, $keywords, $default_thumb, $args);
    }

    return $results;
}

// Extracts a short snippet around the keyword, or falls back to trimmed text.
function init_plugin_suite_live_search_extract_snippet($text, $keyword, $word_limit = 15) {
    $text = wp_strip_all_tags($text);
    $text = preg_replace('/\s+/', ' ', $text);

    $pattern = '/((?:\S+\s+){0,' . floor($word_limit / 2) . '})(' . preg_quote($keyword, '/') . ')((?:\s+\S+){0,' . floor($word_limit / 2) . '})/iu';
    if (preg_match($pattern, $text, $matches)) {
        $before = trim($matches[1]);
        $match  = $matches[2];
        $after  = trim($matches[3]);

        return ($before ? '... ' : '') . $before . ' ' . $match . ' ' . $after . ($after ? ' ...' : '');
    }

    return wp_trim_words($text, $word_limit, '...');
}

// Prepare keyword list and default thumbnail
function init_plugin_suite_live_search_prepare_keywords_and_thumb($term) {
    $keywords = [];

    if ($term) {
        $keywords[] = $term;

        if (str_word_count($term) >= 3) {
            $keywords = array_merge($keywords, init_plugin_suite_live_search_generate_bigrams($term));
        }

        $single_words = preg_split('/\s+/', $term);
        if (!empty($single_words)) {
            $keywords = array_merge($keywords, $single_words);
        }

        $keywords = array_unique(array_filter($keywords));
    }

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    return [$keywords, $default_thumb];
}

// Determine which post types to search against
function init_plugin_suite_live_search_resolve_post_types($options, $args) {
    if (!empty($args['post_types']) && is_array($args['post_types'])) {
        $resolved = array_map('sanitize_key', $args['post_types']);
    } elseif (!empty($args['post_type']) && is_string($args['post_type'])) {
        $types = explode(',', $args['post_type']);
        $resolved = array_map('sanitize_key', array_filter(array_map('trim', $types)));
    } elseif (!empty($options['post_types']) && is_array($options['post_types'])) {
        $resolved = array_map('sanitize_key', $options['post_types']);
    } else {
        $resolved = ['post']; // fallback mặc định
    }

    // Filter cho phép modify list (ép thêm, xoá, etc.)
    $resolved = apply_filters('init_plugin_suite_live_search_post_types', $resolved, $options, $args);

    // Loại bỏ trùng lặp, reset index
    return array_values(array_unique($resolved));
}

// Determine result limit from args or settings
function init_plugin_suite_live_search_resolve_limit($options, $args) {
    if (!empty($args['limit']) && is_numeric($args['limit'])) {
        return (int) $args['limit'];
    }
    if (!empty($options['max_results']) && is_numeric($options['max_results'])) {
        return (int) $options['max_results'];
    }
    return 10;
}

// Generate smart and clean alt text for a post thumbnail.
function init_plugin_suite_live_search_get_smart_post_thumbnail_alt( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();
    $thumb_id = get_post_thumbnail_id( $post_id );

    // 1. Try alt from media library
    $alt = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
    if ( ! empty( $alt ) ) {
        return esc_attr( $alt );
    }

    // 2. Fallback to trimmed title
    $title = get_the_title( $post_id );
    $title = wp_strip_all_tags( $title );
    $title = preg_replace( '/[^\p{L}\p{N}\s]+/u', '', $title ); // remove symbols
    $title = trim( $title );
    $short_title = wp_trim_words( $title, 6, '' );

    // 3. Add generic prefix
    // translators: %s is the post title used as fallback alt text
    $alt = sprintf( __( 'Image for: %s', 'init-live-search' ), $short_title );

    /**
     * Filter the auto-generated image alt text.
     *
     * @param string $alt
     * @param int    $post_id
     */
    return apply_filters( 'init_plugin_suite_live_search_smart_post_thumbnail_alt', esc_attr( $alt ), $post_id );
}
