<?php
// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Option keys (sẽ mở rộng nếu có thêm)
$option_keys = [
    'init_plugin_suite_live_search_settings',
    'init_plugin_suite_live_search_custom_synonyms',
    'ils_log_chunk_index',
    'init_plugin_suite_live_search_fulltext_schema_version',
    'init_plugin_suite_live_search_fulltext_supported',
    'init_plugin_suite_live_search_fulltext_indexed',
    'init_plugin_suite_live_search_fulltext_cron_state',
    'init_plugin_suite_live_search_meili_indexed',
    'init_plugin_suite_live_search_meili_cron_state',
    'init_plugin_suite_live_search_meili_cron_last_error',
];

// Delete all defined options
foreach ( $option_keys as $key ) {
    delete_option( $key );
}

// Delete all ils_log_chunk_* transients
$chunk_index = absint( get_option( 'ils_log_chunk_index', 1 ) );
for ( $i = 1; $i <= $chunk_index; $i++ ) {
    delete_transient( "ils_log_chunk_$i" );
}

delete_transient( 'init_plugin_suite_live_search_fulltext_min_token' );

// Drop the local FULLTEXT search index table (opt-in feature, own table only —
// wp_posts and all core tables are never touched by this plugin).
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}init_live_search_fulltext_index" );
