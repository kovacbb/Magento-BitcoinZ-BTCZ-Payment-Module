<?php

namespace Kovach\Btcz\Block;

class Success extends \Magento\Sales\Block\Order\Totals
{
    protected $checkoutSession;
    protected $customerSession;
    protected $_orderFactory;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
		$this->_scopeConfig = $scopeConfig;
    }

    public function getOrder()
    {
        return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
	
	public function getWalletAddress()
    {
        return $this->_scopeConfig->getValue('payment/btcz/wallet_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
	
	public function getMerchantEmail()
    {
        return $this->_scopeConfig->getValue('payment/btcz/merchant_email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
	
	public function getSecretKey()
    {
        return $this->_scopeConfig->getValue('payment/btcz/secret_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
	
	public function encryptIt( $q ) {
		$cryptKey  = $this->getSecretKey();
		$qEncoded      = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), $q, MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ) );
		
		return( $qEncoded );
	}

	public function decryptIt( $q ) {
		//$q = 'wUIPyZQH2B0MK8zS/4svX3G6YQiM8kLfB6fOFFTs7cs=';
		$cryptKey  = $this->getSecretKey();
		$qDecoded      = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $q ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
		
		return( $qDecoded );
	}
	
	public function getPingbackUrl($InvoiceID)
	{
		//return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'btcz?o='.$this->encryptIt($InvoiceID);
		return $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB).'pingback.php';
	}
	
	public function getGateway($InvoiceID,$Amount,$CurrencyCode,$Expire)
	{
		$APIUrl = 'https://btcz.in/api/process';
		
		$MerchantAddress = $this->getWalletAddress();
		$PingbackUrl = $this->getPingbackUrl($InvoiceID);
		$MerchantEmail = $this->getMerchantEmail();
		$Secret = $this->getSecretKey();
		
		$fields = array(
			'f' => "create",
			'p_addr' => urlencode($MerchantAddress),
			'p_pingback' => urlencode($PingbackUrl),
			'p_invoicename' => urlencode($InvoiceID),
			'p_amount' => urlencode($Amount),
			'p_currency_code' => urlencode($CurrencyCode),		
			'p_email' => urlencode($MerchantEmail),
			'p_secret' => urlencode($Secret),
			'p_expire' => urlencode($Expire)
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
	
	public function getIframe($incrementid, $total, $expire = 15)
	{
		
		$currency = $this->_storeManager->getStore()->getCurrentCurrency()->getCode();
		
		$RESP =  $this->getGateway($incrementid, $total, $currency, $expire);
		$JSON_RESP = json_decode($RESP);

		if(!empty($JSON_RESP))
		{
			//print_r($JSON_RESP);
			$url = $JSON_RESP->url_id;
			$InvoiceURL = "https://btcz.in/invoice?id=".$url;
			$Secret = $JSON_RESP->secret;
			$iframe=  '<div class="checkout-success-url" style="text-align:center;"><a title="Open in new window" href="'.$InvoiceURL.'" target="_blank">'.$InvoiceURL.'</a></div><iframe id="iFrame" style="min-height: 850px;" width="100%"  frameborder="0" src="'.$InvoiceURL.'" scrolling="no" onload="resizeIframe()">
				</iframe>
				<script type="text/javascript">
					function resizeIframe() {
						var obj = document.getElementById("iFrame");
						obj.style.height = (obj.contentWindow.document.body.scrollHeight) + "px";
						setTimeout("resizeIframe()", 200);
					}
				</script>';
			
			//INSERT INTO `btcz` (increment_id, url_id, processed) VALUES (000000020, "a678f3624af972675195cdcc12dc846e", 0)
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
			$connection = $resource->getConnection();
			$tableName = $resource->getTableName('btcz');
			$sql = 'INSERT INTO `' . $tableName . '` (increment_id, url_id, processed) VALUES ("' . $incrementid . '", "' . $url . '", 0)';
			$connection->query($sql);

			//$orderloaded = $objectManager->create('\Magento\Sales\Model\Order') ->loadByIncrementId($incrementid);
			$orderloaded = $this->getOrder();
			$orderloaded->addStatusHistoryComment('BTCZ Invoice: '.$InvoiceURL)->setIsVisibleOnFront(true)->setIsCustomerNotified(true)->save();
			
			return $iframe;
		}
		else if(strlen($RESP))
		{
			return $RESP;
		}
		else
		{
			return "No response from API";
		}
		
	}
	
}
