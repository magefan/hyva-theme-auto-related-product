<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Hyva\MagefanAutoRelatedProduct\Plugin\Frontend\Magento\Catalog\Block\Product;

use Magefan\AutoRelatedProduct\Block\RelatedProductList;
use Magefan\AutoRelatedProduct\Api\RelatedCollectionInterfaceFactory as RuleCollectionFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magefan\AutoRelatedProduct\Model\ActionValidator;

class View
{
    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var ActionValidator
     */
    private $validator;

    /**
     * @var null
     */
    private $rules = null;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LayoutInterface $layout
     * @param ActionValidator $validator
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        ActionValidator $validator,
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
        $this->layout = $layout;
        $this->validator = $validator;
    }

    /**
     * @param $subject
     * @param $result
     * @param string $alias
     * @param bool $useCache
     * @return mixed|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetChildHtml($subject, $result, $alias = '', $useCache = true)
    {
        if (!in_array($alias, ['related', 'upsell'])) {
            return $result;
        }

        $ruleBefore = false;
        $ruleAfter = false;

        foreach ($this->getRulesForBeforeAfterPosition() as $item) {
            if ($item->getBlockPosition() === 'product_before_' . $alias && !$ruleBefore) {
                if (!$this->validator->isRestricted($item)) {
                    $ruleBefore = $item;
                }
            }

            if ($item->getBlockPosition() === 'product_after_' . $alias && !$ruleAfter) {
                if (!$this->validator->isRestricted($item)) {
                    $ruleAfter = $item;
                }
            }
        }

        if ($ruleBefore) {
            $ruleBeforeHtml = $this->layout->createBlock(RelatedProductList::class, $ruleBefore->getRuleBlockIdentifier())
                ->setData('rule', $ruleBefore)->toHtml();
            $result = $ruleBeforeHtml . $result;
        }

        if ($ruleAfter) {
            $ruleAfterHtml = $this->layout->createBlock(RelatedProductList::class, $ruleAfter->getRuleBlockIdentifier())
                ->setData('rule', $ruleAfter)->toHtml();
            $result .= $ruleAfterHtml;
        }

        return $result;
    }

    /**
     * @return null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getRulesForBeforeAfterPosition()
    {
        if (null === $this->rules) {
            $this->rules = $this->ruleCollectionFactory->create()
                ->addActiveFilter()
                ->addStoreFilter($this->storeManager->getStore()->getId())
                ->addFieldToFilter('block_position', ['in' => [
                    'product_before_related',
                    'product_after_related',
                    'product_before_upsell',
                    'product_after_upsell',
                ]])
                ->setOrder('priority', 'ASC');
        }

        return $this->rules;
    }
}
