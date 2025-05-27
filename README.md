# Init Live Search

> REST-API-powered live search for WordPress — with slash commands, WooCommerce filters, ACF + SEO metadata support, voice input, and local cache. Built with pure JavaScript.

**Blazing-fast modal search for WordPress — no jQuery, no reloads, no limits.**

[![Version](https://img.shields.io/badge/stable-v1.5.4-blue.svg)](https://wordpress.org/plugins/init-live-search/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![Made with ❤️ in HCMC](https://img.shields.io/badge/Made%20with-%E2%9D%A4%EF%B8%8F%20in%20HCMC-blue)

## Overview

Init Live Search replaces your default WordPress search with a sleek, fast, command-style modal powered by REST API and modern JavaScript. Navigate your content like a pro — with slash commands, instant results, keyboard shortcuts, tooltip search, and even voice input.

Whether you're building a blog, an eCommerce site, a headless frontend, or a high-performance content portal, this plugin adapts to your workflow.

## What's New in v1.5.x

- **Search in SEO Metadata** (v1.5.4):  
  Match search terms against SEO Titles and Meta Descriptions (Yoast, Rank Math, AIOSEO, TSF, SEOPress).  
  Lightweight, filterable, and optionally toggleable via settings.
- **Weighted Result Ranking** (v1.5.4):  
  Control result priority when merging post IDs from title, SEO, tags, etc. via `init_plugin_suite_live_search_weights`.
- **ACF field search** (v1.5.3):  
  Search within specific ACF fields using a comma-separated list (e.g. `company_name, project_code`)
- **Multilingual support** (v1.5.3):  
  Auto-detect WPML/Polylang language and filter results by locale.
- **Smart Tag-Aware Search** (v1.5.2):  
  Combines title + tag matching with keyword and bigram fallback.
- **Quick Search tooltip**:  
  Now works on single-word selections. Configurable limit (1–20 words).
- **WooCommerce integration**:  
  Show prices, badges, stock, and "Add to Cart" buttons via slash commands like `/product`, `/price`, etc.
- **Unified slash command engine** with pagination, infinite scroll, and better keyboard nav
- Smarter caching, reset logic, and consistent modal triggers across devices

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

## Slash Command Examples

| Command           | Description                                  |
|-------------------|----------------------------------------------|
| `/recent`         | Show latest posts                            |
| `/popular`        | Show most viewed posts (with Init View Count)|
| `/id 123`         | Jump to post with ID                         |
| `/date 2025/05`   | Posts by month                               |
| `/category wp`    | Filter by category slug                      |
| `/tag seo`        | Filter by tag                                |
| `/product`        | Show all products                            |
| `/on-sale`        | Products currently on sale                   |
| `/stock`          | In-stock products only                       |
| `/sku ABC123`     | Lookup product by SKU                        |
| `/fav`            | Show favorite posts                          |
| `/fav_clear`      | Clear all favorites                          |
| `/random`         | Open a random published post instantly       |

## Developer Docs

- [Using filters](https://inithtml.com/wordpress/huong-dan-su-dung-cac-filter-trong-init-live-search/)
- [JavaScript events](https://inithtml.com/html-css/huong-dan-su-dung-su-kien-javascript-ils-trong-init-live-search/)
- [Custom UI override](https://inithtml.com/html-css/huong-dan-tuy-chinh-giao-dien-init-live-search-khi-tat-css-mac-dinh/)
- [REST API reference](https://inithtml.com/wordpress/danh-sach-endpoint-rest-api-trong-init-live-search/)
- [Slash command guide](https://inithtml.com/wordpress/huong-dan-su-dung-slash-command-trong-init-live-search/)
- [Triggering search modal](https://inithtml.com/html-css/toan-tap-cac-cach-mo-init-live-search-modal-bang-javascript/)
- [Headless/static integration](https://inithtml.com/wordpress/tich-hop-init-live-search-voi-headless-wordpress-hoac-static-site-nhu-the-nao/)

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

This plugin is part of the [Init Plugin Suite](https://inithtml.com/init-plugin-suite-bo-plugin-wordpress-toi-gian-manh-me-mien-phi/) — a set of minimalist, high-performance plugins built for WordPress developers and creators who value speed, flexibility, and clarity.
