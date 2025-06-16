=== Init Live Search – Smart, Slash Commands, REST API ===
Contributors: brokensmile.2103  
Tags: live search, instant search, woocommerce, rest api, slash command
Requires at least: 5.2  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.7.2  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Fast, modern live search powered by REST API — with slash commands, SEO-aware, ACF, WooCommerce, and custom UI presets.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. Init Live Search is a modern, lightweight, and fully accessible live search solution for WordPress — now with tag-aware matching, SEO metadata support, ACF integration, WooCommerce product filters, and customizable UI presets.

It replaces the default `<input name="s">` with a clean, intuitive search modal powered entirely by the WordPress REST API. Everything loads in real-time — with zero disruption to browsing flow.

Perfect for content-heavy blogs, WooCommerce stores, or even headless sites. Every interaction is fast, fluid, and designed to work across devices.

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-live-search](https://github.com/brokensmile2103/init-live-search)

== What's New in Version 1.7.x ==

- **Cross-site Search**: merge results from multiple Init Live Search-enabled sites  
  - Just enter `Site Name|https://example.com/` — no auth or CORS needed  
  - Automatically tags results by site (e.g. “Init Docs”)  
  - Now disables single-word fallback on both local and remote sites for cleaner relevance  
- **WooCommerce Slash Commands**: major expansion for store owners  
  - New: `/brand`, `/attribute`, `/variation`, and `/coupon`  
  - `/coupon` shows active coupons with usage and expiration info  
  - Improved `/price` with optional `sort` / `rsort`  
  - Fully supports custom taxonomies and `pa_...` attributes  
- **New Shortcode**: `[init_live_search]`  
  - Insert a search input or icon anywhere  
  - Supports dark mode and custom classes

== Features ==

Packed with everything a modern live search needs — and more:

- Live search via REST API (no admin-ajax, no jQuery)
- Smart tag-aware search mode (title + tag match)
- SEO metadata support: Yoast, Rank Math, AIOSEO, SEOPress, TSF
- ACF field matching and customizable filters
- Synonym expansion with fallback scoring logic
- Slash commands: `/recent`, `/popular`, `/tag`, `/id`, `/sku`, `/price`, `/coupon`, etc.
- WooCommerce support: search by product, SKU, brand, attribute, variation, coupon
- Clean modal UI with keyboard navigation (`↑ ↓ Enter Esc`)
- Optional voice input (SpeechRecognition)
- Tooltip Quick Search (select text to trigger)
- Favorites system via heart icon or `/fav` commands
- Infinite scroll and smart category filter (no extra API)
- Deep linking: `?modal=search&term=...`
- New `[init_live_search]` shortcode: insert input or icon anywhere
- UI presets: default, fullscreen, topbar — with full CSS override
- Local caching + analytics (CSV export, no personal data)
- Developer-ready: custom slash commands, REST filters, JS hooks

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
- Set default slash command to run on modal open (only if slash is enabled)   
- Set debounce time and max results  
- Choose search mode (title-only, tag-aware, full content)  
- Define custom ACF fields to include in search (optional)  
- Enable Search in SEO Metadata (Yoast, Rank Math, etc.)  
- Toggle excerpt display below each result (1-line contextual snippet)  
- Toggle fallback logic (bigram/trim)  
- Enable synonym expansion and manage synonym mappings (JSON editor)  
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

== Developer Reference: Shortcodes, Filters, and Hooks ==

== Shortcodes ==

**`[init_live_search]`**
Display a search icon or input anywhere that opens the Init Live Search modal.

**Attributes:**
- `type`: `icon` (default) or `input` – choose between a clickable icon or a search box  
- `placeholder`: (optional) text inside the input if `type="input"`  
- `label`: (optional) adds a label next to the icon if `type="icon"`  
- `class`: (optional) add custom classes like `dark`, `my-style`, etc.  
- `stroke_width`: (optional) set the stroke width for the search icon (default: `1`)  
- `radius`: (optional) override the border radius of the input form (default: `9999px` from CSS; only applied if value differs)

== Filters for Developers ==

This plugin includes multiple filters to help developers customize behavior and output at various stages of the search flow.

**`init_plugin_suite_live_search_enable_fallback`**  
Enable or disable fallback logic (trimming or bigram) when few results are found.  
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

**`init_plugin_suite_live_search_synonym_map`**  
Inject or override the list of keyword → synonym mappings used in synonym expansion logic.  
**Applies to:** search  
**Params:** `array $map`

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
Customize the list of single-word stopwords removed before generating bigram.  
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
    - `price_order`: `sort` or `rsort` to sort by price  
    - `brand`, `attribute`, `variation`, `value`: For taxonomy filtering  
    - `page`: For pagination  
  Returns basic product info (title, URL, price, category, thumbnail), sale and stock status, and `add_to_cart_url`. Caching is applied per query.

- `/coupon`  
  Fetch valid WooCommerce coupons (non-expired and under usage limit).  
  Returns: code, description, remaining uses, expiration date, copy-to-clipboard support.

== Screenshots ==

1. Search Triggers: input focus, Ctrl + /, triple click, `data-ils` attribute  
2. Search Behavior: post types, slash commands, fallback, SEO fields  
3. Performance & UX: debounce, max results, caching, analytics, voice input  
4. Styling & Suggestions: UI style, custom CSS, suggestions, UTM tracking  
5. Synonym Configuration: define and auto-expand keyword mappings  
6. Search Analytics: view logs, result count, export CSV  
7. Modal UI: clean interface with suggestions and instant results  
8. Results View: filter pills, post types, contextual excerpts  
9. Dark Mode: automatic or manual toggle for night-friendly UI  
10. Slash Command Helper: real-time dropdown with command list  
11. WooCommerce Search: product results with price, stock, sale badge  
12. Fullscreen Style: overlay modal using `style-full.css` preset  
13. Topbar Style: fixed top bar layout using `style-topbar.css` preset  

== Frequently Asked Questions ==

= Does this plugin use jQuery? =  
No. It's built entirely with modern Vanilla JavaScript — no jQuery, no external dependencies.

= Can I insert the search box anywhere on the page? =  
Yes. Use the `[init_live_search]` shortcode to insert a search input or icon anywhere. You can also add custom classes or enable dark mode.

= How is the search triggered? =  
By default, it binds to any `<input name="s">`. You can also trigger it via:  
- Ctrl + / (or Cmd + /)  
- Triple-click on blank space  
- Text selection tooltip  
- `?modal=search` in the URL  
- Any element with `data-ils` attribute

= Can I prefill the modal from a link? =  
Yes. Append `?modal=search&term=your+keyword` or `#search` to any URL to prefill the modal and trigger it.

= Is voice search supported? =  
Yes. It uses the browser’s SpeechRecognition API with auto-stop, language detection, and error handling.

= What are slash commands? =  
Slash commands are typed commands starting with `/`, such as:  
- `/recent` — show latest posts  
- `/tag seo` — filter by tag  
- `/category news` — filter by category  
- `/id 123` — fetch a post by ID  
- `/fav`, `/fav_clear` — manage favorites  
- `/random` — show a random post  
- `/history`, `/history_clear` — manage recent search history  

**If WooCommerce is active:**  
- `/product`, `/sku`, `/price`, `/stock`, `/on-sale`, `/coupon`  

**If other Init plugins are active:**  
- `/popular`, `/trending`, `/day`, `/week`, `/month` — via **Init View Count**  
- `/read` — via **Init Reading Position**

You can disable slash commands entirely in the plugin settings. Developers can register custom ones using the `init_plugin_suite_live_search_commands` filter.

= What is the Quick Search tooltip? =  
When users select 1–8 words, a floating tooltip appears to trigger an instant search. You can configure or disable it in settings.

= Does it support synonyms or alternate keywords? =  
Yes. You can define custom keyword → synonym mappings via the **Synonyms** tab in settings.  
When enabled, the plugin will auto-expand search terms using these synonyms if few results are found.

= Can it search in SEO fields and tags? =  
Yes. The plugin supports a special “Smart Tag-Aware” mode that matches both post titles and tags.  
It can also search inside SEO Titles and Meta Descriptions from plugins like Yoast SEO, Rank Math, AIOSEO, The SEO Framework, and SEOPress.

= Does it support WooCommerce? =  
Yes. You can search for products by:  
- Keyword  
- SKU  
- Price range (`/price`)  
- Stock status (`/stock`)  
- Sale status (`/on-sale`)  
- Brand, attribute, variation, and coupons (`/coupon`)

Results include title, price, stock status, and Add to Cart links.

= Does it support excerpts in search results? =  
Yes. It generates a 1-line contextual excerpt with the keyword highlighted for better scan-ability.

= Can I override or disable the plugin’s CSS? =  
Yes. You can:  
- Drop `init-live-search/style.css` in your theme  
- Choose a built-in preset (`style-full.css`, `style-topbar.css`)  
- Or disable all built-in CSS and style it from scratch

= Is it mobile-friendly? =  
Yes. The modal is responsive with mobile optimizations like excerpt clamping and floating mic button.

= Is result caching supported? =  
Yes. It uses `localStorage` to cache search results and reduce repeat queries.

= Does the plugin track user data? =  
Only if **Search Analytics** is enabled. It logs:  
- Search term  
- Timestamp  
- Result count  
No personal information (IP, user agent, etc.) is stored.

= What happens if I press Enter without selecting a result? =  
The plugin will redirect to WordPress’s default search results page.

= Can I use this in a headless setup? =  
Yes. All features are powered by REST API (`initlise/v1`) — ideal for decoupled frontends.

= Can I preload a default slash command when the modal opens? =  
Yes. In settings, you can define a default command like `/recent`, `/read`, or `/related`. There's also a "smart detection" mode based on page context.

= Does it support multiple languages? =  
Yes. It auto-detects the active language when Polylang or WPML is installed. You can also filter results via `init_plugin_suite_live_search_filter_lang`.

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

= 1.7.2 – June 16, 2025 =
- Added Shortcode Builder support  
  - Visual builder now available for `[init_live_search]` shortcode  
  - Accessible via admin UI with icon, input, label, and style configuration  
  - Supports dynamic shortcode preview, one-click copy, and instant insert  
  - Fully localized with customizable labels via `wp_localize_script()`  

= 1.7.1 – June 14, 2025 =
- Massive WooCommerce slash command expansion  
  - Added `/brand`, `/attribute`, `/variation`, and `/coupon` support  
  - `/coupon` returns active, usable coupons with usage info and expiration  
  - Fully compatible with custom taxonomies and `pa_...` attributes  
- Improved `/price` slash command  
  - Now supports optional `sort` / `rsort` keywords  
  - Full backward compatibility  
- Cross-site search refinement  
  - Automatically disables single-word fallback on both source and remote sites for cleaner results  
- New `[init_live_search]` shortcode  
  - Allows inserting a search icon or input anywhere, with custom classes and optional dark mode

= 1.7.0 – June 11, 2025 =
- Added Cross-site Search  
  - New setting: enter multiple domains (`Site Name|https://example.com/`)  
  - Fetches & merges results from other Init Live Search sites  
  - Results are labeled (e.g. “Init Docs”) for clarity  
  - No auth or CORS setup needed – just works  

= 1.6.9 – June 8, 2025 =
- Added Synonym Expansion system for smarter search matching  
  - New “Synonyms” tab in settings with live JSON editor and helper UI  
  - Define site-specific vocabulary mappings like `{"reaction": ["tương tác", "phản hồi"]}`  
  - Auto-inject or update keyword-synonym pairs via UI button or Enter key  
  - Full JSON validation with inline error messages and form guard
- Enhanced search logic with intelligent synonym fallback  
  - When no or few results are found, synonyms are auto-expanded and re-queried  
  - Synonym results are scored lower than direct matches but included for better coverage  
  - Works seamlessly with all search modes (title, tag, excerpt, ACF, SEO, etc.)
- New toggle setting: "Enable Synonym Expansion?"  
  - Allows enabling/disabling the synonym logic without affecting mappings  
  - Disabled by default for performance-conscious setups

= 1.6.8 – June 7, 2025 =
- Added support for multi-term filtering in `/tag` and `/category` slash commands (AND logic)
- Added `/trending` slash command using real-time trending data from Init View Count
- Excluded "Media" (`attachment`) from selectable post types in settings for cleaner configuration
- Removed tracking of `user_id` and `source` for simpler and privacy-friendly analytics
- Optimized internal query limits by adapting to the configured result count (no longer hardcoded to 200)

= 1.6.7 – June 4, 2025 =
- Internal refinements for long-term maintainability and performance  
  - Unified duplicate language detection logic (`detect_language` → `detect_lang`)  
  - Cleaned up legacy utility functions and applied clearer naming conventions  
  - Standardized logic for excerpt highlighting, thumbnail fallback, and WooCommerce integration  
- Enhanced admin keyword generator (modal default input)  
  - Improved bi-gram extraction from post titles  
  - Added locale-aware stop word filtering (Vietnamese, English)  
  - Now generates up to 7 randomized high-signal keywords for better UX  
- Stabilized and extensible codebase  
  - All core modules are modular, reusable, and optimized for extensibility  
  - Future development will focus on expanding slash command support and developer experience  
- Improved JavaScript hook support for slash commands  
  - Developers can now reliably use `ils:search-started` to render custom commands from themes or plugins

= 1.6.6 – May 31, 2025 =
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

= 1.6.5 – May 30, 2025 =
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

= 1.6.4 – May 30, 2025 =
- Enhanced slash command system and developer API  
  - Finalized `init_plugin_suite_live_search_commands` filter logic  
  - Register custom commands and handle them via JS events  
- Added `ils:result-clicked` custom event  
  - Fires on result click with full metadata payload (`id`, `url`, `type`, etc.)  
- Optimized voice search engine  
  - Language detection from `<html lang>` with fallback map  
  - Improved error handling and auto-stop logic  
  - Mic UI toggle and sessionStorage keyword injection  

= 1.6.3 – May 29, 2025 =
- New slash commands: `/day`, `/week`, `/month` (requires Init View Count plugin)  
- Fully supports infinite scroll and REST API for high-traffic sites  
- Improved dynamic command registration  
- Added search history commands: `/history`, `/history_clear`  

= 1.6.2 – May 28, 2025 =
- New feature: Default Slash Command on Modal Open  
  - Preload `/recent`, `/related`, `/popular`, etc. based on page context  
  - Includes “Smart Detection” mode  
- Plugin-aware slash command detection  
  - Only shows `/popular`, `/read` if supporting plugins are active  
- Improved settings validation and injection prevention  

= 1.6.1 – May 28, 2025 =
- Added Search Analytics panel (Analytics tab in settings)  
  - Log keyword search queries (term, result count, time, source, user ID)  
  - Stored via transient-based chunk system  
  - Exportable to CSV  
- Improved tracking logic  
  - Ignores empty and slash-only queries  
  - Excludes structural commands like `/recent`, `/fav`  
- Renamed tracking file: `analytics.php` → `tracking.php`  
- Better nonce protection and UX polish  

= 1.6.0 – May 27, 2025 =
- Added frontend UI presets:  
  - `style-full.css`: fullscreen overlay  
  - `style-topbar.css`: fixed top bar (like Spotlight)  
- Theme override support: use `init-live-search/style.css` in theme  
- Option to disable plugin CSS entirely  
- Internal CSS loader refactor  

= 1.5.4 – May 27, 2025 =
- Added SEO metadata search support (no AI)  
  - Search within SEO Title and Meta Description  
  - Compatible with Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework  
- New filters for developer control:  
  - `init_plugin_suite_live_search_seo_meta_keys`  
  - `init_plugin_suite_live_search_weights`  

= 1.5.3 – May 27, 2025 =
- ACF search field support (define keys in admin)  
- Multilingual enhancements  
  - Supports Polylang, WPML  
  - Language-aware filters and queries  
- New developer filters:  
  - `init_plugin_suite_live_search_filter_lang`  
  - `init_plugin_suite_live_search_category_taxonomy`  
- Performance: improved ACF join + status filtering  

= 1.5.2 – May 26, 2025 =
- New search mode: Init Smart Tag-Aware Search  
  - Combines title and tag matches  
  - Auto bi-gram fallback for short terms  
- Tooltip Quick Search now works on single-word selections  

= 1.5.1 – May 26, 2025 =
- Added WooCommerce slash commands:  
  - `/product`, `/on-sale`, `/stock`, `/sku`, `/price`  
- Display product data: price, sale badge, stock, add-to-cart  
- Visual indicators: “Sale”, “Sold Out”  
- Infinite scroll and smart SKU/price filtering  
- Enhanced keyboard nav in result list  

= 1.5.0 – May 25, 2025 =
- Quick Search tooltip (2–8 words selection)  
- Support for `data-ils` attribute to trigger modal  
- Favorite management via `/fav`, `/fav_clear` commands  
- Toggle favorite from results list  
- Unified modal trigger logic  
- Optimized state handling  

= 1.4.3 – May 24, 2025 =
- Lazy modal init (only creates DOM on trigger)  
- New JS events:  
  - `ils:modal-opened`, `ils:modal-closed`, `ils:search-started`, `ils:results-loaded`  
- Improved keyboard nav and accessibility  
- Scroll logic optimized for large result sets  

= 1.4.2 – May 24, 2025 =
- Enhanced slash command dropdown (`/re...`)  
- Added `?modal=search&term=...` URL trigger  
- Option to disable all slash commands  
- UI/keyboard refinements  

= 1.4.1 – May 23, 2025 =
- Added advanced slash commands:  
  - `/related`, `/read`, `/random`, `/categories`, `/tags`, `/help`, `/clear`, `/reset`  
- `/read` integration with Init Reading Position  
- Internal command result caching with `localStorage`  
- Fully internationalized commands  
- Refactored JS modules  

= 1.4.0 – May 23, 2025 =
- Introduced full slash command system  
  - `/recent`, `/popular`, `/tag`, `/category`, `/date`, `/id`  
  - Smart `/date` parsing  
  - REST-powered, cached results  
- Modal trigger options: Ctrl + /, triple-click, focus  
- Unified query parser  

= 1.3.0 – May 22, 2025 =
- Added modal triggers:  
  - Ctrl + /, triple-click  
- Client-side category filter  
- Post type badge in results  
- UI: input clear button, dropdown polish  
- Refactored codebase, namespacing  

= 1.2.0 – May 20, 2025 =
- Voice input (SpeechRecognition API)  
- New settings: fallback toggle, CSS toggle, dark mode  
- Developer filters added for full control  

= 1.1.0 – May 18, 2025 =
- Trimmed + bigram fallback logic  
- Remembers last search via `sessionStorage`  
- Enforces 100-char input limit  
- Caching, UTM, dark/light theme options  

= 1.0.0 – May 17, 2025 =
- First stable release  
- Modal-based search via REST API  
- Fully keyboard accessible  
- Inline fallback + suggestions  
- Support for manual and auto-generated keyword suggestions  
- Lightweight Vanilla JS, no jQuery  

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
