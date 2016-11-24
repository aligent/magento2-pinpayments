<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Api\Data\PaymentInterface;

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

    protected $infoInstance;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $_httpClientFactory;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        LoggerInterface $logger,
        ZendClientFactory $httpClientFactory
    )
    {
        $this->_config = $scopeConfigInterface;
        $this->_logger = $logger;
        $this->_httpClientFactory = $httpClientFactory;
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
        // TODO: Implement canOrder() method.
    }

    /**
     * @inheritDoc
     */
    public function canAuthorize()
    {
        // TODO: Implement canAuthorize() method.
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
        // TODO: Implement canCapturePartial() method.
    }

    /**
     * @inheritDoc
     */
    public function canCaptureOnce()
    {
        // TODO: Implement canCaptureOnce() method.
    }

    /**
     * @inheritDoc
     */
    public function canRefund()
    {
        // TODO: Implement canRefund() method.
    }

    /**
     * @inheritDoc
     */
    public function canRefundPartialPerInvoice()
    {
        // TODO: Implement canRefundPartialPerInvoice() method.
    }

    /**
     * @inheritDoc
     */
    public function canVoid()
    {
        // TODO: Implement canVoid() method.
    }

    /**
     * @inheritDoc
     */
    public function canUseInternal()
    {
        // TODO: Implement canUseInternal() method.
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canUseCheckout()
    {
        // TODO: Implement canUseCheckout() method.
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
        // TODO: Implement getInfoBlockType() method.
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
        // TODO: Implement order() method.
        $x = $amount;
    }

    /**
     * @inheritDoc
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO: Implement authorize() method.
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

        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $data = $this->_buildRequestData($order, $payment, $amount);

        $endpoint = $this->getPaymentUrl() . 'charges';

        $client = $this->_httpClientFactory->create();
        $client->setUri($endpoint);
        foreach($data as $reqParam => $reqValue){
            $client->setParameterPost($reqParam, $reqValue);
        }

        $client->setParameterPost('capture', 'true');

        $client->setMethod($client::POST);
        $client->setConfig(['maxredirects' => 0, 'timeout' => 30]);
        $client->setAuth($this->getConfigData('secret_key'),'');

        $response = null;
        try {
            $response = $client->request();
            $resultContent = json_decode($response->getBody());
            $payment->setCcTransId('' . $resultContent->response->token);
            $payment->setTransactionId('' . $resultContent->response->token);
        } catch (\Exception $e) {
            $this->_logger->error("Error capturing funds: " . $e->getMessage());
        }
    }

    /**
     * @param $order \Magento\Sales\Model\Order
     * @param $payment \Magento\Payment\Model\InfoInterface
     * @param $amount float
     * @return array
     */
    protected function _buildRequestData($order, $payment, $amount)
    {
        return [
            'email' => $order->getCustomerEmail(),
            'amount' => $amount * 100,
            'description' => 'Order: #' . $order->getRealOrderId(),
            'card_token' => $payment->getAdditionalInformation('card_token'),//TODO get card_token from additional information
            'ip_address' => $order->getRemoteIp(),
            'currency' => $order->getBaseCurrencyCode()
        ];
    }

    /**
     * @inheritDoc
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO: Implement refund() method.
    }

    /**
     * @inheritDoc
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        // TODO: Implement cancel() method.
    }

    /**
     * @inheritDoc
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        // TODO: Implement void() method.
    }

    /**
     * @inheritDoc
     */
    public function canReviewPayment()
    {
        // TODO: Implement canReviewPayment() method.
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
        if($storeId){
            return $this->_config->getValue($configKey, 'stores', $storeId);
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
        //TODO
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isActive($storeId = null)
    {
        //TODO
        return true;
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
        return \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE;//TODO: config
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