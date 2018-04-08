<?php

namespace Kovach\Btcz\Controller\Adminhtml\System\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Random extends Action
{

    protected $resultJsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {

        $hash = $this->generateRandomString(32);
        $result = $this->resultJsonFactory->create();

        return $result->setData(['success' => true, 'hash' => $hash]);
    }


	function generateRandomString($length = 32) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
}
?>