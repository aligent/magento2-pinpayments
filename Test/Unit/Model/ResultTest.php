<?php

namespace Aligent\Pinpay\Test\Unit\Model;

class ResultTest extends Fixture
{

    protected $reflectionClass;

    /**
     * @var $objectManager \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    protected $fixtures;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->fixtures = $this->getFixture("fx-response");
    }

    public function testIsError()
    {
        $response = $this->getMockBuilder("Zend_Http_Response")->disableOriginalConstructor()->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($this->fixtures['already_captured']);
        $result = $this->objectManager->getObject('Aligent\Pinpay\Model\Result',['response' => $response]);

        $this->assertNotNull($result->getError());
    }

    public function testErrorDescription()
    {
        $response = $this->getMockBuilder("Zend_Http_Response")->disableOriginalConstructor()->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($this->fixtures['suspected_fraud']);
        $response->expects($this->once())->method('getStatus')->willReturn(400);
        $result = $this->objectManager->getObject('Aligent\Pinpay\Model\Result',['response' => $response]);

        $this->assertNotNull($result->getErrorDescription());
    }

    public function testSuccess()
    {
        $response = $this->getMockBuilder("Zend_Http_Response")->disableOriginalConstructor()->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($this->fixtures['success']);
        $response->expects($this->once())->method('getStatus')->willReturn(201);
        $result = $this->objectManager->getObject('Aligent\Pinpay\Model\Result',['response' => $response]);

        $this->assertTrue($result->isSuccess());
    }

    public function testGetToken()
    {
        $response = $this->getMockBuilder("Zend_Http_Response")->disableOriginalConstructor()->getMock();
        $response->expects($this->once())->method('getBody')->willReturn($this->fixtures['success']);
        $response->expects($this->once())->method('getStatus')->willReturn(201);
        $result = $this->objectManager->getObject('Aligent\Pinpay\Model\Result',['response' => $response]);

        $this->assertNotEmpty($result->getToken());
    }

    protected function tearDown()
    {
        $this->objectManager = null;
    }

}