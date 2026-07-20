<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$meili = get_option( INIT_PLUGIN_SUITE_LS_MEILI_OPTION, [] );
$admin_key_from_constant = defined( 'INIT_LIVE_SEARCH_MEILI_ADMIN_KEY' ) && INIT_LIVE_SEARCH_MEILI_ADMIN_KEY;
?>

<h2><?php esc_html_e( 'Meilisearch', 'init-live-search' ); ?></h2>

<p class="description">
    <?php esc_html_e( 'Meilisearch is an optional, bring-your-own-server feature. You install and run Meilisearch yourself, then paste the connection details below. If the connection fails or is disabled, the plugin automatically falls back to the built-in local database search — your search never stops working just because Meilisearch has an issue.', 'init-live-search' ); ?>
</p>

<form method="post" action="options.php">
    <?php settings_fields( INIT_PLUGIN_SUITE_LS_GROUP_MEILI ); ?>

    <table class="form-table" role="presentation">
        <tr>
            <th colspan="2"><h2><?php esc_html_e( 'Connection', 'init-live-search' ); ?></h2></th>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Enable Meilisearch', 'init-live-search' ); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[enabled]" value="1" <?php checked( ! empty( $meili['enabled'] ) ); ?>>
                    <?php esc_html_e( 'Use Meilisearch as the primary search source (falls back to the database automatically on error).', 'init-live-search' ); ?>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="meili_host"><?php esc_html_e( 'Host URL', 'init-live-search' ); ?></label></th>
            <td>
                <input type="url" id="meili_host" class="regular-text code"
                       name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[host]"
                       value="<?php echo esc_attr( $meili['host'] ?? '' ); ?>"
                       placeholder="https://search.example.com">
                <p class="description"><?php esc_html_e( 'The full URL to your Meilisearch server (HTTPS recommended).', 'init-live-search' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="meili_index"><?php esc_html_e( 'Index Name', 'init-live-search' ); ?></label></th>
            <td>
                <input type="text" id="meili_index" class="regular-text code"
                       name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[index]"
                       value="<?php echo esc_attr( $meili['index'] ?? '' ); ?>"
                       placeholder="my_site_posts">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="meili_search_key"><?php esc_html_e( 'Search API Key', 'init-live-search' ); ?></label></th>
            <td>
                <input type="password" id="meili_search_key" class="regular-text code" autocomplete="new-password"
                       name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[search_key]"
                       value="<?php echo esc_attr( $meili['search_key'] ?? '' ); ?>">
                <p class="description"><?php esc_html_e( 'Use a key scoped to the "search" action only — this key is sent with every search request, so never use your master key here.', 'init-live-search' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="meili_admin_key"><?php esc_html_e( 'Admin / Indexing Key', 'init-live-search' ); ?></label></th>
            <td>
                <?php if ( $admin_key_from_constant ) : ?>
                    <p class="description">
                        <?php esc_html_e( 'Currently defined via the INIT_LIVE_SEARCH_MEILI_ADMIN_KEY constant in wp-config.php (this takes priority over any value entered here).', 'init-live-search' ); ?>
                    </p>
                <?php else : ?>
                    <input type="password" id="meili_admin_key" class="regular-text code" autocomplete="new-password"
                           name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[admin_key]"
                           value="<?php echo esc_attr( $meili['admin_key'] ?? '' ); ?>">
                    <p class="description">
                        <?php esc_html_e( 'A key with document add/delete permissions, used to automatically sync posts when you publish, update, or delete them. Recommended: define this via the INIT_LIVE_SEARCH_MEILI_ADMIN_KEY constant in wp-config.php instead of storing it here for better security.', 'init-live-search' ); ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="meili_timeout"><?php esc_html_e( 'Request Timeout (ms)', 'init-live-search' ); ?></label></th>
            <td>
                <input type="number" id="meili_timeout" min="500" max="8000" step="100"
                       name="<?php echo esc_attr( INIT_PLUGIN_SUITE_LS_MEILI_OPTION ); ?>[timeout_ms]"
                       value="<?php echo esc_attr( $meili['timeout_ms'] ?? 3000 ); ?>">
                <p class="description"><?php esc_html_e( 'If Meilisearch does not respond within this time, the plugin automatically falls back to the database search.', 'init-live-search' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"></th>
            <td>
                <button type="button" class="button" id="init-ls-meili-test-connection">
                    <?php esc_html_e( 'Test Connection', 'init-live-search' ); ?>
                </button>
                <span id="init-ls-meili-test-result" style="margin-left: 8px;"></span>
            </td>
        </tr>
    </table>

    <?php submit_button(); ?>
</form>

<hr>

<h2><?php esc_html_e( 'Reindexing', 'init-live-search' ); ?></h2>
<p class="description" style="max-width: 700px;">
    <?php esc_html_e( 'New, updated, or deleted posts are synced to Meilisearch automatically. To index all existing content for the first time (or to rebuild the index), run the following WP-CLI command on your server:', 'init-live-search' ); ?>
</p>
<pre style="background:#f0f0f1; padding: 10px 14px; max-width: 700px; overflow-x: auto;">wp init-live-search meili-reindex</pre>