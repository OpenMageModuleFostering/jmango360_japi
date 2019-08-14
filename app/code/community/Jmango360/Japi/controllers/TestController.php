<?php
/**
 * 
 * Oauth 1.0 3-way token test controller
 * 
 * 
 * 
 */
class Jmango360_Japi_TestController extends Mage_Core_Controller_Front_Action
{
	//Find consumer key in admin: system/webservices/Rest OAuth consumers
	const CONSUMERKEY = '17353f0dbb5153e1318dfede8e97ce6c';
	const CONSUMERSECRET = '7a5ab5fa9d41055b375f4cccca811109';
	
	private $_requestTokenParams = array(
			'siteUrl' => ':baseurl/oauth',
			'requestTokenUrl' => ':baseurl/oauth/initiate',
			'consumerKey' => self::CONSUMERKEY,//Consumer key registered in server administration
			'consumerSecret' => self::CONSUMERSECRET,//Consumer secret registered in server administration
	);
	
	public function translationsTestAction()
	{
		$strings = Mage::app()->getRequest()->getParam('strings');
		$countryId = Mage::app()->getRequest()->getParam('country_id');
		
		if ($countryId) {
			$locale = $countryId;
			Mage::app()->getLocale()->setLocaleCode($locale);
			Mage::getSingleton('core/translate')->setLocale($locale)->init('frontend', true);
		}
		
		foreach((array)$strings as $text) {
			$trans[] = Mage::helper('japi')->__($text);
		}
		print Mage::helper('core')->jsonEncode($trans);
		
		exit();
	}
	
	
// 	public function indexAction() 
// 	{
// 		// Get session
// 		$session = Mage::getSingleton('core/session');
		
// 		$baseurl = Mage::getUrl();
// 		$currentUrl = Mage::getModel('core/url')->getUrl('*/*/*', array('_current' => true));
// 		$session->setLastUsedRestUrl($currentUrl);
		
// 		$restOperationName = $this->getRequest()->getParam('path');
// 		if (! $restOperationName) {
// 			exit('No Path found. Specify path with operation name. Example: ' . $baseurl . 'japi/test?path=api/rest/products/1/categories&method=get. See also <a target="blank" href="http://www.magentocommerce.com/api/rest/Resources/resources.html">http://www.magentocommerce.com/api/rest/Resources/resources.html</a>');
// 		}
// 		$session->setLastRestOperationName($restOperationName);
		
// 		$method = $this->getRequest()->getParam('method');
// 		if (empty($method)) {
// 			$method = 'GET';
// 		}
// 		$session->setLastUsedMethod($method);
		
//         //Basic parameters that need to be provided for oAuth authentication
//         //on Magento
//         $params = array(
//             'siteUrl' => $baseurl . 'oauth',
//             'requestTokenUrl' => $baseurl . 'oauth/initiate',
//             'accessTokenUrl' => $baseurl . 'oauth/token',
//             'authorizeUrl' => $baseurl . 'admin/oAuth_authorize',//This URL is used only if we authenticate as Admin user type
//             'consumerKey' => self::CONSUMERKEY,//Consumer key registered in server administration
//             'consumerSecret' => self::CONSUMERSECRET,//Consumer secret registered in server administration
//             'callbackUrl' => $baseurl . 'japi/test/callback',//Url of callback action below
//         );
 
//         // Initiate oAuth consumer with above parameters
//         $consumer = new Zend_Oauth_Consumer($params);
//         // Get request token
//         $requestToken = $consumer->getRequestToken();
//         // Save serialized request token object in session for later use
//         $session->setRequestToken(serialize($requestToken));
//         // Redirect to authorize URL
//         $consumer->redirect();
 
//         return;
//     }
    
    public function requestTokenAction()
    {
    	$session = Mage::getSingleton('core/session');
    	$consumer = new Zend_Oauth_Consumer($this->_getTestParams());
    	$requestToken = $consumer->getRequestToken();
    	$session->setRequestToken(serialize($requestToken));
    	
    	print '<a target="blank" href="' . Mage::getUrl() . "japi/test/verifierConfirm?oauth_token=" . $requestToken->getToken() . '">confirm</a><br /><br />' . "\n\n";
    	
    	/**TEST OUTPUT**/
    	print "===================Tokens==============";
    	$requestInSession = unserialize($session->getRequestToken());
    	$token = $requestInSession->getToken();
    	$secret = $requestInSession->getTokenSecret();

    	Zend_Debug::dump(array('secret'=>$secret, 'token'=>$token));
    	
    	print '=================DEBUG===============';
    	Zend_Debug::dump(array('secret'=>$secret, 'token'=>$token, 'request token in session'=>$requestInSession, 'requestToken' => $requestToken, 'session' => $session));
    	exit;
    }
    
