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
use Magento\Framework\Escaper;

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
     * @var Escaper
     */
    private $escaper;

    /**
     * @var null
     */
    private $rules = null;

    /**
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LayoutInterface $layout
     * @param ActionValidator $validator
     * @param Escaper $escaper
     */
    public function __construct(
        RuleCollectionFactory $ruleCollectionFactory,
        StoreManagerInterface $storeManager,
        LayoutInterface $layout,
        ActionValidator $validator,
        Escaper $escaper
    ) {
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->storeManager = $storeManager;
        $this->layout = $layout;
        $this->validator = $validator;
        $this->escaper = $escaper;
    }

    /**
     * Replace native related/upsell slider heading with rule block title
     *
     * @param $subject
     * @param mixed $html
     * @return mixed
     */
    public function afterToHtml($subject, $html)
    {
        if (!$html || !is_string($html)
            || !in_array($subject->getData('type'), ['related', 'upsell'], true)
        ) {
            return $html;
        }

        // Title is set by RelatedItemsProcessor while the slider renders its items
        $title = trim((string)$subject->getData('mfautorp_title'));
        if ('' === $title) {
            return $html;
        }

        $title = (string)__($title);

        $result = preg_replace_callback(
            '#(<(h[1-6])\b[^>]*>)(.*?)(</\2>)#s',
            function ($matches) use ($title) {
                return $matches[1] . $this->escaper->escapeHtml($title, ['span', 'p']) . $matches[4];
            },
            $html,
            1
        );

        if (null === $result) {
            return $html;
        }

        $ariaResult = preg_replace_callback(
            '#(<section\b[^>]*\baria-label=")[^"]*(")#',
            function ($matches) use ($title) {
                return $matches[1] . $this->escaper->escapeHtmlAttr($title) . $matches[2];
            },
            $result,
            1
        );

        return null === $ariaResult ? $result : $ariaResult;
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
