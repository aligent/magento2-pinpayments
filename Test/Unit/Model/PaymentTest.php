<?php

namespace Aligent\Pinpay\Test\Unit\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Psr\Log\LoggerInterface;

class PaymentTest extends Fixture
{

    protected $reflectionClass;

    /** @var  \Aligent\Pinpay\Model\Payment|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethod;

    /**
     * @var $objectManager \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;


    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $pinHelper = $this->objectManager->getObject('Aligent\Pinpay\Helper\Pinpay');
        $client = $this->objectManager->getObject('\Magento\Framework\HTTP\ZendClient');
        $clientFactory = $this->getMockBuilder('\Magento\Framework\HTTP\ZendClientFactory')->disableOriginalConstructor()->getMock();
        $clientFactory->expects($this->any())->method('create')->willReturn($client);

        $logger = $this->getMock(LoggerInterface::class);

        $this->paymentMethod = $this->objectManager->getObject('Aligent\Pinpay\Model\Payment',
            ['pinHelper' => $pinHelper, 'logger' => $logger, 'httpClientFactory' => $clientFactory]);
    }

    public function testClientAuth()
    {
        $fixtures = $this->getFixture("fx-request");

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->objectManager->getObject('Magento\Sales\Model\Order');
        $order->addData($fixtures['order_1']);

        /**
         * @var $paymentInfo \Magento\Sales\Model\Order\Payment\Info
         */
        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');
        foreach ($fixtures['payment_1']['additional_information'] as $key => $val) {
            $paymentInfo->setAdditionalInformation($key, $val);
        }

        $client = $this->paymentMethod->getClient($paymentInfo, $order, $fixtures['order_1']['grand_total'],
            \Aligent\Pinpay\Model\Payment::REQUEST_TYPE_AUTH_ONLY);

        $methodProp = new \ReflectionProperty(\Magento\Framework\HTTP\ZendClient::class, 'method');
        $methodProp->setAccessible(true);
        $paramsProp = new \ReflectionProperty(\Magento\Framework\HTTP\ZendClient::class, 'paramsPost');
        $paramsProp->setAccessible(true);

        $this->assertEquals("/1/charges", $client->getUri()->getPath());
        $this->assertEquals("POST", $methodProp->getValue($client));
        $this->assertEquals($fixtures['authrequest_1'], $paramsProp->getValue($client));

    }

    public function testClientCapture()
    {
        $fixtures = $this->getFixture("fx-request");

        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->objectManager->getObject('Magento\Sales\Model\Order');
        $order->addData($fixtures['order_1']);

        /**
         * @var $paymentInfo \Magento\Sales\Model\Order\Payment\Info
         */
        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');
        $paymentInfo->setCcTransId($fixtures['payment_1']['cc_trans_id']);
        foreach ($fixtures['payment_1']['additional_information'] as $key => $val) {
            $paymentInfo->setAdditionalInformation($key, $val);
        }

        $client = $this->paymentMethod->getClient($paymentInfo, $order, $fixtures['order_1']['grand_total'],
            \Aligent\Pinpay\Model\Payment::REQUEST_TYPE_CAPTURE_ONLY);

        $methodProp = new \ReflectionProperty(\Magento\Framework\HTTP\ZendClient::class, 'method');
        $methodProp->setAccessible(true);
        $paramsProp = new \ReflectionProperty(\Magento\Framework\HTTP\ZendClient::class, 'paramsPost');
        $paramsProp->setAccessible(true);

        $this->assertEquals("/1/charges/" . $fixtures['payment_1']['cc_trans_id'] . "/capture", $client->getUri()->getPath());
        $this->assertEquals("PUT", $methodProp->getValue($client));
        $this->assertEquals($fixtures['capturerequest_1'], $paramsProp->getValue($client));
    }

    public function testInvalidAuthAmount()
    {
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException(LocalizedException::class);
        } else {
            $this->expectException(LocalizedException::class);
        }
        /**
         * @var $paymentInfo InfoInterface
         */
        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');
        $this->paymentMethod->authorize($paymentInfo, 0.0000);
    }

    public function testInvalidCaptureAmount()
    {
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException(LocalizedException::class);
        } else {
            $this->expectException(LocalizedException::class);
        }
        /**
         * @var $paymentInfo InfoInterface
         */
        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');
        $this->paymentMethod->capture($paymentInfo, 0.0000);
    }

    public function testSuspectedFraud()
    {
        /**
         * Allows both new and old PHPUnit versions
         */
        if (method_exists($this, 'setExpectedException')) {
            $this->setExpectedException(LocalizedException::class);
        } else {
            $this->expectException(LocalizedException::class);
        }
        $method = new \ReflectionMethod($this->paymentMethod, '_handleResponse');
        $method->setAccessible(true);
        $fixtures = $this->getFixture('fx-response');
        /**
         * @var $response \Zend_Http_Response|\PHPUnit_Framework_MockObject_MockObject
         */
        $response = $this->getMockBuilder("Zend_Http_Response")->disableOriginalConstructor()->getMock();

        $response->expects($this->once())->method('getStatus')->willReturn(400);
        $response->expects($this->once())->method('getBody')->willReturn($fixtures['suspected_fraud']);

        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');
        $method->invoke($this->paymentMethod, $response, $paymentInfo);
    }

    public function testReqBuild()
    {
        $fixtures = $this->getFixture("fx-request");

        $order = $this->objectManager->getObject('Magento\Sales\Model\Order');
        $paymentInfo = $this->objectManager->getObject('Magento\Sales\Model\Order\Payment\Info');

        foreach ($fixtures['payment_1']['additional_information'] as $key => $val) {
            $paymentInfo->setAdditionalInformation($key, $val);
        }

        $order->addData($fixtures['order_1']);

        $method = new \ReflectionMethod($this->paymentMethod, '_buildAuthRequest');
        $method->setAccessible(true);
        $result = $method->invoke($this->paymentMethod, $order, $paymentInfo, $order->getGrandTotal(), true);
        $this->assertEquals($result, $fixtures['request_1']);
    }

    protected function tearDown()
    {
        $this->paymentMethod = null;
        $this->objectManager = null;
    }

}