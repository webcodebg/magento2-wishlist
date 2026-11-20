/*
 * @package      Webcode_Wishlist
 *
 * @author       Kostadin Bashev (bashev@webcode.bg)
 * @copyright    Copyright © 2021 Webcode Ltd. (https://webcode.bg/)
 * @license      https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

define([
    'jquery',
    'mage/translate',
    'mage/url',
    'mage/loader'

], function ($,$t, url) {
    'use strict';

    $.widget('webcode.wishlist', {
        options: {
            isShowSpinner: true,
            formKeyInputSelector: 'input[name="form_key"]',
            removeFromWishlistSelector: '.block-wishlist .btn-remove',
            wishlistBlockSelector: '#wishlist-view-form',
            addToWishlistButtonSelector: '[data-action="add-to-wishlist"]',
            addToWishlistButtonAddedClass: 'added',
            addToWishlistButtonTextWhileAdding: $t('Adding...'),
            addToWishlistButtonTextAdded: $t('Added to Wish List')
        },

        _create: function () {
            this._bind();
            this.viewWishlist();
        },

        viewWishlist: function () {
            $(document).on('click', "#wishlist_checkout", function () {
                window.location.replace(url.build('wishlist'));
            })
        },

        _bind: function () {
            var selectors = [
                    this.options.addToWishlistButtonSelector,
                    this.options.removeFromWishlistSelector
                ];

            $('body').on('click', selectors.join(','), $.proxy(this._processViaAjax, this));
        },

        _processViaAjax: function (event) {
            var self = this,
                post = $(event.currentTarget).data('post'),
                url = post.action,
                parent = $(event.currentTarget).parent(),
                data = $.extend(post.data, {form_key: $(this.options.formKeyInputSelector).val()});

            $.ajax(url, {
                method: 'POST',
                data: data,
                beforeSend: function () {
                    $('body').trigger('processStart');
                    self.disableAddToWishlistButton(parent);
                },
                complete: function () {
                    $('body').trigger('processStop');
                },
            }).done(function (response) {
                self._successHandler(response, parent);
            }).fail(function () {
                self._errorHandler(parent);
            });

            event.stopPropagation();
            event.preventDefault();
        },

        /**
         * The module answers the AJAX wishlist actions with JSON: {"success": true}
         * (with the refreshed wishlist block) when done, {"success": false} when not —
         * "not_logged_in" for a guest, "not_saved" when Magento refused the action.
         * Only an explicit refusal means "not added": an answer of another shape (a
         * theme or module replacing the response) keeps the old behaviour.
         *
         * @param {Object|String} data
         * @param {jQuery} form
         */
        _successHandler: function (data, form) {
            if (data && data.success === false) {
                this.resetAddToWishlistButton(form);

                if (data.error === 'not_logged_in') {
                    // Same as Magento without AJAX: the guest logs in, then the core
                    // completes the add it kept in the session.
                    window.location.href = url.build('customer/account/login');
                }

                return;
            }

            this.enableAddToWishlistButton(form);

            if (data && data.success && data.wishlist) {
                $(this.options.wishlistBlockSelector).replaceWith(data.wishlist);
            }
        },

        /**
         * @param {jQuery} form
         */
        _errorHandler: function (form) {
            this.resetAddToWishlistButton(form);
            console.warn('Add to the wish list unsuccessful');
        },

        /**
         * @param {String} form
         */
        disableAddToWishlistButton: function (form) {
            var addToWishlistButtonTextWhileAdding = this.options.addToWishlistButtonTextWhileAdding,
                addToWishlistButton = $(form).find(this.options.addToWishlistButtonSelector);

            if (addToWishlistButton.data('wishlistText') === undefined) {
                addToWishlistButton.data('wishlistText', addToWishlistButton.find('span').text());
                addToWishlistButton.data('wishlistTitle', addToWishlistButton.attr('title'));
            }

            addToWishlistButton.find('span').text(addToWishlistButtonTextWhileAdding);
            addToWishlistButton.attr('title', addToWishlistButtonTextWhileAdding);
        },

        /**
         * Puts back the text and title the button had before the request.
         *
         * @param {jQuery} form
         */
        resetAddToWishlistButton: function (form) {
            var addToWishlistButton = $(form).find(this.options.addToWishlistButtonSelector);

            if (addToWishlistButton.data('wishlistText') !== undefined) {
                addToWishlistButton.find('span').text(addToWishlistButton.data('wishlistText'));

                if (addToWishlistButton.data('wishlistTitle') === undefined) {
                    // The button had no title (the product page one): do not leave title="".
                    addToWishlistButton.removeAttr('title');
                } else {
                    addToWishlistButton.attr('title', addToWishlistButton.data('wishlistTitle'));
                }
            }
        },

        /**
         * @param {String} form
         */
        enableAddToWishlistButton: function (form) {
            var addToWishlistButtonTextAdded = this.options.addToWishlistButtonTextAdded,
                addToWishlistButton = $(form).find(this.options.addToWishlistButtonSelector);

            addToWishlistButton.addClass(this.options.addToWishlistButtonAddedClass);
            addToWishlistButton.find('span').text(addToWishlistButtonTextAdded);
            addToWishlistButton.attr('title', addToWishlistButtonTextAdded);
        }
    });

    return $.webcode.wishlist;
});
