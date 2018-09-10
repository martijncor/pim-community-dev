<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use PhpSpec\ObjectBehavior;

class DateValueSpec extends ObjectBehavior
{
    function let(\DateTime $date)
    {
        $this->beConstructedThrough('scopableLocalizableValue', ['my_date', $date, 'ecommerce', 'en_US']);
    }

    function it_returns_data($date)
    {
        $this->getData()->shouldBeAnInstanceOf(\DateTime::class);
        $this->getData()->shouldReturn($date);
    }
}
