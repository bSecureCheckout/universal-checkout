<?php

namespace Bsecure\UniversalCheckout\Controller\Index;

class BsecureLogin extends \Magento\Framework\App\Action\Action
{
    
    public $orderHelper;
    public $accessToken;
    public $getCustomerEndpoint;
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
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Framework\Controller\Result\Redirect $resultRedirectFactory,
        \Magento\Checkout\Model\Cart $cart
    ) {
        
        $this->request             = $request;
        $this->bsecureHelper     = $bsecureHelper;
        $this->orderHelper         = $orderHelper;
        $this->orderRepository     = $orderRepository;
        $this->messageManager     = $messageManager;
        $this->customerSession     = $customerSession;
        $this->customerFactory     = $customerFactory;
        $this->storeManager     = $storeManager;
        $this->customerRepository     = $customerRepository;
        $this->addressRepository     = $addressRepository;
        $this->addressFactory     = $addressFactory;
        $this->regionCollection     = $regionCollection;
        $this->regionCollectionFactory     = $regionCollectionFactory;
        $this->resultRedirectFactory     = $resultRedirectFactory;
        $this->getCustomerEndpoint = '/sso/customer/profile';
        $this->cart             = $cart;

        return parent::__construct($context);
    }

    public function execute()
    {
        $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

        if ($this->customerSession->isLoggedIn() || $moduleEnabled != 1) {
            $this->redirectToMyAccountPage();
        }

        $state = filter_var($this->request->getParam('state'), FILTER_SANITIZE_SPECIAL_CHARS);
        $code = filter_var($this->request->getParam('code'), FILTER_SANITIZE_SPECIAL_CHARS);
        $login = filter_var($this->request->getParam('login'), FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($state) && !empty($code)) {
            if ($this->bsecureHelper->validateState($state)) {
                //Process to Register/login customer
                $this->user = $this->setAccessToken($code);

                if (!$this->user) {
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

                   if (!$customer->getEntityId()) {
                       $customer = $this->findByEmailOrCreate($this->user);
                   }

                   $this->setCustomerCustomAttributes($customer, $customAttributes);

                   $customerData = $this->customerRepository->getById($customer->getEntityId());
                   $countryCode = $customerData->getCustomAttribute('country_code')->getValue();
                   $bsecureAuthCode = $customerData->getCustomAttribute('bsecure_auth_code')
                                      ->getValue();
                   $bsecureUserAccountEmail = $customerData
                                              ->getCustomAttribute('bsecure_user_account_email')
                                              ->getValue();
                   $phoneNumber = $this->user->phone_number;
                   $countryCode = $this->user->country_code;
                   $telephone = $this->bsecureHelper->phoneWithCountryCode($phoneNumber, $countryCode);

                   $addressInfo = [
                                    'first_name' => $this->orderHelper->getFirstNameLastName(
                                        $this->user->name
                                    ),
                                    'last_name' => $this->orderHelper->getFirstNameLastName(
                                        $this->user->name,
                                        'last_name'
                                    ),
                                    'street' => ['Test Address'],
                                    'telephone' => $telephone,
                                    'city' => 'Karachi',
                                    'country_id' => 'PK',
                                    'postcode' => '76000',
                                    'region_title' => 'Sindh',
                                    'default_shipping' => 1,
                                    'default_billing' => 1,
                                    'customer_id' => $customer->getEntityId()
                                ];
                
                   if (!empty($this->user->address)) {
                       $customerAddress     = $this->user->address;
                       $country             = $customerAddress->country;
                       $state               = $customerAddress->state;
                       $city                = $customerAddress->city;
                       $address             = $customerAddress->address;
                       // @codingStandardsIgnoreStart
                       $postalCode          = $customerAddress->postal_code;

                       // return Pakistan to PK or United Kingdom to UK//
                       $countryId = array_search($country, \Zend_Locale::getTranslationList('territory'));
                       // @codingStandardsIgnoreEnd

                       $addressInfo = [
                                    'first_name' => $this->orderHelper->getFirstNameLastName(
                                        $this->user->name
                                    ),
                                    'last_name' => $this->orderHelper->getFirstNameLastName(
                                        $this->user->name,
                                        'last_name'
                                    ),
                                    'street' => [$address],
                                    'telephone' => $telephone,//phpcs:ignore
                                    'city' => $city,
                                    'country_id' => $countryId,
                                    'postcode' => $postalCode,
                                    'region_title' => $state,
                                    'default_shipping' => 1,
                                    'default_billing' => 1,
                                    'customer_id' => $customer->getEntityId()
                                ];
                   }

                   if (!empty($this->user->phone_number)) {
                       $addresses = $customer->getAddresses();

                       if (!empty($addresses)) {
                           $billingAddressId = $customer->getDefaultBilling();

                           $this->orderHelper->addUpdateAddress($addressInfo, $billingAddressId);
                       } else {
                           $this->orderHelper->addUpdateAddress($addressInfo);
                       }
                   }

                   $this->customerSession->setCustomerAsLoggedIn($customer);
                   $this->_clearQuote();
                   $this->redirectToMyAccountPage();
            } else {
                $this->redirectToMyAccountPage(__("Access Forbidden: Invalid login request found.
                 Please try again later."));
            }
        } elseif ($login == 1) {
            $redirectUrl = $this->bsecureHelper->buildBsecureRedirectUrl();
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setUrl($redirectUrl);
            return $resultRedirect;
        } else {
            $this->redirectToMyAccountPage(__('You have cancelled to login with bSecure!'));
        }
    }

    /**
     * Sets the accessToken using the response code.
     *
     * @since 1.0.0
     * @param string $code The code provided by bSecure redirect.
     *
     * @return mixed Access token on success or WP_Error.
     */
    protected function setAccessToken($code = '')
    {

        if (! $code) {
            $this->redirectToMyAccountPage(__('No authorization code provided.'));
        }

        $response = $this->bsecureHelper->bsecureGetOauthToken();

        $validateResponse = $this->bsecureHelper->validateResponse($response, 'token_request');
        // @codingStandardsIgnoreStart
        if ($validateResponse['error']) {
            $this->redirectToMyAccountPage(__('Response Error: ').$validateResponse['msg']);
        } elseif (!empty($response->access_token)) {
            $this->accessToken = $response->access_token;
        }
        // @codingStandardsIgnoreEnd
        
        $baseUrl = $this->bsecureHelper->getConfig('universalcheckout/general/bsecure_base_url');
        $url = $baseUrl . $this->getCustomerEndpoint;

        $headers =    ['Authorization' => 'Bearer ' . $this->accessToken];

        $params = [
            'sslverify' => false,
            'method' => 'POST',
            'body' => ['code' => $code],
            'headers'     => $headers
        ];
        
        $response = $this->bsecureHelper->bsecureSendCurlRequest($url, $params);

        $validateResponse = $this->bsecureHelper->validateResponse($response);

        if ($validateResponse['error']) {
            $this->redirectToMyAccountPage(__('Response Error: ') . $validateResponse['msg']);
        } else {
            return ( !empty($response->body) ) ? $response->body : false;
        }

        return false;
    }

    public function redirectToMyAccountPage($msg = '', $type = 'error')
    {

        if (!empty($msg)) {
            switch ($type) {
                case 'success':
                    $this->messageManager->addSuccess($msg);
                    break;

                default:
                    $this->messageManager->addError($msg);
                    break;
            }
        }

        return $this->_redirect('customer/account/');
    }

    /**
     * Add usermeta for current user and bSecure account email.
     *
     * @since 1.0.0
     * @param string $email The users authenticated bSecure account email.
     */
    protected function connectAccount($email = '')
    {
        if (! $email) {
            return false;
        }

        if (!$this->customerSession->isLoggedIn()) {
              return false;
        }
    }

    /**
     * Gets a user by email or creates a new user.
     *
     * @since 1.0.0
     * @param object $userData  The bSecure user data object.
     */
    protected function findByEmailOrCreate($userData)
    {

        $storeId    = $this->storeManager->getStore()->getId();
        $storeId    = $storeId > 0 ? $storeId : 1;
        $store      = $this->storeManager->getStore($storeId);
        $websiteId  = $this->storeManager->getStore($storeId)->getWebsiteId();
        $customer   = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($userData->email);

        if (!$customer->getEntityId()) {
            $firstName      = $this->orderHelper->getFirstNameLastName($userData->name);
            $lastName       = $this->orderHelper->getFirstNameLastName($userData->name, 'last_name');

            //If not avilable then create this customer
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($firstName)
                    ->setLastname($lastName)
                    ->setEmail($userData->email)
                    ->setPassword($userData->email);
            $customer->save();

            return $customer;
        } else {
            $customer->setFirstname($firstName)
                    ->setLastname($lastName);
            $customer->save();

            return $customer;
        }
    }

    protected function setCustomerCustomAttributes($customer, $userData)
    {

        $customerData = $customer->getDataModel();
        $customerData->setCustomAttribute('country_code', $userData['country_code']);
        $customerData->setCustomAttribute('bsecure_auth_code', $userData['auth_code']);
        $customerData->setCustomAttribute('bsecure_user_account_email', $userData['email']);
        $customer->updateData($customerData);
        $customer->save();
    }

    // Clear Cart //
    protected function _clearQuote()
    {
        $this->cart->truncate();
        $this->cart->getQuote()->setTotalsCollectedFlag(false);
         $this->cart->getQuote()->setIsActive(0);
        $this->cart->save();
    }
}
