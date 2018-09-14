<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Normalizer\Storage\Product;

use Akeneo\Pim\Enrichment\Component\Product\Model\ValueCollectionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

/**
 * Normalizer for a collection of product values
 *
 * @author    Julien Janvier <jjanvier@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductValuesNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function normalize($values, $format = null, array $context = [])
    {
        $result = [];
        foreach ($values as $value) {
            $normalizedValue = $this->serializer->normalize($value, $format, $context);
            $attributeCode = $value->getAttributeCode();

            if (!isset($result[$attributeCode])) {
                $result[$attributeCode] = [];
            }

            $result[$attributeCode] = array_merge_recursive($result[$attributeCode], $normalizedValue[$attributeCode]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return 'storage' === $format && $data instanceof ValueCollectionInterface;
    }
}
