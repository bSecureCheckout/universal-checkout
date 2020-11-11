<?php 

namespace Bsecure\UniversalCheckout\Block;

class Checkout extends \Magento\Checkout\Block\Onepage\Link
{
    // This function is created for testing purpose, you can add more function as per your requirement
    public function getTest()
    {
        return 'Test Button';
    }
}
