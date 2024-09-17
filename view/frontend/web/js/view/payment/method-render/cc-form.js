define(
    [
        'domReady',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/totals',
        'Magento_Ui/js/lib/view/utils/dom-observer',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'Payco_Payments/js/model/identification',
        'paycoSdk',
        'Payco_Payments/js/model/installments',
        'Payco_Payments/js/lib/jquery.inputmask.min'
    ],
    function (
        domReady,
        $,
        Component,
        creditCardData,
        quote,
        totals,
        domObserver,
        cardNumberValidator,
        fullScreenLoader,
        messageList,
        identification,
        paycoSdk,
        installments
    ) {
        'use strict';

        return Component.extend(
            {
                defaults: {
                    template: 'Payco_Payments/payment/cc-form',
                    code: 'payco_payments_credit_card',
                    active: false,
                    creditCardHolderName: '',
                    selectedInstallment: 1,
                    getInstallmentsValues: [],
                    creditCardToken: null,
                    codeAntiFraud: '',
                    documentCpf: '',
                    creditCardExp: '',
                    creditCardExpMonth: '',
                    creditCardExpYear: '',
                    hasToken: false,
                    amount: '',
                },
                initObservable: function () {
                    this._super()
                        .observe([
                            'active',
                            'creditCardHolderName',
                            'selectedInstallment',
                            'getInstallmentsValues',
                            'creditCardToken',
                            'codeAntiFraud',
                            'documentCpf',
                            'creditCardExp',
                            'creditCardExpMonth',
                            'creditCardExpYear',
                            'hasToken',
                            'amount'
                        ]);

                    return this;
                },
                initInputsMask: function () {
                    const self = this;
                    domReady(function () {
                        domObserver.get('#' + self.getCode() + '_cpf', function (el) {
                            $(el).inputmask({ mask: "999.999.999-99" })
                        })
                        domObserver.get('#' + self.getCode() + '_expiration', function (el) {
                            $(el).inputmask('99/99', { placeholder: "MM/AA" })
                        })
                    })
                },
                initialize: function () {
                    var self = this;

                    this._super();
                    this.initInputsMask();
                    window.quote = quote

                    //Set credit card number to credit card data object
                    this.creditCardNumber.subscribe(function (value) {
                        var result;

                        self.selectedCardType(null);

                        if (value === '' || value === null) {
                            return false;
                        }
                        result = cardNumberValidator(value);

                        if (!result.isPotentiallyValid && !result.isValid) {
                            return false;
                        }

                        if (result.card !== null) {
                            self.selectedCardType(result.card.type);
                            creditCardData.creditCard = result.card;
                        }

                        if (result.isValid) {
                            creditCardData.creditCardNumber = value;
                            self.creditCardType(result.card.title.toLowerCase());
                            self.calculateInterest()
                        }
                    });

                    //Set expiration year to credit card data object
                    this.creditCardExp.subscribe(function (value) {
                        const exp = value.split('/');
                        self.creditCardExpMonth(exp[0])
                        self.creditCardExpYear(exp[1])
                    });

                    //Set holder name to credit card data object
                    this.creditCardHolderName.subscribe(function (value) {
                        creditCardData.creditCardHolderName = value;
                    })

                    //Set verification number to credit card data object
                    this.creditCardVerificationNumber.subscribe(function (value) {
                        creditCardData.cvvCode = value;
                    })

                    this.selectedInstallment.subscribe(function (value) {
                        self.calculateInterest();
                    })

                    self.setAmount();
                    this.getInstallments();

                    let previousDiscountAmount = 0;
                    quote.totals.subscribe((value) => {
                        const additionalSegment = totals.getSegment('payco_interest_amount') ?? null
                        const additionalAmount = additionalSegment && additionalSegment.value ? additionalSegment.value : 0
                        self.amount(value.base_grand_total - additionalAmount)

                        const currentDiscountAmount = value.base_discount_amount;
                        if (previousDiscountAmount !== null && previousDiscountAmount !== currentDiscountAmount) {
                            self.calculateInterest();
                        }
                        previousDiscountAmount = currentDiscountAmount;

                        self.getInstallments();
                    });

                    this.active.subscribe((value) => {
                        if (value === true) {
                            self.setAmount();
                            self.getInstallments();
                            self.calculateInterest();
                        }

                        if (value === false) {
                            self.cleanFields()
                        }
                    });

                    const formInputs = [
                        '#' + self.getCode() + '_cc_type',
                        '#' + self.getCode() + '_holderName',
                        '#' + self.getCode() + '_cc_number',
                        '#' + self.getCode() + '_expiration',
                        '#' + self.getCode() + '_cc_cid'
                    ];

                    domObserver.get('#' + self.getCode() + '_payment-form input', function (el) {
                        $(el).on('input keyup', self.debounce(function () {
                            if (formInputs.every(input => $(input).val()) && $('#' + self.getCode() + '_cc_cid').val().length > 2) {
                                self.generateCardToken();
                            }
                        }, 1500));
                    });
                },

                setAmount(){
                    const additionalSegment = totals.getSegment('payco_interest_amount') ?? null;
                    const additionalAmount = additionalSegment && additionalSegment.value ? additionalSegment.value : 0;
                    this.amount(quote.totals().base_grand_total - additionalAmount);
                },

                getCode: function () {
                    return this.code;
                },

                isActive: function () {
                    const active = this.getCode() === this.isChecked();
                    this.active(active)
                    return active;
                },

                getSelector: function (field) {
                    return '#' + this.getCode() + '_' + field;
                },

                getData: function () {
                    const codeAntifraud = paycoSdk.sessionId;
                    return {
                        'method': this.item.method,
                        'additional_data': {
                            'cc_type': this.creditCardType(),
                            'cc_exp_year': this.creditCardExpYear(),
                            'cc_exp_month': this.creditCardExpMonth(),
                            "cc_number_encrypted": this.formatDisplayCcNumber(this.creditCardNumber()),
                            'cc_number_token': this.creditCardToken(),
                            'code_antifraud': codeAntifraud,
                            'installments': this.selectedInstallment(),
                            'device_info': JSON.stringify(this.getDevice()),
                            'documentCpf': this.showCpf() ? this.documentCpf().replace(/[^\d]+/g, '') : null
                        }
                    };
                },

                getDevice: function () {
                    return paycoSdk.getDeviceInfo();
                },

                generateCardToken: function () {
                    const self = this;
                    fullScreenLoader.startLoader();

                    paycoSdk.tokenize({
                        cardData: {
                            holderName: self.creditCardHolderName(),
                            holderDocument: self.showCpf() ? self.documentCpf().replace(/[^\d]+/g, '') : identification.cpfFromAddress(),
                            number: self.creditCardNumber(),
                            cardBrand: self.creditCardType(),
                            expirationMonth: self.creditCardExpMonth(),
                            expirationYear: self.creditCardExpYear(),
                            cvv: self.creditCardVerificationNumber()
                        }
                    }).then((response) => {
                        let token;

                        if (response.token) {
                            self.creditCardToken(response.token);
                            fullScreenLoader.stopLoader();
                            self.hasToken(true);
                        }

                        if (!response.token) {
                            fullScreenLoader.stopLoader();
                            self.hasToken(false);
                        }
                    }).catch((e) => {
                        fullScreenLoader.stopLoader();

                        self.cleanFields()

                        messageList.addErrorMessage({
                            message: 'Something went wrong. Please check your credit card information.'
                        });

                        console.log('catch', e);
                    });
                },

                cleanFields() {
                    this.hasToken(false);
                    this.creditCardToken(null);
                    this.creditCardHolderName('');
                    this.creditCardNumber('');
                    this.creditCardVerificationNumber('');
                    this.creditCardExp('');
                    this.creditCardExpYear('');
                    this.creditCardExpMonth('');
                    this.creditCardType('');
                    this.selectedCardType(null);
                    this.documentCpf('');
                },

                /**
                 * Prepare credit card number to output
                 * @param {String} number
                 * @returns {String}
                 */
                formatDisplayCcNumber: function (number) {
                    return 'xxxx-' + number.substr(-4);
                },

                showCpf: function () {
                    return identification.showCpf();
                },

                getInstallments: function () {
                    const self = this;
                    if (window.checkoutConfig.payment[this.getCode()].apply_interest) {
                        const amount = self.amount();
                        const response = installments.simulateInstallmentWithInterest(amount);
                        response.then(r => {
                            self.getInstallmentsValues(r);
                        })
                        return;
                    }

                    const maxInstallment = window.checkoutConfig.payment[this.getCode()].max_installments;
                    self.getInstallmentsValues(installments.simulateInstallmentWithoutInterest(quote.totals().grand_total, maxInstallment))
                    return;
                },

                calculateInterest(installment = 1) {
                    if (!window.checkoutConfig.payment[this.getCode()].apply_interest) {
                        return;
                    }
                    const self = this,
                        selectInstallment = self.selectedInstallment() ?? installment,
                        ccType = self.creditCardType();

                    installments.calculateInterest(selectInstallment, ccType)
                },

                debounce: function (func, delay) {
                    let timeoutId;
                    return function () {
                        const context = this;
                        const args = arguments;
                        clearTimeout(timeoutId);
                        timeoutId = setTimeout(() => {
                            func.apply(context, args);
                        }, delay);
                    };
                },

                placeOrder: function (data, event) {
                    const self = this;
                    let hasToken;

                    if (event) {
                        event.preventDefault();
                    }

                    this._super(data, event);
                },
            }
        )
    }
)

