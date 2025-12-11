<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Auto-insert related posts based on user settings
 * Hooked into: the_content, comment_form_before, comment_form_after
 */

// Check điều kiện có chèn hay không
function init_plugin_suite_live_search_should_auto_insert( $position ) {
	if ( ! is_singular() || ! in_the_loop() ) return false;

	$options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
	$current_type = get_post_type();

	$allowed_types = $options['post_types'] ?? ['post'];
	if ( ! in_array( $current_type, $allowed_types, true ) ) return false;

	$selected = $options['related_auto_insert'] ?? 'none';
	if ( $selected !== $position ) return false;

	// Filter cho phép override
	return apply_filters(
		'init_plugin_suite_live_search_auto_insert_enabled',
		true,
		$position,
		$current_type
	);
}

// Shortcode mặc định (có thể filter)
function init_plugin_suite_live_search_get_default_shortcode() {
	return apply_filters(
		'init_plugin_suite_live_search_default_related_shortcode',
		'[init_live_search_related_posts count="10"]'
	);
}

// After content
add_filter( 'the_content', function( $content ) {
	if ( init_plugin_suite_live_search_should_auto_insert( 'after_content' ) ) {
		$content .= do_shortcode( init_plugin_suite_live_search_get_default_shortcode() );
	}
	return $content;
}, 20 );

// Before comment
add_action( 'comment_form_before', function() {
	if ( init_plugin_suite_live_search_should_auto_insert( 'before_comment' ) ) {
		echo do_shortcode( init_plugin_suite_live_search_get_default_shortcode() );
	}
} );

// After comment
add_action( 'comment_form_after', function() {
	if ( init_plugin_suite_live_search_should_auto_insert( 'after_comment' ) ) {
		echo do_shortcode( init_plugin_suite_live_search_get_default_shortcode() );
	}
} );

// Extract keyword from 404 URL slug
function init_plugin_suite_live_search_extract_404_keyword() {
    // WPCS: sanitize & unslash server input
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

    if ( empty( $request_uri ) ) {
        return '';
    }

    // Lấy path (bỏ query string)
    $parsed = wp_parse_url( home_url( $request_uri ), PHP_URL_PATH );
    $path = trim( (string) $parsed, '/' );

    if ( $path === '' ) {
        return '';
    }

    // Lấy segment cuối cùng
    $segments     = explode( '/', $path );
    $last_segment = end( $segments );
    $last_segment = (string) $last_segment;

    // Bỏ request file tĩnh
    if ( preg_match( '~\.(jpg|jpeg|png|gif|webp|svg|css|js|ico|xml|txt|woff2?|ttf|eot|map)$~i', $last_segment ) ) {
        return '';
    }

    // Chuyển slug thành keyword
    $keyword = str_replace( '-', ' ', $last_segment );
    $keyword = urldecode( $keyword );
    $keyword = sanitize_text_field( $keyword );
    $keyword = trim( $keyword );

    if ( strlen( $keyword ) < 3 ) {
        return '';
    }

    return $keyword;
}

// Redirect 404 to the most relevant post
function init_plugin_suite_live_search_redirect_404_to_related_smart() {
    // Không chạy ở admin hoặc AJAX
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    // Chỉ xử lý trên trang 404
    if ( ! is_404() ) {
        return;
    }

    // Tôn trọng option checkbox: Auto Redirect 404
    $options = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
    if ( empty( $options['auto_redirect_404'] ) || $options['auto_redirect_404'] !== '1' ) {
        return;
    }

    /**
     * Lấy danh sách post_type thông qua resolver chung
     * => tự động áp dụng filter: init_plugin_suite_live_search_post_types
     */
    if ( function_exists( 'init_plugin_suite_live_search_resolve_post_types' ) ) {
        $post_types = init_plugin_suite_live_search_resolve_post_types(
            $options,
            [
                'context' => '404_redirect',
                'source'  => '404_redirect',
            ]
        );
    } else {
        // Fallback nếu vì lý do gì đó resolver chưa tồn tại
        if ( ! empty( $options['post_types'] ) && is_array( $options['post_types'] ) ) {
            $post_types = array_map( 'sanitize_key', $options['post_types'] );
        } else {
            $post_types = [ 'post' ];
        }

        $post_types = array_values(
            array_unique(
                array_filter( $post_types )
            )
        );
    }

    if ( empty( $post_types ) ) {
        $post_types = [ 'post' ];
    }

    $keyword = init_plugin_suite_live_search_extract_404_keyword();
    if ( ! $keyword ) {
        return;
    }

    $target_id = 0;

    // Bước 1: Dùng Init Live Search trước (ưu tiên engine của plugin)
    $related_ids = init_plugin_suite_live_search_find_related_ids(
        $keyword,
        0,          // exclude_id: 0 vì URL 404 không gắn với post cụ thể
        1,          // limit: chỉ cần 1 bài liên quan nhất
        $post_types // Áp dụng đúng các post_type đã resolve
    );

    if ( ! empty( $related_ids ) ) {
        $candidate_id = (int) $related_ids[0];

        if (
            $candidate_id
            && get_post_status( $candidate_id ) === 'publish'
            && in_array( get_post_type( $candidate_id ), $post_types, true )
        ) {
            $target_id = $candidate_id;
        }
    }

    // Bước 2: Fallback về core search nếu Init Live Search không có kết quả
    if ( ! $target_id ) {
        $query = new WP_Query(
            [
                'post_type'           => $post_types,
                'post_status'         => 'publish',
                'posts_per_page'      => 1,
                's'                   => $keyword,
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
            ]
        );

        if ( $query->have_posts() ) {
            $candidate_id = (int) $query->posts[0]->ID;

            if (
                $candidate_id
                && get_post_status( $candidate_id ) === 'publish'
                && in_array( get_post_type( $candidate_id ), $post_types, true )
            ) {
                $target_id = $candidate_id;
            }
        }
    }

    // Bước 3: Nếu tìm được bài phù hợp thì redirect
    if ( $target_id && get_post_status( $target_id ) === 'publish' ) {
        wp_safe_redirect( get_permalink( $target_id ), 301 );
        exit;
    }
}

add_action( 'template_redirect', 'init_plugin_suite_live_search_redirect_404_to_related_smart', 9 );
