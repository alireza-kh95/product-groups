# Changelog

All notable changes to the **Product Groups** plugin will be documented in this file.

## [1.4.1] - 2026-09-18
### Fixed
- **Negative Cache Removal**: Completely removed negative caching of failed API responses (WP_Error, HTTP non-200, invalid JSON, `status: false`, and empty data) to prevent temporary proxy/WAF blocks (such as 403 Forbidden) from causing product cards to disappear for 3 minutes.
- **Cache Invalidation on Post Save**: Updated `save_meta_box` to invalidate cached products for both previous (old) and newly updated product links across API types.
- **Safe WordPress Error Logging**: Added sanitized `error_log()` reporting with API type, product ID, HTTP status code, and error message, with strict omission of any sensitive data or URLs.
- **Shortcode Error Handling**: Distinguished empty groups ("محصولی برای این گروه تنظیم نشده است.") from temporary API fetch errors ("در حال حاضر امکان دریافت اطلاعات محصولات وجود ندارد. لطفاً بعداً مراجعه کنید.").
- **Strict Numeric ID Extraction**: Ensured Snapp Shop API requests exclusively use the numeric ID portion (`https://apix.snappshop.ir/products/v2/{id}`).

## [1.4.0] - 2026-09-18
### Added
- **اسنپ شاپ (Snapp Shop) Integration**:
  - Added Snapp Shop as a full platform option alongside Digikala Normal and Supermarket.
  - Snapp Shop API integration via `https://apix.snappshop.ir/products/v2/{id}` with automatic extraction of title, image, selling price, RRP, discounts, and inventory status.
  - Regex support for Snapp Shop product URLs (`https://snappshop.ir/product/snp-XXXXX`) and direct IDs (`snp-XXXXX`).
  - Contextual affiliate CTA button labels: automatically displays "بررسی و خرید از اسنپ شاپ" for Snapp Shop products and "بررسی و خرید از دیجیکالا" for Digikala products.
  - Visual Snapp Shop radio card in admin with custom brand color (`#b400ae`), icon, and active status animations.
  - Admin post list column indicator for Snapp Shop groups.
  - Separate transient cache isolation for Snapp Shop products (`_pg_cache_snp_{hash}`).
### Added
- **Plugin Settings Page**: Added a dedicated settings page under `گروه محصولات > تنظیمات` with options to:
  - Select between **Classic (Default)** and **Modern** card styles for public posts.
  - Configure Digikala API cache duration (1h, 3h, 6h, 12h, 24h).
  - 1-Click "پاکسازی تمام کش‌های محصولات" (Purge all product caches).
- **Preserved Default Classic Cards**: Public-facing product cards retain the exact original v1.2 HTML and CSS by default. Modern cards are available as an opt-in via Settings.
- **Admin Area Modernization**: Modern visual cards, animated API selector, smooth reordering (Move Up/Down), live product ID validation, and interactive empty states focused specifically on the admin area.

## [1.3.1] - 2026-09-18
### Added
- **Modern Animated UI & UX**:
  - Smooth hover card animations, multi-layer drop shadows, and image scale-zoom transitions.
  - Animated discount badges with percentage calculation (e.g. ۲۰٪ تخفیف).
  - Modern shopping cart icon with hover slide animation on Digikala buttons.
  - Shimmer wave hover effect on action buttons.
- **Enhanced Admin Experience**:
  - Visual segmented API selector cards for Normal and Supermarket APIs with smooth state animations.
  - Reordering support: Added Move Up (↑) and Move Down (↓) quick-action buttons on product cards.
  - Real-time Product ID validation: Instant detection of Digikala product IDs (`dkp-XXXXX`) as you type or paste.
  - Safe deletion confirmation to prevent accidental card loss when editing.
  - Empty state with quick-add action button and item counter.
- **Rock-Solid Backward Compatibility**:
  - Retained all legacy CSS selectors and DOM structure so existing themes and layouts continue working seamlessly.
  - Enhanced pricing and API parsing fallbacks for both current and past product groups.

## [1.3.0] - 2026-09-18
### Added
- **GitHub Release Updater**: Native WordPress admin updates directly from GitHub releases (`Update URI: https://github.com/alireza-kh95/product-groups`).
- **1-Click Update Checker**: Added "بررسی به‌روزرسانی" (Check for updates) row action on `wp-admin/plugins.php` for instant, non-transient-locked update detection.
- **Transients Caching Engine (`PG_API`)**: Cached Digikala API responses with 6-hour TTL to eliminate synchronous external HTTP requests during frontend page rendering.
- **Automatic Cache Invalidation**: Saving a product group in the admin immediately purges the cached product data for that group.
- **1-Click Shortcode Copy**: Added copy button in the admin post list column with visual confirmation.
- **Automated GitHub Release Workflow**: Added `.github/workflows/release.yml` for automated packaging and asset deployment.
- **Uninstallation Hook (`uninstall.php`)**: Clean database options and transient removal upon plugin deletion.

### Changed
- **Architecture Overhaul**: Refactored from procedural monolithic script to clean, modular OOP structure.
- **Asset Restructuring**: Replaced inline `<script>` tags with dedicated `assets/js/admin.js` and split styles into `assets/css/admin.css` and `assets/css/frontend.css`.
- **Security & Standards**: Strict input sanitization with `esc_url_raw()`, output escaping with `esc_html()`/`esc_url()`, and nonce verification according to WordPress Coding Standards (WPCS).
- **Internationalization**: Full i18n support with `product-groups` text domain.

## [1.2.0]
- Added Supermarket API support.
- Shortcode admin column support.

## [1.0.0]
- Initial release.
