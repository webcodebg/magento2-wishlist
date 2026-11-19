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
                success: function () {
                    self.enableAddToWishlistButton(parent);
                },
                complete: function () {
                    $('body').trigger('processStop');
                },
            }).done($.proxy(this._successHandler, this)).fail($.proxy(this._errorHandler, this));

            event.stopPropagation();
            event.preventDefault();
        },

        _successHandler: function (data) {
            if (data.success) {
                $(this.options.wishlistBlockSelector).replaceWith(data.wishlist);
            }
        },

        _errorHandler: function () {
            console.warn("Add to the wish list unsuccessful");
        },

        /**
         * @param {String} form
         */
        disableAddToWishlistButton: function (form) {
            var addToWishlistButtonTextWhileAdding = this.options.addToWishlistButtonTextWhileAdding,
                addToWishlistButton = $(form).find(this.options.addToWishlistButtonSelector);

            addToWishlistButton.find('span').text(addToWishlistButtonTextWhileAdding);
            addToWishlistButton.attr('title', addToWishlistButtonTextWhileAdding);
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
