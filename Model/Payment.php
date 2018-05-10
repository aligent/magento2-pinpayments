<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Aligent\Pinpay\Helper\Pinpay as PinHelper;

/**
 *
 * PIN Payments implementation which supports authorization and capture
 * of payments through a card token retrieved via the hosted fields service.
 *
 * Class Payment
 * @package Aligent\Pinpay\Model
 */
class Payment implements MethodInterface
{

    const PAYMENT_CODE = 'pinpay';
    const CONFIG_PATH_PREFIX = 'payment/pinpay/';
    const TEST_GATEWAY_URL = 'https://test-api.pin.net.au/1/';
    const GATEWAY_URL = 'https://api.pin.net.au/1/';

    const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';

    const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';

    const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';

    /**
     * @var string
     */
    protected $_formBlockType = \Aligent\Pinpay\Block\Form\Offline::class;

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    protected $_canCapture = true;

    /**
     * @var InfoInterface
     */
    protected $infoInstance;

    /**
     * @var PinHelper
     */
    protected $_pinHelper;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $_httpClientFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * Payment constructor.
     * @param State $appState
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param LoggerInterface $logger
     * @param ZendClientFactory $httpClientFactory
     * @param PinHelper $pinHelper
     */
    public function __construct(
        State $appState,
        ScopeConfigInterface $scopeConfigInterface,
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        PinHelper $pinHelper
    ) {
        $this->_config = $scopeConfigInterface;
        $this->_logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
        $this->_pinHelper = $pinHelper;
        $this->_appState = $appState;
    }

    /**
     * @inheritDoc
     */
    public function getCode()
    {
        return self::PAYMENT_CODE;
    }

    /**
     * Note: Intentionally not implemented due to deprecation in favour of UiComponent
     * @inheritDoc
     */
    public function getFormBlockType()
    {
        if ($this->isOffline()) {
            return $this->_formBlockType;
        }
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->_config->getValue(self::CONFIG_PATH_PREFIX . 'title');
    }

    /**
     * @inheritDoc
     */
    public function setStore($storeId)
    {
        // TODO: Implement setStore() method.
    }

