<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AI Related Engine
 * - Candidate pool = latest(50) + random-same-category(50)
 * - Normalized weights (sum = 1)
 * - Weighted-random diversification
 * - Fallback: AI -> keyword -> random same category
 * - Filters (prefix-consistent):
 *   - init_plugin_suite_live_search_ai_candidates
 *   - init_plugin_suite_live_search_ai_signals
 *   - init_plugin_suite_live_search_ai_weights
 *   - init_plugin_suite_live_search_ai_score
 */

function init_plugin_suite_live_search_get_related_ai_ids( $post_id, $limit = 5, $post_type = 'post' ) {
    $cache_key = 'init_related_ai_' . $post_id . '_' . $limit . '_' . $post_type;
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
        return [];
    }

    // ==== Build candidate pool ====
    $cats1 = wp_get_post_categories( $post_id );
    $latest_pool = get_posts( [
        'post_type'      => $post_type,
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'post__not_in'   => [ $post_id ],
        'fields'         => 'ids',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
        'ignore_sticky_posts' => true,
    ] );

    $category_pool = [];
    if ( ! empty( $cats1 ) ) {
        $category_pool = get_posts( [
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'post__not_in'   => [ $post_id ],
            'fields'         => 'ids',
            'orderby'        => 'rand',
            'no_found_rows'  => true,
            'ignore_sticky_posts' => true,
            'tax_query'      => [
                [
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $cats1,
                ]
            ],
        ] );
    }

    $candidates = array_values( array_unique( array_merge( (array) $latest_pool, (array) $category_pool ) ) );

    // Cho dev tự sửa candidate pool nếu muốn
    $candidates = apply_filters( 'init_plugin_suite_live_search_ai_candidates', $candidates, $post_id, $post_type );

    if ( empty( $candidates ) ) {
        return [];
    }

    // ==== Prepare base data ====
    $tags1    = wp_get_post_tags( $post_id, [ 'fields' => 'ids' ] );
    $title1   = get_the_title( $post_id );
    $bigrams1 = init_plugin_suite_live_search_extract_bigrams( $title1 );
    $date1    = get_post_time( 'U', false, $post_id );

    $max_comments = 1;
    $max_views    = 1;
    foreach ( $candidates as $cid ) {
        $max_comments = max( $max_comments, (int) get_comments_number( $cid ) );
        $max_views    = max( $max_views, (int) get_post_meta( $cid, '_init_view_count', true ) );
    }

    // ==== Score each candidate ====
    $scored = [];
    foreach ( $candidates as $cid ) {
        // signals default
        $signals = [
            'category'      => 0.0,
            'tag'           => 0.0,
            'comment'       => 0.0,
            'views'         => 0.0,
            'freshness'     => 0.0,
            'title_bigrams' => 0.0,
        ];

        // category overlap (normalized by size of source set)
        $cats2 = wp_get_post_categories( $cid );
        if ( $cats1 && $cats2 ) {
            $signals['category'] = count( array_intersect( $cats1, $cats2 ) ) / max( count( $cats1 ), 1 );
        }

        // tag overlap
        $tags2 = wp_get_post_tags( $cid, [ 'fields' => 'ids' ] );
        if ( $tags1 && $tags2 ) {
            $signals['tag'] = count( array_intersect( $tags1, $tags2 ) ) / max( count( $tags1 ), 1 );
        }

        // engagement normalized (log)
        $cmt = (int) get_comments_number( $cid );
        $signals['comment'] = ( $max_comments > 0 ) ? ( log( 1 + $cmt ) / log( 1 + $max_comments ) ) : 0.0;

        $views = (int) get_post_meta( $cid, '_init_view_count', true );
        $signals['views'] = ( $max_views > 0 ) ? ( log( 1 + $views ) / log( 1 + $max_views ) ) : 0.0;

        // freshness (decay theo chênh lệch ngày giữa 2 bài, 90d half-life feel)
        $date2     = get_post_time( 'U', false, $cid );
        $diff_days = abs( $date1 - $date2 ) / DAY_IN_SECONDS;
        $signals['freshness'] = exp( - $diff_days / 90 );

        // title bigram cosine
        $bigrams2 = init_plugin_suite_live_search_extract_bigrams( get_the_title( $cid ) );
        $signals['title_bigrams'] = init_plugin_suite_live_search_cosine_similarity( $bigrams1, $bigrams2 );

        // Cho dev bơm thêm/tweak signals
        $signals = apply_filters( 'init_plugin_suite_live_search_ai_signals', $signals, $post_id, $cid );

        // weights (sau đó normalize tổng = 1)
        $weights = [
            'category'      => 0.30,
            'tag'           => 0.25,
            'comment'       => 0.15,
            'views'         => 0.15,
            'freshness'     => 0.10,
            'title_bigrams' => 0.20,
        ];
        $weights = apply_filters( 'init_plugin_suite_live_search_ai_weights', $weights, $post_id, $cid );

        // normalize weights
        $sum_w = array_sum( $weights );
        if ( $sum_w > 0 ) {
            foreach ( $weights as $k => $w ) {
                $weights[ $k ] = $w / $sum_w;
            }
        }

        // final score
        $score = 0.0;
        foreach ( $signals as $k => $v ) {
            $score += ( $weights[ $k ] ?? 0 ) * max( 0.0, (float) $v );
        }

        // dev tweak final score if needed
        $score = apply_filters( 'init_plugin_suite_live_search_ai_score', $score, $signals, $post_id, $cid );

        if ( $score > 0 ) {
            $scored[ $cid ] = $score;
        }
    }

    // ==== Fallbacks ====
    if ( empty( $scored ) ) {
        // Fallback 1: keyword engine hiện có
        $fallback = init_plugin_suite_live_search_find_related_ids(
            get_the_title( $post_id ),
            $post_id,
            $limit,
            $post_type
        );
        if ( ! empty( $fallback ) ) {
            set_transient( $cache_key, $fallback, 12 * HOUR_IN_SECONDS );
            return $fallback;
        }

        // Fallback 2: random cùng category
        if ( ! empty( $cats1 ) ) {
            $rand = get_posts( [
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'post__not_in'   => [ $post_id ],
                'fields'         => 'ids',
                'orderby'        => 'rand',
                'no_found_rows'  => true,
                'ignore_sticky_posts' => true,
                'tax_query'      => [
                    [
                        'taxonomy' => 'category',
                        'field'    => 'term_id',
                        'terms'    => $cats1,
                    ]
                ],
            ] );
            if ( ! empty( $rand ) ) {
                set_transient( $cache_key, $rand, 12 * HOUR_IN_SECONDS );
                return $rand;
            }
        }

        return [];
    }

    // ==== Rank + diversify ====
    arsort( $scored );
    $top_ids = array_keys( $scored );

    // top N: lấy cứng top 2, phần còn lại weighted-random từ top 10
    $selected = array_slice( $top_ids, 0, min( 2, $limit ) );

    if ( count( $selected ) < $limit ) {
        $pool   = array_slice( $top_ids, 2, 10 );
        $weights_for_pool = [];
        foreach ( $pool as $pid ) {
            $weights_for_pool[] = $scored[ $pid ];
        }
        $extra = init_plugin_suite_live_search_weighted_random_pick( $pool, $weights_for_pool, $limit - count( $selected ) );
        $selected = array_merge( $selected, $extra );
    }

    set_transient( $cache_key, $selected, 12 * HOUR_IN_SECONDS );
    return $selected;
}

