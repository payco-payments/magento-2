define( [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ], function (
        Component,
        renderList
    ) {
        'use strict';

        renderList.push(
            {
                type: 'payco_payments_credit_card',
                component: 'Payco_Payments/js/view/payment/method-render/cc-form'
            }
        );

        return Component.extend({})
    }
)
