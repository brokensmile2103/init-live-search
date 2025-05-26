<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    add_options_page(
        __('Init Live Search Settings', 'init-live-search'),
        __('Init Live Search', 'init-live-search'),
        'manage_options',
        'init-live-search-settings',
        'init_plugin_suite_live_search_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting(
        'init_plugin_suite_live_search_settings_group',
        INIT_PLUGIN_SUITE_LS_OPTION,
        'init_plugin_suite_live_search_sanitize_settings'
    );
});

function init_plugin_suite_live_search_render_settings_page() {
    $options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
    $post_types = get_post_types(['public' => true], 'objects');
    ?>
    <div class="wrap">
        <h1 id="init-live-search-settings"><?php esc_html_e('Init Live Search Settings', 'init-live-search'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('init_plugin_suite_live_search_settings_group'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th colspan="2"><h2 id="section-triggers"><?php esc_html_e('Search Triggers', 'init-live-search'); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Post Types to Include', 'init-live-search'); ?></th>
                    <td>
                        <?php foreach ($post_types as $post_type): ?>
                            <label>
                                <input type="checkbox" name="init_plugin_suite_live_search_settings[post_types][]" value="<?php echo esc_attr($post_type->name); ?>" <?php checked(in_array($post_type->name, $options['post_types'] ?? ['post'])); ?>>
                                <?php echo esc_html($post_type->label); ?>
                            </label><br>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Trigger methods to open modal', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[trigger_input_focus]" value="1" <?php checked(!isset($options['trigger_input_focus']) || $options['trigger_input_focus']); ?>>
                            <?php esc_html_e('Attach event to input[name="s"]', 'init-live-search'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[trigger_triple_click]" value="1" <?php checked(!isset($options['trigger_triple_click']) || $options['trigger_triple_click']); ?>>
                            <?php esc_html_e('Triple click anywhere on the page', 'init-live-search'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[trigger_ctrl_slash]" value="1" <?php checked(!isset($options['trigger_ctrl_slash']) || $options['trigger_ctrl_slash']); ?>>
                            <?php esc_html_e('Press Ctrl + / (or Cmd + / on Mac)', 'init-live-search'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Select one or more methods to trigger the search modal.', 'init-live-search'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h2 id="search-behavior"><?php esc_html_e('Search Behavior', 'init-live-search'); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Slash Commands?', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_slash]" value="1" <?php checked(!isset($options['enable_slash']) || $options['enable_slash']); ?>>
                            <?php esc_html_e('Allow search commands starting with "/" such as /recent, /id, /tag, etc.', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Search Mode', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="init_plugin_suite_live_search_settings[search_mode]" value="title" <?php checked(($options['search_mode'] ?? 'title') === 'title'); ?>>
                            <?php esc_html_e('Title Only', 'init-live-search'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="init_plugin_suite_live_search_settings[search_mode]" value="title_tag" <?php checked(($options['search_mode'] ?? 'title') === 'title_tag'); ?>>
                            <?php esc_html_e('Init Smart Tag-Aware Search', 'init-live-search'); ?>
                        </label>
                        <small><span class="dashicons dashicons-editor-help" title="<?php esc_attr_e('This mode matches against post title and tag names. Ideal for content with rich tagging.', 'init-live-search'); ?>"></span></small><br>
                        <label>
                            <input type="radio" name="init_plugin_suite_live_search_settings[search_mode]" value="title_excerpt" <?php checked(($options['search_mode'] ?? 'title') === 'title_excerpt'); ?>>
                            <?php esc_html_e('Title and Excerpt', 'init-live-search'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="init_plugin_suite_live_search_settings[search_mode]" value="title_content" <?php checked(($options['search_mode'] ?? 'title') === 'title_content'); ?>>
                            <?php esc_html_e('Title, Excerpt and Content', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Search in ACF Fields (Advanced)', 'init-live-search'); ?></th>
                    <td>
                        <input type="text" name="init_plugin_suite_live_search_settings[acf_search_fields]" value="<?php echo esc_attr($options['acf_search_fields'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Optional. Enter comma-separated ACF field keys to include in search (e.g. company_name, project_code). If left blank, ACF fields will not be searched.', 'init-live-search'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Enable fallback matching?', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_fallback]" value="1" <?php checked(!isset($options['enable_fallback']) || $options['enable_fallback']); ?>>
                            <?php esc_html_e('Try trimming or using bigrams if not enough results are found.', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h2 id="performance-ux"><?php esc_html_e('Performance & UX', 'init-live-search'); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Debounce Time (ms)', 'init-live-search'); ?></th>
                    <td>
                        <input type="number" name="init_plugin_suite_live_search_settings[debounce]" min="100" max="3000" value="<?php echo esc_attr($options['debounce'] ?? 500); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Max Results', 'init-live-search'); ?></th>
                    <td>
                        <input type="number" name="init_plugin_suite_live_search_settings[max_results]" min="1" max="100" value="<?php echo esc_attr($options['max_results'] ?? 10); ?>">
                        <p class="description"><?php esc_html_e('Maximum number of results to display in the modal.', 'init-live-search'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Max Words to Trigger Highlight Search', 'init-live-search'); ?></th>
                    <td>
                        <input type="number" name="init_plugin_suite_live_search_settings[max_select_word]" min="0" max="20" value="<?php echo esc_attr($options['max_select_word'] ?? 8); ?>">
                        <p class="description"><?php esc_html_e('Set maximum word count allowed to trigger tooltip search on text selection. Set 0 to disable.', 'init-live-search'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Voice Input?', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_voice]" value="1" <?php checked(!isset($options['enable_voice']) || $options['enable_voice']); ?>>
                            <?php esc_html_e('Enable microphone input using the SpeechRecognition API (if supported)', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Cache results in localStorage?', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[use_cache]" value="1" <?php checked(!isset($options['use_cache']) || $options['use_cache']); ?>>
                            <?php esc_html_e('Enable caching search results in localStorage to improve performance and reduce repeated requests.', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><h2 id="styling-suggestions"><?php esc_html_e('Styling & Suggestions', 'init-live-search'); ?></h2></th>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Enqueue default CSS?', 'init-live-search'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="init_plugin_suite_live_search_settings[enqueue_css]" value="1" <?php checked(!isset($options['enqueue_css']) || $options['enqueue_css']); ?>>
                            <?php esc_html_e('Load the built-in style.css file on the front-end.', 'init-live-search'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default UTM Parameter', 'init-live-search'); ?></th>
                    <td>
                        <input type="text" name="init_plugin_suite_live_search_settings[default_utm]" value="<?php echo esc_attr($options['default_utm'] ?? ''); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Optional. Set a default UTM parameter to include in search-related requests. Example: utm_source=search&utm_medium=plugin', 'init-live-search'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Suggested Keywords', 'init-live-search'); ?></th>
                    <td>
                        <input type="text" id="suggested_keywords_input" name="init_plugin_suite_live_search_settings[suggested_keywords]" value="<?php echo esc_attr($options['suggested_keywords'] ?? ''); ?>" class="regular-text">
                        <button type="button" class="button" id="generate_keywords_button"><?php esc_html_e('Generate Automatically', 'init-live-search'); ?></button>
                        <p class="description"><?php esc_html_e('Enter default search keywords for suggestions, separated by commas. You can also auto-generate.', 'init-live-search'); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <h2 id="usage-instructions"><?php esc_html_e('Usage Instructions', 'init-live-search'); ?></h2>
    <div style="background: #f9f9f9; border-left: 4px solid #0073aa; padding: 1em; margin-top: 1em;">
        <p><strong><?php esc_html_e('You can change the theme using one of these two ways:', 'init-live-search'); ?></strong></p>
        <ol>
            <li>
                <p><?php esc_html_e('Add the dark class to the modal container:', 'init-live-search'); ?></p>
                <p><code>document.querySelector('#ils-modal')?.classList.add('dark');</code></p>
            </li>
            <li>
                <p><?php esc_html_e('Or set the theme using a global config before the plugin initializes:', 'init-live-search'); ?></p>
                <p><code>window.InitPluginSuiteLiveSearchConfig = { theme: 'dark' };</code></p>
                <p>
                    <small><?php esc_html_e('Other values:', 'init-live-search'); ?> <code>'light'</code>, <code>'auto'</code></small>
                </p>
            </li>
        </ol>
    </div>
    <?php
}

function init_plugin_suite_live_search_sanitize_settings($input) {
    $output = [];
    $output['post_types'] = array_map('sanitize_key', $input['post_types'] ?? ['post']);
    if (empty($output['post_types'])) {
        $output['post_types'] = ['post'];
    }
    $output['debounce'] = max(100, min(3000, absint($input['debounce'] ?? 500)));
    $output['trigger_triple_click'] = !empty($input['trigger_triple_click']) ? '1' : '0';
    $output['trigger_ctrl_slash'] = !empty($input['trigger_ctrl_slash']) ? '1' : '0';
    $output['trigger_input_focus'] = !empty($input['trigger_input_focus']) ? '1' : '0';
    $output['enable_slash'] = !empty($input['enable_slash']) ? '1' : '0';
    $output['max_results'] = min(100, max(1, absint($input['max_results'] ?? 10)));
    
    $allowed_modes = ['title', 'title_excerpt', 'title_content', 'title_tag'];
    $output['search_mode'] = in_array($input['search_mode'], $allowed_modes, true) ? $input['search_mode'] : 'title';
    
    $output['acf_search_fields'] = sanitize_text_field($input['acf_search_fields'] ?? '');
    $output['enable_fallback'] = !empty($input['enable_fallback']) ? '1' : '0';
    $output['enqueue_css'] = !empty($input['enqueue_css']) ? '1' : '0';
    $output['use_cache'] = !empty($input['use_cache']) ? '1' : '0';
    $output['enable_voice'] = !empty($input['enable_voice']) ? '1' : '0';
    $output['max_select_word'] = max(0, min(20, absint($input['max_select_word'] ?? 8)));
    $output['default_utm'] = esc_url_raw($input['default_utm'] ?? '');
    $output['suggested_keywords'] = sanitize_text_field($input['suggested_keywords'] ?? '');
    return $output;
}

add_action('admin_enqueue_scripts', function ($hook_suffix) {
    if ($hook_suffix !== 'settings_page_init-live-search-settings') return;
    wp_enqueue_script(
        'init_plugin_suite_live_search_admin',
        INIT_PLUGIN_SUITE_LS_ASSETS_URL . 'js/admin.js',
        [],
        '1.0',
        true
    );
    wp_localize_script('init_plugin_suite_live_search_admin', 'init_plugin_suite_live_search_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('init_live_search_admin_nonce')
    ]);
});
