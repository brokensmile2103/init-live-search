=== Init Live Search – AI-Powered, Related Posts, Slash Commands ===
Contributors: brokensmile.2103
Tags: AI search, live search, meilisearch, related posts, woocommerce
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.9.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fast REST API live search with optional FULLTEXT index, Meilisearch, AI-powered Related Posts, slash commands, ACF and WooCommerce support.

== Description ==

Deliver an ultra-responsive search experience to your visitors — no page reloads, no jQuery, no lag. Init Live Search is a modern, lightweight, and fully accessible live search solution for WordPress — now with an optional **MySQL FULLTEXT search index**, optional **Meilisearch** integration, tag-aware matching, SEO metadata support, ACF integration, WooCommerce product filters, and customizable UI presets.

It replaces the default `<input name="s">` with a clean, intuitive search modal powered entirely by the WordPress REST API. Everything loads in real-time — with zero disruption to browsing flow.

Perfect for content-heavy blogs, WooCommerce stores, or even headless sites. Every interaction is fast, fluid, and designed to work across devices.

Want typo-tolerant, sub-50ms relevance ranking on top of that? Connect your own self-hosted or cloud **Meilisearch** instance in a few clicks — Init Live Search will automatically prefer it for search, and just as automatically fall back to the built-in database search if it's ever unreachable. Your visitors never see a broken search box.

It also brings AI-powered related posts and an advanced keyword generator — giving your visitors smarter ways to discover content.

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a collection of minimalist, fast, and developer-focused tools for WordPress.

