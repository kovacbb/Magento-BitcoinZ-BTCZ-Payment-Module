<?php
namespace Kovach\Btcz\Block\Index;

use Magento\Sales\Model\Order;

class Index extends \Magento\Framework\View\Element\Template {
	
	protected $_checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
       
        $this->_checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $scopeConfig;
		parent::__construct($context, $data);
    }
	
	protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }
	
	public function getSecretKey()
    {
        return $this->_scopeConfig->getValue('payment/btcz/secret_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function decryptIt( $q ) {
		$cryptKey  = $this->getSecretKey();
		$qDecoded      = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $q ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
		
		return( $qDecoded );
	}
	
	public function changeState( $orderIdEnc ) {
		
		$orderId = $this->decryptIt($orderIdEnc);
		
		if($orderId){
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
			$connection = $resource->getConnection();
			$tableName = $resource->getTableName('btcz');
			$sql = "SELECT * FROM `" . $tableName . "` WHERE `increment_id` LIKE '". $orderId ."';";
			$result = $connection->fetchAll($sql);
			
			$id = $result[0]['url_id'];
			
			$APIUrl = 'https://btcz.in/api/process?f=getinfo&id='.$id;
		
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, $APIUrl);
			curl_setopt($ch,CURLOPT_POST, count($fields));
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
				
			$result = curl_exec($ch);
			$response = curl_getinfo( $ch );
			curl_close($ch);
			
			if($response['http_code'] != 200){
				return false;
			};
		
			$JSON_RESP = json_decode($result);
			return $JSON_RESP->state;
			
			if($JSON_RESP->state == 2) //success //2-expired
				{		
					return $JSON_RESP->state;
					$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
					$order = $objectManager->create('\Magento\Sales\Model\Order') ->loadByIncrementId($orderId);
					$orderState = Order::STATE_PROCESSING;
					$order->setState($orderState)->setStatus(Order::STATE_PROCESSING);
					$order->save();
					return $JSON_RESP->state;
				}
		}
		
	}


}