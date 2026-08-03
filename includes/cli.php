<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) return;

/**
 * WP-CLI command: wp init-live-search meili-reindex
 *
 * Pushes all published posts (of the post types enabled in General Settings)
 * to Meilisearch in batches. Use for the initial setup or to rebuild the index.
 */
class Init_Plugin_Suite_Live_Search_CLI {

    /**
     * Reindex all published posts into Meilisearch.
     *
     * ## OPTIONS
     *
     * [--batch-size=<number>]
     * : Number of posts processed per batch.
     * ---
     * default: 200
     * ---
     *
     * ## EXAMPLES
     *
     *     wp init-live-search meili-reindex
     *     wp init-live-search meili-reindex --batch-size=100
     *
     * @subcommand meili-reindex
     * @when after_wp_load
     */
    public function meili_reindex( $args, $assoc_args ) {
        $settings = get_option( INIT_PLUGIN_SUITE_LS_MEILI_OPTION, [] );

        if ( ! init_plugin_suite_live_search_meili_is_enabled( $settings ) ) {
            WP_CLI::error( 'Meilisearch is not enabled or not fully configured (Host / Index / Search Key) under Settings > Init Live Search > Meilisearch.' );
        }

        $admin_key = init_plugin_suite_live_search_meili_get_admin_key( $settings );
        if ( ! $admin_key ) {
            WP_CLI::error( 'Missing Admin/Indexing Key. Define the INIT_LIVE_SEARCH_MEILI_ADMIN_KEY constant in wp-config.php, or enter it on the settings page.' );
        }

        $host  = untrailingslashit( trim( $settings['host'] ) );
        $index = trim( $settings['index'] );

        $options       = get_option( INIT_PLUGIN_SUITE_LS_OPTION, [] );
        $allowed_types = apply_filters(
            'init_plugin_suite_live_search_post_types',
            ! empty( $options['post_types'] ) ? (array) $options['post_types'] : [ 'post' ],
            $options,
            []
        );

        $batch_size = isset( $assoc_args['batch-size'] ) ? max( 1, (int) $assoc_args['batch-size'] ) : 200;
        $paged      = 1;
        $total      = 0;

        WP_CLI::log( sprintf( 'Starting reindex of post types [%s] to Meilisearch (%s/indexes/%s)...', implode( ', ', $allowed_types ), $host, $index ) );

        do {
            $query = new WP_Query( [
                'post_type'      => $allowed_types,
                'post_status'    => 'publish',
                'posts_per_page' => $batch_size,
                'paged'          => $paged,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => false,
            ] );

            if ( empty( $query->posts ) ) {
                break;
            }

            $documents = [];
            foreach ( $query->posts as $post ) {
                $documents[] = init_plugin_suite_live_search_meili_build_document( $post );
            }

            $response = wp_remote_post(
                $host . '/indexes/' . rawurlencode( $index ) . '/documents',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $admin_key,
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => wp_json_encode( $documents ),
                    'timeout' => 30,
                ]
            );

            if ( is_wp_error( $response ) ) {
                WP_CLI::warning( sprintf( 'Batch %d error: %s', $paged, $response->get_error_message() ) );
            } else {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code >= 200 && $code < 300 ) {
                    $total += count( $documents );
                    WP_CLI::log( sprintf( 'Batch %d: sent %d posts (HTTP %d)', $paged, count( $documents ), $code ) );
                } else {
                    WP_CLI::warning( sprintf( 'Batch %d failed, HTTP %d: %s', $paged, $code, wp_remote_retrieve_body( $response ) ) );
                }
            }

            $paged++;
            usleep( 200000 );

        } while ( count( $query->posts ) === $batch_size );

        WP_CLI::success( sprintf( 'Done. Total posts indexed: %d.', $total ) );
    }

    /**
     * Rebuild the local FULLTEXT search index used when "FULLTEXT Search Index"
     * is enabled under Settings > General. Required after enabling the option
     * for the first time, and safe to re-run any time (does a full rebuild).
     *
     * ## OPTIONS
     *
     * [--batch-size=<number>]
     * : Number of posts processed per batch.
     * ---
     * default: 200
     * ---
     *
     * ## EXAMPLES
     *
     *     wp init-live-search fulltext-reindex
     *     wp init-live-search fulltext-reindex --batch-size=500
     *
     * @subcommand fulltext-reindex
     * @when after_wp_load
     */
    public function fulltext_reindex( $args, $assoc_args ) {
        if ( ! function_exists( 'init_plugin_suite_live_search_fulltext_is_supported' ) ) {
            WP_CLI::error( 'FULLTEXT index module is not loaded.' );
        }

        // Table creation is lazy — if this is the very first time the feature
        // is ever touched (e.g. admin never opened Settings, went straight to
        // WP-CLI), this creates the table and runs the capability check now.
        init_plugin_suite_live_search_fulltext_maybe_upgrade();

        if ( ! init_plugin_suite_live_search_fulltext_is_supported() ) {
            WP_CLI::error( 'Your MySQL/MariaDB server does not support InnoDB FULLTEXT indexes on this table, or the index could not be created. FULLTEXT search index is unavailable — the plugin will keep using the standard database search.' );
        }

        global $wpdb;
        $table      = init_plugin_suite_live_search_fulltext_table();
        $post_types = init_plugin_suite_live_search_fulltext_get_indexable_post_types();
        $batch_size = isset( $assoc_args['batch-size'] ) ? max( 1, (int) $assoc_args['batch-size'] ) : 200;

        WP_CLI::log( sprintf( 'Rebuilding FULLTEXT index for post types [%s]...', implode( ', ', $post_types ) ) );

        // Rebuild from scratch — simplest way to guarantee no stale rows are
        // left behind (e.g. posts unpublished while the sync hook was off).
        // $table is derived from $wpdb->prefix only, never from user input.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->query( "TRUNCATE TABLE {$table}" );

        $paged = 1;
        $total = 0;

        do {
            $query = new WP_Query( [
                'post_type'      => $post_types,
                'post_status'    => 'publish',
                'posts_per_page' => $batch_size,
                'paged'          => $paged,
                'orderby'        => 'ID',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            ] );

            if ( empty( $query->posts ) ) {
                break;
            }

            foreach ( $query->posts as $post ) {
                init_plugin_suite_live_search_fulltext_upsert_row( $post );
            }

            $total += count( $query->posts );
            WP_CLI::log( sprintf( 'Batch %d: indexed %d posts (total: %d)', $paged, count( $query->posts ), $total ) );

            $paged++;

        } while ( count( $query->posts ) === $batch_size );

        update_option( 'init_plugin_suite_live_search_fulltext_indexed', current_time( 'mysql' ), false );

        WP_CLI::success( sprintf( 'Done. Total posts indexed: %d. FULLTEXT search index is now active.', $total ) );
    }
}

WP_CLI::add_command( 'init-live-search', 'Init_Plugin_Suite_Live_Search_CLI' );
