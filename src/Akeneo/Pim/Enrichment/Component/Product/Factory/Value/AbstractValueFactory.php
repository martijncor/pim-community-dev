<?php

namespace Akeneo\Pim\Enrichment\Component\Product\Factory\Value;

use Akeneo\Pim\Enrichment\Component\Product\Model\ValueInterface;
use Akeneo\Pim\Structure\Component\Model\AttributeInterface;

/**
 * Abstract value factory
 *
 * @author    Benoit Jacquemont (benoit@akeneo.com)
 * @copyright 2018 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
abstract class AbstractValueFactory implements ValueFactoryInterface
{
    /**
     * Handle the condition to call the proper named constructor
     */
    protected function doCreate(AttributeInterface $attribute, ?string $channelCode, ?string $localeCode, $data): ValueInterface
    {
        if ($attribute->isScopable() && $attribute->isLocalizable()) {
            $value = $this->productValueClass::scopableLocalizableValue($attribute->getCode(), $data, $channelCode, $localeCode);
        } else {
            if ($attribute->isScopable()) {
                $value = $this->productValueClass::scopablevalue($attribute->getCode(), $data, $channelCode);
            } elseif ($attribute->isLocalizable()) {
                $value = $this->productValueClass::localizableValue($attribute->getCode(), $data, $localeCode);
            } else {
                $value = $this->productValueClass::value($attribute->getCode(), $data);
            }
        }

        return $value;
    }
}
