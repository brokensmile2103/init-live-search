<?php
// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove every option, transient and table created by the plugin for the
 * current site.
 *
 * Fixes vs 2.0.0:
 * - ils_log_chunk_index was deleted BEFORE being read, so the loop below
 *   always saw index 1 and every other analytics chunk was left behind.
 * - Meilisearch settings, predefined dictionaries, the Meilisearch skipped
 *   list and all cache transients (AI related, related posts, 404, circuit
 *   breaker, cron locks) were never removed.
 */
function init_plugin_suite_live_search_uninstall_site() {
    global $wpdb;

    // Analytics chunks — read the index first, then delete.
    $chunk_index = absint( get_option( 'ils_log_chunk_index', 1 ) );
    for ( $i = 1; $i <= $chunk_index; $i++ ) {
        delete_transient( "ils_log_chunk_$i" );
    }

    $option_keys = [
        'init_plugin_suite_live_search_settings',
        'init_plugin_suite_live_search_custom_synonyms',
        'init_plugin_suite_live_search_meili_settings',
        'init_live_search_predifined_dict',
        'ils_log_chunk_index',
        'init_plugin_suite_live_search_fulltext_schema_version',
        'init_plugin_suite_live_search_fulltext_supported',
        'init_plugin_suite_live_search_fulltext_indexed',
        'init_plugin_suite_live_search_fulltext_cron_state',
        'init_plugin_suite_live_search_meili_indexed',
        'init_plugin_suite_live_search_meili_cron_state',
        'init_plugin_suite_live_search_meili_cron_last_error',
        'init_plugin_suite_live_search_meili_cron_skipped',
    ];

    foreach ( $option_keys as $key ) {
        delete_option( $key );
    }

    $transient_keys = [
        'init_plugin_suite_live_search_fulltext_min_token',
        'init_plugin_suite_live_search_fulltext_cron_lock',
        'init_plugin_suite_live_search_meili_cron_lock',
        'init_plugin_suite_live_search_meili_down',
    ];

    foreach ( $transient_keys as $key ) {
        delete_transient( $key );
    }

    // Per-post cache transients with dynamic names (AI related, related posts,
    // 404 redirect). Only relevant when transients live in the options table;
    // with a persistent object cache they simply expire on their own.
    $prefixes = [ 'init_related_ai_', 'ils_rel_', 'ils_404_' ];
    foreach ( $prefixes as $prefix ) {
        $like_value   = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
        $like_timeout = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                $like_value,
                $like_timeout
            )
        );
    }

    wp_clear_scheduled_hook( 'init_plugin_suite_live_search_fulltext_cron_batch' );
    wp_clear_scheduled_hook( 'init_plugin_suite_live_search_meili_cron_batch' );

    // Drop the local FULLTEXT search index table (opt-in feature, own table only —
    // wp_posts and all core tables are never touched by this plugin).
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}init_live_search_fulltext_index" );
}

if ( is_multisite() ) {
    $init_plugin_suite_live_search_site_ids = get_sites(
        [
            'fields' => 'ids',
            'number' => 0,
        ]
    );

    foreach ( $init_plugin_suite_live_search_site_ids as $init_plugin_suite_live_search_site_id ) {
        switch_to_blog( $init_plugin_suite_live_search_site_id );
        init_plugin_suite_live_search_uninstall_site();
        restore_current_blog();
    }
} else {
    init_plugin_suite_live_search_uninstall_site();
}