/**
 * Weighted random pick (without replacement)
 */
function init_plugin_suite_live_search_weighted_random_pick( $items, $weights, $count ) {
    $result = [];
    $items  = array_values( (array) $items );
    $weights = array_values( (array) $weights );

    $count = max( 0, (int) $count );
    if ( empty( $items ) || empty( $weights ) || $count === 0 ) return $result;

    // lọc weight <= 0
    $filtered_items = [];
    $filtered_weights = [];
    foreach ( $items as $i => $it ) {
        $w = (float) ( $weights[ $i ] ?? 0 );
        if ( $w > 0 ) {
            $filtered_items[]   = $it;
            $filtered_weights[] = $w;
        }
    }

    $items   = $filtered_items;
    $weights = $filtered_weights;

    while ( $count > 0 && ! empty( $items ) ) {
        $total = array_sum( $weights );
        if ( $total <= 0 ) break;

        $r = mt_rand() / mt_getrandmax() * $total;
        $acc = 0.0;
        $pick_index = null;

        foreach ( $items as $idx => $item ) {
            $acc += $weights[ $idx ];
            if ( $r <= $acc ) {
                $pick_index = $idx;
                break;
            }
        }

        if ( $pick_index === null ) {
            // fallback nếu do lỗi số học
            $pick_index = array_key_first( $items );
        }

        $result[] = $items[ $pick_index ];
        array_splice( $items, $pick_index, 1 );
        array_splice( $weights, $pick_index, 1 );
        $count--;
    }

    return $result;
}

/**
 * Bigram extraction (simple tokenizer)
 */
function init_plugin_suite_live_search_extract_bigrams( $text ) {
    $text  = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text  = mb_strtolower( wp_strip_all_tags( $text ), 'UTF-8' );
    $text  = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
    $words = preg_split( '/\s+/', trim( (string) $text ) );

    $out = [];
    if ( $words && count( $words ) > 1 ) {
        $n = count( $words );
        for ( $i = 0; $i < $n - 1; $i++ ) {
            // bỏ bigram quá ngắn kiểu 1 ký tự 1 bên
            if ( mb_strlen( $words[$i], 'UTF-8' ) < 2 || mb_strlen( $words[$i+1], 'UTF-8' ) < 2 ) {
                continue;
            }
            $out[ $words[$i] . ' ' . $words[$i+1] ] = 1;
        }
    }
    return $out;
}

/**
 * Cosine similarity between two bigram sets
 */
function init_plugin_suite_live_search_cosine_similarity( $v1, $v2 ) {
    if ( empty( $v1 ) || empty( $v2 ) ) return 0.0;
    $intersect = array_intersect_key( (array) $v1, (array) $v2 );
    $dot  = count( $intersect );
    $norm1 = sqrt( max( count( $v1 ), 1 ) );
    $norm2 = sqrt( max( count( $v2 ), 1 ) );
    if ( $norm1 == 0.0 || $norm2 == 0.0 ) return 0.0;
    return $dot / ( $norm1 * $norm2 );
}
