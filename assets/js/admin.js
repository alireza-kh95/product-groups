/**
 * Product Groups - Admin JavaScript
 *
 * Provides smooth micro-interactions, live validation, reordering,
 * and copy-to-clipboard functionality.
 *
 * @package ProductGroups
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initApiSelector();
        initMetaBoxRepeater();
        initShortcodeCopy();
    });

    /**
     * Persian number converter for frontend display.
     */
    function toPersianDigits(str) {
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return String(str).replace(/[0-9]/g, function (w) {
            return persian[+w];
        });
    }

    /**
     * Extract product ID from a Digikala URL or string.
     */
    function extractProductId(url) {
        if (!url) return null;
        try {
            url = decodeURIComponent(url.trim());
        } catch (e) {
            url = url.trim();
        }

        const dkpMatch = url.match(/dkp-(\d+)/i);
        if (dkpMatch) return dkpMatch[1];

        const productMatch = url.match(/\/product\/(\d+)/i);
        if (productMatch) return productMatch[1];

        const numMatch = url.match(/^(\d+)$/);
        if (numMatch) return numMatch[1];

        return null;
    }

    /**
     * Initialize Visual API Selector Card switcher.
     */
    function initApiSelector() {
        const cards = document.querySelectorAll('.pg-api-card');
        cards.forEach(function (card) {
            card.addEventListener('click', function () {
                cards.forEach(function (c) {
                    c.classList.remove('is-active');
                });
                card.classList.add('is-active');
                const radio = card.querySelector('.pg-api-radio');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
    }

    /**
     * Initialize Product Items Repeater (Add, Remove, Reorder, Validate).
     */
    function initMetaBoxRepeater() {
        const addBtn = document.getElementById('pg-add-item');
        const emptyAddBtn = document.querySelector('.pg-btn-add-empty');
        const itemsContainer = document.getElementById('pg-items');
        const emptyNotice = document.getElementById('pg-empty-notice');
        const countDisplay = document.getElementById('pg-count-number');

        if (!itemsContainer) return;

        const strings = window.pgAdminStrings || {
            productUrlPlaceholder: 'https://www.digikala.com/product/dkp-...',
            affiliateUrlPlaceholder: 'https://affiliate.digikala.com/...',
            productUrlLabel: 'لینک محصول در دیجیکالا:',
            affiliateUrlLabel: 'لینک افیلیت (همکاری در فروش):',
            removeText: 'حذف',
            copiedText: 'کپی شد! ✓',
            confirmDelete: 'آیا از حذف این محصول اطمینان دارید؟',
            itemTitle: 'محصول',
            idDetected: 'کد کالا: ',
            idNotFound: 'شناسه dkp یافت نشد'
        };

        function updateItemCount() {
            const count = itemsContainer.querySelectorAll('.pg-item').length;
            if (countDisplay) {
                countDisplay.textContent = toPersianDigits(count);
            }
            if (emptyNotice) {
                emptyNotice.style.display = count === 0 ? 'block' : 'none';
            }
        }

        function reindexItems() {
            const items = itemsContainer.querySelectorAll('.pg-item');
            const total = items.length;

            items.forEach(function (item, index) {
                item.setAttribute('data-index', index);

                // Badge Number
                const badge = item.querySelector('.pg-item-index');
                if (badge) {
                    badge.textContent = toPersianDigits(index + 1);
                }

                // Input Names
                const productInput = item.querySelector('input[data-field="product_link"]');
                if (productInput) {
                    productInput.name = `pg_products[${index}][product_link]`;
                    productInput.id = `pg_p_link_${index}`;
                }

                const affiliateInput = item.querySelector('input[data-field="affiliate_link"]');
                if (affiliateInput) {
                    affiliateInput.name = `pg_products[${index}][affiliate_link]`;
                    affiliateInput.id = `pg_a_link_${index}`;
                }

                // Move buttons state
                const moveUp = item.querySelector('.pg-btn-move-up');
                const moveDown = item.querySelector('.pg-btn-move-down');
                if (moveUp) moveUp.disabled = index === 0;
                if (moveDown) moveDown.disabled = index === total - 1;
            });

            updateItemCount();
        }

        function updateIdBadge(input) {
            const item = input.closest('.pg-item');
            if (!item) return;

            const badge = item.querySelector('.pg-detected-id');
            if (!badge) return;

            const val = input.value.trim();
            if (!val) {
                badge.style.display = 'none';
                badge.innerHTML = '';
                return;
            }

            const productId = extractProductId(val);
            if (productId) {
                badge.style.display = 'inline-flex';
                badge.style.background = '#e6f6ec';
                badge.style.color = '#008a20';
                badge.style.borderColor = '#b7ebc7';
                badge.innerHTML = `<span class="dashicons dashicons-yes-alt"></span> ${strings.idDetected}${toPersianDigits(productId)}`;
            } else {
                badge.style.display = 'inline-flex';
                badge.style.background = '#fcf0f0';
                badge.style.color = '#d63638';
                badge.style.borderColor = '#f5c6cb';
                badge.innerHTML = `<span class="dashicons dashicons-warning"></span> ${strings.idNotFound}`;
            }
        }

        function addItem() {
            const index = itemsContainer.querySelectorAll('.pg-item').length;
            const item = document.createElement('div');
            item.classList.add('pg-item');
            item.setAttribute('data-index', index);

            item.innerHTML = `
                <div class="pg-item-header">
                    <div class="pg-item-badge">
                        <span class="pg-item-index">${toPersianDigits(index + 1)}</span>
                        <span class="pg-item-title-label">${strings.itemTitle}</span>
                    </div>
                    <div class="pg-item-tools">
                        <span class="pg-detected-id" style="display:none;"></span>
                        <div class="pg-reorder-group">
                            <button type="button" class="button button-small pg-btn-move pg-btn-move-up" title="انتقال به بالا">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                            <button type="button" class="button button-small pg-btn-move pg-btn-move-down" title="انتقال به پایین">
                                <span class="dashicons dashicons-arrow-down-alt2"></span>
                            </button>
                        </div>
                        <button type="button" class="button pg-remove-item" title="حذف محصول">
                            <span class="dashicons dashicons-trash"></span>
                            <span class="pg-btn-remove-text">${strings.removeText}</span>
                        </button>
                    </div>
                </div>
                <div class="pg-item-body">
                    <div class="pg-field-row">
                        <div class="pg-field-col pg-col-product">
                            <label for="pg_p_link_${index}">
                                <span class="dashicons dashicons-admin-links"></span>
                                ${strings.productUrlLabel}
                                <span class="pg-required">*</span>
                            </label>
                            <div class="pg-input-wrapper">
                                <input type="url"
                                       id="pg_p_link_${index}"
                                       data-field="product_link"
                                       name="pg_products[${index}][product_link]"
                                       placeholder="${strings.productUrlPlaceholder}"
                                       required />
                            </div>
                            <span class="pg-field-hint">شناسه dkp محصول در لینک باید وجود داشته باشد.</span>
                        </div>
                        <div class="pg-field-col pg-col-affiliate">
                            <label for="pg_a_link_${index}">
                                <span class="dashicons dashicons-money-alt"></span>
                                ${strings.affiliateUrlLabel}
                                <span class="pg-required">*</span>
                            </label>
                            <div class="pg-input-wrapper">
                                <input type="url"
                                       id="pg_a_link_${index}"
                                       data-field="affiliate_link"
                                       name="pg_products[${index}][affiliate_link]"
                                       placeholder="${strings.affiliateUrlPlaceholder}"
                                       required />
                            </div>
                            <span class="pg-field-hint">لینکی که کاربر پس از کلیک به آن هدایت می‌شود.</span>
                        </div>
                    </div>
                </div>
            `;

            itemsContainer.appendChild(item);
            reindexItems();

            const firstInput = item.querySelector('input[data-field="product_link"]');
            if (firstInput) {
                firstInput.focus();
            }
        }

        if (addBtn) {
            addBtn.addEventListener('click', addItem);
        }

        if (emptyAddBtn) {
            emptyAddBtn.addEventListener('click', addItem);
        }

        // Live validation on existing and future inputs
        itemsContainer.addEventListener('input', function (e) {
            if (e.target.matches('input[data-field="product_link"]')) {
                updateIdBadge(e.target);
            }
        });

        // Initialize badges on page load
        itemsContainer.querySelectorAll('input[data-field="product_link"]').forEach(function (input) {
            if (input.value) {
                updateIdBadge(input);
            }
        });

        // Actions inside items container (Remove, Move Up, Move Down)
        itemsContainer.addEventListener('click', function (e) {
            const target = e.target;

            // Remove Item
            const removeBtn = target.closest('.pg-remove-item');
            if (removeBtn) {
                const item = removeBtn.closest('.pg-item');
                if (!item) return;

                const productInput = item.querySelector('input[data-field="product_link"]');
                const hasValue = productInput && productInput.value.trim().length > 0;

                if (hasValue && !window.confirm(strings.confirmDelete)) {
                    return;
                }

                item.classList.add('is-removing');
                setTimeout(function () {
                    item.remove();
                    reindexItems();
                }, 280);
                return;
            }

            // Move Up
            const moveUpBtn = target.closest('.pg-btn-move-up');
            if (moveUpBtn && !moveUpBtn.disabled) {
                const item = moveUpBtn.closest('.pg-item');
                const prev = item.previousElementSibling;
                if (prev && prev.classList.contains('pg-item')) {
                    itemsContainer.insertBefore(item, prev);
                    reindexItems();
                    item.style.backgroundColor = '#f0f6fc';
                    setTimeout(function () {
                        item.style.backgroundColor = '';
                    }, 400);
                }
                return;
            }

            // Move Down
            const moveDownBtn = target.closest('.pg-btn-move-down');
            if (moveDownBtn && !moveDownBtn.disabled) {
                const item = moveDownBtn.closest('.pg-item');
                const next = item.nextElementSibling;
                if (next && next.classList.contains('pg-item')) {
                    itemsContainer.insertBefore(next, item);
                    reindexItems();
                    item.style.backgroundColor = '#f0f6fc';
                    setTimeout(function () {
                        item.style.backgroundColor = '';
                    }, 400);
                }
                return;
            }
        });
    }

    /**
     * Initialize 1-click copy for shortcode columns.
     */
    function initShortcodeCopy() {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.pg-copy-shortcode-btn');
            if (!btn) return;

            const shortcode = btn.getAttribute('data-shortcode');
            if (!shortcode) return;

            navigator.clipboard.writeText(shortcode).then(function () {
                const originalText = btn.textContent;
                btn.textContent = 'کپی شد! ✓';
                btn.classList.add('button-primary', 'is-copied');
                setTimeout(function () {
                    btn.textContent = originalText;
                    btn.classList.remove('button-primary', 'is-copied');
                }, 2000);
            }).catch(function (err) {
                console.error('Failed to copy shortcode: ', err);
            });
        });
    }
})();
