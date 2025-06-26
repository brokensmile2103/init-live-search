<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( empty( $related_ids ) || ! is_array( $related_ids ) ) return;

echo '<ul class="ils-compact-list">';
foreach ( $related_ids as $post_id ) {
	$title     = get_the_title( $post_id );
	$permalink = get_permalink( $post_id );
	echo '<li><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></li>';
}
echo '</ul>';

if ( $atts['schema'] !== '0' ) :
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
