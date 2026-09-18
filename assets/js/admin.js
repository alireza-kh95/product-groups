/**
 * Product Groups - Admin JavaScript
 *
 * @package ProductGroups
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initMetaBox();
        initShortcodeCopy();
    });

    /**
     * Initialize Meta Box repeater functionality.
     */
    function initMetaBox() {
        const addBtn = document.getElementById('pg-add-item');
        const itemsContainer = document.getElementById('pg-items');
        const emptyNotice = document.getElementById('pg-empty-notice');

        if (!addBtn || !itemsContainer) {
            return;
        }

        function updateEmptyState() {
            if (!emptyNotice) return;
            if (itemsContainer.children.length === 0) {
                emptyNotice.style.display = 'block';
            } else {
                emptyNotice.style.display = 'none';
            }
        }

        function reindexItems() {
            const items = itemsContainer.querySelectorAll('.pg-item');
            items.forEach((item, index) => {
                const badge = item.querySelector('.pg-item-index');
                if (badge) {
                    badge.textContent = index + 1;
                }

                const productInput = item.querySelector('input[data-field="product_link"]');
                if (productInput) {
                    productInput.name = `pg_products[${index}][product_link]`;
                }

                const affiliateInput = item.querySelector('input[data-field="affiliate_link"]');
                if (affiliateInput) {
                    affiliateInput.name = `pg_products[${index}][affiliate_link]`;
                }
            });
            updateEmptyState();
        }

        addBtn.addEventListener('click', function () {
            const index = itemsContainer.querySelectorAll('.pg-item').length;
            const item = document.createElement('div');
            item.classList.add('pg-item');

            const strings = window.pgAdminStrings || {
                productUrlPlaceholder: 'https://www.digikala.com/product/dkp-...',
                affiliateUrlPlaceholder: 'https://...',
                productUrlLabel: 'لینک محصول دیجیکالا:',
                affiliateUrlLabel: 'لینک افیلیت:',
                removeText: 'حذف'
            };

            item.innerHTML = `
                <div class="pg-item-index">${index + 1}</div>
                <div class="pg-item-fields">
                    <div class="pg-item-field">
                        <label>${strings.productUrlLabel}</label>
                        <input type="url" 
                               data-field="product_link"
                               name="pg_products[${index}][product_link]" 
                               placeholder="${strings.productUrlPlaceholder}" 
                               required />
                    </div>
                    <div class="pg-item-field">
                        <label>${strings.affiliateUrlLabel}</label>
                        <input type="url" 
                               data-field="affiliate_link"
                               name="pg_products[${index}][affiliate_link]" 
                               placeholder="${strings.affiliateUrlPlaceholder}" 
                               required />
                    </div>
                </div>
                <button type="button" class="button pg-remove-item">${strings.removeText}</button>
            `;

            itemsContainer.appendChild(item);
            reindexItems();

            const firstInput = item.querySelector('input');
            if (firstInput) {
                firstInput.focus();
            }
        });

        itemsContainer.addEventListener('click', function (e) {
            const removeBtn = e.target.closest('.pg-remove-item');
            if (removeBtn) {
                const item = removeBtn.closest('.pg-item');
                if (item) {
                    item.remove();
                    reindexItems();
                }
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
                btn.textContent = 'کپی شد!';
                btn.classList.add('button-primary');
                setTimeout(function () {
                    btn.textContent = originalText;
                    btn.classList.remove('button-primary');
                }, 1800);
            }).catch(function (err) {
                console.error('Failed to copy shortcode: ', err);
            });
        });
    }
})();
