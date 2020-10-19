<?php

namespace Bsecure\UniversalCheckout\Observer;


class HandleMiniCart implements \Magento\Framework\Event\ObserverInterface
{
	public function __construct(
       \Bsecure\UniversalCheckout\Helper\Data $bsecureHelper,
       \Magento\Checkout\Helper\Cart $cartHelper
    ){
    	$this->bsecureHelper 	= $bsecureHelper;
    	$this->cartHelper 		= $cartHelper;

    }


	public function execute(\Magento\Framework\Event\Observer $observer)
	{

		
		$module_enabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

		

		if($module_enabled && $this->cartHelper->getItemsCount() === 0){

			var_dump('expression','deleted all');
			// clear mini cart //
			/*echo "<script type='text/javascript'>
				window.addEventListener( 'load', function( event ) {
					require([
			           	'Magento_Customer/js/customer-data'
			        	], function (customerData) {
			            var sections = ['cart'];
			            customerData.invalidate(sections);
			            customerData.reload(sections, true);
			        });
	        	});
			</script>";*/

		}


		return $this;
	}


}