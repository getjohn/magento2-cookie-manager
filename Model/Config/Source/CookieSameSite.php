<?php
namespace GetJohn\CookieManager\Model\Config\Source;

class CookieSameSite implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'Lax', 'label' => __('Lax')], ['value' => 'Strict', 'label' => __('Strict')], ['value' => 'None', 'label' => __('None')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['Lax' => __('Lax'), 'Strict' => __('Strict'), 'None' => __('Strict')];
    }
}
