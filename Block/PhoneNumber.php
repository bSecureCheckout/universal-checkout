<?php


namespace Bsecure\UniversalCheckout\Block;

use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Serialize\Serializer\Json;
use \Bsecure\UniversalCheckout\Helper\Data;
use \Magento\Directory\Api\CountryInformationAcquirerInterface;

class PhoneNumber extends Template
{

    /**
     * @var Json
     */
    protected $jsonHelper;

    /**
     * @var Data
     */
    protected $bsecureHelper;

    /**
     * @var CountryInformationAcquirerInterface
     */
    protected $countryInformation;

    /**
     * PhoneNumber constructor.
     * @param Context $context
     * @param Json $jsonHelper
     */
    public function __construct(
        Context $context,
        Json $jsonHelper,
        CountryInformationAcquirerInterface $countryInformation,
        Data $bsecureHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->bsecureHelper = $bsecureHelper;
        $this->countryInformation = $countryInformation;
        parent::__construct($context);
    }

    /**
     * @return bool|string
     */
    public function phoneConfig()
    {
        $config  = [
            "nationalMode" => false,
            "utilsScript"  => $this->getViewFileUrl('Bsecure_UniversalCheckout::js/utils.js'),
            "initialCountry" => "pk",
            "hiddenInput" => "country_calling_code"
            //"preferredCountries" => [$this->bsecureHelper->preferedCountry()]
        ];

        //if ($this->bsecureHelper->getConfig('universalcheckout/general/auto_append_country_code')) {
            //$config["onlyCountries"] = explode(",", $this->bsecureHelper->allowedCountries());
        //}

        return $this->jsonHelper->serialize($config);
    }
}
