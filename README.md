# Init Live Search – Smart, Slash Commands, REST API

> Fast, modern live search for WordPress. REST API-powered with slash commands, SEO-aware search, ACF field support, WooCommerce integration, and custom UI styles.

**Blazing-fast modal search for WordPress — no jQuery, no reloads, no limits.**

[![Version](https://img.shields.io/badge/stable-v1.7.9-blue.svg)](https://wordpress.org/plugins/init-live-search/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init Live Search replaces your default WordPress search with a sleek, fast, command-style modal powered by REST API and modern JavaScript. Navigate your content like a pro — with slash commands, instant results, keyboard shortcuts, tooltip search, and even voice input.

Whether you're building a blog, an eCommerce site, a headless frontend, or a high-performance content portal, this plugin adapts to your workflow.

![Demo of Init Live Search](https://inithtml.com/wp-content/uploads/2025/05/Init-Live-Search-Demo.gif)

## What's New in v1.7.x

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

## Features

- Clean modal search interface (`Ctrl + /`, triple-click, or `data-ils`)
- Powered by WordPress REST API — no `admin-ajax`, no jQuery
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

Easily generate shortcodes using the built-in **Shortcode Builder UI** under *Settings → Init Live Search*.  

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
