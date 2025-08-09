<?php
// Enhanced keyword generation with TF-IDF - Bigrams only, optimized WP_Query
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

    // Optimized query - only get IDs for performance
    $post_ids = get_posts([
        'post_type'           => $post_types,
        'posts_per_page'      => -1,
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
        'fields'              => 'ids', // Only get IDs
    ]);

    if (empty($post_ids)) {
        wp_send_json_error('No posts found');
    }

    $locale = get_locale();
    $is_vietnamese = ($locale === 'vi' || strpos($locale, 'vi_') === 0);
    
    // Enhanced stop words
    $default_stop_words = $is_vietnamese 
        ? ['là', 'vì', 'và', 'các', 'một', 'có', 'trong', 'khi', 'những', 'được', 'lúc', 'này', 'đây', 'rằng', 'thì', 'sự', 'chap', 'chương', 'của', 'cho', 'từ', 'trên', 'dưới', 'về', 'tại', 'với', 'không', 'đã', 'sẽ', 'bị', 'làm', 'nào', 'như', 'theo', 'giữa', 'sau', 'trước']
        : ['is', 'are', 'the', 'and', 'with', 'this', 'that', 'of', 'for', 'to', 'in', 'on', 'it', 'be', 'by', 'as', 'was', 'were', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'shall'];

    $stop_words = apply_filters('init_plugin_suite_live_search_stop_single_words', $default_stop_words, $locale);

    // Process documents for TF-IDF - TITLE ONLY, using IDs
    $documents = [];
    $all_terms = [];
    $post_weights = [];

    foreach ($post_ids as $post_id) {
        $title = get_the_title($post_id);
        
        // Skip if no title
        if (empty($title)) continue;
        
        // Weight calculation based on post popularity
        $comment_count = get_comments_number($post_id);
        $view_count = get_post_meta($post_id, '_init_view_count', true) ?: 0;
        $post_weight = 1 + log(1 + $comment_count) + log(1 + intval($view_count));
        $post_weights[$post_id] = $post_weight;
        
        // ONLY USE TITLE - repeat 3x for emphasis
        $combined_text = $title . ' ' . $title . ' ' . $title;
        
        $documents[$post_id] = init_plugin_suite_live_search_process_text($combined_text, $stop_words, $is_vietnamese);
        $all_terms = array_merge($all_terms, $documents[$post_id]);
    }

    // Calculate TF-IDF scores
    $term_scores = init_plugin_suite_live_search_calculate_tfidf($documents, $all_terms, $post_weights);
    
    // Generate ONLY bigrams (no trigrams)
    $bigrams = init_plugin_suite_live_search_generate_smart_bigrams($documents, $term_scores, $is_vietnamese);
    
    // Filter and rank keywords
    $keywords = init_plugin_suite_live_search_filter_and_rank_keywords($bigrams, $locale);
    
    if (!empty($keywords)) {
        // Smart selection with more diversity - return 15 keywords instead of 7
        $selected_keywords = init_plugin_suite_live_search_smart_keyword_selection($keywords, 15);
        wp_send_json_success(implode(', ', $selected_keywords));
    }

    wp_send_json_error('No keywords found');
}

function init_plugin_suite_live_search_process_text($text, $stop_words, $is_vietnamese = false) {
    $text = mb_strtolower(trim($text));
    
    // Decode HTML entities and remove HTML tags
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = wp_strip_all_tags($text);
    
    // Remove special characters but keep Vietnamese diacritics
    if ($is_vietnamese) {
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
    } else {
        $text = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $text);
    }
    
    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    
    // Split into words
    $words = explode(' ', $text);
    
    // Remove stop words, short words, numbers, and non-letter sequences
    $words = array_filter($words, function($word) use ($stop_words) {
        return !in_array($word, $stop_words, true) 
            && mb_strlen($word) >= 2 
            && !preg_match('/^\d+$/', $word) // Remove pure numbers like 8211
            && !preg_match('/^[^\p{L}]+$/u', $word); // Remove non-letter sequences
    });
    
    return array_values($words);
}

function init_plugin_suite_live_search_calculate_tfidf($documents, $all_terms, $post_weights) {
    $term_scores = [];
    $total_docs = count($documents);
    
    // Count document frequency for each term
    $doc_freq = [];
    foreach ($all_terms as $term) {
        if (!isset($doc_freq[$term])) {
            $doc_freq[$term] = 0;
            foreach ($documents as $doc_id => $doc_terms) {
                if (in_array($term, $doc_terms)) {
                    $doc_freq[$term]++;
                }
            }
        }
    }
    
    // Calculate TF-IDF for each term in each document
    foreach ($documents as $doc_id => $doc_terms) {
        $term_freq = array_count_values($doc_terms);
        $doc_length = count($doc_terms);
        
        foreach ($term_freq as $term => $freq) {
            $tf = $freq / $doc_length;
            $idf = log($total_docs / ($doc_freq[$term] + 1));
            $tfidf = $tf * $idf * $post_weights[$doc_id];
            
            if (!isset($term_scores[$term])) {
                $term_scores[$term] = 0;
            }
            $term_scores[$term] += $tfidf;
        }
    }
    
    return $term_scores;
}

