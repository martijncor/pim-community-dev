<?php

namespace Pim\Bundle\DataGridBundle\Normalizer\Product;

use Akeneo\Pim\Enrichment\Component\Product\Value\OptionValueInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeOptionInterface;
use Akeneo\Pim\Structure\Component\Repository\AttributeOptionRepositoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @author    Marie Bochu <marie.bochu@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class OptionNormalizer implements NormalizerInterface
{
    /** @var AttributeOptionRepositoryInterface */
    protected $attributeOptionRepository;

    public function __construct(AttributeOptionRepositoryInterface $attributeOptionRepository)
    {
        $this->attributeOptionRepository = $attributeOptionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($optionValue, $format = null, array $context = [])
    {
        $optionCode = $optionValue->getData();
        $attributeCode = $optionValue->getAttributeCode();

        $option = $this->attributeOptionRepository->findOneByIdentifier($attributeCode.'.'.$optionCode);

        $label = '';
        if ($option instanceof AttributeOptionInterface) {
            if (isset($context['data_locale'])) {
                $option->setLocale($context['data_locale']);
            }
            $translation = $option->getTranslation();

            $label = null !== $translation->getValue() ?
                $translation->getValue() :
                sprintf('[%s]', $optionCode);
        }

        return [
            'locale' => $optionValue->getLocaleCode(),
            'scope'  => $optionValue->getScopeCode(),
            'data'   => $label
        ];
    }

    /**
     *
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'datagrid' === $format && $data instanceof OptionValueInterface;
    }
}
