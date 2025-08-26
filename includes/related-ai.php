<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AI Related Engine (NEXT-LEVEL)
 * - Candidate pool = latest(50) + random-same-category(50)
 * - Prime caches to avoid N+1 (post/meta/terms)
 * - Normalized weights (sum = 1)
 * - Signals: category, tag, comment, views, title_bigrams, recency(new), time_gap(new)
 * - Diversify: MMR (Max Marginal Relevance) với lambda có filter
 * - Fallback: AI -> keyword -> random same category
 * - Cache key có algo version để invalidation “mượt”
 * - Filters (prefix-consistent, giữ nguyên + bổ sung):
 *   - init_plugin_suite_live_search_ai_candidates
 *   - init_plugin_suite_live_search_ai_signals
 *   - init_plugin_suite_live_search_ai_weights
 *   - init_plugin_suite_live_search_ai_score
 *   - init_plugin_suite_live_search_ai_selected          (mới, optional post-process selected)
 *   - init_plugin_suite_live_search_ai_half_life_recency (mới)
 *   - init_plugin_suite_live_search_ai_half_life_gap     (mới)
 *   - init_plugin_suite_live_search_ai_mmr_lambda        (mới)
 */

function init_plugin_suite_live_search_get_related_ai_ids( $post_id, $limit = 5, $post_type = 'post' ) {
    $post_id   = intval( $post_id );
    $limit     = max( 1, intval( $limit ) );
    $post_type = sanitize_key( $post_type );

    // algo version để invalidation cache khi đổi logic
    $algo_ver  = 'v2';
    $cache_key = 'init_related_ai_' . $algo_ver . '_' . $post_id . '_' . $limit . '_' . $post_type;

    $cached = get_transient( $cache_key );
    if ( $cached !== false ) return $cached;

    if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
        return [];
    }

    // ==== Build candidate pool ====
    $cats1 = wp_get_post_categories( $post_id );

    $latest_pool = get_posts( [
        'post_type'           => $post_type,
        'post_status'         => 'publish',
        'posts_per_page'      => 50,
        'post__not_in'        => [ $post_id ],
        'fields'              => 'ids',
        'orderby'             => 'date',
        'order'               => 'DESC',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
    ] );

    $category_pool = [];
    if ( ! empty( $cats1 ) ) {
        $category_pool = get_posts( [
            'post_type'           => $post_type,
            'post_status'         => 'publish',
            'posts_per_page'      => 50,
            'post__not_in'        => [ $post_id ],
            'fields'              => 'ids',
            'orderby'             => 'rand',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
            'tax_query'           => [
                [
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => $cats1,
                ]
            ],
        ] );
    }

    $candidates = array_values( array_unique( array_merge( (array) $latest_pool, (array) $category_pool ) ) );

    // Cho dev tùy chỉnh candidate pool
    $candidates = apply_filters( 'init_plugin_suite_live_search_ai_candidates', $candidates, $post_id, $post_type );

    // Chuẩn hóa + lọc hợp lệ + tránh tự tham chiếu
    $candidates = array_values( array_unique( array_map( 'intval', (array) $candidates ) ) );
    $candidates = array_filter( $candidates, function( $id ) use ( $post_id ) {
        return $id && $id !== $post_id && get_post_status( $id ) === 'publish';
    } );

    if ( empty( $candidates ) ) {
        return [];
    }

    // ==== Prime caches để tránh N+1 ====
    if ( function_exists( '_prime_post_caches' ) ) {
        _prime_post_caches( $candidates, true, true ); // posts + meta
    }
    if ( function_exists( 'update_object_term_cache' ) ) {
        update_object_term_cache( $candidates, $post_type ); // terms
    }

    // ==== Prepare base data ====
    $tags1    = wp_get_post_tags( $post_id, [ 'fields' => 'ids' ] );
    $title1   = get_the_title( $post_id );
    $bigrams1 = init_plugin_suite_live_search_extract_bigrams( $title1 );
    $date1    = get_post_time( 'U', false, $post_id );
    $nowU     = current_time( 'timestamp' );

    $max_comments = 1;
    $max_views    = 1;
    foreach ( $candidates as $cid ) {
        $max_comments = max( $max_comments, (int) get_comments_number( $cid ) );
        $max_views    = max( $max_views,    (int) get_post_meta( $cid, '_init_view_count', true ) );
    }

    $half_life_recency = (int) apply_filters( 'init_plugin_suite_live_search_ai_half_life_recency', 60 ); // days
    $half_life_gap     = (int) apply_filters( 'init_plugin_suite_live_search_ai_half_life_gap', 90 );     // days

    // ==== Score each candidate ====
    $scored = [];
    foreach ( $candidates as $cid ) {
        // signals default
        $signals = [
            'category'      => 0.0,
            'tag'           => 0.0,
            'comment'       => 0.0,
            'views'         => 0.0,
            'recency'       => 0.0, // NEW: mới/ lâu so với hiện tại
            'time_gap'      => 0.0, // NEW: chênh lệch ngày giữa 2 bài
            'title_bigrams' => 0.0,
        ];

        // category overlap
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

        // freshness tách 2 thành phần
        $date2     = get_post_time( 'U', false, $cid );
        $age_days  = max( 0, ( $nowU - $date2 ) / DAY_IN_SECONDS );
        $diff_days = abs( $date1 - $date2 ) / DAY_IN_SECONDS;

        $signals['recency']  = exp( - $age_days  / max( 1, $half_life_recency ) );
        $signals['time_gap'] = exp( - $diff_days / max( 1, $half_life_gap ) );

        // title bigram cosine
        $bigrams2 = init_plugin_suite_live_search_extract_bigrams( get_the_title( $cid ) );
        $signals['title_bigrams'] = init_plugin_suite_live_search_cosine_similarity( $bigrams1, $bigrams2 );

        // Cho dev tweak signals
        $signals = apply_filters( 'init_plugin_suite_live_search_ai_signals', $signals, $post_id, $cid );

        // weights (normalize tổng = 1)
        $weights = [
            'category'      => 0.28,
            'tag'           => 0.22,
            'comment'       => 0.12,
            'views'         => 0.12,
            'recency'       => 0.12, // NEW
            'time_gap'      => 0.06, // NEW
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
                'post_type'           => $post_type,
                'post_status'         => 'publish',
                'posts_per_page'      => $limit,
                'post__not_in'        => [ $post_id ],
                'fields'              => 'ids',
                'orderby'             => 'rand',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
                'tax_query'           => [
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

    // ==== Rank + diversify (MMR) ====
    arsort( $scored );
    $ranked  = array_keys( $scored );
    $lambda  = (float) apply_filters( 'init_plugin_suite_live_search_ai_mmr_lambda', 0.75 ); // 1.0 = chỉ relevance
    $k       = max( 1, (int) $limit );
    $selected = [];

    // Để giảm gọi lặp lại, cache nhẹ dữ liệu similarity
    $cache_title_bigrams = [];
    $cache_cats = [];
    $cache_tags = [];

    $get_bigrams = function( $pid ) use ( &$cache_title_bigrams ) {
        if ( ! isset( $cache_title_bigrams[ $pid ] ) ) {
            $cache_title_bigrams[ $pid ] = init_plugin_suite_live_search_extract_bigrams( get_the_title( $pid ) );
        }
        return $cache_title_bigrams[ $pid ];
    };
    $get_cats = function( $pid ) use ( &$cache_cats ) {
        if ( ! isset( $cache_cats[ $pid ] ) ) {
            $cache_cats[ $pid ] = wp_get_post_categories( $pid );
        }
        return $cache_cats[ $pid ];
    };
    $get_tags = function( $pid ) use ( &$cache_tags ) {
        if ( ! isset( $cache_tags[ $pid ] ) ) {
            $cache_tags[ $pid ] = wp_get_post_tags( $pid, [ 'fields' => 'ids' ] );
        }
        return $cache_tags[ $pid ];
    };

    while ( count( $selected ) < $k && ! empty( $ranked ) ) {
        $best_id  = null;
        $best_val = -1;

        foreach ( $ranked as $cand_id ) {
            $rel = (float) $scored[ $cand_id ];

            // diversity penalty: max similarity với các bài đã chọn
            $div_pen = 0.0;
            foreach ( $selected as $sel_id ) {
                $sim = 0.0;

                // category Jaccard
                $c1 = (array) $get_cats( $cand_id );
                $c2 = (array) $get_cats( $sel_id );
                if ( $c1 && $c2 ) {
                    $sim_c = count( array_intersect( $c1, $c2 ) ) / max( 1, count( array_unique( array_merge( $c1, $c2 ) ) ) );
                    $sim = max( $sim, $sim_c );
                }

                // tag Jaccard
                $t1 = (array) $get_tags( $cand_id );
                $t2 = (array) $get_tags( $sel_id );
                if ( $t1 && $t2 ) {
                    $sim_t = count( array_intersect( $t1, $t2 ) ) / max( 1, count( array_unique( array_merge( $t1, $t2 ) ) ) );
                    $sim = max( $sim, $sim_t );
                }

                // title bigrams cosine
                $bg1 = (array) $get_bigrams( $cand_id );
                $bg2 = (array) $get_bigrams( $sel_id );
                $sim_b = init_plugin_suite_live_search_cosine_similarity( $bg1, $bg2 );
                $sim = max( $sim, $sim_b );

                $div_pen = max( $div_pen, $sim );
            }

            $mmr = $lambda * $rel - ( 1 - $lambda ) * $div_pen;

            if ( $mmr > $best_val ) {
                $best_val = $mmr;
                $best_id  = $cand_id;
            }
        }

        if ( $best_id === null ) break;
        $selected[] = $best_id;
        $ranked = array_values( array_diff( $ranked, [ $best_id ] ) );
    }

    // Optional hook cho “spice” hoặc reorder nhẹ
    $selected = apply_filters( 'init_plugin_suite_live_search_ai_selected', $selected, $scored, $post_id );

    set_transient( $cache_key, $selected, 12 * HOUR_IN_SECONDS );
    return $selected;
}

/**
 * Weighted random pick (without replacement)
 */
function init_plugin_suite_live_search_weighted_random_pick( $items, $weights, $count ) {
    $result  = [];
    $items   = array_values( (array) $items );
    $weights = array_values( (array) $weights );

    $count = max( 0, (int) $count );
    if ( empty( $items ) || empty( $weights ) || $count === 0 ) return $result;

    // lọc weight <= 0
    $filtered_items   = [];
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

        // RNG fix: dùng wp_rand chuẩn + thang về [0,total]
        $r = wp_rand( 0, PHP_INT_MAX ) / (float) PHP_INT_MAX * $total;

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
 * (TF=1 mỗi bigram; L2 normalize để tránh bias tiêu đề dài)
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
