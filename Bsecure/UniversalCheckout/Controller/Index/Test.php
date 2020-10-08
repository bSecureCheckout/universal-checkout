<?php
namespace Bsecure\UniversalCheckout\Controller\Index;

class Test extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $request;


	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $pageFactory,
		\Magento\Framework\App\Request\Http $request)
	{
		$this->_pageFactory = $pageFactory;
		$this->request = $request;
		return parent::__construct($context);
	}

	public function execute()
	{
		var_dump($this->request->getParam('order_ref'));
		echo "Hello World";
		exit;
	}
}