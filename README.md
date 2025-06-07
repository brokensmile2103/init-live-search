# Init Live Search – Smart, Slash Commands, REST API

> Fast, modern live search for WordPress. REST API-powered with slash commands, SEO-aware search, ACF field support, WooCommerce integration, and custom UI styles.

**Blazing-fast modal search for WordPress — no jQuery, no reloads, no limits.**

[![Version](https://img.shields.io/badge/stable-v1.6.8-blue.svg)](https://wordpress.org/plugins/init-live-search/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init Live Search replaces your default WordPress search with a sleek, fast, command-style modal powered by REST API and modern JavaScript. Navigate your content like a pro — with slash commands, instant results, keyboard shortcuts, tooltip search, and even voice input.

Whether you're building a blog, an eCommerce site, a headless frontend, or a high-performance content portal, this plugin adapts to your workflow.

![Demo of Init Live Search](https://inithtml.com/wp-content/uploads/2025/05/Init-Live-Search-Demo.gif)

## What's New in v1.6.x

- **Smart Excerpts with Highlight**: 1-line contextual snippets extracted from excerpt or content, with keyword highlighting
- **Single Word Fallback**: automatically splits search terms into individual words if no results found  
- **SEO Metadata Matching**: checks SEO Titles and Meta Descriptions for matches during fallback  
- **Weighted Scoring Improvements**: `title_excerpt` and `title_content` modes now rank results using title > excerpt > content logic
- **New Slash Commands**: `/day`, `/week`, `/month` show most viewed posts by time range (requires Init View Count)
- **Search History Commands**: recall recent queries with `/history` and clear them with `/history_clear`
- **Result Click Tracking**: new JS event `ils:result-clicked` to track user interactions with search results
- **Custom Slash Command Filter**: use `init_plugin_suite_live_search_commands` to define your own commands (plus JS event handling)
- **Default Slash Command**: preload `/recent`, `/related`, `/popular`, or `/read` on modal open
- **Smart Detection Mode**: auto-select slash command based on current page (post, category, tag, shop, etc.)
- **UI Style Presets**: choose between `style-full.css` (fullscreen) and `style-topbar.css` (admin-bar style)
- **Theme CSS Override**: drop a `style.css` file into your theme’s `init-live-search/` folder to override styles
- **Full CSS Control**: disable all built-in CSS for complete customization
- **Search Analytics**: log keyword queries (term, source, timestamp), group by frequency, and export CSV — no personal data collected
- **Updated Settings UI**: choose styles, manage analytics, and tweak options from a polished, responsive panel
- **Scoped Style & File Refactor**: improved loading logic and renamed files (`analytics.php` → `tracking.php`) for clarity
- **Modular Result Engine**: the main `get_results()` logic is now cleanly separated into sub-functions for better performance and extensibility

## Features

- Clean modal search interface (`Ctrl + /`, triple-click, or `data-ils`)
- Powered by WordPress REST API — no `admin-ajax`, no jQuery
- **Search in SEO Metadata** — match keywords in SEO Titles and Meta Descriptions (Yoast, Rank Math, AIOSEO, TSF, SEOPress)
- **Weighted Result Ranking** — control merging priority using custom filter (e.g. title > SEO > tags)
- Smart **Slash Commands**: `/recent`, `/fav`, `/id`, `/tag`, `/product`, `/on-sale`, `/sku`, `/price`, etc.
- **Quick Search Tooltip**: select up to 20 words to trigger instant search (configurable)
- LocalStorage-based **favorites** and **caching**
- Voice input via native `SpeechRecognition` API
- Full **keyboard navigation**: `↑ ↓ ← → Enter Esc`
- Deep linking: prefill search via `?modal=search&term=...`
- Dark mode support: `auto`, `dark`, or `light`
- **WooCommerce integration**: show price, stock, sale badges, "Add to Cart"
- **ACF support**: search custom fields with comma-separated keys
- Developer-friendly: hooks, filters, events, modular JS, REST-first architecture
- Built-in Analytics: track search queries (term, count, source) and export CSV (optional)

## Slash Command Examples

| Command           | Description                                  |
|-------------------|----------------------------------------------|
| `/recent`         | Show latest posts                            |
| `/popular`        | Show most viewed posts (with Init View Count)|
| `/day`            | Most viewed today (requires Init View Count) |
| `/week`           | Most viewed this week                        |
| `/month`          | Most viewed this month                       |
| `/id 123`         | Jump to post with ID                         |
| `/date 2025/05`   | Posts by month                               |
| `/category wp`    | Filter by category slug                      |
| `/tag seo`        | Filter by tag                                |
| `/product`        | Show all products                            |
| `/on-sale`        | Products currently on sale                   |
| `/stock`          | In-stock products only                       |
| `/sku ABC123`     | Lookup product by SKU                        |
| `/price 100 500`  | Show products in a specific price range      |
| `/fav`            | Show favorite posts                          |
| `/fav_clear`      | Clear all favorites                          |
| `/history`        | Show recent search queries                   |
| `/random`         | Open a random published post instantly       |

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

## License

GPLv2 or later — free to use, extend, or modify.

## Part of Init Plugin Suite

This plugin is part of the [Init Plugin Suite](https://en.inithtml.com/init-plugin-suite-minimalist-powerful-and-free-wordpress-plugins/) — a set of minimalist, high-performance plugins built for WordPress developers and creators who value speed, flexibility, and clarity.
