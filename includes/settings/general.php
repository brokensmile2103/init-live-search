<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$options = get_option(INIT_PLUGIN_SUITE_LS_OPTION, []);
$post_types = get_post_types(['public' => true], 'objects');
unset($post_types['attachment']);
?>

<form method="post" action="options.php">
    <?php settings_fields(INIT_PLUGIN_SUITE_LS_GROUP_GENERAL); ?>

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
            <th scope="row"><?php esc_html_e('Enable + / - Search Operators?', 'init-live-search'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_search_operators]" value="1" <?php checked(!empty($options['enable_search_operators']) && $options['enable_search_operators']); ?>>
                    <?php esc_html_e('Allow using +word to force match, -word to exclude.', 'init-live-search'); ?>
                </label>
                <p class="description"><?php esc_html_e('Enable this if you want to support advanced users using must-have (+) and must-not-have (-) keyword operators.', 'init-live-search'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Cross-site Search Domains', 'init-live-search'); ?></th>
            <td>
                <textarea name="init_plugin_suite_live_search_settings[cross_sites]" rows="5" class="large-text code"><?php echo esc_textarea($options['cross_sites'] ?? ''); ?></textarea>
                <p class="description">
                    <?php esc_html_e('Enter one site per line in the format: Site Name|https://example.com/', 'init-live-search'); ?><br>
                    <?php esc_html_e('When filled, your search will fetch results from these additional domains and merge them into one list.', 'init-live-search'); ?><br>
                    <?php esc_html_e('Each external site must also have the Init Live Search plugin installed and active.', 'init-live-search'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Default Slash Command on Modal Open', 'init-live-search'); ?></th>
            <td>
                <?php
                    $raw_default_command = $options['default_command'] ?? 'none';

                    $default_command_options = [
                        'none'    => __('None (do not preload anything)', 'init-live-search'),
                        'default' => __('Default (/recent)', 'init-live-search'),
                        'related' => __('Related Posts (use /related)', 'init-live-search'),
                        'auto'    => __('Smart Detection (based on current page)', 'init-live-search'),
                    ];

                    if (defined('INIT_PLUGIN_SUITE_VIEW_COUNT_VERSION')) {
                        $default_command_options['popular']  = __('Popular Posts (use /popular)', 'init-live-search');
                        $default_command_options['trending'] = __('Trending Posts (use /trending)', 'init-live-search');
                    }

                    if (defined('INIT_PLUGIN_SUITE_RP_VERSION')) {
                        $default_command_options['read'] = __('Continue Reading (use /read)', 'init-live-search');
                    }

                    foreach ($default_command_options as $value => $label) :
                        ?>
                        <label>
                            <input type="radio" name="init_plugin_suite_live_search_settings[default_command]" value="<?php echo esc_attr($value); ?>"
                                <?php checked($raw_default_command === $value); ?>>
                            <?php echo esc_html($label); ?>
                        </label><br>
                    <?php endforeach; ?>

                <p class="description">
                    <?php esc_html_e('Choose a default slash command to run automatically when the search modal opens.', 'init-live-search'); ?><br>
                    <?php esc_html_e('“Smart Detection” automatically detects based on current page: /related for posts, /product for shop pages, taxonomy-based commands, etc.', 'init-live-search'); ?><br>
                    <?php esc_html_e('“Popular”, “Trending” and “Read” options are only available if their respective plugins are active.', 'init-live-search'); ?>
                </p>
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
            <th scope="row"><?php esc_html_e('Show Excerpt in Search Results?', 'init-live-search'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="init_plugin_suite_live_search_settings[show_excerpt]" value="1" <?php checked(!isset($options['show_excerpt']) || $options['show_excerpt']); ?>>
                    <?php esc_html_e('Display the post excerpt below each result item in the modal.', 'init-live-search'); ?>
                </label>
                <p class="description"><?php esc_html_e('If disabled, excerpt will be omitted from results to simplify the UI.', 'init-live-search'); ?></p>
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
            <th scope="row"><?php esc_html_e('Search in SEO Metadata?', 'init-live-search'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="init_plugin_suite_live_search_settings[seo_search_fields_enabled]" value="1" <?php checked(!empty($options['seo_search_fields_enabled'])); ?>>
                    <?php esc_html_e('Include SEO Title and Meta Description (Yoast, Rank Math, TSF, etc.) in search matching.', 'init-live-search'); ?>
                </label>
                <p class="description"><?php esc_html_e('Enable this to improve accuracy by searching within SEO-optimized content written by the author.', 'init-live-search'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Enable Synonym Expansion?', 'init-live-search'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_synonym]" value="1" <?php checked(!empty($options['enable_synonym'])); ?>>
                    <?php esc_html_e('Allow search terms to be expanded using built-in or custom-defined synonyms.', 'init-live-search'); ?>
                </label>
                <p class="description">
                    <?php esc_html_e('Enable this only after defining useful synonym mappings. Otherwise, it will have no effect.', 'init-live-search'); ?>
                </p>
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
            <th scope="row"><?php esc_html_e('Enable Search Analytics?', 'init-live-search'); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="init_plugin_suite_live_search_settings[enable_analytics]" value="1" <?php checked(!empty($options['enable_analytics'])); ?>>
                    <?php esc_html_e('Log search queries (term, result count) for analytics purposes.', 'init-live-search'); ?>
                </label>
                <p class="description"><?php esc_html_e('When enabled, search data will be stored temporarily using transients to help analyze usage trends. No IPs or personal data are stored.', 'init-live-search'); ?></p>
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
            <th scope="row"><?php esc_html_e('Frontend CSS Style', 'init-live-search'); ?></th>
            <td>
                <fieldset>
                    <label>
                        <input type="radio" name="init_plugin_suite_live_search_settings[css_style]" value="default" <?php checked(($options['css_style'] ?? 'default') === 'default'); ?>>
                        <?php esc_html_e('Default (modal-style)', 'init-live-search'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="init_plugin_suite_live_search_settings[css_style]" value="full" <?php checked(($options['css_style'] ?? 'default') === 'full'); ?>>
                        <?php esc_html_e('Full Screen Overlay', 'init-live-search'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="init_plugin_suite_live_search_settings[css_style]" value="topbar" <?php checked(($options['css_style'] ?? 'default') === 'topbar'); ?>>
                        <?php esc_html_e('Top Bar', 'init-live-search'); ?>
                    </label><br>
                    <label>
                        <input type="radio" name="init_plugin_suite_live_search_settings[css_style]" value="none" <?php checked(($options['css_style'] ?? 'default') === 'none'); ?>>
                        <?php esc_html_e('None (disable all built-in CSS, load manually)', 'init-live-search'); ?>
                    </label>
                </fieldset>
                <p class="description">
                    <?php esc_html_e('Choose how the frontend modal should be styled. You can disable all styles and provide your own if needed.', 'init-live-search'); ?>
                </p>
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
    <div id="shortcode-builder-target" data-plugin="init-live-search"></div>
</div>
