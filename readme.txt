=== Init Live Search – AI-Powered, Related Posts, Slash Commands ===
Contributors: brokensmile.2103
Tags: AI search, live search, related posts, slash commands, woocommerce
Requires at least: 5.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.8.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast, modern live search powered by REST API — with AI-powered Related Posts Engine, slash commands, SEO-aware, ACF, Woo, and custom UI presets.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. Init Live Search is a modern, lightweight, and fully accessible live search solution for WordPress — now with tag-aware matching, SEO metadata support, ACF integration, WooCommerce product filters, and customizable UI presets.

It replaces the default `<input name="s">` with a clean, intuitive search modal powered entirely by the WordPress REST API. Everything loads in real-time — with zero disruption to browsing flow.

Perfect for content-heavy blogs, WooCommerce stores, or even headless sites. Every interaction is fast, fluid, and designed to work across devices.

It also brings AI-powered related posts and an advanced keyword generator — giving your visitors smarter ways to discover content.

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-live-search](https://github.com/brokensmile2103/init-live-search)

== What's New in Version 1.8.x ==

- **AI-Powered Related Posts**: brand new `[init_live_search_related_ai]` shortcode  
  - Uses multi-signal scoring (tags, series, title bigrams, same_keyword via ACF, category, views, comments, freshness)  
  - Shares templates with `[init_live_search_related_posts]` (no extra styling needed)  
  - Fully filterable via new developer hooks: `ai_candidates`, `ai_signals`, `ai_weights`, `ai_score`

- **Advanced Keyword Generator**: upgraded algorithm for admin keyword suggestions  
  - Replaced TF-IDF with **BM25** term weighting  
  - Added **NPMI** and **Log-Likelihood Ratio (Dunning)** for collocation strength  
  - Focused on **bigram-only** for higher-quality keywords  
  - Unicode-safe, locale-aware stop words, and soft fallback mode

- **Developer Filters Expansion**  
  - New filters added for AI related posts and keyword signals  
  - Complete list now includes over 20 filters (`*_fallback`, `*_post_ids`, `*_results`, `*_weights`, `*_commands`, etc.)  
  - Developers can hook into candidate pools, signal scores, and schema output with fine-grained control

- **Performance Optimizations**  
  - Smarter candidate pooling for related posts (recent + context-based)  
  - Pre-cached scoring loop for AI signals to minimize queries  
  - Safer regex handling in keyword processing to avoid PCRE errors  
  - Reduced memory footprint in bigram statistics without sacrificing accuracy

- **Backward Compatible Enhancements**  
  - `[init_live_search_related_posts]` and `[init_live_search_related_ai]` now share the same rendering pipeline  
  - Existing templates, schema, and CSS continue to work without modification  
  - Auto insert related posts still works and can be switched to AI mode via shortcode override

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
- Enable support for `+` and `-` keyword operators (must-have, must-not-have)  
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
- Automatically insert related posts after content or comments (optional)
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

**`[init_live_search_related_posts]`**  
Display a list of related posts (static HTML) based on post title or keyword, optimized for SEO and fully themable.

**Attributes:**
- `id`: (optional) the post ID to find related posts for (defaults to current post)  
- `count`: (optional) number of posts to display (default: `5`)  
- `keyword`: (optional) override the keyword used for finding related posts  
- `template`: (optional) layout template to use — `default`, `grid`, `classic`, `compact`, `thumbright`  
- `css`: `1` (default) or `0` – disable default CSS if you want to fully style it yourself  
- `schema`: `1` (default) or `0` – disable JSON-LD `ItemList` output for SEO schema  

**`[init_live_search_related_ai]`**  
Display a list of AI-powered related posts using multi-signal scoring (tags, series, title bigrams, same_keyword via ACF, category, views, comments, freshness).  
Uses the same templates as `[init_live_search_related_posts]`, so no extra styling is required.

**Attributes:**
- `id`: (optional) the post ID to find related posts for (defaults to current post)  
- `count`: (optional) number of posts to display (default: `5`)  
- `post_type`: (optional) restrict results to one or more post types (default: `post`)  
- `template`: (optional) layout template to use — `default`, `grid`, `classic`, `compact`, `thumbright`  
- `css`: `1` (default) or `0` – disable default CSS if you want to fully style it yourself  
- `schema`: `1` (default) or `0` – disable JSON-LD `ItemList` output for SEO schema

== Filters for Developers ==

Init Live Search includes many filters to help developers customize behavior and output at various stages of the search flow.

Full documentation (with code samples & advanced usage): [Using Filters in Init Live Search](https://en.inithtml.com/wordpress/using-filters-in-init-live-search/)

**Popular filters**

**`init_plugin_suite_live_search_enable_fallback`**  
Enable or disable fallback logic when few results are found.  

**`init_plugin_suite_live_search_post_ids`**  
Customize the array of post IDs returned from the query.  

**`init_plugin_suite_live_search_result_item`**  
Modify each result item before it’s sent in the response.  

**`init_plugin_suite_live_search_results`**  
Filter the final array of results before being returned.  

**`init_plugin_suite_live_search_query_args`**  
Modify WP_Query arguments for different commands.  

**`init_plugin_suite_live_search_ai_weights`**  
Adjust AI scoring weights for related posts.  

(...and more in the full docs)

== REST API Endpoints ==

Fully documented, lightweight, and API-first endpoints. Ideal for headless or decoupled builds.  
All endpoints are under namespace: `initlise/v1`

Full documentation (with examples & parameters): [REST API Endpoints in Init Live Search](https://en.inithtml.com/wordpress/list-of-rest-api-endpoints-in-init-live-search/)

**Popular endpoints**

**`/search?term=example`**  
Standard search query (uses plugin settings like post types, search mode, fallback…).  

**`/id/{id}`**  
Fetch a post by ID (returns permalink).  

**`/recent`**  
Fetch the most recent posts.  

**`/tax?taxonomy=category&term=slug-or-id`**  
Fetch posts by taxonomy (category, tag, or custom).  

**`/related?title=page-title&exclude=ID`**  
Fetch posts related to the current page title.  

**`/product?...`**  
Fetch WooCommerce products with flexible query parameters.  

(...and more in the full docs)

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

= Can this plugin automatically display related posts without using a shortcode? =  
Yes. In the plugin settings, you can choose to automatically insert related posts after the content or around the comment section.  
It uses the `[init_live_search_related_posts]` shortcode internally with a default layout.  
You can still use the shortcode manually for full control.

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

= 1.8.7 – December 11, 2025 =
- **404 Smart Redirect**: added new option “Auto Redirect 404 to Best Match” — automatically redirects 404 pages to the most relevant post determined by Init Live Search.
- **Post Type Awareness**: redirect engine now respects the plugin’s “Post Types to Include” setting and works seamlessly with multiple post types.
- **Unified Resolver**: 404 redirect now uses `init_plugin_suite_live_search_resolve_post_types()` and the filter `init_plugin_suite_live_search_post_types` for consistent, extensible post-type handling.
- **Safety & Accuracy**: redirect only triggers on valid, published posts and prevents unexpected loops or mismatches across post types.
- **Code Quality**: improved sanitization of `$_SERVER['REQUEST_URI']` (unslash + sanitize), removed unsafe patterns, standardized function prefixes, and ensured PHPCS compliance.

= 1.8.6 – November 09, 2025 =
- **Shortcode Enhancement**: `[init_live_search]` now supports new attributes:
  `width`, `max_width`, `align`, `id`, `name`, `aria_label`, `button` (show/hide), and improved `radius`.
- **Security / Code Quality**: escaped dynamic attributes, removed unsafe inline output, and improved PHPCS compliance.
- **SQL Safety**: converted `LIMIT` values to `%d` and applied scoped PHPCS ignores for dynamic placeholder lists.

= 1.8.5 – October 15, 2025 =
- **Fix**: `.ils-cart-btn` now consistently redirects to the **product page** for *all* WooCommerce product types (simple, variable, grouped, etc.) instead of calling the AJAX `add_to_cart` endpoint that returned a JSON response
- **UX Consistency**: ensures identical “View Product” behavior across all product types in live search results
- **Thanks**: special thanks to **m0n0brands** for reporting and confirming the issue

= 1.8.4 – September 17, 2025 =
- **Dev Filter**: `init_plugin_suite_live_search_post_types` – allow themes/plugins to modify or enforce post type list  
- **Example Use Case**: ensure a custom post type (e.g. `manga`) is always included in search results without affecting plugin settings  
- **Code Quality**: standardized return handling with `array_values(array_unique())` for consistent output

= 1.8.3 – August 30, 2025 =
- **Fix**: `/coupon` REST endpoint – prevent 500 errors on expired or limited coupons  
- **Code Quality**: added PHPCS ignores for complex SQL queries (placeholders, interpolated vars, direct queries)  
- **Stability**: improved parameter checks and reduced false positives from PHPCS  

= 1.8.2 – August 26, 2025 =
- **AI Related Posts Engine v2**: dual signals (recency + time_gap), smarter diversification (MMR), safer cache versioning
- **Performance**: pre-cache posts & terms, deduplication, optimized scoring loop
- **Dev Filters**: new controls for recency, gap decay, MMR λ, and final candidate selection

= 1.8.1 – August 26, 2025 =
- **AI-Powered Related Posts**: new `[init_live_search_related_ai]` shortcode with multi-signal scoring (tags, series, categories, etc.)
- **Extensible API**: inject candidates, extend signals, adjust weights, override scores
- **Performance**: pre-cached post data, unified template rendering

= 1.8.0 – August 16, 2025 =
- **Keyword Generator Upgrade**: BM25 + NPMI + Log-Likelihood Ratio for high-quality bigrams
- **Bigram-Only Focus**: stricter filtering, Unicode-safe, excludes noise terms
- **Resilience**: fallback mode ensures at least 15 keywords per request
- **Performance**: memory-efficient scoring, optimized regex, robust error handling

View full changelog (all versions): [Init Live Search – Changelog](https://en.inithtml.com/plugin/init-live-search/)

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
