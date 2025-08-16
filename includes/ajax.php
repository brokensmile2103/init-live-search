<?php
/**
 * Bigram Keyword Generator (BM25 + NPMI + LLR)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_ajax_init_plugin_suite_live_search_generate_keywords', 'init_plugin_suite_live_search_generate_keywords_enhanced');

function init_plugin_suite_live_search_generate_keywords_enhanced() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';
    if (!wp_verify_nonce($nonce, 'init_live_search_admin_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
    }

    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $post_types = !empty($options['post_types']) ? (array) $options['post_types'] : ['post'];

    // Query tối ưu: chỉ lấy IDs
    $post_ids = get_posts([
        'post_type'           => $post_types,
        'posts_per_page'      => -1,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
        'fields'              => 'ids',
    ]);

    if (empty($post_ids)) {
        wp_send_json_error('No posts found');
    }

    $locale = get_locale();
    $is_vietnamese = ($locale === 'vi' || strpos($locale, 'vi_') === 0);

    // Stop words đơn
    $default_stop_words = $is_vietnamese
        ? ['là','vì','và','các','một','có','trong','khi','những','được','lúc','này','đây','rằng','thì','sự','chap','chương','của','cho','từ','trên','dưới','về','tại','với','không','đã','sẽ','bị','làm','nào','như','theo','giữa','sau','trước']
        : ['is','are','the','and','with','this','that','of','for','to','in','on','it','be','by','as','was','were','been','have','has','had','do','does','did','will','would','could','should','may','might','must','can','shall'];
    $stop_words = apply_filters('init_plugin_suite_live_search_stop_single_words', $default_stop_words, $locale);
    $stop_words_map = array_fill_keys($stop_words, true);

    // Thu thập tài liệu (TITLE x3), trọng số bài viết, thống kê unigram
    $documents = [];         // doc_id => [tokens...]
    $post_weights = [];      // doc_id => weight
    $global_unigram = [];    // term => total freq
    $total_tokens = 0;

    foreach ($post_ids as $post_id) {
        $title = get_the_title($post_id);
        if (empty($title)) continue;

        // Trọng số độ quan tâm bài viết
        $comment_count = (int) get_comments_number($post_id);
        $view_count    = (int) get_post_meta($post_id, '_init_view_count', true);
        $post_weight   = 1 + log(1 + $comment_count) + log(1 + $view_count);
        $post_weights[$post_id] = $post_weight;

        // Chỉ TITLE, nhân 3 để tăng trọng số
        $combined_text = $title . ' ' . $title . ' ' . $title;

        $tokens = init_plugin_suite_live_search_process_text($combined_text, $stop_words, $is_vietnamese);
        if (empty($tokens)) continue;

        $documents[$post_id] = $tokens;

        foreach ($tokens as $t) {
            $global_unigram[$t] = ($global_unigram[$t] ?? 0) + 1;
            $total_tokens++;
        }
    }

    if (empty($documents)) {
        wp_send_json_error('No valid documents');
    }

    // 1) BM25 cho trọng số từ
    $term_scores = init_plugin_suite_live_search_calculate_bm25($documents, $post_weights);

    // 2) Thống kê bigram toàn cục (raw) để tính NPMI/LLR
    $bigram_stats = init_plugin_suite_live_search_build_bigram_stats($documents);

    // 3) Sinh & chấm điểm BIGRAMS: BM25 + NPMI + LLR (Dunning)
    $bigrams_scored = init_plugin_suite_live_search_generate_scored_bigrams_bm25_npmi_llr(
        $documents,
        $term_scores,
        $post_weights,
        $global_unigram,
        $total_tokens,
        $bigram_stats,
        $stop_words_map
    );

    // 4) Lọc & xếp hạng (chỉ bigram)
    $keywords = init_plugin_suite_live_search_filter_and_rank_keywords_bi($bigrams_scored, $locale);

    if (empty($keywords)) {
        // Fallback mềm: nếu lọc quá gắt, lấy top theo score (vẫn chỉ bigrams, vẫn kiểm tra độ dài/cú pháp)
        $keywords = init_plugin_suite_live_search_fallback_pick_bigrams($bigrams_scored, $locale, 30);
    }

    if (!empty($keywords)) {
        // 5) Chọn 15 keywords đa dạng
        $selected_keywords = init_plugin_suite_live_search_smart_keyword_selection($keywords, 15);
        wp_send_json_success(implode(', ', $selected_keywords));
    }

    wp_send_json_error('No keywords found');
}

/** ========================= Core Helpers ========================= */

