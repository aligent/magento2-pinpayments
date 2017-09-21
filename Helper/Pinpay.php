<?php

namespace Aligent\Pinpay\Helper;

 class Pinpay extends \Magento\Framework\App\Helper\AbstractHelper
 {
     /**
      * Return an amount value that can be handled by PIN services.
      * E.g. Dollar amounts should be sent in cents
      *
      * This currently supports a dollar amount, logic will need to be
      * extended to support non-decimal currency such as Yen.
      *
      * @param $currencyCode string
      * @param $amount float
      * @return integer
      */
     public function getRequestAmount($currencyCode, $amount)
     {
         // Round to avoid issue where number of cents is a decimal due to
         // floating-point precision errors.
         return round($amount * 100);
     }
 }