<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Hyva\MagefanAutoRelatedProduct\Plugin\Frontend\Hyva\Theme\ViewModel;

use Magefan\AutoRelatedProduct\Api\RelatedItemsProcessorInterface;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;

class ProductList
{

    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     */
    private $relatedItemsProcessor;

    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     */

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @param RelatedItemsProcessorInterface $relatedItemsProcessor
     * @param LayoutInterface $layout
     */
    public function __construct(
        RelatedItemsProcessorInterface $relatedItemsProcessor,
        LayoutInterface $layout
    ) {
        $this->relatedItemsProcessor = $relatedItemsProcessor;
        $this->layout = $layout;
    }

    /**
     * @param $subject
     * @param $result
     * @param string $linkType
     * @param ...$items
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetLinkedItems($subject, $result, string $linkType, ...$items)
    {
        if (!in_array($linkType, ['related', 'upsell'])) {
            return $result;
        }

        $abstractBlock = $this->layout->createBlock(AbstractBlock::class);

        $result = $this->relatedItemsProcessor->execute($abstractBlock, $result, 'product_into_' . $linkType);

        if (!is_array($result) && method_exists($result, 'getItems')) {
            $result = $result->getItems();
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetCrosssellItems($subject, $result)
    {
        $abstractBlock = $this->layout->createBlock(AbstractBlock::class);
        $result = $this->relatedItemsProcessor->execute($abstractBlock, $result, 'cart_into_crossSell');

        if (!is_array($result) && method_exists($result, 'getItems')) {
            $result = $result->getItems();
        }

        return $result;
    }
}
