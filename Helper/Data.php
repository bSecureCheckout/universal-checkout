<?php

/*
* Copyright @ 2020 bSecure. All rights reserved.
*/
namespace Bsecure\UniversalCheckout\Helper;

use Magento\Framework\HTTP\Client\Curl;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const BTN_SHOW_BSECURE_ONLY = 'bsecure_only';
    const BTN_SHOW_BSECURE_BOTH = 'bsecure_mag_both';
    const BTN_BUY_WITH_BSECURE = 'Bsecure_UniversalCheckout::images/bsecure-checkout-img.svg';
    const BSECURE_DEV_VIEW_ORDER_URL = 'https://partners-dev.bsecure.app/view-order/';
    const BSECURE_STAGE_VIEW_ORDER_URL = 'https://partners-stage.bsecure.app/view-order/';
    const BSECURE_LIVE_VIEW_ORDER_URL = 'https://partner.bsecure.pk/view-order/';
      
    public $baseUrl = "";

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configInterface,
        \Magento\Framework\Session\SessionManager $sessionManager,
        Curl $curl
    ) {

        $this->configInterface = $configInterface;
        $this->curl = $curl;
        $this->_session = $sessionManager;
        parent::__construct($context);
    }

    public function getConfig($configPath)
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function setConfig($configPath, $value, $default = 'default')
    {
        $this->configInterface->saveConfig($configPath, $value, $default, 0);

        return true;
    }

    /**
     * Send curl request using magento 2 Curl Client Lib for curl request
     *
     * @return array server response .
     */

    public function bsecureSendCurlRequest($url, $params = [], $retry = 0)
    {
        
        $response = [];

        try {
            $this->curl->setOption(CURLOPT_TIMEOUT, 20); // How long the connection should stay open in seconds.
            $this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false); // ssl verfication is off for local setup
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

            if (!empty($params['headers'])) {
                if (is_array($params['headers'])) {
                    $this->curl->setHeaders($params['headers']);
                } else {
                    $this->curl->addHeader($params['headers']);
                }
            }

            if (!empty($params['method'])) {
                if ($params['method'] == 'POST') {
                    $body = !empty($params['body']) ? $params['body'] : [];

                    $this->curl->post($url, $body);
                }
            } else {
                $this->curl->get($url);
            }
            
            $response = $this->curl->getBody();

            if (!empty($response)) {
                return json_decode($response);
            } else {
                // Retry request 3 times if failed
                if ($retry < 3) {
                    $retry++;
                    $this->bsecureSendCurlRequest($url, $params, $retry);
                }
            }
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        return $response;
    }

    /**
     * Get oauth token from server
     *
     * @return array server response .
     */
    public function bsecureGetOauthToken()
    {

        $grantType     = 'client_credentials';
        $clientId      = $this->getConfig('universalcheckout/general/bsecure_client_id');
        $clientSecret  = $this->getConfig('universalcheckout/general/bsecure_client_secret');
        $bsecureStoreId  = $this->getConfig('universalcheckout/general/bsecure_store_id');
        $clientId       = !empty($bsecureStoreId) ? $clientId.':'.$bsecureStoreId : $clientId;

        $config = $this->getBsecureConfig();

        if (!empty($config->token)) {
            $oauthUrl = $config->token;
        } else {
            return false;
        }

        $params =   [
                        'method' => 'POST',
                        'body'      => [
                                        'grant_type'     => $grantType,
                                        'client_id'     => $clientId,
                                        'client_secret' => $clientSecret
                                      ],
                    ];

        $response = $this->bsecureSendCurlRequest($oauthUrl, $params);

        if (!empty($response->body)) {

            if (!empty($response->body->checkout_btn)) {
                $this->setConfig(
                    'universalcheckout/general/bsecure_checkout_btn_url',
                    $response->body->checkout_btn
                );
                
            }

            return $response->body;
        }

        return $response;
    }

    /**
     * Get Configuration
     *
     * @return array server response .
     */

    public function getBsecureConfig()
    {

        $this->baseUrl = $this->getConfig('universalcheckout/general/bsecure_base_url');

        if (!empty($this->baseUrl)) {
            $url = $this->baseUrl."/plugin/configuration";
           
            $response = $this->bsecureSendCurlRequest($url);
            
            if (!empty($response->body->api_end_points)) {
                return $response->body->api_end_points;
            }
        }

        return false;
    }

    public function validateResponse($response, $type = '')
    {

        $errorMessage = ["error" => false, "msg" => "Success"];
        $defaultMessage = __("No response from bSecure server! 
                                Make sure your credentials and settings in the admin are correct!");

        if (empty($response)) {
            return ["error" => true, "msg" => $defaultMessage];
        }

        if (empty($response->status) && !empty($response->message)) {
            return $errorMessage;
        } elseif ((!empty($response->status) && $response->status != 200)) {
            $msg = (is_array($response->message)) ? implode(",", $response->message) : $response->message;

            $errorMessage = ["error" => true, "msg" => $msg];
        } elseif (!empty($response->message) && !is_array($response->message) && !empty($response->status)) {
            if ($response->status != 200) {
                $errorMessage = ["error" => true, "msg" => $response->message];
            }
        } elseif (!empty($response->message) && is_array($response->message) && !empty($response->status)) {
            if ($response->status != 200) {
                $errorMessage = ["error" => true, "msg" => implode(",", $response->message)];
            }
        }

        if ($type == 'token_request') {
            // @codingStandardsIgnoreStart
            // If for some reasons token not found then try again //
            if (empty($response->access_token)) {
                // need validation for $response->message
                if (!empty($response->message)) {
                    $resposneMessage = is_array($response->message) ?
                                        implode(",", $response->message) :
                                        $response->message;
                } else {
                    $resposneMessage = $defaultMessage;
                }

                $errorMessage = ["error" => true, "msg" => $resposneMessage];
                
            }
            // @codingStandardsIgnoreEnd
        }

        return $errorMessage;
    }

    /**
     * Builds out the bSecure redirect URL
     *
     * @since    1.0.0
     */
    public function buildBsecureRedirectUrl()
    {

        // Build the API redirect url.
        $clientId  = $this->getConfig('universalcheckout/general/bsecure_client_id');
        $bsecureClientSecret  = $this->getConfig('universalcheckout/general/bsecure_client_secret');
        $bsecureStoreId  = $this->getConfig('universalcheckout/general/bsecure_store_id');
        $config = $this->getBsecureConfig();
        $ssoEndpoint = !empty($config->ssoLogin) ? $config->ssoLogin : "/";

        $clientId       = !empty($bsecureStoreId) ? $clientId.':'.$bsecureStoreId : $clientId;
        
        $responseType  = 'code';
        $sessioinId    = $this->_session->getSessionId();
        $state          = base64_encode("state-".$sessioinId);
        $scope          = 'profile';

        $ssoEndpoint = $ssoEndpoint . '?scope=' .
                       $scope . '&response_type=' .
                       $responseType . '&client_id=' .
                       $clientId . '&state=' . $state;
                       
        return $ssoEndpoint;
    }

    /*
     * Validate State
     */
    public function validateState($returnedState)
    {

        $sessioinId    = $this->_session->getSessionId();
        $state          = base64_encode("state-".$sessioinId);
        
        if ($returnedState != $state) {
            return false;
        }

        return true;
    }

    /*
     * Remove country code from phone number
     */
    public function phoneWithoutCountryCode($phoneNumber, $countryCode = '92')
    {

        if (preg_match('/^\+\d+$/', $phoneNumber)) {

            if (!empty($countryCode)) {

                 return str_replace('+'.$countryCode, '', $phoneNumber);
            }

            return $phoneNumber;
        }

        $phoneNumber = str_replace(['+','-',' '], '', $phoneNumber);

        if (strlen($phoneNumber) >= 12) {
            $phoneNumber = substr($phoneNumber, -10);
        }
        
        return $phoneNumber;
    }

    /*
     * Add country code in phone number
     */
    public function phoneWithCountryCode($phoneNumber, $countryCode = '92')
    {
        if (preg_match('/^\+\d+$/', $phoneNumber)) {

            return $phoneNumber;
        }

        $phoneNumber = '+'.$countryCode.$phoneNumber;

        return $phoneNumber;
    }
    
    /**
     * Prepare telephone field config according to the Magento default config
     * @param $addressType
     * @param string $method
     * @return array
     */
    public function telephoneFieldConfig($addressType, $method = '')
    {
        return  [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => $addressType . $method,
                'customEntry' => null,
                'template' => 'ui/form/field',
                'elementTmpl' => 'Bsecure_UniversalCheckout/form/element/telephone',
                'tooltip' => [
                    'description' => 'For delivery questions.',
                    'tooltipTpl' => 'ui/form/element/helper/tooltip'
                ],
            ],
            'dataScope' => $addressType . $method . '.telephone',
            'dataScopePrefix' => $addressType . $method,
            'label' => __('Phone Number'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 120,
            'validation' => [
                "required-entry"    => true,
                "max_text_length"   => 255,
                "min_text_length"   => 1
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
            'focused' => false,
        ];
    }
}
