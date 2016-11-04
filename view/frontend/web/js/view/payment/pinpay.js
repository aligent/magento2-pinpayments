/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pinpay',
                component: 'Aligent_Pinpay/js/view/payment/method-renderer/pinpay-hosted'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);