<?php
namespace Kovach\Btcz\Cron;

class Checkpayment
{

    protected $logger;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
		\Psr\Log\LoggerInterface $logger,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	)
    {
        $this->logger = $logger;
		$this->_scopeConfig = $scopeConfig;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->logger->addInfo("Cronjob BTCz Payment Check is executed.");
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		$sql = 'SELECT * FROM `btcz` WHERE `processed` = 0';
		$orders = $connection->query($sql);
		
		$status_after = $this->_scopeConfig->getValue('payment/btcz/after_complete', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		
		foreach ($orders as $order) {
			
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$orderloaded = $objectManager->create('\Magento\Sales\Model\Order') ->loadByIncrementId($order['increment_id']);
			
			$result = $this->checkPayment($order['url_id']);

			if ($result) {
				$data = json_decode($result);

				if($data->state == 5) { //success
					
					$orderloaded->setState($status_after)->setStatus($status_after);
					$orderloaded->addStatusHistoryComment('Invoice '.$data->url_id.' was payed successfully.');
					$orderloaded->save();
					$sqlupt = "UPDATE `btcz` SET `processed` = '1' WHERE `increment_id` = ".$order['increment_id'];
					$connection->query($sqlupt);
						
				} else if($data->state == 2) { //expired
					
					$orderloaded->setState("closed")->setStatus("closed");
					$orderloaded->addStatusHistoryComment('Invoice '.$data->url_id.' was not payed [Gateway expired].');
					$orderloaded->save();
					$sqlupt = "UPDATE `btcz` SET `processed` = '1' WHERE `increment_id` = ".$order['increment_id'];
					$connection->query($sqlupt);
						
				};
			} else {
				continue;
			};
		
		};
		
    }
	
	function checkPayment($InvoiceID)
	{
		$APIUrl = 'https://btcz.in/api/process';
				
		$fields = array(
			'f' => "getinfo",
			'id' => urlencode($InvoiceID)
		);
		
		$fields_string = "";
		foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
		rtrim($fields_string, '&');

		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $APIUrl);
		curl_setopt($ch,CURLOPT_POST, count($fields));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
			
		$result = curl_exec($ch);
		$response = curl_getinfo( $ch );
		curl_close($ch);
		
		if($response['http_code'] != 200)
			return false;
		
		return $result;
	}

}
