<?php

namespace Bsecure\UniversalCheckout\Model\Order\Email\Sender;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\Sender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\App\Request\Http as Request;
use Bsecure\UniversalCheckout\Helper\Data as BsecureHelper;
//use Magento\Framework\App\ObjectManager as ObjectManager;


class OrderSender extends \Magento\Sales\Model\Order\Email\Sender\OrderSender {



    /**
     * Sends order email to the customer.
     *
     * Email will be sent immediately in two cases:
     *
     * - if asynchronous email sending is disabled in global settings
     * - if $forceSyncMode parameter is set to TRUE
     *
     * Otherwise, email will be sent later during running of
     * corresponding cron job.
     *
     * @param Order $order
     * @param bool $forceSyncMode
     * @return bool
     */
    public function send(Order $order, $forceSyncMode = false)
    {
        
        
        $payment = $order->getPayment()->getMethodInstance();
        $paymentCode = $payment->getCode();
        $additionalData = $payment->getAdditionalInformation();
        
        //$isBsecureOrder = (isset($_GET['bsecure_order'])) ? $_GET['bsecure_order'] : 0;
        
      //  $moduleEnabled = $this->bsecureHelper->getConfig('universalcheckout/general/enable');

        if($paymentCode == 'bsecurepayment'){
            //if(rtrim($isBsecureOrder,"/") != 1){
                return false;
            
            //}
        }

        $order->setSendEmail(true);
        if (!$this->globalConfig->getValue('sales_email/general/async_sending') || $forceSyncMode) {
            if ($this->checkAndSend($order)) {
                //$order->setCanSendNewEmailFlag(true);
                //$objectManager =  ObjectManager::getInstance();               

                //$order->setCustomerEmail('khalique.ahmed3@gmail.com');
               // $objectManager->create('\Magento\Sales\Model\OrderNotifier')->notify($order);
                $order->setEmailSent(true);               

                $this->orderResource->saveAttribute($order, ['send_email', 'email_sent']);
                return true;
            }
        }

        $this->orderResource->saveAttribute($order, 'send_email');

        return false;
    }
}