<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use PhpSpec\ObjectBehavior;

class OptionValueSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('scopableLocalizableValue', ['my_option', 'option_a', 'ecommerce', 'en_US']);
    }

    function it_returns_data()
    {
        $this->getData()->shouldReturn('option_a');
    }
}
