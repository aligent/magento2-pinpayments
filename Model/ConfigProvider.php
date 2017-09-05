<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\IframeConfigProvider;

class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{

    /**
     * @var \Magento\Payment\Model\MethodInterface
     */
    protected $method;

    /**
     * @var string
     */
    protected $methodCode;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ConfigProvider constructor.
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     * @param PaymentHelper $paymentHelper
     * @param $methodCode
     */
    public function __construct(
        RequestInterface $request,
        UrlInterface $urlBuilder,
        PaymentHelper $paymentHelper,
        $methodCode
    ) {
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
        $this->method = $paymentHelper->getMethodInstance($methodCode);
        $this->methodCode = $methodCode;
    }

    public function getConfig()
    {
        return [
            'payment' => [
                'iframe' => [
                    'timeoutTime' => [
                        $this->methodCode => IframeConfigProvider::TIMEOUT_TIME
                    ]
                ],
                'pinpay' => [
                    'source' => $this->getSource(),
                    'apiKey' => $this->getApiKey(),
                    'mode' => $this->getMode()
                ]
            ]
        ];
    }

    public function getApiKey()
    {
        return $this->method->getConfigData('publishable_key');
    }

    public function getMode()
    {
        return $this->method->getConfigData('test_mode') === '1' ? 'test' : 'live';
    }

    public function getSource()
    {
        return "https://cdn.pin.net.au/hosted_fields/b4/hosted-fields.html";
    }
}