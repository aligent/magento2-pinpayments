<?php

namespace Aligent\Pinpay\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * PinPayments payment action dropdown source
 */
class PaymentAction implements ArrayInterface
{

    const ACTION_AUTHORIZE = 'authorize';

    const ACTION_BOTH = 'authorize_capture';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::ACTION_AUTHORIZE,
                'label' => __('Authorize Only'),
            ],
            [
                'value' => self::ACTION_BOTH,
                'label' => __('Authorize and Capture')
            ]
        ];
    }
}
