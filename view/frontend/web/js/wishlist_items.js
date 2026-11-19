define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'mage/translate',
    'domReady'
], function ($, Component, customerData,$t) {
    'use strict';

    return Component.extend({
        /** @inheritdoc */
        initialize: function () {
            let _this = this;

            this._super();
            this.wishlist = customerData.get('wishlist');
            this.wishlist.subscribe(function (newValue) {
                _this.decorateItems();
            });
            _this.decorateItems();
        },

        decorateItems: function () {

            let items = this.wishlist().items;
            if (typeof items === 'undefined' || !items.length) {
                return;
            }
            $('.action.towishlist[data-post]').each(function () {
                var data = $(this).data('post'),
                    i;
                for (i = 0; i < items.length; i++) {
                    if (data.data.product == items[i].product_id) {
                        $(this).addClass('added');
                        $(this).attr('title', $t('Added to Wish List'));
                        $(this).children().text($t('Added to Wish List'))
                    }
                }
            });
        }
    });
});
