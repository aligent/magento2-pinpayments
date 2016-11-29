<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
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
     * Payment constructor.
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param LoggerInterface $logger
     * @param ZendClientFactory $httpClientFactory
     * @param PinHelper $pinHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory,
        PinHelper $pinHelper
    ) {
        $this->_config = $scopeConfigInterface;
        $this->_logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
        $this->_pinHelper = $pinHelper;
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
    }

    /**
     * @inheritDoc
     */
    public function canFetchTransactionInfo()
    {
        // TODO: Implement canFetchTransactionInfo() method.
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
        // TODO: Implement isGateway() method.
    }

    /**
     * @inheritDoc
     */
    public function isOffline()
    {
        // TODO: Implement isOffline() method.
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
    }

    /**
     * @inheritDoc
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            $this->_logger->addError('Expected amount for transaction is zero or below');
            throw new LocalizedException(__("Invalid payment amount."));
        }

        $client = $this->_httpClientFactory->create();

        $endpoint = $this->getPaymentUrl() . 'charges';
        $method = $client::POST;

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $payment->getOrder();

        $data = $this->_buildAuthRequest($order, $payment, $amount, false);

        $client->setUri($endpoint);
        $client->setMethod($method);

        foreach ($data as $reqParam => $reqValue) {
            $client->setParameterPost($reqParam, $reqValue);
        }

        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setAuth($this->getConfigData('secret_key'), $order->getStoreId());

        $response = null;
        try {
            $response = $client->request();
            /**
             * @var $result \Aligent\Pinpay\Model\Result
             */
            $result = new Result($response);
            $error = $result->getError();

            if ($result->isSuccess()) {
                $payment->setCcTransId($result->getToken());
                $payment->setTransactionId($result->getToken());
            } elseif($error) {
                throw new LocalizedException(__($result->getErrorDescription()));
            }
        } catch (\Exception $e) {
            $this->_logger->error("Payment Error: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if ($amount <= 0) {
            $this->_logger->addError('Expected amount for transaction is zero or below');
            throw new LocalizedException(__("Invalid payment amount."));
        }

        $client = $this->_httpClientFactory->create();

        $endpoint = $this->getPaymentUrl() . 'charges';
        $method = $client::POST;

        //Check for an existing auth token
        if ($payment->getCcTransId()) {
            $endpoint .= '/' . $payment->getCcTransId() . '/capture';
            $method = $client::PUT;
        }

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $payment->getOrder();

        //Only require amount value if we're just doing a capture.
        if ($payment->getCcTransId()) {
            $data = ['amount' => $this->_pinHelper->getRequestAmount($order->getBaseCurrencyCode(), $amount)];
        } else {
            $data = $this->_buildAuthRequest($order, $payment, $amount, true);
        }

        $client->setUri($endpoint);
        $client->setMethod($method);

        foreach ($data as $reqParam => $reqValue) {
            $client->setParameterPost($reqParam, $reqValue);
        }

        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setAuth($this->getConfigData('secret_key'), $order->getStoreId());

        $response = null;
        try {
            $response = $client->request();
            /**
             * @var $result \Aligent\Pinpay\Model\Result
             */
            $result = new Result($response);
            $error = $result->getError();
            if ($result->isSuccess())
            {
                $payment->setCcTransId($result->getToken());
                $payment->setTransactionId($result->getToken());
            } elseif ($error) {
                throw new LocalizedException(__($result->getErrorDescription()));
            }
        } catch (\Exception $e) {
            $this->_logger->error("Payment Error: " . $e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
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
        return [
            'email' => $order->getCustomerEmail(),
            'amount' => $this->_pinHelper->getRequestAmount($order->getBaseCurrencyCode(), $amount),
            'description' => 'Order: #' . $order->getRealOrderId(),
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
    }

    /**
     * @inheritDoc
     */
    public function denyPayment(InfoInterface $payment)
    {
        // TODO: Implement denyPayment() method.
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
        $info->setAdditionalInformation('card_token', $additionalData->getCardToken());

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