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
