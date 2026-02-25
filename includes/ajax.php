<?php
/**
 * Keyword Generator v3 — BM25 + NPMI + LLR + MMR
 *
 * Nguồn dữ liệu : chỉ POST TITLE (chính xác, nhẹ, phù hợp mọi site)
 * N-gram        : bigram (2 từ) + trigram (3 từ)
 * Scoring       : BM25 × NPMI × LLR-boost
 * Penalty       : cross-document frequency (phrase quá phổ biến → hạ điểm)
 * Selection     : MMR – Maximal Marginal Relevance (đa dạng + điểm cao)
 * Ngôn ngữ     : auto-detect theo locale, stop-words riêng cho mỗi ngôn ngữ
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action(
    'wp_ajax_init_plugin_suite_live_search_generate_keywords',
    'init_plugin_suite_live_search_generate_keywords_v3'
);

// ═══════════════════════════════════════════════════════════════════════════
// Main handler
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_generate_keywords_v3() {

    /* ── Auth ─────────────────────────────────────────────────────────── */
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
        : '';

    if ( ! wp_verify_nonce( $nonce, 'init_live_search_admin_nonce' ) ) {
        wp_send_json_error( 'Invalid nonce', 403 );
    }

    /* ── Config ───────────────────────────────────────────────────────── */
    $options    = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    $post_types = ! empty( $options['post_types'] ) ? (array) $options['post_types'] : [ 'post' ];

    /* ── Fetch post IDs ───────────────────────────────────────────────── */
    $post_ids = get_posts( [
        'post_type'           => $post_types,
        'posts_per_page'      => -1,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
        'fields'              => 'ids',
    ] );

    if ( empty( $post_ids ) ) {
        wp_send_json_error( 'No posts found' );
    }

    /* ── Locale & stop-words ──────────────────────────────────────────── */
    $locale = get_locale();
    $is_vi  = ( $locale === 'vi' || str_starts_with( $locale, 'vi_' ) );

    $stop_words = $is_vi
        ? init_plugin_suite_live_search_stop_words_vi()
        : init_plugin_suite_live_search_stop_words_en();

    $stop_words     = apply_filters( 'init_plugin_suite_live_search_stop_single_words', $stop_words, $locale );
    $stop_words_map = array_fill_keys( $stop_words, true );

    /* ── Build documents (title only, no artificial repetition) ──────── */
    $documents      = [];   // post_id => [token, …]
    $post_weights   = [];   // post_id => engagement weight
    $global_unigram = [];   // token   => total count
    $total_tokens   = 0;

    foreach ( $post_ids as $pid ) {
        $title = get_the_title( $pid );
        if ( empty( $title ) ) continue;

        // Engagement weight — boosts posts users actually care about
        $comments    = (int) get_comments_number( $pid );
        $views       = (int) get_post_meta( $pid, '_init_view_count', true );
        $post_weight = 1.0 + log( 1 + $comments ) + log( 1 + $views );
        $post_weights[ $pid ] = $post_weight;

        $tokens = init_plugin_suite_live_search_tokenise( $title, $stop_words, $is_vi );
        if ( empty( $tokens ) ) continue;

        $documents[ $pid ] = $tokens;

        foreach ( $tokens as $t ) {
            $global_unigram[ $t ] = ( $global_unigram[ $t ] ?? 0 ) + 1;
            ++$total_tokens;
        }
    }

    if ( empty( $documents ) ) {
        wp_send_json_error( 'No valid documents' );
    }

    $total_docs = count( $documents );

    /* ── BM25 unigram scores ──────────────────────────────────────────── */
    $term_scores = init_plugin_suite_live_search_bm25( $documents, $post_weights );

    /* ── N-gram stats ─────────────────────────────────────────────────── */
    $bi_stats  = init_plugin_suite_live_search_ngram_stats( $documents, 2 );
    $tri_stats = init_plugin_suite_live_search_ngram_stats( $documents, 3 );

    /* ── Score n-grams ────────────────────────────────────────────────── */
    $bi_scored  = init_plugin_suite_live_search_score_ngrams(
        $documents, $term_scores, $post_weights,
        $global_unigram, $total_tokens,
        $bi_stats, $stop_words_map, 2
    );
    $tri_scored = init_plugin_suite_live_search_score_ngrams(
        $documents, $term_scores, $post_weights,
        $global_unigram, $total_tokens,
        $tri_stats, $stop_words_map, 3
    );

    // Merge pools — trigrams boost ×1.2 (more specific → more valuable)
    $all_scored = $bi_scored;
    foreach ( $tri_scored as $ng => $s ) {
        $all_scored[ $ng ] = $s * 1.2;
    }

    /* ── Cross-document frequency penalty ────────────────────────────── */
    // A phrase appearing in >60% of docs is too generic for a plugin
    // serving diverse sites → down-rank proportionally up to ×0.3.
    $doc_ng_freq = init_plugin_suite_live_search_doc_ngram_freq( $documents, [ 2, 3 ] );

    foreach ( $all_scored as $ng => &$score ) {
        $ratio = ( $doc_ng_freq[ $ng ] ?? 0 ) / $total_docs;
        if ( $ratio > 0.6 ) {
            $penalty = 1.0 - ( ( $ratio - 0.6 ) / 0.4 ) * 0.7; // min ×0.3
            $score  *= max( $penalty, 0.3 );
        }
    }
    unset( $score );

    /* ── Filter & rank ────────────────────────────────────────────────── */
    $filtered = init_plugin_suite_live_search_filter_ngrams( $all_scored, $locale, $stop_words_map );

    if ( empty( $filtered ) ) {
        $filtered = init_plugin_suite_live_search_fallback_ngrams( $all_scored );
    }

    if ( empty( $filtered ) ) {
        wp_send_json_error( 'No keywords found' );
    }

    /* ── MMR diverse selection ────────────────────────────────────────── */
    $selected = init_plugin_suite_live_search_mmr_selection( $filtered, 15, 0.65 );

    wp_send_json_success( implode( ', ', $selected ) );
}

