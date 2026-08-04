<?php
// Dynamic render cho block init-live-search/related-posts.
// $attributes, $content, $block được WordPress tự inject khi dùng "render" trong block.json.
if ( ! defined( 'ABSPATH' ) ) exit;

$atts = [
    'count'     => isset( $attributes['count'] ) ? (string) absint( $attributes['count'] ) : '5',
    'keyword'   => isset( $attributes['keyword'] ) ? (string) $attributes['keyword'] : '',
    'css'       => ( isset( $attributes['loadCss'] ) && ! $attributes['loadCss'] ) ? '0' : '1',
    'schema'    => ( isset( $attributes['showSchema'] ) && ! $attributes['showSchema'] ) ? '0' : '1',
    'template'  => isset( $attributes['template'] ) ? (string) $attributes['template'] : 'default',
    'post_type' => isset( $attributes['postType'] ) ? (string) $attributes['postType'] : 'post',
];

if ( ! empty( $attributes['postId'] ) ) {
    $atts['id'] = (string) absint( $attributes['postId'] );
}

// init_plugin_suite_live_search_related_posts_shortcode() renders the exact
// same markup as [init_live_search_related_posts] and already escapes
// everything internally (it includes one of the templates/ files, same as
// the shortcode does) — no extra wrapper div here.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo init_plugin_suite_live_search_related_posts_shortcode( $atts );
