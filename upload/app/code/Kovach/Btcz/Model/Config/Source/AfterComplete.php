<?php


namespace Kovach\Btcz\Model\Config\Source;

class AfterComplete implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [
			['value' => 'processing', 'label' => __('Processing')],
			['value' => 'complete', 'label' => __('Complete')]
		];
    }

    public function toArray()
    {
        return [
			'processing' => __('Processing'),
			'complete' => __('Complete')
		];
    }
}