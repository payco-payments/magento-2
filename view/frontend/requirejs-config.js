/**
 * Copyright (c) 2024 PAYCO SERVICOS LTDA
 * Licenciado sob a Licença MIT
 * Veja o arquivo LICENSE para mais detalhes
 */

var config = {
    map: {
        '*': {
            'widgetExpiration':'Payco_Payments/js/model/widget-expiration',
            'updateStatus':'Payco_Payments/js/model/update-status'
        }
    },
    paths: {
        paycoSdk: 'Payco_Payments/js/lib/payco-sdk',
    },
    shim: {
        paycoSdk: { exports: "SDK" },
    },
};
