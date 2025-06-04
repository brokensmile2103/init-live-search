=== Init Live Search – Smart, Slash Commands, REST API ===
Contributors: brokensmile.2103  
Tags: live search, instant search, woocommerce, rest api, slash command
Requires at least: 5.2  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.6.7  
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

- **Search Analytics (New Tab)**: track search queries, view counts, export CSV, and group results by frequency  
- **Contextual 1-Line Excerpts**: auto-highlight and display a short snippet from content or excerpt in all search results  
- **Weighted Search Ranking**: smarter scoring system prioritizes title > excerpt > content in relevance-based modes  
- **Single Word Fallback**: automatically splits search terms into individual words if no results found  
- **SEO Metadata Matching**: checks SEO Titles and Meta Descriptions for matches during fallback  
- **New Slash Commands**: `/day`, `/week`, and `/month` show most viewed posts by timeframe (requires Init View Count)  
- **Search History Commands**: recall recent queries with `/history` and clear them with `/history_clear`  
- **Improved Voice Search**: optimized mic control, language detection, and error handling  
- **Default Slash Command**: preload a command like `/recent`, `/related`, `/popular`, or `/read` when modal opens  
- **New Filter: `init_plugin_suite_live_search_commands`**: register custom slash commands from theme or plugin, define your own REST endpoint if needed, and handle results via `ils:*` JavaScript events  
- **New Event: `ils:result-clicked`**: track clicks on search results with full metadata  
- **New UI Style Presets**: choose from fullscreen (`style-full.css`) or top bar (`style-topbar.css`) layouts  
- **UI Style Picker**: select a style directly from the admin settings  
- **Theme CSS Override**: place `init-live-search/style.css` in your theme to customize styles  
- **Disable Built-in CSS**: turn off all plugin styles and build your own from scratch  
- **Scoped CSS Loader**: clean separation of core, presets, and theme overrides  
- **Developer-Friendly**: styles are minimal and safe to integrate with any theme or builder  

== Features ==

Packed with everything a modern live search needs — and more:

- Live search powered by WordPress REST API (no admin-ajax)
- Smart tag-aware search mode: match keywords in both titles and post tags
- Search in SEO Metadata: match keywords in SEO Titles and Meta Descriptions from popular SEO plugins (Yoast, Rank Math, AIOSEO, TSF, SEOPress)
- Clean modal interface that works with any theme — no template override required
- Fully keyboard accessible (Arrow keys, Enter, Escape)
- Slash command system (`/recent`, `/popular`, `/tag`, `/id`, `/fav`, etc.)
- WooCommerce support: search by product, sale status, stock, SKU, or price range
- Contextual excerpts: auto-generate 1-line snippet containing the keyword, improving scan-ability
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
- Toggle excerpt display below each result (1-line contextual snippet)  
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
No. It’s built entirely with modern Vanilla JavaScript — no jQuery, no dependencies.

= How is search triggered? =  
Search is auto-bound to `<input name="s">`, but you can also trigger it via:  
- Ctrl + / (or Cmd + /)  
- Triple-click on blank space  
- Text selection tooltip  
- `?modal=search` in the URL  
- Any element with `data-ils` attribute

= Can I prefill the modal from a link? =  
Yes. Append `?modal=search&term=your+keyword` or `#search` to any URL.

= Is voice search supported? =  
Yes. It uses the browser’s SpeechRecognition API, with auto-stop, language detection, and optional auto-restart.

= What are slash commands? =  
They’re typed commands starting with `/`, like:

**Built-in commands (no extra plugins needed):**  
- `/recent` — show latest posts  
- `/tag seo` — filter by tag  
- `/category news` — filter by category  
- `/id 123` — fetch a post by ID  
- `/fav`, `/fav_clear` — manage favorite posts  
- `/random` — get a random post  
- `/product`, `/sku`, `/price`, `/stock`, `/on-sale` — WooCommerce product filters (if WooCommerce is active)  
- `/history`, `/history_clear` — manage recent search history (stored in browser)

**Plugin-powered commands (require other Init plugins):**  
- `/popular` — requires **Init View Count**  
- `/day`, `/week`, `/month` — requires **Init View Count**  
- `/read` — requires **Init Reading Position**

You can disable the entire slash command system in the plugin settings.  
Developers can register custom commands via the `init_plugin_suite_live_search_commands` filter.

= Can I disable slash commands? =  
Yes. You can turn off the entire system via plugin settings.

= What is the Quick Search tooltip? =  
When users select 1–8 words, a tooltip appears to let them search instantly.  
Fully configurable or can be disabled.

= What is Smart Tag-Aware Search? =  
A special mode that searches both post titles and tags, and uses fallback logic like trimmed terms and bigrams to increase result coverage.

= What is “Search in SEO Metadata”? =  
This allows searching SEO Titles and Meta Descriptions from plugins like:  
- Yoast SEO  
- Rank Math  
- AIOSEO  
- The SEO Framework  
- SEOPress

