<?php 
namespace Bsecure\UniversalCheckout\Api;
 
interface PostManagementInterface
{

    /**
     * GET for Post api
     * @param string $sku
     * @return string
     */
    
    public function getPost($sku);


    /**
     * Post for product api
     * @param string POST
     * @return mixed[]
     */
    
    public function manageOrder();
}