    public function verifierConfirmAction()
    {
    	$helper = Mage::helper('oauth');
    	$server = Mage::getModel('oauth/server');
    	
    	/*TEST*/
    	$userId = 1; //$user->getId()
    	$userType = Mage_Oauth_Model_Token::USER_TYPE_ADMIN;
    	
    	$token = $server->authorizeToken($userId, Mage_Oauth_Model_Token::USER_TYPE_ADMIN);
    	$tokenString = $token->getToken();
    	$verifierString = $token->getVerifier();

    	print '<a target="blank" href="' . Mage::getUrl() . "japi/test/callback?oauth_token={$tokenString}&oauth_verifier={$verifierString}" . '">callBack</a><br /><br />' . "\n\n";

    	/*TEST*/
    	print '==================TEST Token==============';
    	Zend_Debug::dump($token);
    	
    }
    
    /**
     * Basic parameters that need to be provided for oAuth authentication on Magento
     * @return $params
     */
    private function _getTestParams()
    {
    	$baseurl = Mage::getUrl();
    	foreach ($this->_requestTokenParams as $key => $value) {
    		$params[$key] = str_replace(':baseurl/', $baseurl, $value);
    	}
    	return $params;
    }
 
    public function callbackAction() {
 
        //oAuth parameters
        $method = 'GET';
    	$baseurl = Mage::getUrl();
        $params = array(
            'siteUrl' => $baseurl . 'oauth',
            'requestTokenUrl' => $baseurl . 'oauth/initiate',
            'accessTokenUrl' => $baseurl . 'oauth/token',
            'consumerKey' => self::CONSUMERKEY,
            'consumerSecret' => self::CONSUMERSECRET
        );
 
        // Get session
        $session = Mage::getSingleton('core/session');
        // Read and unserialize request token from session
        $requestToken = unserialize($session->getRequestToken());
        // Initiate oAuth consumer
        $consumer = new Zend_Oauth_Consumer($params);
        // Using oAuth parameters and request Token we got, get access token
        $acessToken = $consumer->getAccessToken($_GET, $requestToken);
        // Get HTTP client from access token object
        $restClient = $acessToken->getHttpClient($params);
        
        // Set REST resource URL, example:  api/rest/products/1/categories
        //$restOperationName = $session->getLastRestOperationName();
        //if (empty($restOperationName)) {
        	$restOperationName = 'api/rest/products';
        //}
        
        
        
        
        $restClient->setUri($baseurl . $restOperationName);
        // In Magento it is neccesary to set json or xml headers in order to work
        $restClient->setHeaders('Accept', 'application/json');
        // Get method
        //$method = strtoupper($session->getLastUsedMethod());
        //$restClient->setMethod(constant('Zend_Http_Client::'.$method));
        $restClient->setMethod($method);
        //Make REST request
        $response = $restClient->request();
        // Here we can see that response body contains json list of products
        print "<pre>";
        print "JSON: " . $response->getBody();
        print "JSON_DECODE: "; print_r((array)json_decode((string)$response->getBody()));
        print "</pre><br /><br />\n\n";
        print '<a href="' . $session->getLastUsedRestUrl() . '" target="blank">' . $session->getLastUsedRestUrl() . '</a>';
 
        return;
    }

	public function addJapiColumnAction()
	{
		$_coreResource = Mage::getSingleton('core/resource');
		$adapter = $_coreResource->getConnection('core_read');

		$_gridTableName = $_coreResource->getTableName('sales/order_grid');
		$_orderTableName = $_coreResource->getTableName('sales/order');

		if ($adapter->isTableExists($_gridTableName)){
			if ($adapter->tableColumnExists($_gridTableName, 'japi')) {
				echo 'Column japi exits. Not need to update! <br/><br/><br/>';
				$selectOrder = $adapter->select()
					->from($_gridTableName)
					->where('japi = ?', 1);
				$dataOrder = $adapter->fetchAll($selectOrder);
				foreach ($dataOrder as $data) {
					echo($data['entity_id'] . ' =====> ' . $data['japi']); echo '<br/>';
				}
			} else {
				$write = $_coreResource->getConnection('core_write');
				$write->addColumn($_gridTableName, 'japi', 'int');

				$select = $adapter->select();
				$select->join(
					array('order_table' => $_orderTableName),
					'order_table.entity_id = grid_table.entity_id',
					array('japi')
				);
				$write->query($select->crossUpdateFromSelect(array(
					'grid_table' => $_gridTableName
				)));
				echo 'Column japi has been added';
			}
		} else {
			echo "Table " . $_gridTableName . " not exist";
		}

	}
  
}
