<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Value;

use Akeneo\Pim\Enrichment\Component\Product\Exception\InvalidOptionException;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionInterface;
use Akeneo\Tool\Component\StorageUtils\Exception\InvalidPropertyTypeException;
use Akeneo\Tool\Component\StorageUtils\Repository\IdentifiableObjectRepositoryInterface;

/**
 * Factory that creates option (simple-select) product values.
 *
 * @internal  Please, do not use this class directly. You must use \Akeneo\Pim\Enrichment\Component\Product\Factory\ValueFactory.
 *
 * @author    Damien Carcel (damien.carcel@akeneo.com)
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
class OptionValueFactory extends AbstractValueFactory
{
    /** @var IdentifiableObjectRepositoryInterface */
    protected $attrOptionRepository;

    /** @var string */
    protected $productValueClass;

    /** @var string */
    protected $supportedAttributeType;

    /**
     * @param IdentifiableObjectRepositoryInterface $attrOptionRepository
     * @param string                             $productValueClass
     * @param string                             $supportedAttributeType
     */
    public function __construct(
        IdentifiableObjectRepositoryInterface $attrOptionRepository,
        $productValueClass,
        $supportedAttributeType
    ) {
        $this->attrOptionRepository = $attrOptionRepository;
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
     * Checks if data is valid.
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

        if (!is_string($data) && !is_numeric($data)) {
            throw InvalidPropertyTypeException::stringExpected(
                $attribute->getCode(),
                static::class,
                $data
            );
        }

        $identifier = $attribute->getCode() . '.' . $data;
        $option = $this->attrOptionRepository->findOneByIdentifier($identifier);

        if (null === $option) {
            throw InvalidOptionException::validEntityCodeExpected(
                $attribute->getCode(),
                'code',
                'The option does not exist',
                static::class,
                $data
            );
        }

        return $option;
    }
}