Fully customizable via filters.

= Does it support WooCommerce? =  
Yes. You can search products by:  
- Keyword  
- SKU  
- Price range (`/price`)  
- Stock status (`/stock`)  
- On sale (`/on-sale`)  
Results display price, sale badge, stock state, and Add to Cart links.

= Is excerpt supported in results? =  
Yes. It auto-extracts a 1-line contextual snippet with the keyword highlighted — improves scan-ability.

= Can I override the plugin’s CSS? =  
Yes:  
- Drop `init-live-search/style.css` in your theme  
- Or select built-in presets like `style-full.css` or `style-topbar.css`  
- Or disable all styles and write from scratch

= Is it mobile-friendly? =  
Yes. The modal and styles are responsive, with mobile-specific UI optimizations (excerpt clamping, floating mic button, etc.)

= Is result caching enabled? =  
Yes. `localStorage` is used to cache queries and results for faster subsequent access.

= Does it log or track user data? =  
Only if you enable **Search Analytics**.  
Logs:  
- Search term  
- Timestamp  
- Result count  
- Source (user, guest, trigger)  
No personal info (IP, user agent, etc.) is stored.

= What happens when no result is selected and I press Enter? =  
The plugin falls back to WordPress’s native search page.

= Can I use this with a headless setup? =  
Absolutely. All functionality is powered via REST API under the `initlise/v1` namespace — perfect for decoupled frontends.

= Can I set a default slash command when the modal opens? =  
Yes. From the settings panel, you can preload `/recent`, `/read`, `/related`, etc.  
Smart detection mode is also available, selecting a command based on page context.

= Does the plugin support multiple languages? =  
Yes. It auto-detects current language when Polylang or WPML is active.  
A filter `init_plugin_suite_live_search_filter_lang` is provided for custom logic.

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

### = 1.6.7 – June 4, 2025 =
- Internal refinements for long-term maintainability and performance  
  - Removed duplicate language detection logic (`detect_language` → unified `detect_lang`)  
  - Cleaned up legacy utility functions and renamed for clarity  
  - Standardized excerpt highlighting, thumbnail fallback, and WooCommerce integration logic  
- Improved admin keyword generator for modal default input  
  - Enhanced bi-gram extraction from post titles  
  - Now supports locale-aware stop word filtering (Vietnamese, English)  
  - Outputs up to 7 randomized high-signal keywords for better UX
- Codebase deemed stable and feature-complete  
  - All core features are modular, reusable, and optimized for extensibility  
  - No major architectural changes planned beyond slash command expansions  
- Improved JavaScript hook support for custom slash commands  
  - Developers can now reliably use `ils:search-started` to render their own commands from themes or plugins