function init_plugin_suite_live_search_process_text($text, $stop_words, $is_vietnamese = false) {
    if (function_exists('normalizer_normalize')) {
        $text = normalizer_normalize($text, Normalizer::FORM_C);
    }

    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = wp_strip_all_tags($text);
    $text = mb_strtolower(trim($text), 'UTF-8');

    // Chuẩn hoá dash: dùng Unicode escapes & đặt '-' literal rõ ràng
    // U+2010 (‐), U+2011 (-), U+2012 (‒), U+2013 (–), U+2014 (—), U+2212 (−), và '-'
    $text = preg_replace('/[\x{2010}\x{2011}\x{2012}\x{2013}\x{2014}\x{2212}\-]+/u', '-', $text);
    if ($text === null) $text = ''; // an toàn nếu PCRE lỗi vì môi trường

    $text = str_replace(['&nbsp;'], ' ', $text);

    // Loại ký tự đặc biệt (giữ dấu TV nếu cần)
    if ($is_vietnamese) {
        $text = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $text);
    } else {
        $text = preg_replace('/[^a-zA-Z0-9\s\-]/', ' ', $text);
    }
    if ($text === null) $text = '';

    $text = preg_replace('/\s+/u', ' ', $text);
    if ($text === null) $text = '';

    $words = $text !== '' ? explode(' ', trim($text)) : [];
    if (empty($words)) return [];

    $stop_map = array_fill_keys($stop_words, true);
    $out = [];
    foreach ($words as $w) {
        if ($w === '' || isset($stop_map[$w])) continue;
        if (mb_strlen($w, 'UTF-8') < 2) continue;
        if (preg_match('/^\d+$/u', $w)) continue;
        if (preg_match('/^[^\p{L}]+$/u', $w)) continue;
        $out[] = $w;
    }
    return array_values($out);
}

function init_plugin_suite_live_search_calculate_bm25($documents, $post_weights, $k1 = 1.5, $b = 0.75) {
    $total_docs = count($documents);
    if ($total_docs === 0) return [];

    $doc_freq = [];
    $doc_lengths = [];
    $avgdl = 0;

    foreach ($documents as $doc_id => $terms) {
        $doc_lengths[$doc_id] = count($terms);
        $avgdl += $doc_lengths[$doc_id];
        foreach (array_unique($terms) as $t) {
            $doc_freq[$t] = ($doc_freq[$t] ?? 0) + 1;
        }
    }
    $avgdl = max($avgdl / $total_docs, 1);

    $term_scores = [];
    foreach ($documents as $doc_id => $terms) {
        if (empty($terms)) continue;
        $dl   = $doc_lengths[$doc_id];
        $freq = array_count_values($terms);
        $wdoc = $post_weights[$doc_id] ?? 1;

        foreach ($freq as $term => $f) {
            $df  = $doc_freq[$term] ?? 0;
            $idf = log( ( ($total_docs - $df + 0.5) ) / ( ($df + 0.5) ) + 1 );
            $den = $f + $k1 * (1 - $b + $b * ($dl / $avgdl));
            $bm25 = $idf * ( ($f * ($k1 + 1)) / max($den, 1e-9) );
            $term_scores[$term] = ($term_scores[$term] ?? 0) + $bm25 * $wdoc;
        }
    }
    return $term_scores;
}

function init_plugin_suite_live_search_build_bigram_stats($documents) {
    $counts = []; // raw bigram counts (không trọng số)
    $left   = []; // w1 => c(w1,*)
    $right  = []; // w2 => c(*,w2)
    $N = 0;       // tổng số bigram (raw)

    foreach ($documents as $terms) {
        $n = count($terms);
        for ($i = 0; $i < $n - 1; $i++) {
            $w1 = $terms[$i];
            $w2 = $terms[$i+1];
            $bg = $w1.' '.$w2;

            $counts[$bg] = ($counts[$bg] ?? 0) + 1;
            $left[$w1]   = ($left[$w1] ?? 0) + 1;
            $right[$w2]  = ($right[$w2] ?? 0) + 1;
            $N++;
        }
    }

    return [
        'counts' => $counts,
        'left'   => $left,
        'right'  => $right,
        'N'      => $N,
    ];
}