GitHub repository: [https://github.com/brokensmile2103/init-live-search](https://github.com/brokensmile2103/init-live-search)

== What's New in Version 1.8.x & 1.9.x ==

- **Optional FULLTEXT Search Index (1.9.5)**: opt-in MySQL FULLTEXT-indexed table for Title/Excerpt/Content, replacing slow `LIKE '%term%'` scans on large sites. Off by default, auto-builds in the background via WP-Cron once enabled (no SSH/WP-CLI needed), with `wp init-live-search fulltext-reindex` available for a manual/faster build. See "FULLTEXT Search Index" below for details.

- **Optional Meilisearch Integration**: connect your own Meilisearch server (self-hosted or cloud) as the primary search engine
  - Typo-tolerant, relevance-ranked, sub-50ms search results straight from Meilisearch
  - Automatic, transparent fallback to the local database search whenever Meilisearch is disabled, unreachable, or misconfigured — search availability is never compromised
  - New **Meilisearch** settings tab: Host URL, Index Name, Search Key, Admin/Indexing Key, request timeout, one-click "Test Connection", and (1.9.5) a one-click **"Reindex Now"** button for sites without WP-CLI/SSH access
  - Posts are synced automatically on publish/update/trash/delete (non-blocking — won't slow down the editor)
  - WP-CLI command `wp init-live-search meili-reindex` for bulk-indexing or rebuilding the entire index
  - Sensitive indexing key can be defined via the `INIT_LIVE_SEARCH_MEILI_ADMIN_KEY` constant in `wp-config.php` instead of the database, for extra security

- **AI-Powered Related Posts**: brand new `[init_live_search_related_ai]` shortcode  
  - Uses multi-signal scoring (tags, series, title bigrams, same_keyword via ACF, category, views, comments, freshness)  
  - Shares templates with `[init_live_search_related_posts]` (no extra styling needed)  
  - Fully filterable via new developer hooks: `ai_candidates`, `ai_signals`, `ai_weights`, `ai_score`

- **Advanced Keyword Generator**: upgraded algorithm for admin keyword suggestions  
  - Replaced TF-IDF with **BM25** term weighting  
  - Added **NPMI** (fixed probability base) and **Log-Likelihood Ratio (Dunning)** for collocation strength  
  - Generates **bigrams and trigrams** for richer, more specific keyword suggestions  
  - **Cross-document frequency penalty** down-ranks phrases that are too generic across the site  
  - **MMR (Maximal Marginal Relevance)** selection ensures diverse, non-redundant final keywords  
  - Title-only source: clean signal, no excerpt noise, works reliably across all site types  
  - Unicode-safe, locale-aware stop words (Vietnamese & English), and soft fallback mode

- **404 Smart Redirect**
  - Added "Auto Redirect 404 to Best Match" mode driven by Init Live Search scoring
  - Fully respects "Post Types to Include" settings
  - Uses unified resolver + filters for extensible post-type handling
  - Safety checks to avoid loops, invalid targets, and cross-type mismatches

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
- **NEW:** Optional MySQL FULLTEXT search index — faster Title/Excerpt/Content matching than `LIKE` queries on the built-in database search, auto-builds in the background
- **NEW:** Optional Meilisearch integration — typo-tolerant, relevance-ranked external search with automatic fallback to local DB search
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
- Enable an optional MySQL FULLTEXT search index for faster Title/Excerpt/Content matching, with automatic background indexing  
- Connect an optional Meilisearch server (Host, Index, Search/Admin keys, timeout, one-click connection test, one-click Reindex Now)  
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

== FULLTEXT Search Index (Optional) ==

The built-in database search normally uses `LIKE '%term%'` queries, which can't use a regular index and get slow on sites with a large number of posts. Tick **FULLTEXT Search Index** (Settings → Init Live Search → General) to sync Title/Excerpt/Content into a dedicated indexed table (your `wp_posts` table is untouched) and match with MySQL's `MATCH() AGAINST()` instead.

Once enabled, the index builds itself automatically in the background via WP-Cron — no SSH/WP-CLI needed — or you can speed things up with `wp init-live-search fulltext-reindex`. If your server doesn't support FULLTEXT, or the index isn't built yet, the plugin simply keeps using the standard LIKE-based search — nothing ever breaks. Tag search, SEO metadata, ACF fields, synonyms, and the `+`/`-` operators all keep working the same either way.

== Meilisearch Integration (Optional) ==

Init Live Search works great out of the box with zero setup — the built-in database search handles everything by default. But if you want faster, typo-tolerant, relevance-ranked search at scale, you can connect your own [Meilisearch](https://www.meilisearch.com/) instance in a few minutes.

**How it works:**

1. Install and run Meilisearch yourself — self-hosted (a small VPS is plenty for most blogs) or via Meilisearch Cloud. This plugin does not host or manage a server for you.
2. Go to **Settings → Init Live Search → Meilisearch**, enter your Host URL, Index Name, and a **search-only** API key, then enable the integration.
3. Index your existing published content either by running `wp init-live-search meili-reindex` via WP-CLI, or by clicking the **"Reindex Now"** button right on the Meilisearch settings tab (handy if you don't have WP-CLI/SSH access) — it runs in the background (~200 posts every 5 seconds) with a live progress status.
4. That's it — search requests are now answered by Meilisearch. New, updated, and deleted posts stay in sync automatically.

**Built for safety, not lock-in:**

- If Meilisearch is disabled, unconfigured, or fails to respond (timeout, wrong key, server down), the plugin **automatically and silently falls back** to the local database search — visitors never see a broken search box.
- Reindexing (via the button or WP-CLI) is always manual-only, and auto-stops with a clear error after 3 consecutive failed batches instead of retrying forever.
- Your sensitive indexing key can live in `wp-config.php` (`INIT_LIVE_SEARCH_MEILI_ADMIN_KEY`) instead of the database.
- Turn it off any time — nothing about your site's core search behavior depends on Meilisearch being present.

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
Modify each result item before it's sent in the response.  

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
14. Meilisearch Settings: connect your Host URL, Index, and API keys, with one-click connection test

== Frequently Asked Questions ==

= Does this plugin use jQuery? =  
No. It's built entirely with modern Vanilla JavaScript — no jQuery, no external dependencies.

= What is the FULLTEXT search index and do I need SSH/WP-CLI for it? =  
It's an optional speed-up for the built-in database search — matches Title/Excerpt/Content via a MySQL FULLTEXT index instead of slow `LIKE '%term%'` scans, worth it once your content library grows. No SSH/WP-CLI needed: once enabled in settings, it builds itself in the background via WP-Cron (WP-CLI's `fulltext-reindex` is just an optional faster alternative).

= What is Meilisearch and do I need it? =  
Meilisearch is a fast, open-source search engine known for typo-tolerant, relevance-ranked results. You don't need it — Init Live Search's built-in database search works fully on its own. Meilisearch is an optional upgrade for sites that want that extra layer of speed and fuzzy-matching quality at scale.

= Does Init Live Search host Meilisearch for me? =  
No. Meilisearch is bring-your-own-server: you install and run it yourself (self-hosted or via Meilisearch Cloud), then connect it from the plugin's settings. This keeps the plugin free and keeps you in full control of your data and infrastructure.

= What happens if my Meilisearch server goes down? =  
Nothing breaks. The plugin automatically detects the failure (or timeout) and falls back to the local database search for that request — visitors won't notice anything except perhaps a slightly less fuzzy match.

= How do I index my existing posts into Meilisearch? =  
Either run `wp init-live-search meili-reindex` via WP-CLI, or click the **"Reindex Now"** button on the Meilisearch settings tab if you don't have WP-CLI/SSH access — it indexes in the background with a live progress status. New, updated, and deleted posts sync automatically after that.

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
Yes. It uses the browser's SpeechRecognition API with auto-stop, language detection, and error handling.

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
Yes. The plugin supports a special "Smart Tag-Aware" mode that matches both post titles and tags.  
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

= Can I override or disable the plugin's CSS? =  
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
The plugin will redirect to WordPress's default search results page.

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

= 1.9.6 – August 03, 2026 =
- **Fixed: FULLTEXT background indexing showed a false "not running" error.** Right after enabling the checkbox, the status could briefly (and misleadingly) report that WP-Cron wasn't running — even though it was, mid-batch — because WP-Cron unschedules an event *before* running it, so a plain `wp_next_scheduled()` check could catch it in that gap. Status detection now also checks whether a batch is actively processing, and the page load immediately after clicking Save shows a neutral "Checking indexing status…" message instead of guessing right or wrong — it's simply too early to know at that point.
- **Fixed: Meilisearch reindex could fail outright with `HTTP error 413 from Meilisearch`.** A batch's JSON payload size depends on post content, not just post count, so a fixed batch size that works on one site can exceed Meilisearch's (or a reverse proxy's) payload size limit on another. The background reindex, the "Reindex Now" button, and `wp init-live-search meili-reindex` now automatically split an oversized batch and retry with smaller payloads; if a single post is still too large on its own, it's skipped (with a warning listing the post ID) instead of blocking the entire reindex.
- **Fixed**: the Meilisearch reindex progress indicator (Settings page + live polling) could stop early on the same kind of false "not running" signal described above, requiring a manual refresh to show accurate progress.
- **Changed: reindexing no longer requires "Enable Meilisearch" to be checked.** The "Reindex Now" button, the background cron job, and `wp init-live-search meili-reindex` now only need Host and Index to be filled in — useful for building or testing a new index while the current search source (database or an existing Meilisearch index) keeps running, without switching it live first. Auto-sync on save/delete still only starts once the checkbox is enabled, as before.

= 1.9.5 – August 03, 2026 =
- **Performance**: fixed N+1 queries when building search/related-posts result lists — post, postmeta, and term caches for the whole batch are now primed in one shot before rendering, instead of running fresh queries per result.
- **New: FULLTEXT Search Index (opt-in)**: added an optional MySQL FULLTEXT-indexed table as a faster alternative to `LIKE '%term%'` for Title/Excerpt/Content matching on the standard database search pipeline — a major speed-up on sites with a large number of posts.
- **Safety**: the FULLTEXT index is off by default and only takes effect once the server's support is confirmed and the index has been fully built; until then the plugin transparently keeps using the existing LIKE-based search.
- **Auto-indexing**: once enabled, the FULLTEXT index builds itself automatically in the background via WP-Cron (~300 posts every 5 seconds) — no SSH/WP-CLI access required.
- **WP-CLI**: added `wp init-live-search fulltext-reindex` to build or rebuild the FULLTEXT index manually/faster.
- **Unaffected**: Tag, SEO metadata, ACF field search, synonym expansion, and the +/- operators are untouched by the FULLTEXT index change and continue to work exactly as before.
- **Meilisearch: "Reindex Now" button**: added to the Meilisearch settings tab so sites without WP-CLI/SSH access can build or rebuild the index too — runs in the background (~200 posts every 5 seconds) with a live progress status.
- **Meilisearch: error backoff**: the background reindex automatically stops with a clear error message after 3 consecutive failed batches, instead of retrying forever against an external server.
- **Meilisearch: manual-only by design**: unlike the FULLTEXT index, background reindexing never starts on its own — Meilisearch is a user-owned, potentially paid external service, so it only runs when explicitly started via the button or `wp init-live-search meili-reindex`.

= 1.9.4 – July 20, 2026 =
- **Fixed**: the Search API Key and Admin/Indexing Key fields on the Meilisearch settings tab could be silently autofilled by the browser's saved password manager, risking accidental exposure of an unrelated saved password if the form was submitted without checking. These fields are now correctly excluded from autofill.
- **Fixed**: the "Test Connection" button on the Meilisearch settings tab always displayed "? documents" instead of the actual estimated document count, due to a naming mismatch between the connection test response and the display script.
- **Code Quality**: moved the Meilisearch "Test Connection" JavaScript out of an inline `<script>` block into `admin.js` (enqueued properly via `wp_enqueue_script`), in line with WordPress Coding Standards; translated strings are passed through via `wp_localize_script` so existing translations are unaffected.

= 1.9.3 – July 20, 2026 =
- **Meilisearch Integration (optional)**: added a new "Meilisearch" settings tab to connect a self-hosted (bring-your-own-server) Meilisearch instance as the primary search source. When enabled and reachable, search requests are answered by Meilisearch's typo-tolerant, relevance-ranked engine; if the request fails or times out for any reason, the plugin automatically falls back to the existing local database search — search never goes down solely because of a Meilisearch outage.
- **Auto-sync on Save/Delete**: publishing, updating, trashing, or deleting a post now automatically pushes/removes the corresponding document in the configured Meilisearch index (non-blocking, does not slow down editor saves).
- **WP-CLI Command**: added `wp init-live-search meili-reindex` to bulk-index (or rebuild) all published posts of the enabled post types into Meilisearch.
- **Test Connection**: added a one-click connection test on the Meilisearch settings tab.
- **Security**: the sensitive indexing/admin API key can be defined via the `INIT_LIVE_SEARCH_MEILI_ADMIN_KEY` constant in `wp-config.php` (recommended) instead of being stored in the database.
- **i18n**: all new UI strings use English as the source (msgid) with the `init-live-search` text domain, per WordPress.org standards; `.pot` regenerated and the Vietnamese `.po`/`.mo` translation updated with 27 new/previously-missing strings translated.

= 1.9.2 – July 17, 2026 =
- **Race Condition Fix**: search requests in the modal (`script.js`) now use `AbortController` to cancel stale in-flight requests when the user keeps typing, preventing older/slower responses from overwriting newer search results.
- **+/- Search Operator Redesign**: `+word`/`-word` now correctly *narrow* the results of the plain search terms instead of being merged into the same fuzzy/fallback pool. Plain words still drive the base search (with all existing fuzzy matching, synonyms, and fallback logic intact); `+word` requires the narrowed results to also contain that word, `-word` excludes results containing it — supporting multiple `+`/`-` operators in the same query (e.g. `wordpress -plugin -theme`).
- **Predefined Dictionary Expansion**: significantly expanded the E-commerce, Technology, Business, and Health dictionaries (previously the thinnest of the 10 built-in dictionaries) with dozens of additional English/Vietnamese synonym pairs, bringing them in line with the other dictionaries.
- **Dictionary Cleanup**: fixed 4 duplicate dictionary keys (`sale`, `visa`, `gym`, `vintage`) that were silently overwriting earlier synonym definitions.
- **Minor Bug Fixes**: added missing optional chaining in `loadMoreRecent()` to prevent a potential crash when an item is missing its `data-url` attribute; normalized trailing-slash URL comparison in `loadMoreGeneric()` to prevent possible duplicate items when loading more results.

= 1.9.1 – July 07, 2026 =
- **Related Posts Command Conditions**: added two new conditional settings for the "/related" default command — restrict auto-execution to single post pages only, and exclude specific URL slug keywords.
- **Smart Default Command Guard**: frontend now validates `related_only_single` and `related_exclude_slugs` before auto-injecting `/related` into the search modal, preventing unwanted related searches on archives, pages, or excluded paths.
- **Settings UX**: related command options are visually dimmed and non-interactive when "Related Posts" is not selected as the default slash command.
- **Sanitization**: `related_only_single` and `related_exclude_slugs` are properly sanitized via the existing settings flow with keyword trimming and line-by-line validation.

= 1.9.0 – May 12, 2026 =
- **Thumbnail Fallback**: added new option "Use First Image as Thumbnail Fallback?" — automatically extracts the first image from post content when no featured image is available.
- **WordPress-native Detection**: fallback engine now prioritizes parsing `wp-image-{ID}` classes and retrieves the proper WordPress thumbnail size using attachment metadata.
- **Smart Fallback Chain**: if attachment lookup fails, the plugin gracefully falls back to the raw `<img src="">` URL before using the default thumbnail.
- **Host Validation**: external image URLs are validated against the current site host/subdomain by default to prevent unwanted third-party hotlinks.
- **Developer Extensibility**: introduced new filter `init_plugin_suite_live_search_allow_fallback_image_host` for customizing allowed fallback image hosts.
- **Settings Integration**: added full admin setting, sanitization flow, and translation support for the new thumbnail fallback feature.

View full changelog (all versions): [Init Live Search – Changelog](https://en.inithtml.com/plugin/init-live-search/)

== License ==

This plugin is licensed under the GPLv2 or later.  
You are free to use, modify, and distribute it under the same license.
