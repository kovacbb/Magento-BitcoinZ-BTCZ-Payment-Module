<?php

namespace Kovach\Btcz\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
