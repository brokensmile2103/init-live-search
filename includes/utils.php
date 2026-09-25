<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Generate bi-grams from search term.
function init_plugin_suite_live_search_generate_bigrams( $term ) {
    $words   = preg_split( '/\s+/u', trim( (string) $term ), -1, PREG_SPLIT_NO_EMPTY );
    $bigrams = [];
    $count   = count( $words );

    for ( $i = 0; $i < $count - 1; $i++ ) {
        $bigrams[] = $words[ $i ] . ' ' . $words[ $i + 1 ];
    }

    return $bigrams;
}

// Unicode-aware word count. PHP's str_word_count() only understands ASCII
// letters, so it splits Vietnamese words at every accented character
// ("tìm kiếm" was counted as 4+ words) and skewed the bigram/fallback logic.
function init_plugin_suite_live_search_count_words( $term ) {
    $words = preg_split( '/\s+/u', trim( (string) $term ), -1, PREG_SPLIT_NO_EMPTY );
    return is_array( $words ) ? count( $words ) : 0;
}

/**
 * Normalize a raw search term before it enters the pipeline.
 *
 * - sanitize_text_field() exactly like before.
 * - Unicode NFC normalization when ext-intl is available: macOS/iOS keyboards
 *   often send Vietnamese as decomposed (NFD) characters, which never match
 *   the precomposed (NFC) text stored in the database.
 * - Hard length cap, so a pasted wall of text cannot fan out into dozens of
 *   fallback queries.
 *
 * @param string $term Raw term.
 * @return string
 */
function init_plugin_suite_live_search_normalize_term( $term ) {
    $term = sanitize_text_field( (string) $term );

    if ( '' === $term ) {
        return '';
    }

    if ( class_exists( 'Normalizer' ) && ! Normalizer::isNormalized( $term, Normalizer::FORM_C ) ) {
        $normalized = Normalizer::normalize( $term, Normalizer::FORM_C );
        if ( is_string( $normalized ) && '' !== $normalized ) {
            $term = $normalized;
        }
    }

    $max_length = (int) apply_filters( 'init_plugin_suite_live_search_max_term_length', 200 );
    if ( $max_length > 0 && mb_strlen( $term ) > $max_length ) {
        $term = trim( mb_substr( $term, 0, $max_length ) );
    }

    return $term;
}

// Salt for object-cache keys. Changes automatically whenever any post or term
// is created/updated/deleted (core bumps these "last_changed" values), so
// persistent caches (Redis/Memcached) never serve results that are stale.
function init_plugin_suite_live_search_cache_salt() {
    return wp_cache_get_last_changed( 'posts' ) . ':' . wp_cache_get_last_changed( 'terms' );
}

// Merge arrays of post IDs using custom weights to prioritize sources.
// Score = sum of the weights of every list an ID appears in (unchanged).
// Ties are broken deterministically: best position reached in any source
// list first, then first-seen order. Previously ties relied on arsort(),
// which is not stable on PHP 7.4 (random order between equal scores).
function init_plugin_suite_live_search_ranked_merge_weighted( array $arrays, array $weights = [] ) {
    $non_empty = array_filter(
        $arrays,
        function ( $arr ) {
            return is_array( $arr ) && ! empty( $arr );
        }
    );

    if ( count( $non_empty ) <= 1 ) {
        return array_values( array_unique( array_merge( [], ...array_values( $non_empty ) ) ) );
    }

    $score = [];
    $best  = [];
    $order = [];
    $seen  = 0;

    foreach ( $arrays as $i => $arr ) {
        $weight = $weights[ $i ] ?? 1;
        $rank   = 0;

        foreach ( (array) $arr as $id ) {
            if ( ! isset( $score[ $id ] ) ) {
                $score[ $id ] = 0;
                $best[ $id ]  = PHP_INT_MAX;
                $order[ $id ] = $seen++;
            }

            $score[ $id ] += $weight;

            if ( $rank < $best[ $id ] ) {
                $best[ $id ] = $rank;
            }

            ++$rank;
        }
    }

    $ids = array_keys( $score );

    usort(
        $ids,
        function ( $a, $b ) use ( $score, $best, $order ) {
            if ( $score[ $a ] != $score[ $b ] ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- int/float weights from filters.
                return ( $score[ $b ] < $score[ $a ] ) ? -1 : 1;
            }
            if ( $best[ $a ] !== $best[ $b ] ) {
                return ( $best[ $a ] < $best[ $b ] ) ? -1 : 1;
            }
            return ( $order[ $a ] < $order[ $b ] ) ? -1 : 1;
        }
    );

    return $ids;
}

