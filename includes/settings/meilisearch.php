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
    <?php esc_html_e( 'New, updated, or deleted posts are synced to Meilisearch automatically. To index all existing content for the first time (or to rebuild the index), either click the button below (runs in the background, no server access needed) or run it yourself via WP-CLI.', 'init-live-search' ); ?>
</p>

<p>
    <button type="button" class="button button-primary" id="init-ls-meili-reindex-now">
        <?php esc_html_e( 'Reindex Now', 'init-live-search' ); ?>
    </button>
</p>

<?php
$meili_running     = (bool) wp_next_scheduled( 'init_plugin_suite_live_search_meili_cron_batch' );
$meili_last_error  = get_option( 'init_plugin_suite_live_search_meili_cron_last_error', '' );
$meili_indexed_at  = get_option( 'init_plugin_suite_live_search_meili_indexed', '' );
?>

<div id="init-ls-meili-reindex-status" style="max-width: 700px;" data-running="<?php echo $meili_running ? '1' : '0'; ?>">
    <?php if ( $meili_running ) : ?>
        <p class="description">
            <?php esc_html_e( 'Reindexing in the background (about 200 posts every 5 seconds)…', 'init-live-search' ); ?>
        </p>
    <?php elseif ( $meili_last_error ) : ?>
        <p class="description" style="color:#d63638;">
            <?php
            printf(
                /* translators: %s: error message returned by Meilisearch or the HTTP request */
                esc_html__( 'Background reindex stopped after repeated errors: %s', 'init-live-search' ),
                esc_html( $meili_last_error )
            );
            ?>
        </p>
    <?php elseif ( $meili_indexed_at ) : ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: date/time the index was last built */
                esc_html__( 'Index last built: %s.', 'init-live-search' ),
                esc_html( $meili_indexed_at )
            );
            ?>
        </p>
    <?php endif; ?>
</div>

<p class="description" style="max-width: 700px;">
    <?php esc_html_e( 'Or run it manually any time via WP-CLI:', 'init-live-search' ); ?><br>
    <code>wp init-live-search meili-reindex</code>
</p>