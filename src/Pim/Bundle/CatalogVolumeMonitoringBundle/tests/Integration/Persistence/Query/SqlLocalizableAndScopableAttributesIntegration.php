<?php

declare(strict_types=1);

namespace Pim\Bundle\CatalogVolumeMonitoringBundle\tests\Integration\Persistence\Query;

use PHPUnit\Framework\Assert;
use Pim\Bundle\CatalogVolumeMonitoringBundle\tests\Integration\Persistence\AbstractQueryTestCase;

class SqlLocalizableAndScopableAttributesIntegration extends AbstractQueryTestCase
{
    public function testGetCountOfLocalizableAndScopableAttributes()
    {
        $query = $this->get('pim_volume_monitoring.persistence.query.localizable_and_scopable_attributes');
        $this->createLocalizableAndScopableAttributes(3);
        $this->createScopableAttributes(5);
        $this->createLocalizableAttributes(4);

        $volume = $query->fetch();

        Assert::assertEquals(3, $volume->getVolume());
        Assert::assertEquals('localizable_and_scopable_attributes', $volume->getVolumeName());
        Assert::assertEquals(false, $volume->hasWarning());
    }
}