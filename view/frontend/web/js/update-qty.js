define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return function () {
        var cart = customerData.get('cart');

        cart.subscribe(function (data) {
            console.log(data.items.length);
        });
    }
});
