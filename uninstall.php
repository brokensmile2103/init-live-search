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
