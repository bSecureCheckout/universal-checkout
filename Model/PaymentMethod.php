<?php
 
namespace Bsecure\UniversalCheckout\Model;
use Magento\Framework\DataObject;
 
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

    /**
     * @var string
     */
    protected $_formBlockType = \Magento\Payment\Block\Form::class;

    /**
     * @var string
     */
    protected $_infoBlockType = \Magento\Payment\Block\Info::class;

    /**
     * @var bool
     */
    protected $_isOffline = false;


      

  
    
}