// ═══════════════════════════════════════════════════════════════════════════
// Stop-word lists
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_stop_words_vi(): array {
    return [
        // Giới từ, liên từ, trợ từ
        'là','vì','và','các','một','có','trong','khi','những','được','lúc',
        'này','đây','rằng','thì','sự','của','cho','từ','trên','dưới','về',
        'tại','với','không','đã','sẽ','bị','làm','nào','như','theo','giữa',
        'sau','trước','hay','hoặc','mà','nên','vẫn','cũng','đến','ra','vào',
        'lên','xuống','qua','lại','đi','đó','thế','thôi','nhưng','hơn','nhất',
        'rất','quá','còn','chỉ','mình','ta','bạn','họ','chúng','tôi','anh',
        'chị','em','ông','bà','cô','chú','bác','nó','gì','sao','đâu','kể',
        'dù','dù','hết','khác','nhiều','ít','cần','thể','ai','mọi','luôn',
        // Từ meta truyện/blog hay gặp
        'chap','chương','phần','tập','số','hồi','bài','mục','kỳ','kì',
    ];
}

function init_plugin_suite_live_search_stop_words_en(): array {
    return [
        // Articles, conjunctions, prepositions
        'a','an','the','and','or','but','nor','so','yet','for','of','to',
        'in','on','at','by','up','as','is','are','was','were','be','been',
        'being','have','has','had','do','does','did','will','would','could',
        'should','may','might','must','can','shall','not','no','nor','than',
        'then','too','very','just','also','both','each','few','more','most',
        'other','some','such','into','over','after','before','between','out',
        'from','with','this','that','these','those','what','which','who',
        'how','when','where','why','all','any','its','our','your','my','his',
        'her','we','you','they','it','he','she','i','me','him','us','them',
        // Common filler words in post titles
        'new','get','top','best','how','why','what','review','list','guide',
        'tips','free','now','here','using','make','use','via','per','vs',
        'one','two','three','part','step','ways','week','year','day','time',
    ];
}

// ═══════════════════════════════════════════════════════════════════════════
// Tokeniser
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_tokenise( string $text, array $stop_words, bool $is_vi ): array {
    if ( function_exists( 'normalizer_normalize' ) ) {
        $text = normalizer_normalize( $text, Normalizer::FORM_C );
    }

    $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = wp_strip_all_tags( $text );
    $text = mb_strtolower( trim( $text ), 'UTF-8' );

    // Normalise various dash characters → space (hyphens split tokens)
    $text = preg_replace( '/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2212}\-]+/u', ' ', $text ) ?? '';

    $text = str_replace( '&nbsp;', ' ', $text );

    // Strip punctuation (keep Unicode letters/numbers and spaces)
    $text = $is_vi
        ? ( preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $text ) ?? '' )
        : ( preg_replace( '/[^a-zA-Z0-9\s]/', ' ', $text ) ?? '' );

    $text = preg_replace( '/\s+/u', ' ', $text ) ?? '';

    if ( $text === '' ) return [];

    $stop_map = array_fill_keys( $stop_words, true );
    $tokens   = [];

    foreach ( explode( ' ', trim( $text ) ) as $w ) {
        if ( $w === '' ) continue;
        if ( isset( $stop_map[ $w ] ) ) continue;
        if ( mb_strlen( $w, 'UTF-8' ) < 2 ) continue;
        if ( preg_match( '/^\d+$/u', $w ) ) continue;           // pure numbers
        if ( preg_match( '/^[^\p{L}]+$/u', $w ) ) continue;     // no letters at all
        $tokens[] = $w;
    }

    return $tokens;
}

