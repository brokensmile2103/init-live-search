=== Init Live Search – Smart, Slash Commands, REST API ===
Contributors: brokensmile.2103  
Tags: live search, ajax search, woocommerce, rest api, slash command  
Requires at least: 5.2  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.6.5  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Fast, modern live search powered by REST API — with slash commands, SEO-aware, ACF, WooCommerce, and custom UI presets.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. Init Live Search is a modern, lightweight, and fully accessible live search solution for WordPress — now with tag-aware matching, SEO metadata support, ACF integration, WooCommerce product filters, and customizable UI presets.

It replaces the default `<input name="s">` with a clean, intuitive search modal powered entirely by the WordPress REST API. Everything loads in real-time — with zero disruption to browsing flow.

You get:
- Beautiful preset styles (fullscreen, topbar, or default)  
- Fully keyboard accessible (`↑ ↓ ← → Enter Esc`)  
- Slash commands (`/recent`, `/tag`, `/id`, etc.) for power users  
- Quick Search tooltip triggered by selecting text  
- Voice input via browser SpeechRecognition  
- SEO-aware matching from popular plugins (Yoast, Rank Math, AIOSEO, etc.)  
- ACF field support for advanced content types  
- Local result caching and fallback logic  
- Theme override support or option to disable all plugin CSS

Perfect for content-heavy blogs, WooCommerce stores, or even headless sites. Every interaction is fast, fluid, and designed to work across devices.

