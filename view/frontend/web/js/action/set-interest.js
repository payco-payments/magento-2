define([
  'jquery',
  'Magento_Checkout/js/model/full-screen-loader',
  'Magento_Checkout/js/action/get-totals',
  'Magento_Checkout/js/model/error-processor',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/totals',
  'Magento_Checkout/js/model/url-builder',
  'Magento_Customer/js/model/customer',
  'mage/url'
], function (
  $,
  fullScreenLoader,
  getTotalsAction,
  errorProcessor,
  quote,
  totals,
  urlBuilder,
  customer,
  urlFormatter
) {
  'use strict';
  return {
    calculateInterest(
      selectedInstallment,
      ccType
    ) {
      let requestUrl,
        cartId = quote.getQuoteId(),
        payload;

      if (!customer.isLoggedIn()) {
        requestUrl = urlBuilder.createUrl('/guest-carts/:cartId/payco/set-interest', {
          cartId: cartId
        });
      } else {
        requestUrl = urlBuilder.createUrl('/carts/mine/payco/set-interest', {});
      }

      payload = {
        cartId: cartId,
        selectedInstallment: selectedInstallment,
        brand: ccType
      }
      fullScreenLoader.startLoader();
      $.ajax({
        url: urlFormatter.build(requestUrl),
        data: JSON.stringify(payload),
        global: false,        
        contentType: 'application/json',
        type: 'POST',
        async: true
      }).done(
        () => {
          var deferred = $.Deferred();
          getTotalsAction([], deferred);
          fullScreenLoader.stopLoader();
        }
      ).fail(
        (response) => {
          errorProcessor.process(response);
          fullScreenLoader.stopLoader();
        }
      );
    }
  }
});