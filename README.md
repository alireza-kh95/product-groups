# Product Groups (Link + Affiliate)

A modern, high-performance WordPress plugin for creating and displaying responsive Digikala product cards and product groups with affiliate links.

![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/License-GPLv2-green.svg)

---

## Features

- **Custom Post Type**: Easily manage product groups in their own dedicated admin menu.
- **Dual API Support**: Supports both Digikala **Normal** (`api.digikala.com/v2/`) and **Supermarket** (`api.digikala.com/fresh/v1/`) endpoints.
- **High-Performance Transient Caching**: Eliminates slow page loads by caching API product responses in WordPress transients with configurable TTL, preventing repetitive external HTTP requests.
- **Cache Invalidation**: Automatically flushes cached product data whenever a product group is saved or updated.
- **Plugin Settings Page**: Choose between **Classic (Default)** and **Modern** card styles, configure cache duration, and purge cache with 1-click under `گروه محصولات > تنظیمات`.
- **Preserved Default Classic Cards**: Public-facing product cards maintain the original v1.2 design and markup by default for 100% backward compatibility.
- **Modernized Admin UI & UX**: Visual animated API selector cards, product cards with live Digikala ID validation, and smooth reordering (Move Up / Move Down).
- **1-Click Shortcode Copy**: Copy shortcodes with one click directly from the admin post listing table.
- **Seamless GitHub Updates**: Native WordPress updater integrated with GitHub Releases.
  - Includes a **1-Click "Check for updates"** link on the WordPress Plugins screen.
  - Updates are **not tied to removing transients**: force-checks instantly query GitHub, refresh WordPress core transients, and display notifications.
  - Supports both public repositories and private repositories (via `PRODUCT_GROUPS_GITHUB_TOKEN`).

---

## Installation

1. Download `product-groups.zip` from the [Latest Release](https://github.com/alireza-kh95/product-groups/releases/latest).
2. In your WordPress admin dashboard, navigate to **Plugins > Add New > Upload Plugin**.
3. Choose the zip file and click **Install Now**, then **Activate**.

---

## Usage

1. Go to **گروه محصولات > افزودن گروه جدید**.
2. Enter a title for the product group (e.g., "بهترین گوشی‌های هوشمند").
3. Choose the **API Type** (معمولی or سوپرمارکتی).
4. Click **افزودن محصول جدید** and enter:
   - **لینک محصول دیجیکالا**: e.g., `https://www.digikala.com/product/dkp-123456/`
   - **لینک افیلیت**: your custom affiliate tracking URL.
5. Publish or update the post.
6. Copy the shortcode `[product_group id="X"]` and place it in any post, page, or widget.

---

## Automatic Updates

This plugin receives updates directly from GitHub releases:
- When visiting **Dashboard > Updates** or **Plugins**, updates are detected automatically.
- To check for updates immediately at any time, click **بررسی به‌روزرسانی (Check for updates)** under the plugin name in the Plugins list. This bypasses all caching and contacts GitHub in real time.

---

## Developer Documentation

### Filters

- `product_groups_api_cache_ttl` *(int)*: Filter cache expiration time for Digikala product data (default: 6 hours).
- `product_groups_github_token` *(string|null)*: Filter GitHub personal access token for private repository releases.
