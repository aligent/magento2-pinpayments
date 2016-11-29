<?php

namespace Aligent\Pinpay\Model;

use Magento\Framework\DataObject;

class Result extends DataObject
{

    const HTTP_RESPONSE_CODE_APPROVED = 201;
    const HTTP_RESPONSE_CODE_INVALID = 422;
    const HTTP_RESPONSE_CODE_FAILED = 400;

    const ERROR_CODE_FRAUD = "suspected_fraud";
    const ERROR_CODE_FUNDS = "insufficient_funds";
    const ERROR_CODE_EXPIRED = "expired_card";
    const ERROR_CODE_DECLINED = "card_declined";
    const ERROR_CODE_GENERIC = "processing_error";

    /**
     * @var \Zend_Http_Response
     */
    protected $_response;

    /**
     * @var int
     */
    protected $_responseCode;


    /**
     * Result constructor.
     * @param \Zend_Http_Response $response
     * @throws \InvalidArgumentException
     */
    public function __construct(\Zend_Http_Response $response)
    {
        parent::__construct();
        $this->_responseCode = $response->getStatus();
        $this->_response = json_decode($response->getBody());
        if (is_null($this->_response)) {
            throw new \InvalidArgumentException('Unable to parse payment gateway response.');
        }
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        if (isset($this->_response->response) && isset($this->_response->response->success)) {
            return (boolean)$this->_response->response->success;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        if (isset($this->_response->response->token)) {
            return '' . $this->_response->response->token;
        }
        return '';
    }

    /**
     * @return null | string
     */
    public function getError()
    {
        if (isset($this->_response->error)) {
            return $this->_response->error;
        }
        return null;
    }

    /**
     * @return null | string
     */
    public function getErrorDescription()
    {
        if (isset($this->_response->error_description)) {
            return $this->_response->error_description;
        }
        return null;
    }
}