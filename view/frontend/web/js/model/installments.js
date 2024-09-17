define([
  'jquery',
  'mage/translate',
  'mage/url',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/url-builder',
  'Magento_Catalog/js/price-utils',
  'Payco_Payments/js/action/set-interest'
], function ($, $t, url, quote, urlBuilder, priceUtils, setInterest) {
  'use strict';

  return {
    /**
     * Calculate the interest amount
     * @param {Number} installment 
     * @param {String} ccType 
     * @returns {Boolean}
     */
    calculateInterest: function (installment, ccType) {
      if(!installment || !ccType){
        return;
      }

      setInterest.calculateInterest(installment, ccType);
    },
    /**
     * Fetch the installment with interest
     * @param {Number} amount 
     * @returns {Array}
     */
    simulateInstallmentWithInterest: function (amount) {
      let installments,
        requestUrl;

      requestUrl = urlBuilder.createUrl('/payco/interest/simulate', {})
      return $.ajax({
        url: url.build(requestUrl),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          amount: amount,
        })
      })
    },

    simulateInstallmentWithoutInterest: function (amount, maxInstallment) {
      let installments = [];
      
      function truncatePrice(price, precision) {
        const factor = Math.pow(10, precision);
        return Math.floor(price * factor) / factor;
    }

      for (let i = 1; i <= maxInstallment; i++) {
        let installmentValue = amount / i;
        installments.push({
          value: i,
          label: $t("%1x of %2 (without interest)").replace('%1', i).replace('%2',  priceUtils.formatPrice(truncatePrice(installmentValue,2), quote.getPriceFormat()))
        });
      }
      return installments;
    }
  };
});

