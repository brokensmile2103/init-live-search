<?php
// Handle AJAX: Generate suggested keywords from post titles

if (!defined('ABSPATH')) exit;

add_action('wp_ajax_init_plugin_suite_live_search_generate_keywords', 'init_plugin_suite_live_search_generate_keywords');

function init_plugin_suite_live_search_generate_keywords() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $nonce = isset($_SERVER['HTTP_X_WP_NONCE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_WP_NONCE'])) : '';
    if (!wp_verify_nonce($nonce, 'init_live_search_admin_nonce')) {
        wp_send_json_error('Invalid nonce', 403);
    }

    $options = get_option('init_plugin_suite_live_search_settings', []);
    $post_types = !empty($options['post_types']) ? (array) $options['post_types'] : ['post'];

    $post_ids = get_posts([
        'post_type'           => $post_types,
        'posts_per_page'      => -1,
        'fields'              => 'ids',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'post_status'         => 'publish',
    ]);

    $all_post_title = '';
    foreach ($post_ids as $post_id) {
        $title = get_the_title($post_id);
        if ($title) {
            $all_post_title .= $title . ' ';
        }
    }

    if (!empty($all_post_title)) {
        $all_post_title = mb_strtolower(trim($all_post_title));
        $all_post_title = preg_replace('~[^\pL\s]+~u', '', $all_post_title);
        $all_post_title = preg_replace('/\s+/', ' ', $all_post_title);

        $title_arr = explode(' ', $all_post_title);

        $locale = get_locale();

        $default_stop_words = ($locale === 'vi' || strpos($locale, 'vi_') === 0)
            ? ['là', 'vì', 'và', 'các', 'một', 'có', 'trong', 'khi', 'những', 'được', 'lúc', 'này', 'đây', 'rằng', 'thì', 'sự', 'chap', 'chương']
            : ['is', 'are', 'the', 'and', 'with', 'this', 'that', 'of', 'for', 'to', 'in', 'on', 'it', 'be', 'by', 'as', 'was'];

        $stop_single_words = apply_filters('init_plugin_suite_live_search_stop_single_words', $default_stop_words, $locale);

        // Loại bỏ stopwords đơn trước khi tạo bigram
        $title_arr = array_values(array_filter($title_arr, function($word) use ($stop_single_words) {
            return !in_array($word, $stop_single_words, true);
        }));

        $keyword_arr = [];
        for ($i = 0; $i < count($title_arr) - 1; $i++) {
            $bigram = $title_arr[$i] . ' ' . $title_arr[$i + 1];
            $keyword_arr[] = $bigram;
        }

        $counts = array_count_values($keyword_arr);

        uksort($counts, function($a, $b) use ($counts) {
            $count_diff = $counts[$b] <=> $counts[$a];
            if ($count_diff !== 0) return $count_diff;
            return mb_strlen($b) <=> mb_strlen($a);
        });

        $default_stop_phrases = ($locale === 'vi' || strpos($locale, 'vi_') === 0)
            ? ['là gì', 'và các', 'có thể', 'với các', 'là một', 'trong khi']
            : ['what is', 'and the', 'can be', 'with the', 'this is', 'while the'];

        $stop_phrases = apply_filters('init_plugin_suite_live_search_stop_words', $default_stop_phrases, $locale);

        $filtered_keywords = [];
        foreach ($counts as $keyword => $count) {
            if (in_array($keyword, $stop_phrases, true)) continue;
            $filtered_keywords[] = $keyword;
            if (count($filtered_keywords) >= 35) break;
        }

        if (!empty($filtered_keywords)) {
            shuffle($filtered_keywords);
            $random_keywords = array_slice($filtered_keywords, 0, 7);
            wp_send_json_success(implode(', ', $random_keywords));
        }
    }

    wp_send_json_error('No keywords found');
}