### = 1.6.6 – May 31, 2025 =
- Major fallback upgrade: added intelligent single-word fallback logic  
  - Automatically breaks long queries into single words if no results found  
  - Matches each word as full term only (e.g. `sea` won't match `search`)  
  - Each match is scored and weighted for relevance  
- Improved result ranking with weight-based merge  
  - Dynamically ranks fallback results based on number of keyword hits  
  - Prioritizes stronger matches while still showing broader coverage  
- SEO-aware fallback reuse  
  - Reuses existing SEO meta fields (title + description) in fallback queries  
  - Controlled by setting and `init_plugin_suite_live_search_seo_meta_keys` filter  
- Full refactor: fallback logic moved to `search-core.php`  
  - Improves code maintainability and separation of concerns  
  - Paves the way for future optimizations like partial streaming  

### = 1.6.5 – May 30, 2025 =
- Introduced intelligent 1-line excerpt for all search results  
  - Automatically extracts a short snippet containing the search keyword from excerpt or content  
  - Falls back to `get_the_excerpt()` if no relevant match is found  
  - Keyword is highlighted within the snippet  
- Mobile-optimized: excerpt is displayed as a single line with ellipsis (`-webkit-line-clamp: 1`)  
- Fully integrated into existing REST API output  
- Improved relevance ranking for `title_excerpt` and `title_content` modes  
  - Weighted scoring: `title > excerpt > content`  
- Refactored result handler logic into modular subroutines  
  - Improves readability, performance, and extensibility  

### = 1.6.4 – May 30, 2025 =
- Enhanced slash command system and developer API  
  - Finalized `init_plugin_suite_live_search_commands` filter logic  
  - Register custom commands and handle them via JS events  
- Added `ils:result-clicked` custom event  
  - Fires on result click with full metadata payload (`id`, `url`, `type`, etc.)  
- Optimized voice search engine  
  - Language detection from `<html lang>` with fallback map  
  - Improved error handling and auto-stop logic  
  - Mic UI toggle and sessionStorage keyword injection  

### = 1.6.3 – May 29, 2025 =
- New slash commands: `/day`, `/week`, `/month` (requires Init View Count plugin)  
- Fully supports infinite scroll and REST API for high-traffic sites  
- Improved dynamic command registration  
- Added search history commands: `/history`, `/history_clear`  

### = 1.6.2 – May 28, 2025 =
- New feature: Default Slash Command on Modal Open  
  - Preload `/recent`, `/related`, `/popular`, etc. based on page context  
  - Includes “Smart Detection” mode  
- Plugin-aware slash command detection  
  - Only shows `/popular`, `/read` if supporting plugins are active  
- Improved settings validation and injection prevention  

### = 1.6.1 – May 28, 2025 =
- Added Search Analytics panel (Analytics tab in settings)  
  - Log keyword search queries (term, result count, time, source, user ID)  
  - Stored via transient-based chunk system  
  - Exportable to CSV  
- Improved tracking logic  
  - Ignores empty and slash-only queries  
  - Excludes structural commands like `/recent`, `/fav`  
- Renamed tracking file: `analytics.php` → `tracking.php`  
- Better nonce protection and UX polish  

### = 1.6 – May 27, 2025 =
- Added frontend UI presets:  
  - `style-full.css`: fullscreen overlay  
  - `style-topbar.css`: fixed top bar (like Spotlight)  
- Theme override support: use `init-live-search/style.css` in theme  
- Option to disable plugin CSS entirely  
- Internal CSS loader refactor  

### = 1.5.4 – May 27, 2025 =
- Added SEO metadata search support (no AI)  
  - Search within SEO Title and Meta Description  
  - Compatible with Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework  
- New filters for developer control:  
  - `init_plugin_suite_live_search_seo_meta_keys`  
  - `init_plugin_suite_live_search_weights`  

### = 1.5.3 – May 27, 2025 =
- ACF search field support (define keys in admin)  
- Multilingual enhancements  
  - Supports Polylang, WPML  
  - Language-aware filters and queries  
- New developer filters:  
  - `init_plugin_suite_live_search_filter_lang`  
  - `init_plugin_suite_live_search_category_taxonomy`  
- Performance: improved ACF join + status filtering  

### = 1.5.2 – May 26, 2025 =
- New search mode: Init Smart Tag-Aware Search  
  - Combines title and tag matches  
  - Auto bi-gram fallback for short terms  
- Tooltip Quick Search now works on single-word selections  

### = 1.5.1 – May 26, 2025 =
- Added WooCommerce slash commands:  
  - `/product`, `/on-sale`, `/stock`, `/sku`, `/price`  
- Display product data: price, sale badge, stock, add-to-cart  
- Visual indicators: “Sale”, “Sold Out”  
- Infinite scroll and smart SKU/price filtering  
- Enhanced keyboard nav in result list  

### = 1.5 – May 25, 2025 =
- Quick Search tooltip (2–8 words selection)  
- Support for `data-ils` attribute to trigger modal  
- Favorite management via `/fav`, `/fav_clear` commands  
- Toggle favorite from results list  
- Unified modal trigger logic  
- Optimized state handling  

### = 1.4.3 – May 24, 2025 =
- Lazy modal init (only creates DOM on trigger)  
- New JS events:  
  - `ils:modal-opened`, `ils:modal-closed`, `ils:search-started`, `ils:results-loaded`  
- Improved keyboard nav and accessibility  
- Scroll logic optimized for large result sets  

### = 1.4.2 – May 24, 2025 =
- Enhanced slash command dropdown (`/re...`)  
- Added `?modal=search&term=...` URL trigger  
- Option to disable all slash commands  
- UI/keyboard refinements  

### = 1.4.1 – May 23, 2025 =
- Added advanced slash commands:  
  - `/related`, `/read`, `/random`, `/categories`, `/tags`, `/help`, `/clear`, `/reset`  
- `/read` integration with Init Reading Position  
- Internal command result caching with `localStorage`  
- Fully internationalized commands  
- Refactored JS modules  

### = 1.4 – May 23, 2025 =
- Introduced full slash command system  
  - `/recent`, `/popular`, `/tag`, `/category`, `/date`, `/id`  
  - Smart `/date` parsing  
  - REST-powered, cached results  
- Modal trigger options: Ctrl + /, triple-click, focus  
- Unified query parser  

### = 1.3 – May 22, 2025 =
- Added modal triggers:  
  - Ctrl + /, triple-click  
- Client-side category filter  
- Post type badge in results  
- UI: input clear button, dropdown polish  
- Refactored codebase, namespacing  

### = 1.2 – May 20, 2025 =
- Voice input (SpeechRecognition API)  
- New settings: fallback toggle, CSS toggle, dark mode  
- Developer filters added for full control  

### = 1.1 – May 18, 2025 =
- Trimmed + bigram fallback logic  
- Remembers last search via `sessionStorage`  
- Enforces 100-char input limit  
- Caching, UTM, dark/light theme options  

### = 1.0 – May 17, 2025 =
- First stable release  
- Modal-based search via REST API  
- Fully keyboard accessible  
- Inline fallback + suggestions  
- Support for manual and auto-generated keyword suggestions  
- Lightweight Vanilla JS, no jQuery  

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