function init_plugin_suite_live_search_generate_smart_bigrams($documents, $term_scores, $is_vietnamese = false) {
    $bigrams = [];
    $min_score_threshold = array_sum($term_scores) / count($term_scores) * 0.5; // 50% of average score
    
    foreach ($documents as $doc_id => $words) {
        // Generate ONLY bigrams (no trigrams)
        for ($i = 0; $i < count($words) - 1; $i++) {
            $word1 = $words[$i];
            $word2 = $words[$i + 1];
            
            // Skip if either word has low TF-IDF score
            if (($term_scores[$word1] ?? 0) < $min_score_threshold || 
                ($term_scores[$word2] ?? 0) < $min_score_threshold) {
                continue;
            }
            
            $bigram = $word1 . ' ' . $word2;
            $bigrams[$bigram] = ($bigrams[$bigram] ?? 0) + 1;
        }
    }
    
    return $bigrams;
}

function init_plugin_suite_live_search_filter_and_rank_keywords($ngrams, $locale) {
    $is_vietnamese = ($locale === 'vi' || strpos($locale, 'vi_') === 0);
    
    // Enhanced stop phrases
    $default_stop_phrases = $is_vietnamese
        ? ['là gì', 'và các', 'có thể', 'với các', 'là một', 'trong khi', 'của các', 'cho các', 'từ các', 'tại các', 'về các', 'như các', 'theo các']
        : ['what is', 'and the', 'can be', 'with the', 'this is', 'while the', 'of the', 'for the', 'in the', 'on the', 'to the', 'by the', 'as the'];
    
    $stop_phrases = apply_filters('init_plugin_suite_live_search_stop_words', $default_stop_phrases, $locale);
    
    // Filter out stop phrases and low-quality ngrams
    $filtered = [];
    foreach ($ngrams as $ngram => $count) {
        // Skip stop phrases
        if (in_array($ngram, $stop_phrases, true)) continue;
        
        // Skip if contains numbers only or HTML entities
        if (preg_match('/^\d+\s+\d+/', $ngram)) continue;
        if (preg_match('/\b\d{4,}\b/', $ngram)) continue; // Remove 4+ digit numbers like 8211
        
        // For bigrams only - ensure exactly 2 words
        $word_count = substr_count($ngram, ' ') + 1;
        if ($word_count !== 2) continue;
        
        // Skip if total length is too short or too long
        $clean_length = mb_strlen(str_replace(' ', '', $ngram));
        if ($clean_length < 4 || $clean_length > 30) continue;
        
        $filtered[$ngram] = $count;
    }
    
    // Sort by frequency and then by length
    uksort($filtered, function($a, $b) use ($filtered) {
        $count_diff = $filtered[$b] <=> $filtered[$a];
        if ($count_diff !== 0) return $count_diff;
        
        $length_diff = mb_strlen($b) <=> mb_strlen($a);
        if ($length_diff !== 0) return $length_diff;
        
        return strcmp($a, $b);
    });
    
    return array_keys(array_slice($filtered, 0, 30, true)); // Reduced to 30 since only bigrams
}

function init_plugin_suite_live_search_smart_keyword_selection($keywords, $limit = 10) {
    if (count($keywords) <= $limit) {
        return $keywords;
    }
    
    $selected = [];
    $used_words = [];
    
    // Shuffle keywords first for more randomness
    $shuffled_keywords = $keywords;
    shuffle($shuffled_keywords);
    
    // Take top 50% and random 50% for diversity
    $half_limit = intval($limit / 2);
    $top_keywords = array_slice($keywords, 0, $half_limit); // Top keywords
    $random_pool = array_slice($shuffled_keywords, 0, min(20, count($shuffled_keywords))); // Random pool
    
    // Combine top and random keywords
    $combined_pool = array_merge($top_keywords, $random_pool);
    $combined_pool = array_unique($combined_pool); // Remove duplicates
    
    // Select diverse keywords with relaxed overlap rules
    foreach ($combined_pool as $keyword) {
        if (count($selected) >= $limit) break;
        
        $words = explode(' ', $keyword);
        $overlap = array_intersect($words, $used_words);
        
        // More relaxed overlap rule - allow up to 60% overlap for more variety
        if (count($overlap) < count($words) * 0.6) {
            $selected[] = $keyword;
            $used_words = array_merge($used_words, $words);
        }
    }
    
    // Fill remaining slots with any remaining keywords
    if (count($selected) < $limit) {
        foreach ($shuffled_keywords as $keyword) {
            if (count($selected) >= $limit) break;
            if (!in_array($keyword, $selected)) {
                $selected[] = $keyword;
            }
        }
    }
    
    // Final shuffle for variety
    shuffle($selected);
    
    return array_slice($selected, 0, $limit);
}
