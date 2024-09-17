define([
        'Payco_Payments/js/view/checkout/summary/interest'
    ], function (Component) {
        'use strict';

        return Component.extend({
            /**
             * Check if interest amount is displayed
             *
             * @returns {boolean}
             */
            isDisplayed: function () {
                return this.getPureValue() !== 0;
            }
        });
    }
);
