<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\AbstractValue;
use Akeneo\Pim\Enrichment\Component\Product\Model\MetricInterface;

/**
 * Product value for "pim_catalog_metric" attribute type
 *
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MetricValue extends AbstractValue implements MetricValueInterface
{
    /** @var MetricInterface */
    protected $data;

    /**
     * {@inheritdoc}
     */
    protected function __construct(string $attributeCode, ?MetricInterface $data, ?string $scopeCode, ?string $localeCode)
    {
        parent::__construct($attributeCode, $data, $scopeCode, $localeCode);
    }

    /**
     */
    public function getData(): ?MetricInterface
    {
        return $this->data;
    }

    /**
     * @return float
     */
    public function getAmount(): ?float
    {
        return $this->data->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function getUnit(): ?string
    {
        return $this->data->getUnit();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if (null !== $this->data && (null !== $data = $this->data->getData())) {
            return sprintf('%.4F %s', $data, $this->data->getUnit());
        }

        return '';
    }
}
