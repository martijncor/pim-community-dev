<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use PhpSpec\ObjectBehavior;

class ScalarValueSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('scopableLocalizableValue', ['my_text', 'A nice text', 'ecommerce', 'en_US']);
    }

    function it_returns_data()
    {
        $this->getData()->shouldReturn('A nice text');
    }
}
