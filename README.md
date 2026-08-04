# Init Live Search – AI-Powered, Related Posts, Slash Commands

> Fast, modern live search for WordPress. REST API-powered with optional Meilisearch integration, Block Editor & Abilities API support, slash commands, SEO-aware search, ACF field support, WooCommerce integration, and custom UI styles.

**Blazing-fast modal search for WordPress — no jQuery, no reloads, no limits.**

[![Version](https://img.shields.io/badge/stable-v2.0.0-blue.svg)](https://wordpress.org/plugins/init-live-search/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init Live Search replaces your default WordPress search with a sleek, fast, command-style modal powered by REST API and modern JavaScript. Navigate your content like a pro — with slash commands, instant results, keyboard shortcuts, tooltip search, and even voice input.

Whether you're building a blog, an eCommerce site, a headless frontend, or a high-performance content portal, this plugin adapts to your workflow.

Want typo-tolerant, sub-50ms relevance ranking on top of that? Connect your own self-hosted or cloud [Meilisearch](https://www.meilisearch.com/) instance — Init Live Search will automatically prefer it for search, and just as automatically fall back to the built-in database search if it's ever unreachable.

![Demo of Init Live Search](https://inithtml.com/wp-content/uploads/2025/05/Init-Live-Search-Demo.gif)

## What's New in v2.0.0

- **Abilities API support (WordPress 6.9+)**: registers two read-only abilities under the `init-live-search` category — `init-live-search/search-posts` (runs the plugin's search engine and returns matching results) and `init-live-search/get-related-posts` (returns posts related to a given post ID). Discoverable and executable via PHP, `wp_get_abilities()`, and — when a site opts in — the `wp-abilities/v1` REST namespace. Fully optional and backward-compatible: on WordPress versions older than 6.9, the integration silently does nothing
- **Block Editor (Gutenberg) support**: three dynamic blocks, grouped under their own **Init Live Search** block category
  - **Live Search Box** — the icon/input launcher, equivalent to `[init_live_search]`
  - **Live Search: Related Posts** — keyword-based related posts, equivalent to `[init_live_search_related_posts]`
  - **Live Search: AI Related Posts** — AI multi-signal related posts, equivalent to `[init_live_search_related_ai]`

  Each block is registered via `block.json` with a PHP `render.php` that calls the exact same shortcode function as its shortcode counterpart, so output never diverges. A single no-build-step vanilla JS editor integration uses `wp.serverSideRender` for a live preview directly in the editor, and CSS is declared once via `block.json`'s `"style"` field so WordPress enqueues it automatically wherever needed
- **Requires at least** raised from 5.9 to 6.9 to support the Abilities API integration. `Requires PHP` remains 7.4
- **Fixed**: Block Editor inserter/sidebar strings now translate correctly on both the JS side (Jed-formatted JSON via `wp_set_script_translations()`) and the `block.json` metadata side (standard `.mo`/gettext)

## What's New in v1.7.x, v1.8.x & v1.9.x

- **Optional Meilisearch Integration**: connect your own Meilisearch server (self-hosted or cloud) as the primary search engine, with automatic, transparent fallback to the local database search whenever it's disabled, unreachable, or misconfigured
- **Auto-sync to Meilisearch**: posts are pushed/removed from the index automatically on publish, update, trash, and delete (non-blocking)
- **WP-CLI Reindex Command**: `wp init-live-search meili-reindex` for bulk-indexing or rebuilding the entire index
- **FULLTEXT Search Index**: a MySQL FULLTEXT-indexed table can now be used for Title/Excerpt/Content matching on the standard database search pipeline instead of `LIKE '%term%'` queries — a major speed-up on sites with a large number of posts. Off by default; the plugin automatically detects server support and transparently keeps using the existing LIKE-based search until it's confirmed and built
- **Cross-site Search**: fetch & merge results from other Init Live Search-powered sites  
- **No CORS or Auth Setup**: just enter `Site Name|https://example.com` — it works instantly  
- **Auto Labeling**: results from external sources are tagged (e.g. "Init Docs")  
- **WooCommerce Slash Expansion**: added support for `/brand`, `/attribute`, `/variation`, `/coupon`  
- **Improved `/price` Command**: now supports `sort` and `rsort` modifiers  
- **Cleaner Cross-site Results**: disables single-word fallback for external queries  
- **New Shortcode**: `[init_live_search]` to render a search icon or input anywhere  
- **New Shortcode**: `[init_live_search_related_posts]` to render static, themeable related posts  
- **Search Operators**: support for `+musthave` and `-mustnot` terms in queries  
- **Visual Shortcode Builder**: build `[init_live_search]` and `[init_live_search_related_posts]` shortcode visually with live preview  
- **Auto Insert Related Posts**: no shortcode needed — insert after content or comments automatically  
- **Template-based Layouts**: use `template="..."` to switch between `grid`, `classic`, `compact`, `thumbright`  
- **Theme Override Ready**: copy any layout to your theme via `init-live-search/related-posts-{template}.php`  
- **Filter-Driven Logic**: fully extensible via `*_auto_insert_enabled` and `*_default_related_shortcode` hooks  
- **AI-Powered Related Posts**: new `[init_live_search_related_ai]` shortcode using multi-signal scoring (tags, ACF, series, views, etc.)  
- **Advanced Keyword Generator**: upgraded to BM25 + NPMI + LLR engine for higher-quality suggestions
- **404 Smart Redirect**: automatically redirect 404 URLs to the most relevant post, respecting post type settings and unified resolver logic

## Features

- Clean modal search interface (`Ctrl + /`, triple-click, or `data-ils`)
- Powered by WordPress REST API — no `admin-ajax`, no jQuery
- **Block Editor (Gutenberg) blocks**: Live Search Box, Related Posts, and AI Related Posts, each with a live server-side preview in the editor
- **Abilities API support (WordPress 6.9+)**: search and related-posts exposed as discoverable, executable abilities via `wp_get_abilities()` and the `wp-abilities/v1` REST namespace
- **Optional Meilisearch integration**: typo-tolerant, relevance-ranked external search with automatic fallback to local DB search
- **Optional FULLTEXT search index**: MySQL FULLTEXT-backed matching for the local database search pipeline on large sites, with automatic background indexing via WP-Cron
- **Cross-site Search**: query multiple domains seamlessly
- **Search in SEO Metadata** — support Yoast, Rank Math, AIOSEO, TSF, SEOPress
- **Weighted Ranking** — control priority via filters (e.g. title > SEO > tags)
- Smart **Slash Commands**: `/recent`, `/fav`, `/id`, `/tag`, `/product`, etc.
- **Quick Search Tooltip**: select up to 20 words for instant search
- LocalStorage-based **favorites** and **caching**
- Voice input via native `SpeechRecognition` API
- Full **keyboard navigation**: `↑ ↓ ← → Enter Esc`
- Deep linking: prefill search via `?modal=search&term=...`
- Template-driven related post rendering with optional auto-insert
- Dark mode support: `auto`, `dark`, or `light` — or add `.dark` class manually
- **WooCommerce**: price, stock, sale badge, SKU, Add to Cart, coupon detection
- **ACF support**: search custom fields
- Built-in **Analytics**: log search terms (no personal data)
- Developer-ready: filters, JS events, REST-first architecture

## Block Editor (Gutenberg)

Three dynamic blocks are available under their own **Init Live Search** category in the block inserter — no shortcodes needed if you prefer working entirely in the editor:

| Block | Equivalent shortcode | Description |
|-------|----------------------|--------------|
| **Live Search Box** | `[init_live_search]` | The icon/input launcher that opens the search modal |
| **Live Search: Related Posts** | `[init_live_search_related_posts]` | Keyword-based related posts |
| **Live Search: AI Related Posts** | `[init_live_search_related_ai]` | AI multi-signal related posts |

Each block shares the exact same rendering code as its shortcode, so switching between the Block Editor and shortcodes never changes the output. Block settings map directly to shortcode attributes, and a live preview (via `wp.serverSideRender`) is shown right in the editor as you configure it.

## Abilities API (WordPress 6.9+)

On WordPress 6.9 and above, Init Live Search registers two read-only abilities under the `init-live-search` category:

- `init-live-search/search-posts` — runs the plugin's search engine and returns matching results
- `init-live-search/get-related-posts` — returns posts related to a given post ID

These are discoverable and executable via PHP (`wp_get_abilities()`), and — for sites that opt into exposing it — the `wp-abilities/v1` REST namespace, making it straightforward for AI agents and automated tools to query search and related-posts data through a standardized interface. This integration is fully optional: on WordPress versions older than 6.9, it silently does nothing and the rest of the plugin is unaffected.

## FULLTEXT Search Index (Optional)

On sites with a large number of posts, the default database search (`LIKE '%term%'`) can't use an index — every search runs a full table scan. If you don't want to run an external service like Meilisearch, you can instead switch the local database search pipeline to use a MySQL FULLTEXT index.

**Setup:**

1. Go to **Settings → Init Live Search → General** and enable **FULLTEXT Search Index**. The plugin checks your server's support automatically and won't let you enable it if InnoDB FULLTEXT indexes aren't available.
2. That's it — the index builds itself automatically in the background via WP-Cron (no server access needed). Progress is shown right on the Settings page.
3. Prefer to build it yourself, or need it to go faster? Run:
   ```bash
   wp init-live-search fulltext-reindex
   ```
   or click **Run Now** on the Settings page if WP-Cron is disabled on your site.

**Built for safety:**

- Off by default — existing sites are completely unaffected until you turn it on.
- Until the index is fully built, the plugin keeps using the existing LIKE-based search automatically — search results are never empty or broken mid-build.
- Tag, SEO metadata, ACF field search, synonym expansion, and the `+`/`-` operators are unaffected and keep working exactly as before.

## Meilisearch Integration (Optional)

Init Live Search works great out of the box with zero setup — the built-in database search handles everything by default. If you want faster, typo-tolerant, relevance-ranked search at scale, connect your own Meilisearch instance in a few minutes.

**Setup:**

1. Install and run Meilisearch yourself — self-hosted or via [Meilisearch Cloud](https://www.meilisearch.com/cloud). This plugin does not host or manage a server for you.
2. Go to **Settings → Init Live Search → Meilisearch**, enter your Host URL, Index Name, and a **search-only** API key, then enable the integration.
3. Index your existing content — click **Reindex Now** on the Settings page (runs in the background, no server access needed), or run it yourself:
   ```bash
   wp init-live-search meili-reindex
   ```
4. Done — search requests are now answered by Meilisearch. New, updated, and deleted posts stay in sync automatically.

**Built for safety, not lock-in:**

- If Meilisearch is disabled, unconfigured, or fails to respond, the plugin automatically and silently falls back to the local database search — visitors never see a broken search box.
- The sensitive indexing/admin key can be defined via the `INIT_LIVE_SEARCH_MEILI_ADMIN_KEY` constant in `wp-config.php` instead of the database.
- Turn it off any time — core search behavior never depends on Meilisearch being present.
- Unlike the FULLTEXT index above, background reindexing never starts on its own — Meilisearch is a server you own (and may pay for), so it only runs when you explicitly click **Reindex Now** or use WP-CLI.

## Slash Command Examples

| Command           | Description                                  |
|-------------------|----------------------------------------------|
| `/recent`         | Show latest posts                            |
| `/popular`        | Show most viewed posts (with Init View Count)|
| `/day`            | Most viewed today (requires Init View Count) |
| `/week`           | Most viewed this week                        |
| `/month`          | Most viewed this month                       |
| `/trending`       | Rapidly rising posts based on view growth    |
| `/id 123`         | Jump to post with ID                         |
| `/date 2025/05`   | Posts by month                               |
| `/category wp`    | Filter by category slug                      |
| `/tag seo`        | Filter by tag                                |
| `/product`        | Show all products                            |
| `/on-sale`        | Products currently on sale                   |
| `/stock`          | In-stock products only                       |
| `/sku ABC123`     | Lookup product by SKU                        |
| `/price 100 500`  | Show products in a specific price range      |
| `/coupon`         | Show active and usable coupons               |
| `/fav`            | Show favorite posts                          |
| `/fav_clear`      | Clear all favorites                          |
| `/history`        | Show recent search queries                   |
| `/random`         | Open a random published post instantly       |

## Shortcodes

Easily generate shortcodes using the built-in **Shortcode Builder UI** under *Settings → Init Live Search*. Prefer working in the Block Editor? Each shortcode below has an equivalent block — see [Block Editor (Gutenberg)](#block-editor-gutenberg) above.

### `[init_live_search]`  
Display a search icon or input anywhere that opens the Init Live Search modal.

**Attributes:**
- `type`: `icon` (default) or `input` – choose between a clickable icon or a search box  
- `placeholder`: (optional) text inside the input if `type="input"`  
- `label`: (optional) adds a label next to the icon if `type="icon"`  
- `class`: (optional) add custom classes like `dark`, `my-style`, etc.  
- `stroke_width`: (optional) change SVG stroke width (default: `1`)  
- `radius`: (optional) override border-radius (default: handled via class)

### `[init_live_search_related_posts]`  
Display a list of related posts based on the current post title (or a custom keyword). Static HTML output, SEO-friendly, and fully themable.

**Attributes:**
- `id`: (optional) Post ID to fetch related posts for (defaults to current post)  
- `count`: (optional) Number of posts to display (default: `5`)  
- `keyword`: (optional) Override the keyword for matching  
- `template`: (optional) Layout style — `default`, `grid`, `classic`, `compact`, `thumbright`  
- `css`: `1` (default) or `0` — disable default CSS if styling manually  
- `schema`: `1` (default) or `0` — disable JSON-LD schema output
- `post_type`: (optional) Filter by one or more post types (e.g. `post`, `post,page`)

### `[init_live_search_related_ai]`  
Display a list of AI-powered related posts using multi-signal scoring (tags, series, ACF `same_keyword`, title bigrams, category, views, comments, freshness).  

**Attributes:**
- `id`: (optional) Post ID to fetch related posts for (defaults to current post)  
- `count`: (optional) Number of posts to display (default: `5`)  
- `post_type`: (optional) Restrict results to one or more post types (default: `post`)  
- `template`: (optional) Layout style — `default`, `grid`, `classic`, `compact`, `thumbright`  
- `css`: `1` (default) or `0` — disable default CSS if styling manually  
- `schema`: `1` (default) or `0` — disable JSON-LD schema output  

## Developer Docs

### Filters & API
- [Using filters](https://en.inithtml.com/wordpress/using-filters-in-init-live-search/)
- [REST API reference](https://en.inithtml.com/wordpress/list-of-rest-api-endpoints-in-init-live-search/)

### UI / JavaScript
- [JavaScript events](https://en.inithtml.com/wordpress/how-to-use-javascript-events-ils-in-init-live-search/)
- [Triggering search modal](https://en.inithtml.com/wordpress/all-the-ways-to-trigger-init-live-search-modal-via-javascript/)
- [Custom UI override](https://en.inithtml.com/wordpress/how-to-customize-the-init-live-search-ui-when-disabling-default-css/)
- [Custom start screen](https://en.inithtml.com/wordpress/how-to-create-a-start-screen-for-init-live-search/)

### Slash Commands
- [Slash command guide](https://en.inithtml.com/wordpress/how-to-use-slash-commands-in-init-live-search/)
- [Custom slash command](https://en.inithtml.com/wordpress/create-a-custom-slash-command-for-init-live-search-with-just-3-snippets/)

### Integration
- [Headless/static integration](https://en.inithtml.com/wordpress/integrating-init-live-search-with-headless-wordpress-or-static-sites/)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress admin panel.
2. Activate the plugin through **Plugins → Installed Plugins**.
3. Configure options via **Settings → Init Live Search**.
4. The search modal can be triggered by default through:
   - Focusing any `<input name="s">` field
   - Pressing `Ctrl + /` (or `Cmd + /` on Mac)
   - Triple-clicking anywhere on the page (within 0.5s)
   - Clicking any element with a `data-ils` attribute
   - Visiting a URL with `#search` or `?modal=search&term=...`

## Requirements

- WordPress 6.9 or later (raised from 5.9 in v2.0.0, to support the Abilities API integration)
- PHP 7.4 or later

## License

GPLv2 or later — free to use, extend, or modify.

## Part of Init Plugin Suite

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a set of minimalist, high-performance plugins built for WordPress developers and creators who value speed, flexibility, and clarity.