// Fold Vietnamese diacritics to their base ASCII letter. Every mapping is one
// character to one character, so offsets computed on the folded string are
// valid on the original string. strtr() with a map is several times faster
// than the 14 chained preg_replace() calls used previously (same output).
function init_plugin_suite_live_search_fold_diacritics( $text ) {
    static $map = null;

    if ( null === $map ) {
        $groups = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'A' => 'ÁÀẢÃẠĂẮẰẲẴẶÂẤẦẨẪẬ',
            'e' => 'éèẻẽẹêếềểễệ',
            'E' => 'ÉÈẺẼẸÊẾỀỂỄỆ',
            'i' => 'íìỉĩị',
            'I' => 'ÍÌỈĨỊ',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'O' => 'ÓÒỎÕỌÔỐỒỔỖỘƠỚỜỞỠỢ',
            'u' => 'úùủũụưứừửữự',
            'U' => 'ÚÙỦŨỤƯỨỪỬỮỰ',
            'y' => 'ýỳỷỹỵ',
            'Y' => 'ÝỲỶỸỴ',
            'd' => 'đ',
            'D' => 'Đ',
        ];

        $map = [];
        foreach ( $groups as $base => $chars ) {
            foreach ( preg_split( '//u', $chars, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
                $map[ $char ] = $base;
            }
        }
    }

    return strtr( (string) $text, $map );
}

