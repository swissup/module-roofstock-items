define([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    'use strict';

    return function () {
        var cart = customerData.get('cart');

        cart.subscribe(function (cartData) {

            if (cartData && cartData.summary_count !== cartData.items.length) {

                var local_storage = window.localStorage,
                    cashStorage = JSON.parse(local_storage.getItem('mage-cache-storage')) || {};

                if (cashStorage) {
                    cashStorage.cart.summary_count = cartData.items.length;
                    local_storage.setItem('mage-cache-storage', JSON.stringify(cashStorage));
                }

                return this;
            }
        });
    }
});
