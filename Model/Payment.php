<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\CartInterface;

class Payment implements MethodInterface
{

    const PAYMENT_CODE = 'pinpay';
    const CONFIG_PATH_PREFIX = 'payment/pinpay/';

    /**
     * @var ScopeConfigInterface
     */
    protected $_config;

    public function __construct(ScopeConfigInterface $scopeConfigInterface)
    {
        $this->_config = $scopeConfigInterface;
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
        // TODO: Implement canCapture() method.
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
        // TODO: Implement isInitializeNeeded() method.
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
     * @inheritDoc
     */
    public function getInfoInstance()
    {
        // TODO: Implement getInfoInstance() method.
    }

    /**
     * @inheritDoc
     */
    public function setInfoInstance(InfoInterface $info)
    {
        // TODO: Implement setInfoInstance() method.
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        // TODO: Implement validate() method.
    }

    /**
     * @inheritDoc
     */
    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        // TODO: Implement order() method.
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
        // TODO: Implement capture() method.
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
        // TODO: Implement assignData() method.
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
        // TODO: Implement getConfigPaymentAction() method.
    }

}