define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe'
    ],
    function ($, Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aligent_Pinpay/payment/pinpay-hosted',
                timeoutMessage: 'Sorry, but something went wrong. Please contact the seller.'
            },
            placeOrderHandler: null,
            validateHandler: null,

            /**
             * @param {Object} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            /**
             * @param {Object} handler
             */
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            /**
             * @returns {Object}
             */
            context: function () {
                return this;
            },

            /**
             * @returns {Boolean}
             */
            isShowLegend: function () {
                return true;
            },

            /**
             * @returns {String}
             */
            getCode: function () {
                return 'pinpay';
            },

            /**
             * @returns {Boolean}
             */
            isActive: function () {
                return true;
            }
        });
    }
);
