<?php
 
namespace Bsecure\UniversalCheckout\Model;
 
/**
 * Pay In Store payment method model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
 
    /**
     * Payment code
     *
     * @var string
     */
    const METHOD_CODE     = 'bsecurepayment';
 
    protected $_code      = self::METHOD_CODE;

    protected $_isOffline = true;
}
