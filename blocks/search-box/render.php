<?php
// Dynamic render cho block init-live-search/search-box.
// $attributes, $content, $block được WordPress tự inject khi dùng "render" trong block.json.
if ( ! defined( 'ABSPATH' ) ) exit;

$atts = [
    'type'         => ( isset( $attributes['boxType'] ) && 'input' === $attributes['boxType'] ) ? 'input' : 'icon',
    'placeholder'  => isset( $attributes['placeholder'] ) ? (string) $attributes['placeholder'] : 'Search...',
    'label'        => isset( $attributes['label'] ) ? (string) $attributes['label'] : '',
    'class'        => isset( $attributes['htmlClass'] ) ? (string) $attributes['htmlClass'] : '',
    'id'           => isset( $attributes['htmlId'] ) ? (string) $attributes['htmlId'] : '',
    'stroke_width' => isset( $attributes['strokeWidth'] ) ? (string) $attributes['strokeWidth'] : '1',
    'radius'       => isset( $attributes['radius'] ) ? (string) $attributes['radius'] : '9999px',
    'width'        => isset( $attributes['width'] ) ? (string) $attributes['width'] : '',
    'max_width'    => isset( $attributes['maxWidth'] ) ? (string) $attributes['maxWidth'] : '',
    'align'        => isset( $attributes['align'] ) ? (string) $attributes['align'] : '',
    'name'         => isset( $attributes['inputName'] ) ? (string) $attributes['inputName'] : '',
    'aria_label'   => isset( $attributes['ariaLabel'] ) ? (string) $attributes['ariaLabel'] : '',
    'button'       => ( isset( $attributes['showButton'] ) && ! $attributes['showButton'] ) ? 'hide' : 'show',
];

// init_plugin_suite_live_search_shortcode() renders the exact same markup as
// [init_live_search] and already escapes every attribute internally — no
// wrapper div is added here so the block's frontend output is byte-for-byte
// identical to the shortcode's.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo init_plugin_suite_live_search_shortcode( $atts );