// ═══════════════════════════════════════════════════════════════════════════
// BM25 (k1 = 1.5, b = 0.75)
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_bm25( array $documents, array $post_weights, float $k1 = 1.5, float $b = 0.75 ): array {
    $total_docs = count( $documents );
    if ( $total_docs === 0 ) return [];

    $doc_freq    = [];
    $doc_lengths = [];
    $sum_len     = 0;

    foreach ( $documents as $pid => $terms ) {
        $len               = count( $terms );
        $doc_lengths[$pid] = $len;
        $sum_len          += $len;
        foreach ( array_unique( $terms ) as $t ) {
            $doc_freq[$t] = ( $doc_freq[$t] ?? 0 ) + 1;
        }
    }

    $avgdl = max( $sum_len / $total_docs, 1 );

    $term_scores = [];
    foreach ( $documents as $pid => $terms ) {
        $dl   = $doc_lengths[$pid];
        $freq = array_count_values( $terms );
        $wdoc = $post_weights[$pid] ?? 1.0;

        foreach ( $freq as $term => $f ) {
            $df   = $doc_freq[$term] ?? 0;
            $idf  = log( ( $total_docs - $df + 0.5 ) / ( $df + 0.5 ) + 1 );
            $den  = $f + $k1 * ( 1 - $b + $b * $dl / $avgdl );
            $bm25 = $idf * ( $f * ( $k1 + 1 ) ) / max( $den, 1e-9 );
            $term_scores[$term] = ( $term_scores[$term] ?? 0.0 ) + $bm25 * $wdoc;
        }
    }

    return $term_scores;
}

// ═══════════════════════════════════════════════════════════════════════════
// N-gram statistics
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_ngram_stats( array $documents, int $n ): array {
    $counts = [];   // ngram  => raw count
    $left   = [];   // first token => marginal count
    $right  = [];   // last  token => marginal count
    $N      = 0;

    foreach ( $documents as $terms ) {
        $len = count( $terms );
        for ( $i = 0; $i <= $len - $n; $i++ ) {
            $ng          = implode( ' ', array_slice( $terms, $i, $n ) );
            $counts[$ng] = ( $counts[$ng] ?? 0 ) + 1;
            $left[ $terms[$i] ]            = ( $left[ $terms[$i] ] ?? 0 ) + 1;
            $right[ $terms[$i + $n - 1] ]  = ( $right[ $terms[$i + $n - 1] ] ?? 0 ) + 1;
            ++$N;
        }
    }

    return [ 'counts' => $counts, 'left' => $left, 'right' => $right, 'N' => $N ];
}

