<?php
namespace Bsecure\UniversalCheckout\Plugin;
 
use Magento\Checkout\Model\GuestPaymentInformationManagement as CheckoutGuestPaymentInformationManagement;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartManagementInterface;
use Psr\Log\LoggerInterface;
 
/**
 * Class GuestPaymentInformationManagement
 */
class GuestPaymentInformationManagement
{
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
 
    /**
     * @var LoggerInterface
     */
    private $logger;
 
    /**
     * GuestPaymentInformationManagement constructor.
     * @param CartManagementInterface $cartManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartManagementInterface $cartManagement,
        LoggerInterface $logger
    ) {
        $this->cartManagement = $cartManagement;
        $this->logger = $logger;
    }
 
    /**
     * @param CheckoutPaymentInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return int
     * @throws CouldNotSaveException
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
	    CheckoutGuestPaymentInformationManagement $subject,
	    \Closure $proceed,
	    $cartId,
	    $email,
	    \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
	    \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
	) {
	    $subject->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
	    try {
	        $orderId = $this->cartManagement->placeOrder($cartId);
	    } catch (LocalizedException $exception) {
	        throw new CouldNotSaveException(__($exception->getMessage()));
	    } catch (\Exception $exception) {
	        $this->logger->critical($exception);
	        throw new CouldNotSaveException(
	            __('An error occurred on the server. Please try to place the order again.'),
	            $exception
	        );
	    }
	    return $orderId;
	}
}