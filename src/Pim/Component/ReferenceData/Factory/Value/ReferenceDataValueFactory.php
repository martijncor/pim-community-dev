<?php

namespace Pim\Component\ReferenceData\Factory\Value;

use Akeneo\Pim\Enrichment\Component\Product\Factory\Value\AbstractValueFactory;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyException;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;
use Pim\Component\ReferenceData\Model\ReferenceDataInterface;
use Pim\Component\ReferenceData\Repository\ReferenceDataRepositoryInterface;
use Pim\Component\ReferenceData\Repository\ReferenceDataRepositoryResolverInterface;

/**
 * Factory that creates simple-select and multi-select product values.
 *
 * @internal  Please, do not use this class directly. You must use \Akeneo\Pim\Enrichment\Component\Product\Factory\ProductValueFactory.
 *
 * @author    Damien Carcel (damien.carcel@akeneo.com)
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class ReferenceDataValueFactory extends AbstractValueFactory
{
    /** @var ReferenceDataRepositoryResolverInterface */
    protected $repositoryResolver;

    /** @var string */
    protected $productValueClass;

    /** @var string */
    protected $supportedAttributeType;

    /**
     * @param ReferenceDataRepositoryResolverInterface $repositoryResolver
     * @param string                                   $productValueClass
     * @param string                                   $supportedAttributeType
     */
    public function __construct(
        ReferenceDataRepositoryResolverInterface $repositoryResolver,
        $productValueClass,
        $supportedAttributeType
    ) {
        $this->repositoryResolver = $repositoryResolver;
        $this->productValueClass = $productValueClass;
        $this->supportedAttributeType = $supportedAttributeType;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeInterface $attribute, $channelCode, $localeCode, $data)
    {
        $this->checkData($attribute, $data);

        return $this->doCreate($attribute, $channelCode, $localeCode, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($attributeType)
    {
        return $attributeType === $this->supportedAttributeType;
    }

    /**
     * Check if data is valid
     *
     * @param AttributeInterface $attribute
     * @param mixed              $data
     *
     * @throws InvalidPropertyTypeException
     */
    protected function checkData(AttributeInterface $attribute, $data)
    {
        if (null === $data) {
            return;
        }

        if (!is_string($data)) {
            throw InvalidPropertyTypeException::stringExpected(
                $attribute->getCode(),
                static::class,
                $data
            );
        }

        $repository = $this->repositoryResolver->resolve($attribute->getReferenceDataName());
        $referenceData = $repository->findOneBy(['code' => $data]);

        if (null === $referenceData) {
            throw InvalidPropertyException::validEntityCodeExpected(
                $attribute->getCode(),
                'reference data code',
                sprintf('The code of the reference data "%s" does not exist', $attribute->getReferenceDataName()),
                static::class,
                $data
            );
        }
    }
}