// ═══════════════════════════════════════════════════════════════════════════
// Score n-grams: weighted count × BM25 pair score × NPMI bonus × LLR boost
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_score_ngrams(
    array $documents,
    array $term_scores,
    array $post_weights,
    array $global_unigram,
    int   $total_tokens,
    array $stats,
    array $stop_words_map,
    int   $n
): array {

    $N_tok = max( $total_tokens, 1 );
    $N_ng  = max( $stats['N'], 1 );
    $raw   = $stats['counts'];

    /* ── Weighted occurrence count per n-gram ─────────────────────────── */
    $weighted = [];
    foreach ( $documents as $pid => $words ) {
        $wdoc = $post_weights[$pid] ?? 1.0;
        $len  = count( $words );

        for ( $i = 0; $i <= $len - $n; $i++ ) {
            $slice = array_slice( $words, $i, $n );

            // Skip if any token is a stop word
            foreach ( $slice as $w ) {
                if ( isset( $stop_words_map[$w] ) ) continue 2;
            }

            // Skip if all tokens are identical
            if ( count( array_unique( $slice ) ) < $n ) continue;

            $ng            = implode( ' ', $slice );
            $weighted[$ng] = ( $weighted[$ng] ?? 0.0 ) + $wdoc;
        }
    }

    if ( empty( $weighted ) ) return [];

    /* ── Compute final score ──────────────────────────────────────────── */
    $scored = [];
    foreach ( $weighted as $ng => $wcount ) {
        $parts = explode( ' ', $ng );

        // BM25 sum across constituent tokens
        $bm_sum = 0.0;
        foreach ( $parts as $p ) {
            $bm_sum += $term_scores[$p] ?? 0.0;
        }

        // NPMI — both p_joint AND p_ng use N_tok (uniform base)
        $p_joint = 1.0;
        foreach ( $parts as $p ) {
            $p_joint *= max( $global_unigram[$p] ?? 0, 1 ) / $N_tok;
        }
        $c_ng = max( $raw[$ng] ?? 0, 0 );
        $p_ng = ( $c_ng + 1 ) / ( $N_tok + 1 );   // smoothed, same base as p_joint

        $pmi  = log( $p_ng / max( $p_joint, 1e-15 ) );
        $npmi = $pmi / max( -log( $p_ng ), 1e-9 ); // ∈ [-1, 1]

        $base = $wcount * $bm_sum * ( 1.0 + max( $npmi, 0.0 ) );

        // LLR / G-test boost
        if ( $n === 2 ) {
            // Exact Dunning 2×2 contingency table
            $a   = $c_ng;
            $b_v = max( ( $stats['left'][ $parts[0] ] ?? 0 ) - $a, 0 );
            $c_v = max( ( $stats['right'][ $parts[1] ] ?? 0 ) - $a, 0 );
            $d_v = max( $N_ng - $a - $b_v - $c_v, 0 );
            $llr = init_plugin_suite_live_search_llr_dunning( $a, $b_v, $c_v, $d_v );
        } else {
            // Trigram: approximate LLR from observed vs expected
            $expected = $p_joint * $N_tok;
            $llr      = ( $c_ng > 0 && $expected > 0 )
                ? max( 2.0 * $c_ng * log( $c_ng / $expected ), 0.0 )
                : 0.0;
        }

        $boost         = 1.0 + min( $llr / 12.0, 2.0 ); // cap: max ×3 total
        $scored[$ng]   = $base * $boost;
    }

    return $scored;
}

// ═══════════════════════════════════════════════════════════════════════════
// LLR — Dunning G-test
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_ll( float $k, float $n ): float {
    return ( $k > 0 && $n > 0 ) ? $k * log( $k / $n ) : 0.0;
}

function init_plugin_suite_live_search_llr_dunning( float $a, float $b, float $c, float $d ): float {
    $n = $a + $b + $c + $d;
    if ( $n <= 0 ) return 0.0;

    return 2.0 * (
        init_plugin_suite_live_search_ll( $a, ( ( $a + $b ) * ( $a + $c ) ) / $n ) +
        init_plugin_suite_live_search_ll( $b, ( ( $a + $b ) * ( $b + $d ) ) / $n ) +
        init_plugin_suite_live_search_ll( $c, ( ( $c + $d ) * ( $a + $c ) ) / $n ) +
        init_plugin_suite_live_search_ll( $d, ( ( $c + $d ) * ( $b + $d ) ) / $n )
    );
}

// ═══════════════════════════════════════════════════════════════════════════
// Cross-document n-gram frequency (how many docs contain the phrase)
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_doc_ngram_freq( array $documents, array $sizes ): array {
    $freq = [];
    foreach ( $documents as $terms ) {
        $seen = [];
        $len  = count( $terms );
        foreach ( $sizes as $n ) {
            for ( $i = 0; $i <= $len - $n; $i++ ) {
                $ng = implode( ' ', array_slice( $terms, $i, $n ) );
                if ( ! isset( $seen[$ng] ) ) {
                    $freq[$ng] = ( $freq[$ng] ?? 0 ) + 1;
                    $seen[$ng] = true;
                }
            }
        }
    }
    return $freq;
}

