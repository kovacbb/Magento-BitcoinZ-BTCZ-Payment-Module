<?php 

set_time_limit(0);
error_reporting(E_ALL);
ini_set('max_execution_time',1800);
//ini_set('memory_limit','1024M');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

use \Magento\Framework\App\Bootstrap;
include('app/bootstrap.php');
 
// add bootstrap
$bootstraps = Bootstrap::create(BP, $_SERVER);
$object_Manager = $bootstraps->getObjectManager();
 
$app_state = $object_Manager->get('\Magento\Framework\App\State');
$app_state->setAreaCode('frontend');

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
$connection = $resource->getConnection();
$tableName = $resource->getTableName('btcz_pingback');

// IP Check
$pingbackIP = getRealClientIP();
if($pingbackIP != '164.132.164.206'){
	die();
};

$data = json_decode($_POST["data"]);

// Log POST data
if(!isset($_POST["data"])){
	$sql = 'INSERT INTO `' . $tableName . '` (json) VALUES ("-- no data --")';
	$connection->query($sql);
	die();
} else {
	$sql = 'INSERT INTO `' . $tableName . '` (json) VALUES (:json)';
	$connection->query($sql, array('json' => $_POST["data"]));
	
	// skip cron
	if($data->state == 2 || $data->state == 5){
		$sqlupt = "UPDATE `btcz` SET `processed` = '1' WHERE `url_id` = (:url_id)";
		$connection->query($sqlupt, array('url_id' => $data->url_id));
	}
	
};


function ProcessPingback($data)
{
	if($data->state == 2) //expired
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($data->invoicename);
		$order->setState("closed")->setStatus("closed");
		$order->addStatusHistoryComment('Invoice '.$data->url_id.' was not payed ['.$data->strState.']');
		$order->save();
	}
	else if($data->state == 5) //success
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$status_after = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')->getValue('payment/btcz/after_complete', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$order = $objectManager->create('\Magento\Sales\Model\Order')->loadByIncrementId($data->invoicename);
		$order->setState($status_after)->setStatus($status_after);
		$order->addStatusHistoryComment('Invoice '.$data->url_id.' was payed successfully.');
		$order->save();
	};
}

function getRealClientIP()
{
	if (function_exists('getallheaders')) {
		$headers = getallheaders();
	} else {
		$headers = $_SERVER;
	}
	//Get the forwarded IP if it exists
	if (array_key_exists('X-Forwarded-For', $headers)
		&& filter_var($headers['X-Forwarded-For'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
	) {
		$the_ip = $headers['X-Forwarded-For'];
	} elseif (array_key_exists('HTTP_X_FORWARDED_FOR', $headers)
		&& filter_var($headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
	) {
		$the_ip = $headers['HTTP_X_FORWARDED_FOR'];
	} elseif(array_key_exists('Cf-Connecting-Ip', $headers)) {
		$the_ip = $headers['Cf-Connecting-Ip'];
	} else {
		$the_ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}
	return $the_ip;
}



echo ProcessPingback($data);

