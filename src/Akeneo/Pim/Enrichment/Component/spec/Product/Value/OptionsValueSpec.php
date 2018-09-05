<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use PhpSpec\ObjectBehavior;

class OptionsValueSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough(
            'scopableLocalizableValue',
            ['my_options', ['option_a', 'option_b'], 'ecommerce', 'en_US']
        );
    }

    function it_returns_data()
    {
        $this->getData()->shouldReturn(['option_a', 'option_b']);
    }

    function it_checks_if_code_exist()
    {
        $this->hasCode('option_a')->shouldReturn(true);
        $this->hasCode('option_c')->shouldReturn(false);
    }
}
