define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/action/redirect-on-success'
    ],
    function ($, Component, fullScreenLoader, placeOrderAction, checkoutData, redirectOnSuccessAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aligent_Pinpay/payment/pinpay-hosted',
                timeoutMessage: 'Sorry, but something went wrong. Please contact the seller.'
            },
            card_token: '',
            placeOrderHandler: null,
            validateHandler: null,
            iframe: null,
            token_element_selector: null,

            initialize: function () {
                this._super();
                this.checkoutData = checkoutData;
                this.token_element_selector = "#pinpay_card_token";
                this.setValidateHandler(function(){return true;});//todo
                this.setPlaceOrderHandler($.proxy(function(){
                    if(this.iframe.length > 0) {
                        this.iframe[0].contentWindow.postMessage('set-token', "*");
                    }
                }, this));
            },
            /**
             * @param {Object} handler
             */
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            initIframe: function() {
                this.iframe = $("#pinpay-transparent-iframe");
                if(this.iframe.length > 0){
                    this.iframe.on("load", $.proxy(this.handleIframeLoad, this));
                }
                $(window).on('message', $.proxy(this.handleMessage, this));
            },
            handleIframeLoad: function() {
                this.iframe[0].contentWindow.postMessage(JSON.stringify(this.getConfig()), "*");
            },
            handleMessage: function(msg) {
                //Pull originalEvent from resulting jQuery wrapper
                var msgEvent = msg.originalEvent;
                if(msgEvent.origin === 'https://cdn.pin.net.au')
                {
                    this.card_token = msgEvent.data;
                    placeOrderAction(this.getData(), this.messageContainer);
                    this.isPlaceOrderActionAllowed(true);
                    fullScreenLoader.stopLoader(true);
                }
            },
            /**
             * @param {Object} handler
             */
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },
            /**
             * @override
             */
            placeOrder: function () {
                var self = this;

                if (this.validateHandler()) {
                    this.isPlaceOrderActionAllowed(false);
                    fullScreenLoader.startLoader();
                    self.placeOrderHandler();//Trigger pinpay request inside iframe
                    redirectOnSuccessAction.execute();
                }
            },

            /**
             * @returns {Object}
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'card_token': this.card_token
                    }
                };
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
            },

            getSource: function() {
                return window.checkoutConfig.payment.pinpay.source;
            },

            getConfig: function() {
                var _addressData = this.checkoutData.getBillingAddressFromData();
                if(typeof(_addressData) === 'undefined'){
                    _addressData = this.checkoutData.getShippingAddressFromData();
                }
                return {
                    config: {
                        style: [".CardFields--address_line1 { display: none; }",".CardFields--address_line2 { display: none; }",".CardFieldsGroup--city-state-postcode { display: none; }", ".CardFields input { font-size: 18px; }", ".CardFields--expiry input { width: 90px; }", ".CardFields--cvc input { width: 90px; }", ".CardFields--expiry, .CardFields--cvc {display: inline-block; }", ".Errors { color: red; font-size: 12px }", ".CardFields label:after { content: ':'; }", ".CardFields--expiry { margin-right: 20px}"],
                        api_env: window.checkoutConfig.payment.pinpay.mode,
                        api_key: window.checkoutConfig.payment.pinpay.apiKey,
                        values: {
                            address_line1: _addressData.street[0],
                            address_line2: _addressData.street[1],
                            address_city: _addressData.city,
                            address_state: _addressData.region,
                            address_postcode: _addressData.postcode,
                            address_country: _addressData.country_id
                        }
                    }
                };
            }
        });
    }
);