// Case- and diacritic-insensitive position (in characters) of $keyword inside
// $text, or false. Falls back to a plain mb_stripos() in the rare case where
// lowercasing changes the character count (e.g. Turkish dotted İ), because the
// folded offsets would no longer map back onto the original string.
function init_plugin_suite_live_search_find_keyword_pos( $text, $keyword, $offset = 0 ) {
    $text    = (string) $text;
    $keyword = (string) $keyword;

    if ( '' === $text || '' === $keyword ) {
        return false;
    }

    $haystack = init_plugin_suite_live_search_fold_diacritics( mb_strtolower( $text ) );
    $needle   = init_plugin_suite_live_search_fold_diacritics( mb_strtolower( $keyword ) );

    if ( '' === $needle ) {
        return false;
    }

    if ( mb_strlen( $haystack ) !== mb_strlen( $text ) ) {
        return mb_stripos( $text, $keyword, $offset );
    }

    return mb_strpos( $haystack, $needle, $offset );
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
function init_plugin_suite_live_search_highlight_keyword( $text, $keywords ) {
    if ( empty( $text ) || empty( $keywords ) ) {
        return esc_html( $text );
    }

    $text            = (string) $text;
    $text_normalized = init_plugin_suite_live_search_fold_diacritics( mb_strtolower( $text ) );

    // Offsets below are computed on the normalized string and applied to the
    // original one; only valid while both have the same character count.
    if ( mb_strlen( $text_normalized ) !== mb_strlen( $text ) ) {
        return esc_html( $text );
    }

    if ( is_string( $keywords ) ) {
        $keywords = [ $keywords ];
    }

    // --- Collect all match ranges ---
    $ranges = [];
    foreach ( $keywords as $keyword ) {
        $kw_normalized = init_plugin_suite_live_search_fold_diacritics( mb_strtolower( (string) $keyword ) );
        if ( '' === $kw_normalized ) {
            continue;
        }

        $kw_len = mb_strlen( $kw_normalized );
        $offset = 0;
        while ( false !== ( $pos = mb_strpos( $text_normalized, $kw_normalized, $offset ) ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
            $ranges[] = [ $pos, $pos + $kw_len ];
            $offset   = $pos + 1; // +1 thay vì +$kw_len để không bỏ sót overlapping keywords
        }
    }

    if ( empty( $ranges ) ) {
        return esc_html( $text );
    }

    // --- Sort + merge overlapping/adjacent ranges ---
    usort(
        $ranges,
        function ( $a, $b ) {
            return $a[0] <=> $b[0];
        }
    );

    $merged                  = [];
    [ $cur_start, $cur_end ] = $ranges[0];
    foreach ( array_slice( $ranges, 1 ) as [ $start, $end ] ) {
        if ( $start <= $cur_end ) {
            // Overlap hoặc adjacent → merge
            $cur_end = max( $cur_end, $end );
        } else {
            $merged[]                = [ $cur_start, $cur_end ];
            [ $cur_start, $cur_end ] = [ $start, $end ];
        }
    }
    $merged[] = [ $cur_start, $cur_end ];

    // --- Build result, escape từng segment ---
    $result   = '';
    $last_pos = 0;
    foreach ( $merged as [ $start, $end ] ) {
        $result  .= esc_html( mb_substr( $text, $last_pos, $start - $last_pos ) );
        $result  .= '<mark>' . esc_html( mb_substr( $text, $start, $end - $start ) ) . '</mark>';
        $last_pos = $end;
    }
    $result .= esc_html( mb_substr( $text, $last_pos ) );

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

// Allowed post types for requests coming from outside the settings screen
// (shortcode attributes, block attributes, Abilities API input, REST args).
// A type is allowed when it is publicly viewable, or when the site admin has
// explicitly enabled it in Settings > Post Types to Include. 'any' is kept as
// a special marker (used by the /read endpoint, validated per result instead).
function init_plugin_suite_live_search_filter_allowed_post_types( $post_types, $options = null ) {
    if ( null === $options ) {
        $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    }

    $configured = ( ! empty( $options['post_types'] ) && is_array( $options['post_types'] ) )
        ? array_map( 'sanitize_key', $options['post_types'] )
        : [ 'post' ];

    $allowed = [];
    foreach ( (array) $post_types as $post_type ) {
        $post_type = sanitize_key( $post_type );

        if ( '' === $post_type ) {
            continue;
        }

        if (
            'any' === $post_type
            || in_array( $post_type, $configured, true )
            || ( post_type_exists( $post_type ) && is_post_type_viewable( $post_type ) )
        ) {
            $allowed[] = $post_type;
        }
    }

    return array_values( array_unique( $allowed ) );
}

// Whether a post may be exposed in public results (REST, blocks, shortcodes,
// abilities). Guards every result list, including IDs that did not come from
// the SQL pipeline (the /read endpoint, Meilisearch hits that are out of sync,
// IDs injected by third-party filters).
function init_plugin_suite_live_search_is_result_visible( $post_id, $options = null ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    $visible = false;

    if ( 'publish' === $post->post_status ) {
        if ( null === $options ) {
            $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
        }

        $configured = ( ! empty( $options['post_types'] ) && is_array( $options['post_types'] ) )
            ? $options['post_types']
            : [ 'post' ];

        $visible = in_array( $post->post_type, $configured, true ) || is_post_type_viewable( $post->post_type );
    }

    return (bool) apply_filters( 'init_plugin_suite_live_search_is_result_visible', $visible, $post );
}

// Plain-text version of a post body for snippets: shortcodes and block markup
// removed without running the_content filters (page builders, embeds...).
function init_plugin_suite_live_search_get_plain_content( $post_id ) {
    $content = (string) get_post_field( 'post_content', $post_id );

    if ( '' === $content ) {
        return '';
    }

    $content = strip_shortcodes( $content );
    $content = wp_strip_all_tags( $content );

    return trim( preg_replace( '/\s+/u', ' ', $content ) );
}

// Fallback excerpt when no keyword snippet was found.
//
// Up to 2.0.0 this always called get_the_excerpt(). For posts WITHOUT a manual
// excerpt that runs the full the_content filter chain (shortcodes, blocks,
// page builders, oEmbed...) for every single result — by far the most
// expensive part of building a result list. Now:
// - manual excerpt present  -> get_the_excerpt() exactly as before (cheap,
// themes' get_the_excerpt filters still apply);
// - password-protected post -> get_the_excerpt() (returns core's protected
// notice without running the_content, exactly as before);
// - otherwise               -> trimmed plain text of the raw content.
// Sites that relied on the_content filters for excerpts can restore the old
// behaviour with: add_filter( 'init_plugin_suite_live_search_use_legacy_excerpt', '__return_true' );
function init_plugin_suite_live_search_get_fallback_excerpt( $post_id, $word_limit = 15 ) {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return '';
    }

    $use_legacy = apply_filters( 'init_plugin_suite_live_search_use_legacy_excerpt', false, $post_id );

    if ( $use_legacy || '' !== trim( (string) $post->post_excerpt ) || '' !== $post->post_password ) {
        $text = wp_strip_all_tags( get_the_excerpt( $post ) );
    } else {
        $text = init_plugin_suite_live_search_get_plain_content( $post_id );
    }

    $excerpt = wp_trim_words( $text, $word_limit, '...' );

    return (string) apply_filters( 'init_plugin_suite_live_search_fallback_excerpt', $excerpt, $post_id );
}

// Build result item for a post: title, thumb, category, etc.
function init_plugin_suite_live_search_build_result_item($post_id, $term = '', $keywords = [], $default_thumb = '', $args = []) {
    $post_id   = (int) $post_id;
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
        // Decode entities first so a keyword can never match inside an
        // entity ("amp" in "&amp;") and break the markup; the highlighter
        // escapes every segment again on output.
        $title = init_plugin_suite_live_search_highlight_keyword(
            html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
            $keywords
        );
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

    $show_excerpt = apply_filters('init_live_search_show_excerpt', !isset($options['show_excerpt']) || $options['show_excerpt']);

    if ($show_excerpt) {
        // Never build a snippet from the body of a password-protected post.
        $is_protected = '' !== (string) get_post_field( 'post_password', $post_id );

        if ( ! $is_protected && ! empty( $keywords ) ) {
            $manual_excerpt = (string) get_post_field( 'post_excerpt', $post_id );
            $clean_text     = '' !== trim( $manual_excerpt )
                ? trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $manual_excerpt ) ) )
                : init_plugin_suite_live_search_get_plain_content( $post_id );

            foreach ( $keywords as $keyword ) {
                if ( false !== init_plugin_suite_live_search_find_keyword_pos( $clean_text, $keyword ) ) {
                    $item['excerpt'] = init_plugin_suite_live_search_extract_snippet( $clean_text, $keyword );
                    break;
                }
            }
        }

        if (empty($item['excerpt'])) {
            $item['excerpt'] = init_plugin_suite_live_search_get_fallback_excerpt( $post_id, 15 );
        }

        if (!empty($item['excerpt']) && !empty($keywords)) {
            $item['excerpt'] = init_plugin_suite_live_search_highlight_keyword($item['excerpt'], $keywords);
        }
    }

    return apply_filters('init_plugin_suite_live_search_result_item', $item, $post_id, $term, $args);
}