    /**
     * @inheritDoc
     */
    public function getStore()
    {
        // TODO: Implement getStore() method.
        return \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    /**
     * @inheritDoc
     */
    public function canOrder()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canCapture()
    {
        return $this->_canCapture;
    }

    /**
     * @inheritDoc
     */
    public function canCapturePartial()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canCaptureOnce()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canRefund()
    {
        // TODO: Implement canRefund() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canRefundPartialPerInvoice()
    {
        // TODO: Implement canRefundPartialPerInvoice() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canVoid()
    {
        // TODO: Implement canVoid() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canUseInternal()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseCheckout()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canEdit()
    {
        // TODO: Implement canEdit() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canFetchTransactionInfo()
    {
        // TODO: Implement canFetchTransactionInfo() method.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId)
    {
        // TODO: Implement fetchTransactionInfo() method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function isGateway()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isOffline()
    {
        if ($this->_appState->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isInitializeNeeded()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canUseForCountry($country)
    {
        // TODO: Implement canUseForCountry() method.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseForCurrency($currencyCode)
    {
        //TODO: Implement required rules
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getInfoBlockType()
    {
        return "Magento\\Payment\\Block\\ConfigurableInfo";
    }

    /**
     * @inheritdoc
     */
    public function getInfoInstance()
    {
        return $this->infoInstance;
    }

    /**
     * @inheritdoc
     */
    public function setInfoInstance(InfoInterface $info)
    {
        $this->infoInstance = $info;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        // TODO: Implement validate()
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @param $payment InfoInterface
     * @param $order \Magento\Sales\Model\Order
     * @param $amount float
     * @param $transactionType string
     * @return \Magento\Framework\HTTP\ZendClient
     */
    public function getClient($payment, $order, $amount, $transactionType = self::REQUEST_TYPE_AUTH_CAPTURE)
    {
        $client = $this->_httpClientFactory->create();
        $endpoint = $this->getPaymentUrl() . 'charges';
        $method = \Zend_Http_Client::POST;

        if ($transactionType === self::REQUEST_TYPE_CAPTURE_ONLY) {
            $endpoint .= '/' . $payment->getCcTransId() . '/capture';
            $method = \Zend_Http_Client::PUT;
        }

        $client->setAuth($this->getConfigData('secret_key', $order->getStoreId()));
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setUri($endpoint);
        $client->setMethod($method);

        /**
         * A capture-only request requires amount value as the only parameter.
         * Note: the charge token is part of the URL.
         */
        if ($transactionType === self::REQUEST_TYPE_CAPTURE_ONLY) {
            $data = ['amount' => $this->_pinHelper->getRequestAmount($order->getBaseCurrencyCode(), $amount)];
        } else {
            $capture = $transactionType === self::REQUEST_TYPE_AUTH_CAPTURE;
            $data = $this->_buildAuthRequest($order, $payment, $amount, $capture);
        }

        foreach ($data as $reqParam => $reqValue) {
            $client->setParameterPost($reqParam, $reqValue);
        }

        return $client;
    }

    /**
     * @inheritDoc
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            $this->_logger->error('Expected amount for transaction is zero or below');
            throw new LocalizedException(__("Invalid payment amount."));
        }

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $payment->getOrder();
        $client = $this->getClient($payment, $order, $amount, self::REQUEST_TYPE_AUTH_ONLY);

        $response = null;
        try {
            $response = $client->request();
            $this->_handleResponse($response, $payment);
        } catch (\Exception $e) {
            $this->_logger->error("Payment Error: " . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            $this->_logger->error('Expected amount for transaction is zero or below');
            throw new LocalizedException(__("Invalid payment amount."));
        }

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $payment->getOrder();

        if ($this->isOffline()) {
            $payment->setCcTransId($payment->getAdditionalInformation('reference_number'));
            $payment->setTransactionId($payment->getAdditionalInformation('reference_number'));
        } else {
            $transactionType = self::REQUEST_TYPE_AUTH_CAPTURE;
            if ($payment->getCcTransId()) {
                $transactionType = self::REQUEST_TYPE_CAPTURE_ONLY;
            }
            $client = $this->getClient($payment, $order, $amount, $transactionType);

            $response = null;
            try {
                $response = $client->request();
                $this->_handleResponse($response, $payment);
            } catch (\Exception $e) {
                $this->_logger->error("Payment Error: " . $e->getMessage());
                throw new LocalizedException(__($e->getMessage()));
            }
        }
    }

    /**
     * @param $response \Zend_Http_Response
     * @param $payment \Magento\Payment\Model\InfoInterface
     * @throws LocalizedException
     */
    protected function _handleResponse($response, $payment)
    {
        /**
         * @var $result \Aligent\Pinpay\Model\Result
         */
        $result = new Result($response);
        $error = $result->getError();
        if ($result->isSuccess()) {
            $payment->setCcTransId($result->getToken());
            $payment->setTransactionId($result->getToken());
            $payment->setCcType($result->getCCType());
        } elseif ($error) {
            throw new LocalizedException(__($result->getErrorDescription()));
        }
    }

    /**
     * @param $order \Magento\Sales\Model\Order
     * @param $payment \Magento\Payment\Model\InfoInterface
     * @param $amount float
     * @param $capture boolean
     * @return array
     */
    protected function _buildAuthRequest($order, $payment, $amount, $capture = true)
    {
        $descPrefix = $this->getConfigData('description_prefix', $order->getStoreId());
        if (is_null($descPrefix)) {
            $descPrefix = '';
        } else {
            $descPrefix = $descPrefix . ' ';
        }
        return [
            'email' => $order->getCustomerEmail(),
            'amount' => $this->_pinHelper->getRequestAmount($order->getBaseCurrencyCode(), $amount),
            'description' => $descPrefix . 'Order: #' . $order->getRealOrderId(),
            'card_token' => $payment->getAdditionalInformation('card_token'),
            'ip_address' => $order->getRemoteIp(),
            'currency' => $order->getBaseCurrencyCode(),
            'capture' => $capture
        ];
    }

    /**
     * @inheritDoc
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO: Implement refund() method.
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // TODO: Implement void() method.
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function canReviewPayment()
    {
        // TODO: Implement canReviewPayment() method.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function acceptPayment(InfoInterface $payment)
    {
        // TODO: Implement acceptPayment() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function denyPayment(InfoInterface $payment)
    {
        // TODO: Implement denyPayment() method.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getConfigData($field, $storeId = null)
    {
        $configKey = self::CONFIG_PATH_PREFIX . $field;
        if ($storeId) {
            return $this->_config->getValue($configKey, ScopeInterface::SCOPE_STORES, $storeId);
        }
        return $this->_config->getValue($configKey);
    }

    /**
     * @inheritDoc
     */
    public function assignData(DataObject $data)
    {
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        $info = $this->getInfoInstance();
        if ($this->isOffline()) {
            $info->setAdditionalInformation('reference_number', $additionalData->getReferenceNumber());
        } else {
            $info->setAdditionalInformation('card_token', $additionalData->getCardToken());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(CartInterface $quote = null)
    {
        return $this->isActive();
    }

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null)
    {
        return $this->getConfigData('active');
    }

    /**
     * @inheritDoc
     */
    public function initialize($paymentAction, $stateObject)
    {
        // TODO: Implement initialize() method.
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getConfigPaymentAction()
    {
        return $this->getConfigData('payment_action');
    }

    /**
     * Get the URL for sending payment API requests based on whether test mode is configured.
     * @param int $storeId
     * @return string
     */
    public function getPaymentUrl($storeId = 0)
    {
        $isTest = $this->getConfigData('test_mode', $storeId);
        return $isTest ? self::TEST_GATEWAY_URL : self::GATEWAY_URL;
    }

}