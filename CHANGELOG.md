# Changelog

All notable changes to the **Product Groups** plugin will be documented in this file.

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
