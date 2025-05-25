=== Init Live Search ===
Contributors: brokensmile.2103  
Tags: live search, ajax search, wordpress search, rest api, slash command  
Requires at least: 5.2  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.5  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Blazing-fast live search modal for WordPress. Powered by REST API and Vanilla JS. Supports voice, keyboard, slash commands, and caching.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. **Init Live Search** is a lightweight, modern, and fully accessible live search solution for WordPress.

It replaces the default `<input name="s">` with a clean, intuitive modal that retrieves results instantly via the WordPress REST API. Everything happens in real-time — without disrupting the browsing flow.

Designed for both blogs and headless sites, it includes optional features like voice input, dark mode, keyword suggestions, and advanced developer hooks for total flexibility.

When a user focuses on any `<input name="s">`, a sleek modal appears and instantly displays results — no page reloads, no disruption.

The plugin supports:
- Keyboard navigation (↑ ↓ ← → Enter Esc)
- Slash commands (e.g. `/recent`, `/id`, `/tag`)
- Voice input (if supported)
- Dark mode (`.dark` class or global config)
- Smart fallback and result caching

This plugin is part of the [Init Plugin Suite](https://inithtml.com/init-plugin-suite-bo-plugin-wordpress-toi-gian-manh-me-mien-phi/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

== What’s New in Version 1.5 ==

- Quick Search tooltip: select 2–8 words on any page to trigger instant search
- `data-ils` attribute: open modal and prefill slash commands from any HTML element
- New slash commands: `/fav` and `/fav_clear` to manage favorite posts via `localStorage`
- Favorite system: add or remove posts directly from result list using a star icon
- Improved command handling: better stateful rendering and reset behavior
- `hiddenUrl` logic refined to reset when no result is selected
- Consistent modal trigger behavior across all entry points
- UX boost on mobile: auto-focus and select search input with keyboard support
- Codebase optimized for future extensibility with minimal API changes

== Features ==

Everything you expect from a modern live search — and more:

- Live search powered by WordPress REST API (no admin-ajax)
- Clean modal interface that works with any theme
- Fully keyboard accessible (Arrow keys, Enter, Escape)
- Slash command system (`/recent`, `/popular`, `/tag`, `/id`, `/fav`, etc.)
- Favorites support: manage with slash commands or heart icon in results
- Quick Search tooltip: select text to trigger instant search
- Voice input support using built-in SpeechRecognition
- Smart category filter (client-side, no extra API calls)
- Deep linking: open modal and prefill terms from URL (`?modal=search&term=...`)
- Custom triggers: Ctrl + /, triple-click, or `data-ils` attribute
- Optional keyword suggestions (manual or auto-generated)
- Built with pure JavaScript — no jQuery required

== Dark Mode Support ==

Enable dark mode for the modal by either:

1. Adding the dark class:

    document.querySelector('#ils-modal')?.classList.add('dark');

2. Or using a global config: 

    window.InitPluginSuiteLiveSearchConfig = { theme: 'dark' };

Options: `dark`, `light`, `auto`

== Admin Settings ==

- Choose post types to include in search
- Set debounce time, max results, and search mode
- Toggle fallback logic (bigrams/trim)
- Enable/disable default CSS
- Enable result caching (localStorage)
- Define or auto-generate keyword suggestions
- Add default UTM parameter to result links

== Keyboard Navigation ==

- Arrow Up / Down — navigate between results
- Arrow Right — add selected result to favorites (if not already added)
- Arrow Left — remove selected result from favorites
- Enter — open selected result or submit
- Escape — close modal and reset state
- Slash (/) — start a command instantly (e.g., `/recent`, `/id 123`)

== Filters for Developers ==

This plugin includes multiple filters to help developers customize behavior and output at various stages of the search flow.

### `init_plugin_suite_live_search_enable_fallback`

Enable or disable fallback logic (trimming or bigrams) when few results are found.  
**Applies to:** `/search`  
**Params:** `bool $enabled`, `string $term`, `WP_REST_Request $request`

### `init_plugin_suite_live_search_post_ids`

Customize the array of post IDs returned from the search query.  
**Applies to:** `/search`  
**Params:** `array $post_ids`, `string $term`, `WP_REST_Request $request`

### `init_plugin_suite_live_search_result_item`

Modify each result item before it's sent in the response.  
**Applies to:** `/search`  
**Params:** `array $item`, `int $post_id`, `string $term`, `WP_REST_Request $request`

### `init_plugin_suite_live_search_results`

Filter the final array of results before being returned.  
**Applies to:** `/search`  
**Params:** `array $results`, `array $post_ids`, `string $term`, `WP_REST_Request $request`

### `init_plugin_suite_live_search_category`

Customize the category label shown in search results.  
**Applies to:** all endpoints  
**Params:** `string $category_name`, `int $post_id`

### `init_plugin_suite_live_search_default_thumb`

Override the default thumbnail if the post lacks a featured image.  
**Applies to:** all endpoints  
**Params:** `string $thumb_url`

### `init_plugin_suite_live_search_query_args`

Modify WP_Query arguments for recent, date, or taxonomy-based commands.  
**Applies to:** `/recent`, `/date`, `/tax`  
**Params:** `array $args`, `string $type ('recent' | 'date' | 'tax')`, `WP_REST_Request $request`

### `init_plugin_suite_live_search_stop_words`

Customize the stop-word list used when auto-generating suggested keywords.  
**Params:** `array $stop_words`, `string $locale`

### `init_plugin_suite_live_search_taxonomy_cache_ttl`

Customize the cache duration (in seconds) for the `/taxonomies` endpoint. Return `0` to disable caching.
**Applies to:** `/taxonomies`  
**Params:** `int $ttl`, `string $taxonomy`, `int $limit`

== REST API Endpoints ==

Fully documented, lightweight, and API-first endpoints. Ideal for headless or decoupled builds.

All endpoints are under namespace: `initlise/v1`

- `/search?term=example`  
  Standard search query (uses settings like post types, search mode, fallback…)

- `/id/{id}`  
  Fetch a post by ID. Returns permalink.

- `/recent`  
  Fetch the most recent posts based on plugin settings.

- `/date?value=Y`, `/date?value=Y/m`, `/date?value=Y/m/d`  
  Fetch posts by year, month, or day.

- `/tax?taxonomy=category&term=slug-or-id`  
  Fetch posts by taxonomy (e.g., `category`, `post_tag`, or custom).

- `/related?title=page-title&exclude=ID`  
  Fetch posts related to the current page title (useful for showing similar articles).

- `/read?ids=1,2,3`  
  Fetch post data by IDs stored in localStorage (e.g., by `Init Reading Position` plugin or custom logic).

- `/random`  
  Return a random published post based on settings. Redirects via JavaScript.

- `/taxonomies?taxonomy=category`  
  Return a list of taxonomy terms (e.g., categories, tags), sorted by count.

== Screenshots ==

1. Admin settings with search behavior options
2. Clean modal interface with keyword suggestions
3. Search results with filter pills and post types
4. Fully supports dark mode (auto or manual)
5. Slash command dropdown helper with real-time suggestions

== FAQ ==

= Does this plugin use jQuery? =  
No, it’s written entirely in modern Vanilla JavaScript.

= How is search triggered? =  
It automatically detects and overrides any `<input name="s">`. You can also trigger the modal by triple-clicking, pressing Ctrl + / (or Cmd + / on Mac), or visiting a URL with `#search` or `?modal=search`.

= Can I open the modal and prefill a search term via URL? =  
Yes. Use `?modal=search&term=your+keyword` in the URL to auto-open the modal and prefill the input. The search will start automatically.

= Is voice input supported? =  
Yes. If supported by the browser, it uses the built-in `SpeechRecognition` API for microphone input.

= Can I generate keyword suggestions automatically? =  
Yes. You can either enter keywords manually or auto-generate them from your content via the settings panel.

= Is caching enabled by default? =  
Yes. Search results are cached in `localStorage` to improve speed and reduce repeat queries.

= What happens if no result is selected? =  
The plugin will fallback to the default WordPress search behavior when you press Enter.

= Can I use this on mobile? =  
Absolutely. The modal is fully responsive, mobile-friendly, and works seamlessly across devices.

= What’s the triple-click trigger? =  
You can triple-click anywhere on the page (within 0.5 seconds) to instantly open the search modal.

= Can I disable all triggers and only use the REST API? =  
Yes. If you turn off all three triggers (input focus, Ctrl + /, and triple-click), the plugin won’t enqueue any assets — only the REST API endpoints will be registered.

= What are slash commands? =  
Slash commands are special quick actions you can type like `/recent`, `/id 123`, or `/tag wordpress`. They let you filter or jump directly without typing a keyword.

= Can I disable slash commands completely? =  
Yes. There’s a toggle in the admin settings to turn off all slash command functionality.

= Can I override the search template? =  
No need — this plugin uses a modal and doesn’t require template overrides. All results are rendered via JavaScript.

= What is Quick Search tooltip? =  
When you select 2–8 words on any page, a small tooltip appears allowing you to search instantly — no typing required.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install via the admin panel.
2. Activate the plugin from the Plugins menu.
3. It will automatically enhance all `<input name="s">` fields.
4. (Optional) Configure advanced settings in Settings → Init Live Search.

== Changelog ==

= 1.5.0 – May 25, 2025 =
- Added Quick Search tooltip when selecting 2–8 words of text, allowing instant modal activation
- Added support for `data-ils` attribute to trigger the modal and prefill slash commands from any HTML element
- Introduced `/fav` and `/fav_clear` commands to manage favorite posts using `localStorage`
- Enabled adding/removing favorites directly in the result list via a new star icon
- Improved internal command handling for better stateful list rendering and filter reset
- Refined `hiddenUrl` logic to reset properly when no result is selected
- Unified modal trigger behavior for consistent UX across all entry points
- Optimized codebase for future extensibility with minimal impact on existing API or markup

= 1.4.3 – May 24, 2025 =
- Lazy initialization: modal is only created when the user triggers search
- Added `ils:search-started`, `ils:results-loaded`, `ils:modal-opened` and `ils:modal-closed` events for developer integrations
- Improved keyboard UX when navigating suggestions and command lists
- Enhanced accessibility: ARIA roles and keyboard behavior polish
- Optimized DOM selection and scroll handling for large result sets
- Fixed minor bugs related to triple-click and voice recognition edge cases
- Internal cleanup: separated state logic and added inline documentation
- Final polish for 1.4.x series — ready for production on large-scale content sites

= 1.4.2 – May 24, 2025 =
- Improved keyboard navigation UX and modal interactions
- Added live dropdown suggestions for slash commands (e.g., `/re...`)
- New admin setting to completely disable slash commands
- Added support for deep linking via `?modal=search&term=...`
- Auto-open modal and prefill command term from URL
- Minor JS improvements and accessibility enhancements

= 1.4.1 – May 23, 2025 =
- Extended slash command system: added `/related`, `/read`, `/random`, `/categories`, `/tags`, `/help`, `/clear`, and `/reset`
- New toggle to enable/disable voice input in admin settings
- Improved compatibility with Init Reading Position for `/read`
- Smart highlight and reverse-order support for recently read posts
- New REST API endpoints for related posts, taxonomy lists, and more
- Internal command result caching using `localStorage` (e.g., `/date`, `/tax`, `/categories`)
- Full internationalization (i18n) for commands and messages
- UI enhancements and pill-style suggestion rendering
- Refactored JS for modularity and fallback handling

= 1.4 – May 23, 2025 =
- Introduced slash command system: supports `/recent`, `/popular`, `/tag`, `/category`, `/date`, and `/id`
- Smart `/date` parsing (supports year, month, and day)
- `/id` command jumps directly to a post by ID
- Unified command parsing and custom REST endpoints
- More powerful taxonomy and date search handling
- Optimized all WP_Query calls for performance
- Internal command result caching (`localStorage`)
- New options to toggle individual triggers: Ctrl + /, triple-click, or input focus
- Codebase polish and improved JS architecture

= 1.3 – May 22, 2025 =
- Triple-click to open modal
- Keyboard shortcut: Ctrl + /
- Display post type name
- Client-side category filter UI (based on returned results)
- Clear icon inside search input
- New developer filter prefix

= 1.2 – May 20, 2025 =
- Voice input (SpeechRecognition)
- Fallback logic and CSS settings
- Developer filters

= 1.1 – May 18, 2025 =
- Improved fallback matching
- Prefill previous term
- Character limit
- UTM and cache support
- Theme control

= 1.0 – May 17, 2025 =
- Initial release
- REST API-powered modal search
- Manual keyword suggestions
- No external assets: all icons and fallback thumbnails are inlined SVGs — ultra-fast, zero overhead

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
