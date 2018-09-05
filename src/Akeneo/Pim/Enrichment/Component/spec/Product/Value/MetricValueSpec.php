<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use PhpSpec\ObjectBehavior;
use Akeneo\Pim\Enrichment\Component\Product\Model\MetricInterface;

class MetricValueSpec extends ObjectBehavior
{
    function let(MetricInterface $metric)
    {
        $this->beConstructedThrough('scopableLocalizableValue', ['my_date', $metric, 'ecommerce', 'en_US']);
    }

    function it_returns_data($metric)
    {
        $this->getData()->shouldBeAnInstanceOf(MetricInterface::class);
        $this->getData()->shouldReturn($metric);
    }

    function it_returns_amount_of_metric($metric)
    {
        $metric->getData()->willReturn(12);

        $this->getAmount()->shouldReturn(12.0);
    }

    function it_returns_unit_of_metric($metric)
    {
        $metric->getUnit()->willReturn('KILO');

        $this->getUnit()->shouldReturn('KILO');
    }
}
