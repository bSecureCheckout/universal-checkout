<?php

namespace Bsecure\UniversalCheckout\Model\Config\Source;

class ListMode implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => 'bsecure_only',
                'label' => __('Show only bSecure checkout button')
            ],
            
            [
                'value' => 'bsecure_mag_both',
                'label' => __('Show both bSecure checkout and default Magento checkout buttons')
            ],

            [
                'value' => 'bsecure_mag_only',
                'label' => __('Show only Magento default checkout button')
            ]
        ];
    }
}
