<?php

namespace Aligent\Pinpay\Model\Logger\Handler;

/**
 * Class Debug
 * @package Aligent\Pinpay\Model\Logger\Handler
 */
class Debug extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = '/var/log/pinpay.log';
}