<?php

namespace Bsecure\UniversalCheckout\Api;
 
interface PostManagementInterface
{

    /**
     * @api
     * @param string $sku
     * @return mixed[]
     * @since 1.0.0
     */
    
    public function getPost($sku);

    /**
     * Post for product api
     * @param string POST
     * @return mixed[]
     * @since 1.0.0
     */
    
    public function manageOrder();
}
