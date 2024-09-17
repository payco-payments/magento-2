/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define( [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                template: 'Payco_Payments/checkout/summary/interest'
            },
            totals: quote.getTotals(),

            /**
             * Check if interest amount is displayed
             *
             * @returns {boolean}
             */
            isDisplayed: function () {
                return this.getPureValue() !== 0;
            },

            /**
             * Get formatted interest value
             *
             * @returns {string}
             */
            getValue: function () {
                return this.getFormattedPrice(this.getPureValue());
            },

            /**
             * Get the pure interest value
             *
             * @returns {number}
             */
            getPureValue() {
                var addition = 0;

                if (this.totals() && totals.getSegment('payco_interest_amount')) {
                    addition = totals.getSegment('payco_interest_amount').value;
                    return addition;
                }

                return addition;
            },
        });
    }
);
