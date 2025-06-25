<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $related_ids ) || ! is_array( $related_ids ) ) return;

echo '<div class="ils-related-grid' . ( count( $related_ids ) >= 10 ? ' ils-related-grid--columns' : '' ) . '">';

foreach ( $related_ids as $post_id ) {
    $title     = get_the_title( $post_id );
    $permalink = get_permalink( $post_id );
    $date      = get_the_date( '', $post_id );

    $thumb = get_the_post_thumbnail( $post_id, [85, 85], [
        'class'   => 'ils-related-thumb',
        'loading' => 'lazy',
        'decoding' => 'async',
        'alt'     => init_plugin_suite_live_search_get_smart_post_thumbnail_alt( $post_id ),
    ] );

    if ( ! $thumb ) {
        $thumb = '<img src="' . esc_url( INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg' ) . '" alt="' . esc_attr( $title ) . '" class="ils-related-thumb" width="85" height="85" loading="lazy" decoding="async" />';
    }

    echo '<article class="ils-related-row">';
    echo '<a href="' . esc_url( $permalink ) . '" class="ils-related-thumb-wrap">' . wp_kses_post( $thumb ) . '</a>';
    echo '<div class="ils-related-meta">';
    echo '<h4 class="ils-related-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h4>';
    echo '<time class="ils-related-date">' . esc_html( $date ) . '</time>';
    echo '</div>';
    echo '</article>';
}

echo '</div>';

if ( $atts['schema'] !== '0' && ! empty( $related_ids ) ) :
    $items = [];
    foreach ( $related_ids as $i => $id ) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => get_permalink( $id ),
            'name' => get_the_title( $id ),
        ];
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type'    => 'ItemList',
        'itemListElement' => $items,
    ];
    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
endif;
