<?php 

namespace Bsecure\UniversalCheckout\Block\Cart;

use Magento\Framework\View\Element\Template;

class Sidebar extends Template
{
   /**
    * Sidebar constructor.
    * @param Template\Context $context
    * @param array $data
    */
   public function __construct(
       Template\Context $context,
       \Magento\Checkout\Helper\Cart $cartHelper,
       array $data = array()
   ) {

      $this->cartHelper = $cartHelper; 
      parent::__construct($context, $data);
   }


   public function isCartEmpty()
   {

    $quote = $this->cartHelper->getQuote();
    $totalItems = count($quote->getAllItems());    
 
    return ($totalItems == 0) ? true : false;

   }
}