// ═══════════════════════════════════════════════════════════════════════════
// Filter — remove noise, rank by score
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_filter_ngrams( array $scored, string $locale, array $stop_words_map ): array {
    $is_vi = ( $locale === 'vi' || str_starts_with( $locale, 'vi_' ) );

    $stop_phrases = $is_vi
        ? [ 'là gì','và các','có thể','với các','là một','trong khi','của các','cho các','từ các' ]
        : [ 'what is','and the','can be','with the','this is','of the','for the','in the','on the','by the' ];

    $stop_phrases = apply_filters( 'init_plugin_suite_live_search_stop_words', $stop_phrases, $locale );
    $stop_ph_map  = array_fill_keys( $stop_phrases, true );

    if ( empty( $scored ) ) return [];

    $avg       = array_sum( $scored ) / count( $scored );
    $threshold = $avg * 0.35; // generous lower bound — MMR will handle diversity

    $filtered = [];
    foreach ( $scored as $ng => $score ) {
        if ( $score < $threshold ) continue;
        if ( isset( $stop_ph_map[$ng] ) ) continue;
        if ( preg_match( '/^\d[\d\s]*$/u', $ng ) ) continue;   // pure digits
        if ( preg_match( '/\b\d{4,}\b/u', $ng ) ) continue;    // 4-digit years etc.

        $parts = preg_split( '/\s+/u', trim( $ng ) );
        $pc    = count( $parts );
        if ( $pc < 2 || $pc > 3 ) continue;

        $clean_len = mb_strlen( str_replace( ' ', '', $ng ), 'UTF-8' );
        if ( $clean_len < 4 || $clean_len > 45 ) continue;     // wider max for trigrams

        // Every token must be ≥ 2 chars and not a stop word
        foreach ( $parts as $p ) {
            if ( mb_strlen( $p, 'UTF-8' ) < 2 ) continue 2;
            if ( isset( $stop_words_map[$p] ) ) continue 2;
        }

        $filtered[$ng] = $score;
    }

    arsort( $filtered );
    return array_keys( array_slice( $filtered, 0, 80, true ) );
}

function init_plugin_suite_live_search_fallback_ngrams( array $scored ): array {
    $items = [];
    foreach ( $scored as $ng => $score ) {
        $parts = preg_split( '/\s+/u', trim( $ng ) );
        $pc    = count( $parts );
        if ( $pc < 2 || $pc > 3 ) continue;
        if ( preg_match( '/^\d[\d\s]*$/u', $ng ) ) continue;
        $cl = mb_strlen( str_replace( ' ', '', $ng ), 'UTF-8' );
        if ( $cl < 4 || $cl > 45 ) continue;
        foreach ( $parts as $p ) {
            if ( mb_strlen( $p, 'UTF-8' ) < 2 ) continue 2;
        }
        $items[$ng] = $score;
    }
    arsort( $items );
    return array_keys( array_slice( $items, 0, 50, true ) );
}

// ═══════════════════════════════════════════════════════════════════════════
// MMR — Maximal Marginal Relevance
//   λ = 0.65 → prefer relevance over diversity (tunable)
//   Similarity: Jaccard over token sets
// ═══════════════════════════════════════════════════════════════════════════

function init_plugin_suite_live_search_mmr_selection( array $keywords, int $limit = 15, float $lambda = 0.65 ): array {
    if ( count( $keywords ) <= $limit ) return $keywords;

    // Rank-based relevance proxy (keywords already sorted score DESC)
    $total = count( $keywords );
    $rel   = [];
    foreach ( $keywords as $i => $kw ) {
        $rel[$kw] = ( $total - $i ) / $total;
    }

    // Token sets for Jaccard
    $tok = [];
    foreach ( $keywords as $kw ) {
        $tok[$kw] = array_flip( explode( ' ', $kw ) );
    }

    $selected  = [];
    $remaining = $keywords;

    while ( count( $selected ) < $limit && ! empty( $remaining ) ) {
        $best_kw    = null;
        $best_score = -INF;

        foreach ( $remaining as $kw ) {
            // Max Jaccard similarity to already-selected set
            $max_sim = 0.0;
            foreach ( $selected as $s ) {
                $inter   = count( array_intersect_key( $tok[$kw], $tok[$s] ) );
                $union   = count( $tok[$kw] ) + count( $tok[$s] ) - $inter;
                $sim     = $union > 0 ? $inter / $union : 0.0;
                if ( $sim > $max_sim ) $max_sim = $sim;
            }

            $mmr = $lambda * $rel[$kw] - ( 1.0 - $lambda ) * $max_sim;
            if ( $mmr > $best_score ) {
                $best_score = $mmr;
                $best_kw    = $kw;
            }
        }

        if ( $best_kw === null ) break;
        $selected[] = $best_kw;
        $remaining  = array_values( array_diff( $remaining, [ $best_kw ] ) );
    }

    return $selected;
}
