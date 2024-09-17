define([
    'jquery',
    'mage/storage',
    'jquery-ui-modules/widget'
], function ($, storage) {
    'use strict';

    $.widget('mage.updateStatus', {
        /**
         * Create.
         *
         * @returns {void}
         */
        _create() {
            this._super();

            this._init();
        },

        /**
         * Init Update Status.
         *
         * @returns {void}
         */
        _init() {
            const self = this,
                element = $(self.element);

            self.requestUpdate()

        },

        /**
         * Request Update
         */
        requestUpdate() {
            const self = this,
                element = $(self.element),
                url = 'payco/checkout/status?transactionId=' + this.options.transactionId;
            storage.get(url)
                .done(response => {
                    const statusLabel = element.find('#status-container span');
                    if (typeof response === "object") {
                        statusLabel.html(response.status.label);
                        $('#status-container').removeClass().addClass(response.status.value);
                        $('.payco-payments-pix .details').attr('data-status',response.status.value)
                        if (['paid', 'expired', 'failed'].includes(response.status.value)) {
                            if (this.options.expirationContainer) {
                                $(this.options.expirationContainer).remove();
                            }
                            return;
                        }
                    }

                    setTimeout(() => self.requestUpdate(), 5000)
                })
        }
    });

    return $.mage.updateStatus;
});
