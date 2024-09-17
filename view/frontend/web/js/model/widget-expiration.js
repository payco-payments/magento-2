define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.widgetExpiration', {
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
         * Init Countdown.
         *
         * @returns {void}
         */
        _init() {
            const self = this,
                element = $(self.element),
                expirationDate = self.options.expirationDate;

            const interval = setInterval(() => {
                const dateNow = new Date();
                const expirationDateFormated = new Date(expirationDate);
                const difference = expirationDateFormated.getTime() - dateNow.getTime();

                if (difference <= 0) {

                    // Timer done
                    clearInterval(interval);

                } else {
                    let seconds = Math.floor(difference / 1000);
                    let minutes = Math.floor(seconds / 60);
                    let hours = Math.floor(minutes / 60);
                    let days = Math.floor(hours / 24);


                    hours %= 24;
                    minutes %= 60;
                    seconds %= 60;


                    if (seconds < 10) {
                        seconds = '0' + seconds;
                    }

                    if (minutes < 10) {
                        minutes = '0' + minutes;
                    }

                    if (hours < 10) {
                        hours = '0' + hours;
                    }

                    const result = hours + ':' + minutes + ':' + seconds

                    element.find('.timer span').html(result)
                }
            }, 1000);
        }
    });

    return $.mage.widgetExpiration;
});
