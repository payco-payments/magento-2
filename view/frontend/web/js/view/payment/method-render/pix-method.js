define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Payco_Payments/js/model/identification',
        'domReady',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'Payco_Payments/js/lib/jquery.inputmask.min'
    ],
    function (
        Component,
        $,
        identification,
        domReady,
        domObserver
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Payco_Payments/payment/pix',
                    form: 'Payco_Payments/payment/identification-fields',
                    code: 'payco_payments_pix',
                    documentCpf: ''
                },

                initObservable: function () {
                    this._super()
                        .observe([
                            'documentCpf',
                        ]);
                    return this;
                },
                initInputsMask: function () {
                    const self = this;
                    domReady(function () {
                        domObserver.get('#' + self.getCode() + '_cpf', function (el) {
                            $(el).inputmask({mask: "999.999.999-99"})
                        })
                    })
                },
                initialize: function () {
                    this._super();
                    this.initInputsMask();
                },

                getCode: function () {
                    return this.code;
                },

                getData: function () {
                    return {
                        'method': this.getCode(),
                        'additional_data': {
                            'document_cpf': this.showCpf() ? this.documentCpf().replace(/[^\d]+/g, ''): null,
                        }
                    }
                },

                showCpf: function () {
                    return identification.showCpf()
                },

                validate: function () {
                    var $form = $('#' + this.getCode() + '-form');
                    return $form.validation() && $form.validation('isValid');
                }
            }
        )
    }
)
