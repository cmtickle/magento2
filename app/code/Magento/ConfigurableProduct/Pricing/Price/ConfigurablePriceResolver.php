<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\ConfigurableProduct\Pricing\Price;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class ConfigurablePriceResolver implements PriceResolverInterface
{
    /** @var PriceResolverInterface */
    protected $priceResolver;

    /**
     * @var PriceCurrencyInterface
     * @deprecated
     */
    protected $priceCurrency;

    /**
     * @var Configurable
     * @deprecated
     */
    protected $configurable;

    /**
     * @var LowestPriceOptionsProviderInterface
     */
    private $lowestPriceOptionsProvider;

    /**
     * @param PriceResolverInterface $priceResolver
     * @param Configurable $configurable
     * @param PriceCurrencyInterface $priceCurrency
     * @param LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider
     */
    public function __construct(
        PriceResolverInterface $priceResolver,
        Configurable $configurable,
        PriceCurrencyInterface $priceCurrency,
        LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider = null
    ) {
        $this->priceResolver = $priceResolver;
        $this->configurable = $configurable;
        $this->priceCurrency = $priceCurrency;
        $this->lowestPriceOptionsProvider = $lowestPriceOptionsProvider ?:
            ObjectManager::getInstance()->get(LowestPriceOptionsProviderInterface::class);
    }

    /**
     * @param \Magento\Framework\Pricing\SaleableInterface|\Magento\Catalog\Model\Product $product
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function resolvePrice(\Magento\Framework\Pricing\SaleableInterface $product)
    {
        $price = null;

        foreach ($this->lowestPriceOptionsProvider->getProducts($product) as $subProduct) {
            $productPrice = $this->priceResolver->resolvePrice($subProduct);
            $price = $price ? min($price, $productPrice) : $productPrice;
        }
        if ($price === null) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Configurable product "%1" does not have sub-products', $product->getSku())
            );
        }

        return (float)$price;
    }
}