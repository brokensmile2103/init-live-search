=== Init Live Search ===
Contributors: brokensmile.2103  
Tags: live search, ajax search, woocommerce, rest api, slash command  
Requires at least: 5.2  
Tested up to: 6.8  
Requires PHP: 7.4  
Stable tag: 1.5.3  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Blazing-fast live search modal for WordPress. Powered by REST API and Vanilla JS. Supports voice, keyboard, slash commands, and caching.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. **Init Live Search** is a lightweight, modern, and fully accessible live search solution for WordPress. Now with semantic keyword matching and smart tag awareness.

It replaces the default `<input name="s">` with a clean, intuitive modal that retrieves results instantly via the WordPress REST API. Everything happens in real-time — without disrupting the browsing flow.

Designed for both blogs and headless sites, it includes optional features like voice input, dark mode, keyword suggestions, and advanced developer hooks for total flexibility.

The plugin supports:
- Keyboard navigation (↑ ↓ ← → Enter Esc)
- Slash commands (e.g. `/recent`, `/id`, `/tag`)
- Voice input (if supported)
- Dark mode (`.dark` class or global config)
- Smart fallback and result caching

This plugin is part of the [Init Plugin Suite](https://inithtml.com/init-plugin-suite-bo-plugin-wordpress-toi-gian-manh-me-mien-phi/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-live-search](https://github.com/brokensmile2103/init-live-search)

== What's New in Version 1.5.x ==

- New Search Mode: **Init Smart Tag-Aware Search** — combine title and post_tag matching with intelligent fallback using keywords and bi-grams
- Quick Search tooltip: select 1–8 words on any page to trigger instant search
- `data-ils` attribute: open modal and prefill slash commands from any HTML element
- New slash commands: `/fav` and `/fav_clear` to manage favorite posts via `localStorage`
- Favorite system: add or remove posts directly from result list using a star icon
- Improved command handling: better stateful rendering and reset behavior
- `hiddenUrl` logic refined to reset when no result is selected
- Consistent modal trigger behavior across all entry points
- UX boost on mobile: auto-focus and select search input with keyboard support
- Codebase optimized for future extensibility with minimal API changes
- WooCommerce integration: support for `/product`, `/on-sale`, `/stock`, `/sku`, and `/price {min} {max}` slash commands
- Display product prices, sale badges, stock status, and "Add to Cart" button in results
- Smart badge UI: auto-highlight “Sale” and “Sold out” states
- Added infinite scroll support for WooCommerce-based slash commands
- Unified command architecture with pagination support across all slash commands
- Enhanced keyboard navigation and auto-scrolling for commands and suggestions
- **ACF field search**: search specific ACF fields via settings (`field_a, field_b`), with filter hook for full control
- **Multilingual support**: built-in language detection + filters for WPML and Polylang compatibility

== Features ==

Everything you expect from a modern live search — and more:

- Live search powered by WordPress REST API (no admin-ajax)
- Smart tag-aware search mode: match keywords in both titles and post tags
- Clean modal interface that works with any theme
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
- Developer-friendly with filters and REST API endpoints
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
- Set debounce time, max results, and search mode  
- Toggle fallback logic (bigrams/trim)  
- Set max words for tooltip search  
- Enable voice input (SpeechRecognition API)  
- Enable result caching (localStorage)  
- Enable/disable default CSS  
- Define or auto-generate keyword suggestions  
- Add default UTM parameter to result links  

== Keyboard Navigation ==

- Arrow Up / Down — navigate between results
- Arrow Right — add selected result to favorites (if not already added)
- Arrow Left — remove selected result from favorites
- Enter — open selected result or submit
- Escape — close modal and reset state
- Slash (/) — start a command instantly (e.g., `/recent`, `/id 123`)

== Developer Reference ==

== Filters for Developers ==

This plugin includes multiple filters to help developers customize behavior and output at various stages of the search flow.

**`init_plugin_suite_live_search_enable_fallback`**

Enable or disable fallback logic (trimming or bigrams) when few results are found.  
**Applies to:** `/search`  
**Params:** `bool $enabled`, `string $term`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_post_ids`**

Customize the array of post IDs returned from the search query.  
**Applies to:** `/search`  
**Params:** `array $post_ids`, `string $term`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_result_item`**

Modify each result item before it's sent in the response.  
**Applies to:** `/search`  
**Params:** `array $item`, `int $post_id`, `string $term`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_results`**

Filter the final array of results before being returned.  
**Applies to:** `/search`  
**Params:** `array $results`, `array $post_ids`, `string $term`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_category`**

Customize the category label shown in search results.  
**Applies to:** all endpoints  
**Params:** `string $category_name`, `int $post_id`

**`init_plugin_suite_live_search_default_thumb`**

Override the default thumbnail if the post lacks a featured image.  
**Applies to:** all endpoints  
**Params:** `string $thumb_url`

**`init_plugin_suite_live_search_query_args`**

Modify WP_Query arguments for recent, date, or taxonomy-based commands.  
**Applies to:** `/recent`, `/date`, `/tax`  
**Params:** `array $args`, `string $type ('recent' | 'date' | 'tax')`, `WP_REST_Request $request`

**`init_plugin_suite_live_search_stop_single_words`**

Customize the list of single-word stopwords removed before generating bigrams.  
**Applies to:** keyword suggestion  
**Params:** `array $stop_words`, `string $locale`

**`init_plugin_suite_live_search_stop_words`**

Customize the stop-word list used when auto-generating suggested keywords.  
**Params:** `array $stop_words`, `string $locale`

**`init_plugin_suite_live_search_taxonomy_cache_ttl`**

Customize the cache duration (in seconds) for the `/taxonomies` endpoint. Return `0` to disable caching.
**Applies to:** `/taxonomies`  
**Params:** `int $ttl`, `string $taxonomy`, `int $limit`

**`init_plugin_suite_live_search_filter_lang`**

Filter the list of post IDs by the current language. Supports Polylang and WPML.  
**Applies to:** `/search`, `/related`, `/read`, and other multilingual-aware endpoints  
**Params:** `array $post_ids`, `string $term`, `array $args`

**`init_plugin_suite_live_search_category_taxonomy`**

Override the taxonomy used to fetch and display category labels in results.  
**Applies to:** all endpoints  
**Params:** `string $taxonomy`, `int $post_id`

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
  Fetch WooCommerce products using flexible query parameters. Supports slash commands like `/product`, `/on-sale`, `/stock`, `/sku`, and `/price`.  
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
2. Clean modal interface with keyword suggestions
3. Search results with filter pills and post types
4. Fully supports dark mode (auto or manual)
5. Slash command dropdown helper with real-time suggestions
6. WooCommerce product search via `/product` slash command with price, sale, and out-of-stock indicators

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
When you select 1–8 words on any page, a small tooltip appears allowing you to search instantly — no typing required.

= Does it support WooCommerce products? =  
Yes. It can search and display WooCommerce products including price, stock status, sale badges, and “Add to Cart” buttons — with support for slash commands like `/product`, `/on-sale`, `/stock`, `/sku`, and `/price`.

= Can I filter products by price or stock? =  
Yes. Use the slash command `/price 100 500` to filter by price range, or `/stock` to show in-stock products only.

= What is “Init Smart Tag-Aware Search”? =  
It’s an advanced search mode that matches keywords not only in post titles but also in post tags — with automatic fallback using word splitting and bi-grams. This helps catch results like “JavaScript” or “SEO” even if they’re only used as tags.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/ or install via the admin panel.
2. Activate the plugin from the Plugins menu.
3. It will automatically enhance all `<input name="s">` fields.
4. (Optional) Configure advanced settings in Settings → Init Live Search.

== Changelog ==

= 1.5.3 – May 27, 2025 =
- Added support for searching specific ACF fields (Advanced Custom Fields)
  → Optional admin setting to define comma-separated field keys (e.g. `company_name, project_code`)
  → Only searches published posts and supports intelligent fallback logic
  → Built-in filter for full control: `init_plugin_suite_live_search_post_ids`
- Multilingual compatibility enhancements
  → Automatic language detection with Polylang and WPML
  → Added `init_plugin_suite_live_search_filter_lang` filter to restrict results by current language
  → Filterable language-aware REST queries for slash commands like `/recent`, `/tax`, etc.
- New developer filter: `init_plugin_suite_live_search_category_taxonomy`
  → Allows customizing the taxonomy used for displaying categories (e.g. use `product_cat` for WooCommerce)
- Improved ACF query performance and status filtering (joins `postmeta` with published posts only)
- Internal consistency tweaks and filter documentation improvements

= 1.5.2 – May 26, 2025 =
- Introduced new search mode: **Init Smart Tag-Aware Search**
  → Combines post title and post tag matching with intelligent fallback using keywords and bi-grams
  → Automatically splits terms into single words to match short tags like “php”, “css”, or “seo”
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
