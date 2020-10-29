<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class BsecureLogin extends \Magento\Framework\App\Action\Action
{
	
	public $orderHelper;
	public $access_token;
	public $get_customer_endpoint;
	public $user;


	public function __construct(
		\Magento\Framework\App\Action\Context $context,		
		\Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
		\Bsecure\UniversalCheckout\Helper\OrderHelper $orderHelper,		
		\Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
		\Magento\Framework\Message\ManagerInterface $messageManager,
		\Magento\Framework\App\Request\Http $request,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Customer\Model\CustomerFactory $customerFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		\Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
		\Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
		\Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory,
		\Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
		\Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory

		)
	{
		
		
		$this->request 			= $request; 
		$this->bsecureHelper 	= $bsecureHelper; 
		$this->orderHelper 		= $orderHelper;		
		$this->orderRepository 	= $orderRepository; 
		$this->messageManager 	= $messageManager; 
		$this->customerSession 	= $customerSession; 
		$this->customerFactory 	= $customerFactory; 
		$this->storeManager 	= $storeManager; 
		$this->customerRepository 	= $customerRepository; 
		$this->addressRepository 	= $addressRepository; 
		$this->addressFactory 	= $addressFactory; 
		$this->regionCollection 	= $regionCollection; 
		$this->regionCollectionFactory 	= $regionCollectionFactory; 
		$this->get_customer_endpoint = '/sso/customer/profile';

		return parent::__construct($context);
	}

	public function execute()
	{

		if($this->customerSession->isLoggedIn()) {
		   $this->redirectToMyAccountPage();
		}

		$state = filter_var($this->request->getParam('state'), FILTER_SANITIZE_STRING);
		$code = filter_var($this->request->getParam('code'), FILTER_SANITIZE_STRING);

		if(!empty($state) && !empty($code)){

			if($this->bsecureHelper->validateState($state)){

				//Process to Register/login customer
				$this->user = $this->set_access_token($code);				

				if(!$this->user){
					
					$this->redirectToMyAccountPage(__('Authorization code expired.'));
				}			
				

				$storeId    = $this->storeManager->getStore()->getId();
    			$storeId    = $storeId > 0 ? $storeId : 1;        			
    			$store      = $this->storeManager->getStore($storeId);
    			$websiteId  = $this->storeManager->getStore($storeId)->getWebsiteId();

				$customer   = $this->customerFactory->create();
				$customer->setWebsiteId($websiteId);
		        $customer->loadByEmail($this->user->email);
		       	
		       	$customAttributes = [
								'country_code' => $this->user->country_code,
								'auth_code' => $code,
								'email' => $this->user->email,
							];

				if ( !$customer->getEntityId() ) {

					$customer = $this->find_by_email_or_create($this->user);				
					
				}



				$this->setCustomerCustomAttributes($customer,$customAttributes);

				$customerData = $this->customerRepository->getById($customer->getEntityId());
				$country_code = $customerData->getCustomAttribute('country_code')->getValue();
				$bsecure_auth_code = $customerData->getCustomAttribute('bsecure_auth_code')->getValue();
				$bsecure_user_account_email = $customerData->getCustomAttribute('bsecure_user_account_email')->getValue();

				$addressInfo = [
            						'first_name' => $this->orderHelper->get_first_name_or_last_name($this->user->name),
            						'last_name' => $this->orderHelper->get_first_name_or_last_name($this->user->name),
            						'street' => ['Test Address'],
            						'telephone' => $this->bsecureHelper->phoneWithCountryCode($this->user->phone_number, $this->user->country_code),
            						'city' => 'Karachi',
            						'country_id' => 'PK',
            						'postcode' => '76000',
            						'region_title' => 'Sindh',
            						'default_shipping' => 1,
            						'default_billing' => 1,          						
            						'customer_id' => $customer->getEntityId()
            					];
				
				if(!empty($this->user->address)){

					$customerAddress 	= $this->user->address;
					$country 			= $customerAddress->country;
					$state 				= $customerAddress->state;
					$city 				= $customerAddress->city;
					$address 			= $customerAddress->address;
					$postal_code 		= $customerAddress->postal_code;

					// return Pakistan to PK or United Kingdom to UK//
        			$country_id = array_search($country, \Zend_Locale::getTranslationList('territory'));

        			$addressInfo = [
            						'first_name' => $this->orderHelper->get_first_name_or_last_name($this->user->name),
            						'last_name' => $this->orderHelper->get_first_name_or_last_name($this->user->name),
            						'street' => [$address],
            						'telephone' => $this->bsecureHelper->phoneWithCountryCode($this->user->phone_number, $this->user->country_code),
            						'city' => $city,
            						'country_id' => $country_id,
            						'postcode' => $postal_code,
            						'region_title' => $state,
            						'default_shipping' => 1,
            						'default_billing' => 1,          						
            						'customer_id' => $customer->getEntityId()
            					];
				}

				

		            if(!empty($this->user->phone_number)){

		            	$addresses = $customer->getAddresses();

		            	if(!empty($addresses)){

		            		$billingAddressId = $customer->getDefaultBilling();		            		

		            		$this->addUpdateAddress($addressInfo, $billingAddressId);              		

		            	}else{	

		            		$this->addUpdateAddress($addressInfo);         							        

		            	}		            	

		            }

				$this->customerSession->setCustomerAsLoggedIn($customer);

		        $this->redirectToMyAccountPage();

			}else{
				
				$this->redirectToMyAccountPage(__("Invalid Request: state is not verified."));

			}

			

		}else{

			$redirectUrl = $this->bsecureHelper->build_bsecure_redirect_url();		
			$this->_redirect($redirectUrl);

		}
		
		
		
	}	


	/**
	 * Sets the access_token using the response code.
	 *
	 * @since 1.0.0
	 * @param string $code The code provided by bSecure redirect.
	 *
	 * @return mixed Access token on success or WP_Error.
	 */
	protected function set_access_token( $code = '' ) {

		if ( ! $code ) {

			$this->redirectToMyAccountPage(__('No authorization code provided.'));
		}

		$response = $this->bsecureHelper->bsecureGetOauthToken();	

		$validateResponse = $this->bsecureHelper->validateResponse($response,'token_request');		

		if( $validateResponse['error'] ){
			
			$this->redirectToMyAccountPage(__('Response Error: ').$validateResponse['msg']);

		}else if(!empty($response->access_token)){

			$this->access_token = $response->access_token;
		}
		
		$base_url = $this->bsecureHelper->getConfig( 'universalcheckout/general/bsecure_base_url' );
		$url = $base_url.$this->get_customer_endpoint;

		$headers =	['Authorization' => 'Bearer '.$this->access_token];

		$params = [
			'sslverify' => false,
			'method' => 'POST',
			'body' => ['code' => $code],
			'headers' 	=> $headers
		];

		$response = $this->bsecureHelper->bsecureSendCurlRequest($url, $params);	

		$validateResponse = $this->bsecureHelper->validateResponse($response);	

		if($validateResponse['error']){
			
			$this->redirectToMyAccountPage(__('Response Error: ').$validateResponse['msg']);

		}else{

			return ( !empty($response->body) ) ? $response->body : false;
		}

		return false;
	}


	/**
	 * Get the user's info.
	 *
	 * @since 1.0.0
	 */
	protected function get_user_by_token($code) {

		$headers =	['Authorization' => 'Bearer '.$this->access_token];

		$args = [
			'method' 	=> 	'POST',
			'body'		=>	['code' => $code],
			'headers' 	=> 	$headers
		];


		$response = $this->bsecureHelper->bsecureSendCurlRequest( $this->base_url.$this->get_customer_endpoint, $args );

		

		$validateResponse = $this->bsecureHelper->validateResponse($response);

		if($validateResponse['error']){

			$this->redirectToMyAccountPage(__('Response Error: ').$validateResponse['msg']);
		}

		return ( !empty($response->body) ) ? $response->body : false;
	}



	public function redirectToMyAccountPage($msg='',$type='error'){

		if(!empty($msg)){

			switch($type){

				case 'success':
					$this->messageManager->addSuccess($msg);
				break;

				default:
					$this->messageManager->addError($msg);
				break;

			}
			
		}

		$this->_redirect('customer/account/');
	}


	/**
	 * Add usermeta for current user and bSecure account email.
	 *
	 * @since 1.0.0
	 * @param string $email The users authenticated bSecure account email.
	 */
	protected function connect_account( $email = '' ) {

		if ( ! $email ) {
			return false;
		}




		if(!$this->customerSession->isLoggedIn()) {  		  
		 
		  	return false;
		  
		}  

		
		//return add_user_meta( $current_user->ID, 'wc_bsecure_user_account_email',  sanitize_email($email), true );
	}


	/**
	 * Sets the user's information.
	 *
	 * @since 1.2.0
	 */
	protected function set_user_info($code) {
		$this->user = $this->get_user_by_token($code);
	}


	/**
	 * Gets a user by email or creates a new user.
	 *
	 * @since 1.0.0
	 * @param object $user_data  The bSecure user data object.
	 */
	protected function find_by_email_or_create( $user_data ) {

		$storeId    = $this->storeManager->getStore()->getId();
        $storeId    = $storeId > 0 ? $storeId : 1;        
        $store      = $this->storeManager->getStore($storeId);
        $websiteId  = $this->storeManager->getStore($storeId)->getWebsiteId();

		$customer   = $this->customerFactory->create();
		$customer->setWebsiteId($websiteId);
		$customer->loadByEmail($user_data->email);

		if(!$customer->getEntityId()){

			$first_name      = $this->orderHelper->get_first_name_or_last_name($user_data->name);
			$last_name       = $this->orderHelper->get_first_name_or_last_name($user_data->name,'last_name');

            //If not avilable then create this customer 
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($first_name)
                    ->setLastname($last_name)
                    ->setEmail($user_data->email) 
                    ->setPassword($user_data->email);
            $customer->save();

            return $customer;           
            
        }else{

        	$customer->setFirstname($first_name)
                    ->setLastname($last_name);                   
            $customer->save();          

            return $customer;
        }		

	}


	protected function setCustomerCustomAttributes($customer,$user_data){

		$customerData = $customer->getDataModel();
        $customerData->setCustomAttribute('country_code',$user_data['country_code']);            
        $customerData->setCustomAttribute('bsecure_auth_code',$user_data['auth_code']);
        $customerData->setCustomAttribute('bsecure_user_account_email',$user_data['email']);
        $customer->updateData($customerData);
        $customer->save();
	}


	public function addUpdateAddress($address_info, $address_id = 0 ){

		if(!empty($address_id)){
			// Update Address
			$address = $this->addressRepository->getById($address_id);	   

		}else{
			// Add new address for customer //
			$address = $this->addressFactory->create();
		}
			
        $address->setFirstname($address_info['first_name']);
        $address->setLastname($this->orderHelper->get_first_name_or_last_name($address_info['last_name'],'last_name'));
        $address->setTelephone($address_info['telephone']);	       		        
        $address->setStreet($address_info['street']);	        
        $address->setCity($address_info['city']);
        $address->setCountryId($address_info['country_id']);
        $address->setPostcode($address_info['postcode']);
        $region = $this->getRegionCode($address_info['region_title']);
       
        if(!empty($region['region_id'])){
        	$address->setRegionId($region['region_id']);
        }
        
        $address->setIsDefaultShipping($address_info['default_shipping']);
        $address->setIsDefaultBilling($address_info['default_billing']);
        $address->setCustomerId($address_info['customer_id']);
        $this->addressRepository->save($address);	
		

	}



	public function getRegionCode(string $region): array
    {
        $regionCode = $this->regionCollectionFactory->create()
            ->addRegionNameFilter($region)
            ->getFirstItem()
            ->toArray();
        return $regionCode;
    }
	
}