This plugin is part of the [Init Plugin Suite](https://inithtml.com/init-plugin-suite-bo-plugin-wordpress-toi-gian-manh-me-mien-phi/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-live-search](https://github.com/brokensmile2103/init-live-search)

== What's New in Version 1.6.x ==

- **New UI Style Presets**: choose from fullscreen (`style-full.css`) or top bar (`style-topbar.css`) layouts  
- **Theme CSS Override**: place `init-live-search/style.css` in your theme to customize styles  
- **Disable Built-in CSS**: turn off all plugin styles and build your own from scratch  
- **UI Style Picker**: select a style directly from the admin settings  
- **Scoped CSS Loader**: clean separation of core, presets, and theme overrides  
- **Developer-Friendly**: styles are minimal and safe to integrate with any theme or builder  
- **Search Analytics (New Tab)**: track search queries, view counts, export CSV, and group results by frequency  
- **Default Slash Command**: preload a command like `/recent`, `/related`, `/popular`, or `/read` when modal opens  
- **New Slash Commands**: `/day`, `/week`, and `/month` show most viewed posts by timeframe (requires Init View Count)  
- **Search History Commands**: recall recent queries with `/history` and clear them with `/history_clear`  
- **Improved Voice Search**: optimized mic control, language detection, and error handling  
- **New Event: `ils:result-clicked`**: track clicks on search results with full metadata  
- **New Filter: `init_plugin_suite_live_search_commands`**: register custom slash commands from theme or plugin, define your own REST endpoint if needed, and handle results via `ils:*` JavaScript events
- **Contextual 1-Line Excerpts**: auto-highlight and display a short snippet from content or excerpt in all search results

== Features ==

Packed with everything a modern live search needs — and more:

- Live search powered by WordPress REST API (no admin-ajax)
- Smart tag-aware search mode: match keywords in both titles and post tags
- Search in SEO Metadata: match keywords in SEO Titles and Meta Descriptions from popular SEO plugins (Yoast, Rank Math, AIOSEO, TSF, SEOPress)
- Clean modal interface that works with any theme — no template override required
- Fully keyboard accessible (Arrow keys, Enter, Escape)
- Slash command system (`/recent`, `/popular`, `/tag`, `/id`, `/fav`, etc.)
- WooCommerce support: search by product, sale status, stock, SKU, or price range
- Favorites support: manage with slash commands or heart icon in results
- Quick Search tooltip: select text to trigger instant search
- Voice input support using built-in SpeechRecognition
- Smart category filter (client-side, no extra API calls)
- Infinite scroll for long result lists (search and slash commands)
- Deep linking: open modal and prefill terms from URL (`?modal=search&term=...`)
- Custom triggers: Ctrl + /, triple-click, or `data-ils` attribute
- Local caching with `localStorage` to improve performance
- Optional keyword suggestions (manual or auto-generated)
- UI style presets: choose between default, fullscreen (`style-full.css`), or topbar (`style-topbar.css`) layouts
- Theme override support: add `init-live-search/style.css` to fully customize design
- Option to disable all built-in CSS and style from scratch
- Search analytics: track queries, group results, export CSV — all without storing personal data
- Developer-friendly with filters and custom REST API endpoints
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
- Configure modal triggers (input focus, triple click, Ctrl+/)  
- Enable slash commands (e.g. /recent, /tag, /id)  
- Set **default slash command to run on modal open** (only if slash is enabled)   
- Set debounce time and max results  
- Choose search mode (title-only, tag-aware, full content)  
- Define custom ACF fields to include in search (optional)  
- Enable Search in SEO Metadata (Yoast, Rank Math, etc.)  
- Toggle fallback logic (bigrams/trim)  
- Enable Search Analytics to log queries (no personal data stored)  
- Set max words for tooltip search  
- Enable voice input (SpeechRecognition API)  
- Enable result caching (localStorage)  
- Choose frontend UI style (default, fullscreen, or topbar)  
- Allow theme override via `init-live-search/style.css`  
- Option to disable all built-in CSS completely  
- Add default UTM parameter to result links  
- Define or auto-generate keyword suggestions   

== Keyboard Shortcuts ==

- Arrow Up / Down — navigate between results
- Arrow Right — add selected result to favorites (if not already added)
- Arrow Left — remove selected result from favorites
- Enter — open selected result or submit
- Escape — close modal and reset state
- Slash (/) — start a command instantly (e.g., `/recent`, `/id 123`)

== Developer Reference: Filters and Hooks ==

== Filters for Developers ==

This plugin includes multiple filters to help developers customize behavior and output at various stages of the search flow.

**`init_plugin_suite_live_search_enable_fallback`**

Enable or disable fallback logic (trimming or bigrams) when few results are found.  
**Applies to:** search  
**Params:** `bool $enabled`, `string $term`, `array $args`

**`init_plugin_suite_live_search_post_ids`**

Customize the array of post IDs returned from the search query.  
**Applies to:** search  
**Params:** `array $post_ids`, `string $term`, `array $args`

**`init_plugin_suite_live_search_result_item`**

Modify each result item before it's sent in the response.  
**Applies to:** search  
**Params:** `array $item`, `int $post_id`, `string $term`, `array $args`

**`init_plugin_suite_live_search_results`**

Filter the final array of results before being returned.  
**Applies to:** search  
**Params:** `array $results`, `array $post_ids`, `string $term`, `array $args`

**`init_plugin_suite_live_search_category`**

Customize the category label shown in search results.  
**Applies to:** all endpoints  
**Params:** `string $category_name`, `int $post_id`

**`init_plugin_suite_live_search_default_thumb`**

Override the default thumbnail if the post lacks a featured image.  
**Applies to:** all endpoints  
**Params:** `string $thumb_url`

**`init_plugin_suite_live_search_query_args`**

Modify WP_Query arguments for recent, date, taxonomy-based, or product-based commands.  
**Applies to:** `recent`, `date`, `tax`, `product`, `random`  
**Params:** `array $args`, `string $type`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_stop_single_words`**

Customize the list of single-word stopwords removed before generating bigrams.  
**Applies to:** keyword suggestion  
**Params:** `array $stop_words`, `string $locale`

**`init_plugin_suite_live_search_stop_words`**

Customize the stop-word list used when auto-generating suggested keywords.  
**Params:** `array $stop_words`, `string $locale`

**`init_plugin_suite_live_search_taxonomy_cache_ttl`**

Customize the cache duration (in seconds) for the `/taxonomies` endpoint. Return `0` to disable caching.  
**Applies to:** `taxonomies`  
**Params:** `int $ttl`, `string $taxonomy`, `int $limit`

**`init_plugin_suite_live_search_filter_lang`**

Filter the list of post IDs by the current language. Supports Polylang and WPML.  
**Applies to:** search, related, read, and other multilingual-aware endpoints  
**Params:** `array $post_ids`, `string $term`, `array $args`

**`init_plugin_suite_live_search_category_taxonomy`**

Override the taxonomy used to fetch and display category labels in results.  
**Applies to:** all endpoints  
**Params:** `string $taxonomy`, `int $post_id`

**`init_plugin_suite_live_search_seo_meta_keys`**

Customize the list of meta keys used for matching SEO Titles and Meta Descriptions.  
**Applies to:** search (when Search in SEO Metadata is enabled)  
**Params:** `array $meta_keys`

**`init_plugin_suite_live_search_weights`**

Customize the weighting array used to merge and sort post IDs from multiple sources (title, SEO, tag, etc.).  
**Applies to:** search (search modes: `title`, `title_tag`, `title_excerpt`)  
**Params:** `array $weights`, `string $search_mode`

**`init_plugin_suite_live_search_commands`**  

Allow registration of custom slash commands to be displayed in the command list and handled via custom logic.  
**Applies to:** slash command system (`/` prefix input)  
**Params:** `array $commands`, `array $options`

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

- `/product?page=1&on_sale=1&in_stock=1&sku=ABC&min_price=100&max_price=500`  
  Fetch WooCommerce products using flexible query parameters. Supports slash commands: `/product`, `/on-sale`, `/stock`, `/sku`, `/price`.  
  Accepts:  
    - `term`: Search keyword  
    - `sku`: Partial or full SKU match  
    - `on_sale`: `1` to filter products on sale  
    - `in_stock`: `1` to filter products in stock  
    - `min_price` / `max_price`: Numeric range filter  
    - `page`: For pagination  
  Returns basic product info (title, URL, price, category, thumbnail), sale and stock status, and `add_to_cart_url`. Caching is applied per query.

== Screenshots ==

1. Admin settings with search behavior options  
2. Search Analytics tab: view and export recent query logs  
3. Clean modal interface with keyword suggestions  
4. Search results with filter pills and post types  
5. Fully supports dark mode (auto or manual)  
6. Slash command dropdown helper with real-time suggestions  
7. WooCommerce product search via `/product` slash command with price, sale, and out-of-stock indicators  
8. Fullscreen search interface using the `style-full.css` preset  
9. Top bar search layout using the `style-topbar.css` preset

== Frequently Asked Questions ==

= Does this plugin use jQuery? =  
No. It’s built entirely with modern Vanilla JavaScript.

= How is search triggered? =  
It auto-detects `<input name="s">` fields. You can also trigger it via triple-click, keyboard shortcut (Ctrl + /), selection tooltip, or `?modal=search` URL.

= Can I prefill the modal from a URL? =  
Yes. Use `?modal=search&term=your+keyword` to open the modal with a prefilled term.

= Is voice input supported? =  
Yes, via the browser’s built-in SpeechRecognition API (if available).

= What are slash commands? =  
Slash commands let you filter instantly with `/recent`, `/tag {slug}`, `/id {post_id}`, `/product`, `/price 100 500`, etc.

= Can I disable slash commands? =  
Yes. There’s an option in settings to disable them completely.

= What is Quick Search tooltip? =  
When you highlight 1–8 words, a tooltip appears to trigger instant search.  
This is configurable in settings (max 20; set 0 to disable).

= What is Smart Tag-Aware Search? =  
An advanced mode that matches keywords in titles and tags with fallback using split terms and bi-gram logic.

= What is Search in SEO Metadata? =  
Allows searching within SEO Titles and Meta Descriptions from plugins like Yoast SEO, Rank Math, AIOSEO, The SEO Framework, and SEOPress.

= Does it support WooCommerce? =  
Yes. Product commands like `/product`, `/on-sale`, `/stock`, and `/sku` show prices, badges, and “Add to Cart” links.

= Can I override the plugin’s CSS? =  
Yes. Add `init-live-search/style.css` to your theme, or choose from built-in UI styles like `style-full.css` or `style-topbar.css`.

= Is it mobile-friendly? =  
Yes. The UI is fully responsive and optimized for all devices.

= Is search result caching enabled? =  
Yes. Results are cached in `localStorage` for faster repeat access.

= Does it track or log searches? =  
Optionally, yes. You can enable Search Analytics in settings to log recent queries (no IPs or personal data are stored).

= What happens when no result is selected? =  
Pressing Enter will redirect to the default WordPress search page.

= Can I use this plugin with headless WordPress? =
Yes. All features are powered by the REST API with clean, documented endpoints — ideal for decoupled frontends or JavaScript-based rendering.

= Can I set a default slash command when the modal opens? =  
Yes. In plugin settings, you can choose a default command like `/recent`, `/related`, or use “Smart Detection” to auto-select based on current page.  
You can also choose `/popular` or `/read` — but these options only appear if their respective plugins are active.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress admin panel.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Go to **Settings → Init Live Search** to configure options.
4. The search modal can be triggered by default using:
   - Focusing any `<input name="s">` field
   - Pressing **Ctrl + /** (or **Cmd + /** on Mac)
   - Triple-clicking anywhere on the page (within 0.5s)
   - Clicking an element with a `data-ils` attribute
   - Visiting a URL with `#search` or `?modal=search&term=your+keyword`

== Changelog ==

= 1.6.5 – May 30, 2025 =
- Introduced intelligent 1-line excerpt for all search results  
  - Automatically extracts a short snippet containing the search keyword from excerpt or content  
  - Falls back to `get_the_excerpt()` if no relevant match is found  
  - Keyword is highlighted within the snippet using existing highlighter logic  
  - Improves result clarity, especially in `title_excerpt` and `title_content` search modes  
- Mobile-optimized: excerpt is displayed as a single line with ellipsis (`-webkit-line-clamp: 1`)  
- Fully integrated into existing REST API output with no performance impact  
- No settings required — feature is enabled by default for all search modes

= 1.6.4 – May 30, 2025 =
- Enhanced slash command integration and developer extensibility  
  - Extracted and finalized `init_plugin_suite_live_search_commands` filter logic into a stable API pattern  
  - Developers can now fully register custom commands and define their handling logic via JavaScript events  
  - Improved consistency and compatibility across different command sources  
- Added new custom event: `ils:result-clicked`  
  - Fires when a user clicks on a search result  
  - Provides full metadata in `detail`, including `id`, `url`, `title`, `type`, `category`, and `command`  
  - Enables better tracking, analytics, and advanced UX features
- Optimized voice search engine with `SpeechRecognition`  
  - Language detection based on `<html lang="">` with fallback mapping for `vi`, `en`, `fr`, `ja`  
  - Improved error handling and auto-stop behavior  
  - Added auto-restart option (`voice_auto_restart`) with timeout logic  
  - Fully responsive mic UI toggle via `.ils-voice-active`  
  - Voice input result is stored in sessionStorage and automatically triggers a search  

= 1.6.3 – May 29, 2025 =
- Added new slash commands: `/day`, `/week`, and `/month` to display the most viewed posts by day, week, or month  
  - Powered by Init View Count plugin (commands only available if plugin is active)  
  - Fully supports infinite scroll and REST API queries for high-traffic sites  
- Improved command detection logic  
  - Slash commands are now dynamically registered based on plugin availability and settings  
  - Cleaner UI behavior with consistent fallback if commands are unavailable  
- Added support for search history commands: `/history` to recall recent queries and `/history_clear` to wipe them  

= 1.6.2 – May 28, 2025 =
- New setting: **Default Slash Command on Modal Open**
  - Automatically preload slash commands like `/recent`, `/related`, `/popular`, or `/read` when modal opens
  - Includes “Smart Detection” mode to auto-select command based on current page context
  - Supports WooCommerce (`/product`), categories, tags, single post, and search results
- Slash command options are **plugin-aware**
  - `/popular` only available if Init View Count is active
  - `/read` only available if Init Reading Position is active
- New admin option: setting is only active if **slash commands are enabled**
- Improved validation and security
  - Only allow known valid default command values during settings save
  - Prevent command injection when slash is disabled

= 1.6.1 – May 28, 2025 =
- Introduced **Search Analytics** panel in admin settings (`Analytics` tab)
  - Log every keyword-based search query (term, result count, timestamp, source, user ID)
  - Store logs in rotating chunks using WordPress transients (lightweight, privacy-respecting)
  - Group similar queries and sort by frequency with one click (client-side JS powered)
  - Export logs to CSV directly from admin
  - Clear all logs with secure nonce validation
- Refined tracking logic
  - Only logs meaningful search terms (ignores empty or slash-only commands)
  - Excludes slash commands like `/recent` or `/fav` from analytics for relevance and clarity
- Optimized admin UX
  - Group toggle now sorts results by most frequent queries
  - Improved layout with responsive buttons and compact styling
  - Nonce protection for all form actions
- Internal improvements
  - Cleaned up tracking hook and filters for future extensibility
  - Renamed internal tracking file (`analytics.php` → `tracking.php`) to avoid conflicts with analytics view logic

= 1.6 – May 27, 2025 =
- Introduced optional frontend UI presets for enhanced search experience  
  - `style-full.css`: fullscreen modal overlay with centered input, ideal for immersive search UX  
  - `style-topbar.css`: fixed top bar search layout, similar to Spotlight or admin bar  
  - Choose preset style from the new “UI Style” setting in plugin options
- Theme override support  
  - Place `init-live-search/style.css` in your theme to override plugin styles completely  
  - Option to disable all default CSS and style from scratch
- Improved developer experience  
  - Automatically detects and loads custom `style.css` if placed in theme folder  
  - Preset styles are scoped and minimal to reduce conflicts
- Internal CSS loader and selector refactor to support future style expansions
- Updated plugin assets and settings screen to reflect new style options

= 1.5.4 – May 27, 2025 =
- Introduced semantic SEO-aware search layer with lightweight logic and zero AI dependencies  
  - Enable searching within SEO Titles and Meta Descriptions  
  - Supports Yoast SEO, Rank Math, AIOSEO, The SEO Framework, and SEOPress  
  - Optional setting in admin panel, with filter hook to customize meta keys
- New developer filter: `init_plugin_suite_live_search_seo_meta_keys`  
  - Customize which SEO meta fields are searched (e.g. `_yoast_wpseo_title`, `rank_math_description`, etc.)
- New developer filter: `init_plugin_suite_live_search_weights`  
  - Customize weighting when merging post IDs from multiple sources (title, SEO, tags) to control result order

= 1.5.3 – May 27, 2025 =
- Added support for searching specific ACF fields (Advanced Custom Fields)
  - Optional admin setting to define comma-separated field keys (e.g. `company_name, project_code`)
  - Only searches published posts and supports intelligent fallback logic
  - Built-in filter for full control: `init_plugin_suite_live_search_post_ids`
- Multilingual compatibility enhancements
  - Automatic language detection with Polylang and WPML
  - Added `init_plugin_suite_live_search_filter_lang` filter to restrict results by current language
  - Filterable language-aware REST queries for slash commands like `/recent`, `/tax`, etc.
- New developer filter: `init_plugin_suite_live_search_category_taxonomy`
  - Allows customizing the taxonomy used for displaying categories (e.g. use `product_cat` for WooCommerce)
- Improved ACF query performance and status filtering (joins `postmeta` with published posts only)
- Internal consistency tweaks and filter documentation improvements

= 1.5.2 – May 26, 2025 =
- Introduced new search mode: **Init Smart Tag-Aware Search**
  - Combines post title and post tag matching with intelligent fallback using keywords and bi-grams
  - Automatically splits terms into single words to match short tags like “php”, “css”, or “seo”
- Improved Quick Search tooltip behavior: now triggers on single-word selections (e.g. “JavaScript”, “PHP”)
- Minor UI polish and internal consistency improvements

= 1.5.1 – May 26, 2025 =
- Added WooCommerce product search with slash commands: `/product`, `/on-sale`, `/stock`, `/sku {code}`, `/price {min} {max}`
- Display prices, sale badges, stock status, and “Add to Cart” links (with out-of-stock detection)
- Introduced `/price` command with min/max filters powered by REST API
- Improved infinite scroll behavior for WooCommerce commands
- Added visual badges for “Sale” and “Sold out” states in results
- Slash command visibility now respects `product` post type setting in admin
- Enhanced keyboard navigation for command lists (scroll + max height)
- Optimized JS rendering logic for cart buttons and stock display
- Improved SKU matching accuracy and price filter precision

= 1.5 – May 25, 2025 =
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
- Added new modal triggers:
  - Keyboard shortcut: Ctrl + /
  - Triple-click anywhere on blank space
- Show post type label (e.g. Post, Page) next to each result
- Client-side category filter UI: auto-generates from results without extra API calls
- Improved input UX: search icon becomes clear button when input has value
- Codebase standardization:
  - All PHP filters and options now use `init_plugin_suite_live_search_*` prefix
  - REST API namespace renamed to `initlise/v1`
  - Global JS config moved to `window.InitPluginSuiteLiveSearch`

= 1.2 – May 20, 2025 =
- Added experimental voice input using the SpeechRecognition API (if supported by browser)
- New settings:
  - Enable/disable fallback logic (trimmed terms and bigrams)
  - Enable/disable plugin’s default CSS
  - Support for dark mode via `.dark` class or global JS config
- Added developer filters for advanced customization:
  - `init_plugin_suite_live_search_enable_fallback`
  - `init_plugin_suite_live_search_post_ids`
  - `init_plugin_suite_live_search_result_item`
  - `init_plugin_suite_live_search_results`

= 1.1 – May 18, 2025 =
- Enhanced fallback logic: trim terms and suggest using bigram strategy if no results found
- Modal remembers and pre-fills the last search term using `sessionStorage`
- Enforced character limit: input capped at 100 characters
- New options added:
  - Enable result caching via `localStorage`
  - Auto-append default UTM parameters to result URLs
  - Theme support: switch between light, dark, or auto mode via class or JS config

= 1.0 – May 17, 2025 =
- First stable release of Init Live Search
- Modal-based search powered entirely by the WordPress REST API
- Fully keyboard accessible: Arrow keys, Enter, Escape
- Manual keyword suggestions with optional fallback
- Lightweight: no external assets, no jQuery — all icons and fallbacks are inlined SVGs
- Built with Vanilla JavaScript, optimized for performance and accessibility

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
