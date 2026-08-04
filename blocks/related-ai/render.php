<?php
// Dynamic render cho block init-live-search/related-ai.
// $attributes, $content, $block được WordPress tự inject khi dùng "render" trong block.json.
if ( ! defined( 'ABSPATH' ) ) exit;

$atts = [
    'count'     => isset( $attributes['count'] ) ? (string) absint( $attributes['count'] ) : '5',
    'css'       => ( isset( $attributes['loadCss'] ) && ! $attributes['loadCss'] ) ? '0' : '1',
    'schema'    => ( isset( $attributes['showSchema'] ) && ! $attributes['showSchema'] ) ? '0' : '1',
    'template'  => isset( $attributes['template'] ) ? (string) $attributes['template'] : 'default',
    'post_type' => isset( $attributes['postType'] ) ? (string) $attributes['postType'] : 'post',
];

if ( ! empty( $attributes['postId'] ) ) {
    $atts['id'] = (string) absint( $attributes['postId'] );
}

// init_plugin_suite_live_search_related_ai_shortcode() renders the exact
// same markup as [init_live_search_related_ai] and already escapes
// everything internally — no extra wrapper div here.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo init_plugin_suite_live_search_related_ai_shortcode( $atts );