// Warm the post/meta/term object caches for a batch of IDs in one shot.
// Without this, build_result_item() below triggers its own DB query per post
// per lookup (get_post_thumbnail_id, get_the_terms, get_post_field...) any
// time the object cache is cold — classic N+1. This mirrors what WP_Query
// itself does internally (_prime_post_caches) before looping over results.
function init_plugin_suite_live_search_prime_result_caches($post_ids) {
    if (empty($post_ids) || !function_exists('_prime_post_caches')) return;

    $post_ids = array_values(array_unique(array_map('absint', $post_ids)));
    $post_ids = array_filter($post_ids);
    if (empty($post_ids)) return;

    // Primes: post row cache (get_post_field/get_post_type/...), postmeta
    // cache (get_post_thumbnail_id, ACF-less meta reads...), and term cache
    // (get_the_terms) for every taxonomy registered on the involved post types.
    _prime_post_caches($post_ids, true, true);

    // Thumbnails are attachment posts of their own — prime those too so
    // wp_get_attachment_image_url()/wp_get_attachment_image_src() (used per
    // result) don't each trigger a fresh query for the attachment row/meta.
    $thumb_ids = [];
    foreach ($post_ids as $post_id) {
        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            $thumb_ids[] = $thumb_id;
        }
    }

    if (!empty($thumb_ids)) {
        _prime_post_caches(array_values(array_unique($thumb_ids)), false, true);
    }
}

// Build full list of results from post IDs.
function init_plugin_suite_live_search_build_result_list($post_ids, $args = [], $term = '', $keywords = [], $default_thumb = '') {
    if (!is_array($post_ids)) return [];
    if (empty($post_ids)) return [];

    init_plugin_suite_live_search_prime_result_caches($post_ids);

    $exclude = !empty($args['exclude']) ? (int)$args['exclude'] : null;
    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $results = [];

    foreach ($post_ids as $post_id) {
        $post_id = (int) $post_id;

        // IDs from $wpdb->get_col() are strings: cast before the strict
        // comparison (it never matched before, exclusion only worked because
        // get_results() filtered the list earlier).
        if ( ! $post_id || ( $exclude && $post_id === $exclude ) ) {
            continue;
        }

        if ( ! init_plugin_suite_live_search_is_result_visible( $post_id, $options ) ) {
            continue;
        }

        $results[] = init_plugin_suite_live_search_build_result_item($post_id, $term, $keywords, $default_thumb, $args);
    }

    return $results;
}