function init_plugin_suite_live_search_generate_scored_bigrams_bm25_npmi_llr(
    $documents,
    $term_scores,
    $post_weights,
    $global_unigram,
    $total_tokens,
    $bigram_stats,
    $stop_words_map
) {
    $N_tokens  = max($total_tokens, 1);
    $N_bigrams = max($bigram_stats['N'], 1);

    $raw_bg_counts = $bigram_stats['counts'];
    $left_totals   = $bigram_stats['left'];
    $right_totals  = $bigram_stats['right'];

    // Accumulate weighted counts
    $weighted_counts = []; // bigram => weighted count
    foreach ($documents as $doc_id => $words) {
        $wdoc = $post_weights[$doc_id] ?? 1;
        for ($i = 0, $n = count($words) - 1; $i < $n; $i++) {
            $w1 = $words[$i];
            $w2 = $words[$i+1];

            if (isset($stop_words_map[$w1]) || isset($stop_words_map[$w2])) continue;
            if ($w1 === $w2) continue;

            $bg = $w1.' '.$w2;
            $weighted_counts[$bg] = ($weighted_counts[$bg] ?? 0) + 1.0 * $wdoc;
        }
    }

    if (empty($weighted_counts)) return [];

    // Convert to final score with BM25 + NPMI + LLR
    $scored = [];
    foreach ($weighted_counts as $bg => $wcount) {
        [$w1, $w2] = explode(' ', $bg, 2);

        // BM25 pair
        $bm_pair = ($term_scores[$w1] ?? 0) + ($term_scores[$w2] ?? 0);

        // NPMI xấp xỉ từ unigram & bigram raw
        $c1 = max($global_unigram[$w1] ?? 0, 1);
        $c2 = max($global_unigram[$w2] ?? 0, 1);
        $p1 = $c1 / $N_tokens;
        $p2 = $c2 / $N_tokens;

        $c_bg_raw = max($raw_bg_counts[$bg] ?? 0, 0);
        // Dùng N_bigrams thay vì (N_tokens - 1) cho p_bg hợp lý hơn
        $p_bg = ($c_bg_raw + 1) / $N_bigrams;

        $pmi  = log( $p_bg / max($p1 * $p2, 1e-12) );
        $npmi = $pmi / max(-log($p_bg), 1e-9); // [-1,1]

        $base = $wcount * $bm_pair * (1 + max($npmi, 0));

        // LLR (Dunning)
        $a = max($c_bg_raw, 0);
        $b = max(($left_totals[$w1] ?? 0) - $a, 0);
        $c = max(($right_totals[$w2] ?? 0) - $a, 0);
        $d = max($N_bigrams - $a - $b - $c, 0);

        $llr = init_plugin_suite_live_search_llr_dunning($a, $b, $c, $d);
        $boost_llr = 1.0 + min($llr / 12.0, 2.0); // cap x3

        $scored[$bg] = $base * $boost_llr;
    }

    return $scored;
}

/** ========================= Stats: LLR (Dunning) ========================= */

function init_plugin_suite_live_search_ll($k, $n) {
    if ($k == 0 || $n == 0) return 0.0;
    return $k * log($k / $n);
}

function init_plugin_suite_live_search_llr_dunning($a, $b, $c, $d) {
    $n = $a + $b + $c + $d;
    if ($n == 0) return 0.0;

    $row1 = $a + $b;
    $row2 = $c + $d;
    $col1 = $a + $c;
    $col2 = $b + $d;

    $m1 = (($row1) * ($col1)) / max($n,1);
    $m2 = (($row1) * ($col2)) / max($n,1);
    $m3 = (($row2) * ($col1)) / max($n,1);
    $m4 = (($row2) * ($col2)) / max($n,1);

    $ll = 0.0;
    $ll += init_plugin_suite_live_search_ll($a, $m1);
    $ll += init_plugin_suite_live_search_ll($b, $m2);
    $ll += init_plugin_suite_live_search_ll($c, $m3);
    $ll += init_plugin_suite_live_search_ll($d, $m4);

    return 2.0 * $ll; // G-statistic
}

/** ========================= Filtering & Selection ========================= */

