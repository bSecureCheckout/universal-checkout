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
	public $base_url = "";

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

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }


    public function setConfig($config_path,$value,$default='default')
    {
        $this->configInterface->saveConfig($config_path, $value, $default, 0);

        return true;
    }


    /**
     * Send curl request using magento 2 Curl Client Lib for curl request
     *
     * @return array server response .
     */

	public function bsecureSendCurlRequest($url, $params = [],  $retry = 0){
		
		$response = [];

		try{

			$this->curl->setOption(CURLOPT_TIMEOUT, 20); // How long the connection should stay open in seconds.
			$this->curl->setOption(CURLOPT_SSL_VERIFYHOST, false); // ssl verfication is off for local setup
			$this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);

			if(!empty($params['headers'])){

				if(is_array($params['headers'])){

					$this->curl->setHeaders($params['headers']);

				}else{

					$this->curl->addHeader($params['headers']);
				}

			}

			if(!empty($params['method'])){

				if($params['method'] == 'POST'){

					$body = !empty($params['body']) ? $params['body'] : [];					

					$this->curl->post($url, $body);
				
				}  

			} else {

				$this->curl->get($url);
				
			}			
			
			$response = $this->curl->getBody();

			if(!empty($response)){

				return json_decode($response);	

			}else{

				// Retry request 3 times if failed
				if($retry < 3){
		            $retry++;
		            error_log('retries:'.$retry.' response blank');
		            $this->bsecureSendCurlRequest($url, $params,  $retry);
		            
		        }
			}         	


		}catch(\Exception $ex){

			return $ex->getMessage();
		}


		return $response;

	}


	/**
     * Get oauth token from server
     *
     * @return array server response .
     */
    public function bsecureGetOauthToken(){        

        $grant_type 	= 'client_credentials';
        $client_id 		= $this->getConfig('universalcheckout/general/bsecure_client_id');
        $client_secret 	= $this->getConfig('universalcheckout/general/bsecure_client_secret');

        $config = $this->getBsecureConfig();

        if(!empty($config->token)){

            $oauth_url = $config->token;
            
        }else{

            return false;
        }        

        $params =   [
                        'method' => 'POST',
                        'body' 	 => [
                        				'grant_type' 	=> $grant_type, 
			                        	'client_id' 	=> $client_id, 
			                        	'client_secret' => $client_secret
			                      	],                         
                    ];


        $response = $this->bsecureSendCurlRequest($oauth_url,$params);

        if(!empty($response->body)){

            return $response->body;
        }

        return $response;

        
    }



	/**
     * Get Configuration
     *
     * @return array server response .
     */

    public function getBsecureConfig(){

    	$this->base_url = $this->getConfig('universalcheckout/general/bsecure_base_url');

        if(!empty($this->base_url)){
            
            $url = $this->base_url."/plugin/configuration";
           
            $response = $this->bsecureSendCurlRequest($url);            
            
            if(!empty($response->body->api_end_points)){

                return $response->body->api_end_points;

            }

        }

        return false;
    }



    public function validateResponse($response, $type = ''){

        $errorMessage = ["error" => false, "msg" => ""];

        if(empty($response)){

            return ["error" => true, "msg" => __("No response from bSecure server!")];
        }

        if(empty($response->status) && !empty($response->message)){         

            return $errorMessage;

        }else if((!empty($response->status) && $response->status != 200)){
            
            $msg = (is_array($response->message)) ? implode(",", $response->message) : $response->message;

            $errorMessage = ["error" => true, "msg" => $msg];

        } else if(!empty($response->message) && !is_array($response->message) && !empty($response->status)){

            
            if($response->status != 200){
                $errorMessage = ["error" => true, "msg" => $response->message];
            }
            

        }else if(!empty($response->message) && is_array($response->message) && !empty($response->status)){

            
            if($response->status != 200){
                $errorMessage = ["error" => true, "msg" => implode(",", $response->message)];
            }
            
        }


        /*if($type == 'token_request'){

            // If for some reasons token not found then try again //
            if(empty($response->access_token)){

                $errorMessage = ["error" => true, "msg" => implode(",", $response->message)];
            }

        } */      

        return $errorMessage;
    }


    /**
     * Builds out the bSecure redirect URL
     *
     * @since    1.0.0
     */
    public function build_bsecure_redirect_url(ConsumerInterface $consumer=null) {

        // Build the API redirect url.
        $client_id  = $this->getConfig( 'universalcheckout/general/bsecure_client_id' );
        $bsecure_client_secret  = $this->getConfig( 'universalcheckout/general/bsecure_client_secret' );
        $config = $this->getBsecureConfig();             
        $sso_endpoint = !empty($config->ssoLogin) ? $config->ssoLogin : "/";        
        
        $response_type  = 'code';
        $sessioin_id    = $this->_session->getSessionId();  
        $state          = base64_encode("state-".$sessioin_id); 
        $scope          = 'profile';        

        return $sso_endpoint . '?scope=' . $scope . '&response_type=' . $response_type . '&client_id=' . $client_id . '&state=' . $state;
    }


    /*
     * Validate State
     */
    public function validateState($returnedState){

        $sessioin_id    = $this->_session->getSessionId();  
        $state          = base64_encode("state-".$sessioin_id); 
        
        if($returnedState != $state){
           return false;
        }

        return true;

    }



    /*
     * Remove country code from phone number
     */
    public function  phoneWithoutCountryCode($phone_number,$country_code='92'){

        $phone_number = str_replace(array('+','-',' '), '', $phone_number);

        if(strlen($phone_number) > 10){

            $phone_number = substr($phone_number, -10);
        }
        
        return $phone_number;

    }


    /*
     * Add country code in phone number
     */
    public function  phoneWithCountryCode($phone_number,$country_code='92'){
        
        $phone_number = str_replace(array('+','-',' '), '', $phone_number);

        if(strlen($phone_number) > 10){

            $phone_number = substr(ltrim($phone_number,'+'), -10);
            $phone_number = '+'.$country_code.$phone_number;

        }else{

            //$phone_number = substr(ltrim($phone_number,'+'), -10);
            $phone_number = '+'.$country_code.$phone_number;
        }
       
        return $phone_number;

    }


}