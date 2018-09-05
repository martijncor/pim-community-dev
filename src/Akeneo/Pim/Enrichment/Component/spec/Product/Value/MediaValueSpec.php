<?php

namespace spec\Akeneo\Pim\Enrichment\Component\Product\Value;

use Akeneo\Tool\Component\FileStorage\Model\FileInfoInterface;
use PhpSpec\ObjectBehavior;

class MediaValueSpec extends ObjectBehavior
{
    function let(FileInfoInterface $fileInfo)
    {
        $this->beConstructedThrough('scopableLocalizableValue', ['my_media', $fileInfo, 'ecommerce', 'en_US']);
    }

    function it_returns_data($fileInfo)
    {
        $this->getData()->shouldBeAnInstanceOf(FileInfoInterface::class);
        $this->getData()->shouldReturn($fileInfo);
    }
}