function init_plugin_suite_live_search_filter_and_rank_keywords_bi($bigram_scores, $locale) {
    $is_vietnamese = ($locale === 'vi' || strpos($locale, 'vi_') === 0);

    // Stop phrases (cụm 2 từ vô nghĩa)
    $default_stop_phrases = $is_vietnamese
        ? ['là gì','và các','có thể','với các','là một','trong khi','của các','cho các','từ các','tại các','về các','như các','theo các']
        : ['what is','and the','can be','with the','this is','while the','of the','for the','in the','on the','to the','by the','as the'];
    $stop_phrases = apply_filters('init_plugin_suite_live_search_stop_words', $default_stop_phrases, $locale);
    $stop_map = array_fill_keys($stop_phrases, true);

    if (empty($bigram_scores)) return [];

    $avg = array_sum($bigram_scores) / max(count($bigram_scores), 1);
    $threshold = $avg * 0.5; // ngưỡng mềm

    $filtered = [];
    foreach ($bigram_scores as $bg => $score) {
        if ($score < $threshold) continue;
        if (isset($stop_map[$bg])) continue;

        // loại số/HTML
        if (preg_match('/^\d+\s+\d+$/u', $bg)) continue;
        if (preg_match('/\b\d{4,}\b/u', $bg)) continue;

        $parts = preg_split('/\s+/u', trim($bg));
        if (count($parts) !== 2) continue;

        $clean_len = mb_strlen(str_replace(' ', '', $bg), 'UTF-8');
        if ($clean_len < 4 || $clean_len > 30) continue;

        if (mb_strlen($parts[0], 'UTF-8') < 2 || mb_strlen($parts[1], 'UTF-8') < 2) continue;

        $filtered[$bg] = $score;
    }

    if (empty($filtered)) return [];

    // Xếp hạng: score DESC, rồi độ dài DESC, rồi từ điển ASC
    uksort($filtered, function($a, $b) use ($filtered) {
        $diff = $filtered[$b] <=> $filtered[$a];
        if ($diff !== 0) return $diff;

        $len = mb_strlen($b, 'UTF-8') <=> mb_strlen($a, 'UTF-8');
        if ($len !== 0) return $len;

        return strcmp($a, $b);
    });

    // Lấy tối đa 30 vì chỉ bigrams
    return array_keys(array_slice($filtered, 0, 30, true));
}

function init_plugin_suite_live_search_fallback_pick_bigrams($bigram_scores, $locale, $take = 30) {
    // Fallback: nới lỏng threshold, vẫn chỉ nhận đúng 2 từ và kiểm tra độ dài
    if (empty($bigram_scores)) return [];
    $items = [];

    foreach ($bigram_scores as $bg => $score) {
        $parts = preg_split('/\s+/u', trim($bg));
        if (count($parts) !== 2) continue;

        // loại số/HTML
        if (preg_match('/^\d+\s+\d+$/u', $bg)) continue;
        if (preg_match('/\b\d{4,}\b/u', $bg)) continue;

        $clean_len = mb_strlen(str_replace(' ', '', $bg), 'UTF-8');
        if ($clean_len < 4 || $clean_len > 30) continue;

        if (mb_strlen($parts[0], 'UTF-8') < 2 || mb_strlen($parts[1], 'UTF-8') < 2) continue;

        $items[$bg] = $score;
    }

    if (empty($items)) return [];

    uksort($items, function($a, $b) use ($items) {
        $diff = $items[$b] <=> $items[$a];
        if ($diff !== 0) return $diff;
        return strcmp($a, $b);
    });

    return array_keys(array_slice($items, 0, $take, true));
}

function init_plugin_suite_live_search_smart_keyword_selection($keywords, $limit = 10) {
    if (count($keywords) <= $limit) {
        return $keywords;
    }

    $selected = [];
    $used_words = [];

    $shuffled = $keywords;
    shuffle($shuffled);

    $half_limit = intval($limit / 2);
    $top_keywords = array_slice($keywords, 0, $half_limit);
    $random_pool  = array_slice($shuffled, 0, min(20, count($shuffled)));

    $combined_pool = array_values(array_unique(array_merge($top_keywords, $random_pool)));

    foreach ($combined_pool as $kw) {
        if (count($selected) >= $limit) break;

        $words = explode(' ', $kw);
        $overlap = array_intersect($words, $used_words);

        // Với bigram: cho phép trùng 1 từ (60%)
        if (count($overlap) < count($words) * 0.6) {
            $selected[] = $kw;
            $used_words = array_merge($used_words, $words);
        }
    }

    if (count($selected) < $limit) {
        foreach ($shuffled as $kw) {
            if (count($selected) >= $limit) break;
            if (!in_array($kw, $selected, true)) {
                $selected[] = $kw;
            }
        }
    }

    shuffle($selected);
    return array_slice($selected, 0, $limit);
}
