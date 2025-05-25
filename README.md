# Init Live Search

**Blazing-fast modal search for WordPress — no jQuery, no reloads, no limits.**

[![Version](https://img.shields.io/badge/stable-v1.5.0-blue.svg)](https://wordpress.org/plugins/init-live-search/)
[![License](https://img.shields.io/badge/license-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Overview

Init Live Search replaces your default WordPress search with a sleek, fast, command-style modal powered by REST API and modern JavaScript. Navigate your content like a pro — with slash commands, instant results, keyboard shortcuts, tooltip search, and even voice input.

Whether you're building a blog, a headless frontend, or a high-performance content site, this plugin adapts to your workflow.

## Features

- Modal search interface (Ctrl + /, triple-click, or `data-ils`)
- REST API-powered — no admin-ajax
- Slash commands: `/recent`, `/fav`, `/id`, `/tag`, ...
- Tooltip-based Quick Search (select text → click)
- LocalStorage-powered favorites and caching
- Voice input via `SpeechRecognition` API
- Keyboard navigation: ↑ ↓ → ← Enter Esc
- Deep linking: open modal with `?modal=search&term=/recent`
- Adaptive dark mode (`auto`, `dark`, `light`)
- Developer-friendly: filters, events, modular JS, REST-first design

## Slash Command Examples

| Command         | Description                                  |
|-----------------|----------------------------------------------|
| `/recent`       | Show latest posts                            |
| `/popular`      | Show most viewed posts (with Init View Count)|
| `/id 123`       | Jump to post with ID                         |
| `/date 2025/05` | Posts by month                               |
| `/category wp`  | Filter by category slug                      |
| `/tag seo`      | Filter by tag                                |
| `/fav`          | Show favorite posts                          |
| `/fav_clear`    | Clear all favorites                          |

## Developer Docs

- [Using filters](https://inithtml.com/wordpress/huong-dan-su-dung-cac-filter-trong-init-live-search/)
- [JavaScript events](https://inithtml.com/html-css/huong-dan-su-dung-su-kien-javascript-ils-trong-init-live-search/)
- [Custom UI override](https://inithtml.com/html-css/huong-dan-tuy-chinh-giao-dien-init-live-search-khi-tat-css-mac-dinh/)
- [REST API reference](https://inithtml.com/wordpress/danh-sach-endpoint-rest-api-trong-init-live-search/)
- [Slash command guide](https://inithtml.com/wordpress/huong-dan-su-dung-slash-command-trong-init-live-search/)
- [Triggering search modal](https://inithtml.com/html-css/toan-tap-cac-cach-mo-init-live-search-modal-bang-javascript/)
- [Headless/static integration](https://inithtml.com/wordpress/tich-hop-init-live-search-voi-headless-wordpress-hoac-static-site-nhu-the-nao/)

## Installation

1. Upload to `/wp-content/plugins/init-live-search/` or install via admin
2. Activate the plugin
3. Go to **Settings → Init Live Search** and configure
4. Modal search will auto-attach to all `<input name="s">` fields

## License

GPLv2 or later — free to use, extend, or modify.

## Part of Init Plugin Suite

This plugin is part of the [Init Plugin Suite](https://inithtml.com/init-plugin-suite-bo-plugin-wordpress-toi-gian-manh-me-mien-phi/) — a set of minimalist, high-performance plugins built for WordPress developers and creators who value speed, flexibility, and clarity.
