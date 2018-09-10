<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\AbstractValue;
use Akeneo\Pim\Enrichment\Component\Product\Model\PriceCollectionInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductPriceInterface;

/**
 * Product value for "pim_catalog_price_collection" attribute type
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class PriceCollectionValue extends AbstractValue implements PriceCollectionValueInterface
{
    /** @var PriceCollectionInterface */
    protected $data;

    /**
     * {@inheritdoc}
     */
    protected function __construct(
        string $attributeCode,
        ?PriceCollectionInterface $data,
        ?string $scopeCode,
        ?string $localeCode
    ) {
        parent::__construct($attributeCode, $data, $scopeCode, $localeCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): ?PriceCollectionInterface
    {
        return $this->data;
    }

    /**
     * @param string $currency
     *
     * @return PriceCollectionInterface|null
     */
    public function getPrice(string $currency): ?ProductPriceInterface
    {
        foreach ($this->data as $price) {
            if ($price->getCurrency() === $currency) {
                return $price;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasData(): bool
    {
        foreach ($this->data as $price) {
            if (null !== $price->getData()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $options = [];
        foreach ($this->data as $price) {
            if (null !== $price->getData()) {
                $options[] = sprintf('%.2F %s', $price->getData(), $price->getCurrency());
            }
        }

        return implode(', ', $options);
    }
}
