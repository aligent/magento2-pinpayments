define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/iframe',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/set-payment-information'

    ],
    function ($, Component, fullScreenLoader, setPaymentInformationAction) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Aligent_Pinpay/payment/pinpay-hosted',
                timeoutMessage: 'Sorry, but something went wrong. Please contact the seller.'
            },
            card_token:'',
            placeOrderHandler: null,
            validateHandler: null,
            iframe: null,
            token_element_selector: null,
            config: {
                api_env: 'test',
                api_key: '',
                values: {
                    address_line1: '',
                    address_line2: '',
                    address_city: '',
                    address_state: '',
                    address_postcode: '',
                    address_country: ''
                },
                style: [".CardFields--address_line1 { display: none; }",".CardFields--address_line2 { display: none; }",".CardFieldsGroup--city-state-postcode { display: none; }", ".CardFields input { font-size: 18px; }", ".CardFields--expiry input { width: 90px; }", ".CardFields--cvc input { width: 90px; }", ".CardFields--expiry, .CardFields--cvc {display: inline-block; }", ".Errors { color: red; font-size: 12px }", ".CardFields label:after { content: ':'; }", ".CardFields--expiry { margin-right: 20px}"]
            },

            initialize: function () {
                this._super();
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
                var configMessage = {
                    config: this.config
                };
                this.iframe[0].contentWindow.postMessage(JSON.stringify(configMessage), "*");
            },
            handleMessage: function(msg) {
                //Pull originalEvent from resulting jQuery wrapper
                var msgEvent = msg.originalEvent;
                if(msgEvent.origin === 'https://cdn.pin.net.au')
                {
                    this.card_token = msgEvent.data;
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
                    $.when(
                        setPaymentInformationAction(this.messageContainer, self.getData())
                    ).done(
                        function () {
                            self.placeOrderHandler().fail(
                                function () {
                                    fullScreenLoader.stopLoader();
                                }
                            );
                        }
                    ).always(
                        function () {
                            self.isPlaceOrderActionAllowed(true);
                            fullScreenLoader.stopLoader();
                        }
                    );
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
                return "https://cdn.pin.net.au/hosted_fields/b4/hosted-fields.html";
            },

            getConfig: function() {
                return {
                    config: {
                        style: [".CardFields--address_line1 { display: none; }",".CardFields--address_line2 { display: none; }",".CardFieldsGroup--city-state-postcode { display: none; }", ".CardFields input { font-size: 18px; }", ".CardFields--expiry input { width: 90px; }", ".CardFields--cvc input { width: 90px; }", ".CardFields--expiry, .CardFields--cvc {display: inline-block; }", ".Errors { color: red; font-size: 12px }", ".CardFields label:after { content: ':'; }", ".CardFields--expiry { margin-right: 20px}"],
                        api_env: 'test',//TODO
                        api_key: 'TODO'
                    }
                }
            }
        });
    }
);