// Extracts a short snippet around the keyword, or falls back to trimmed text.
// Matching is case- and diacritic-insensitive (consistent with highlighting),
// and the whole word containing the keyword is kept intact.
function init_plugin_suite_live_search_extract_snippet( $text, $keyword, $word_limit = 15 ) {
    $text = wp_strip_all_tags( (string) $text );
    $text = trim( preg_replace( '/\s+/u', ' ', $text ) );

    $pos = init_plugin_suite_live_search_find_keyword_pos( $text, $keyword );

    if ( false === $pos ) {
        return wp_trim_words( $text, $word_limit, '...' );
    }

    $half   = (int) floor( $word_limit / 2 );
    $kw_len = mb_strlen( (string) $keyword );

    // Expand the match to the full surrounding word.
    $before_raw = mb_substr( $text, 0, $pos );
    $after_raw  = mb_substr( $text, $pos + $kw_len );

    $prefix = '';
    if ( preg_match( '/(\S+)$/u', $before_raw, $m ) ) {
        $prefix     = $m[1];
        $before_raw = mb_substr( $before_raw, 0, mb_strlen( $before_raw ) - mb_strlen( $prefix ) );
    }

    $suffix = '';
    if ( preg_match( '/^(\S+)/u', $after_raw, $m ) ) {
        $suffix    = $m[1];
        $after_raw = mb_substr( $after_raw, mb_strlen( $suffix ) );
    }

    $match = $prefix . mb_substr( $text, $pos, $kw_len ) . $suffix;

    $before_words = preg_split( '/\s+/u', trim( $before_raw ), -1, PREG_SPLIT_NO_EMPTY );
    $after_words  = preg_split( '/\s+/u', trim( $after_raw ), -1, PREG_SPLIT_NO_EMPTY );

    $before = $half > 0 ? implode( ' ', array_slice( $before_words, -$half ) ) : '';
    $after  = implode( ' ', array_slice( $after_words, 0, $half ) );

    $has_more_before = count( $before_words ) > $half || ( $half <= 0 && ! empty( $before_words ) );
    $has_more_after  = count( $after_words ) > $half;

    $snippet = trim( $before . ' ' . $match . ' ' . $after );

    return ( '' !== $before || $has_more_before ? '... ' : '' ) . $snippet . ( '' !== $after || $has_more_after ? ' ...' : '' );
}

// Prepare keyword list and default thumbnail
function init_plugin_suite_live_search_prepare_keywords_and_thumb($term) {
    $keywords = [];

    if ($term) {
        $keywords[] = $term;

        if ( init_plugin_suite_live_search_count_words( $term ) >= 3 ) {
            $keywords = array_merge($keywords, init_plugin_suite_live_search_generate_bigrams($term));
        }

        $single_words = preg_split( '/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY );
        if (!empty($single_words)) {
            $keywords = array_merge($keywords, $single_words);
        }

        $keywords = array_values( array_unique( array_filter( $keywords ) ) );
    }

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    return [$keywords, $default_thumb];
}

// Determine which post types to search against
function init_plugin_suite_live_search_resolve_post_types($options, $args) {
    $from_request = false;

    if (!empty($args['post_types']) && is_array($args['post_types'])) {
        $resolved     = array_map( 'sanitize_key', $args['post_types'] );
        $from_request = true;
    } elseif (!empty($args['post_type']) && is_string($args['post_type'])) {
        $types        = explode( ',', $args['post_type'] );
        $resolved     = array_map( 'sanitize_key', array_filter( array_map( 'trim', $types ) ) );
        $from_request = true;
    } elseif (!empty($options['post_types']) && is_array($options['post_types'])) {
        $resolved = array_map('sanitize_key', $options['post_types']);
    } else {
        $resolved = ['post']; // fallback mặc định
    }

    // Post types passed by callers (shortcode/block attributes, Abilities
    // API input...) must be public or explicitly enabled by the admin —
    // otherwise e.g. post_type="shop_coupon" or "wp_block" could be queried.
    if ( $from_request ) {
        $resolved = init_plugin_suite_live_search_filter_allowed_post_types( $resolved, $options );
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
