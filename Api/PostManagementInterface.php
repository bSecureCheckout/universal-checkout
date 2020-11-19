<?php
namespace Bsecure\UniversalCheckout\Api;
 
interface PostManagementInterface
{

    /**
     * @api
     * @param string $sku
     * @return array
     */
    
    public function getPost($sku);

    /**
     * Post for product api
     * @param string POST
     * @return mixed[]
     */
    
    public function manageOrder();
}
