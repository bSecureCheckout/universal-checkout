<?php

namespace Bsecure\UniversalCheckout\Model\Config\Source;

class ShowHideOptions implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'yes',
                'label' => __('Show bSecure Button')
            ],
            
            [
                'value' => 'no',
                'label' => __('Hide bSecure Button')
            ]
        ];
    }
}
