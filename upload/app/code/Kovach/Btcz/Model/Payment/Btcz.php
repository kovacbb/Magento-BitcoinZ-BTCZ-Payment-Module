<?php


namespace Kovach\Btcz\Model\Payment;

class Btcz extends \Magento\Payment\Model\Method\AbstractMethod
{

    protected $_code = "btcz";
    protected $_isOffline = true;

    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}
