<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Abilities API (WordPress 6.9+). Bail out silently on older versions so this
// file is always safe to require regardless of the host site's WP version.
if ( ! function_exists( 'wp_register_ability' ) ) {
    return;
}

// Register the ability category used by this plugin.
add_action( 'wp_abilities_api_categories_init', 'init_plugin_suite_live_search_register_ability_categories' );
function init_plugin_suite_live_search_register_ability_categories() {
    if ( function_exists( 'wp_has_ability_category' ) && wp_has_ability_category( 'init-live-search' ) ) {
        return;
    }

    wp_register_ability_category(
        'init-live-search',
        [
            'label'       => __( 'Init Live Search', 'init-live-search' ),
            'description' => __( 'Abilities exposed by the Init Live Search plugin.', 'init-live-search' ),
        ]
    );
}

// Register the abilities themselves.
add_action( 'wp_abilities_api_init', 'init_plugin_suite_live_search_register_abilities' );
function init_plugin_suite_live_search_register_abilities() {
    wp_register_ability(
        'init-live-search/search-posts',
        [
            'label'               => __( 'Search Posts', 'init-live-search' ),
            'description'         => __( 'Searches posts using the Init Live Search engine (FULLTEXT index, Meilisearch, or LIKE fallback) and returns matching results.', 'init-live-search' ),
            'category'            => 'init-live-search',
            'input_schema'        => [
                'type'       => 'object',
                'properties' => [
                    'term' => [
                        'type'        => 'string',
                        'description' => __( 'The search term.', 'init-live-search' ),
                        'minLength'   => 1,
                    ],
                ],
                'required'   => [ 'term' ],
            ],
            'output_schema'       => init_plugin_suite_live_search_ability_result_list_schema(),
            'execute_callback'    => 'init_plugin_suite_live_search_ability_search_posts',
            'permission_callback' => '__return_true',
            'meta'                => [
                'show_in_rest' => true,
                'annotations'  => [
                    'readonly' => true,
                ],
            ],
        ]
    );

    wp_register_ability(
        'init-live-search/get-related-posts',
        [
            'label'               => __( 'Get Related Posts', 'init-live-search' ),
            'description'         => __( 'Returns posts related to a given post ID, using the same keyword-similarity matching as the [init_live_search_related_posts] shortcode.', 'init-live-search' ),
            'category'            => 'init-live-search',
            'input_schema'        => [
                'type'       => 'object',
                'properties' => [
                    'post_id'   => [
                        'type'        => 'integer',
                        'description' => __( 'The reference post ID to find related posts for.', 'init-live-search' ),
                        'minimum'     => 1,
                    ],
                    'count'     => [
                        'type'        => 'integer',
                        'description' => __( 'Maximum number of related posts to return.', 'init-live-search' ),
                        'default'     => 5,
                        'minimum'     => 1,
                        'maximum'     => 50,
                    ],
                    'post_type' => [
                        'type'        => 'string',
                        'description' => __( 'Restrict related posts to a specific post type.', 'init-live-search' ),
                        'default'     => 'post',
                    ],
                ],
                'required'   => [ 'post_id' ],
            ],
            'output_schema'       => init_plugin_suite_live_search_ability_result_list_schema(),
            'execute_callback'    => 'init_plugin_suite_live_search_ability_get_related_posts',
            'permission_callback' => '__return_true',
            'meta'                => [
                'show_in_rest' => true,
                'annotations'  => [
                    'readonly' => true,
                ],
            ],
        ]
    );
}

// Shared output schema for both abilities: an array of result-item objects,
// matching the shape produced by init_plugin_suite_live_search_build_result_item().
function init_plugin_suite_live_search_ability_result_list_schema() {
    return [
        'type'  => 'array',
        'items' => [
            'type'       => 'object',
            'properties' => [
                'id'        => [ 'type' => 'integer' ],
                'title'     => [ 'type' => 'string' ],
                'url'       => [ 'type' => 'string' ],
                'type'      => [ 'type' => 'string' ],
                'post_type' => [ 'type' => 'string' ],
                'thumb'     => [ 'type' => 'string' ],
                'date'      => [ 'type' => 'string' ],
                'category'  => [ 'type' => 'string' ],
                'excerpt'   => [ 'type' => 'string' ],
            ],
        ],
    ];
}

// Execute callback for init-live-search/search-posts.
function init_plugin_suite_live_search_ability_search_posts( $input ) {
    $term = isset( $input['term'] ) ? sanitize_text_field( $input['term'] ) : '';

    if ( '' === $term ) {
        return new WP_Error(
            'init_live_search_invalid_term',
            __( 'A non-empty search term is required.', 'init-live-search' )
        );
    }

    return init_plugin_suite_live_search_get_results( $term );
}

// Execute callback for init-live-search/get-related-posts.
function init_plugin_suite_live_search_ability_get_related_posts( $input ) {
    $post_id   = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
    $count     = isset( $input['count'] ) ? absint( $input['count'] ) : 5;
    $post_type = isset( $input['post_type'] ) && is_string( $input['post_type'] ) ? sanitize_key( $input['post_type'] ) : 'post';

    if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
        return new WP_Error(
            'init_live_search_invalid_post_id',
            __( 'A valid, published post ID is required.', 'init-live-search' )
        );
    }

    $count = $count > 0 ? min( $count, 50 ) : 5;

    $raw_title = get_the_title( $post_id );
    $decoded   = html_entity_decode( $raw_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $clean     = wp_strip_all_tags( $decoded );
    $keyword   = trim( preg_replace( '/[^\p{L}\p{N}\s]+/u', '', $clean ) );

    $related_ids = init_plugin_suite_live_search_find_related_ids( $keyword, $post_id, $count, $post_type );

    if ( empty( $related_ids ) ) {
        return [];
    }

    $default_thumb = apply_filters(
        'init_plugin_suite_live_search_default_thumb',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'img/thumbnail.svg'
    );

    return init_plugin_suite_live_search_build_result_list( $related_ids, [], '', [], $default_thumb );
}
