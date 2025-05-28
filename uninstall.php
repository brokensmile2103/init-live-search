<?php
// Exit if accessed directly or not uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option('init_plugin_suite_live_search_settings');

// Delete analytics logs (ils_log_chunk_*) and index
$chunk_index = absint(get_option('ils_log_chunk_index', 1));
for ($i = 1; $i <= $chunk_index; $i++) {
    delete_transient("ils_log_chunk_$i");
}
delete_option('ils_log_chunk_index');
