<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\AdminBundle\Twig\Component\Product;

use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\TwigHooks\LiveComponent\HookableLiveComponentTrait;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

class ProductAttributeAutocompleteComponent
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use HookableLiveComponentTrait;
    use TemplatePropTrait;

    /** @var array<string> */
    #[LiveProp(writable: true, hydrateWith: 'hydrateSelectedAttributeCodes', dehydrateWith: 'dehydrateSelectedAttributeCodes', updateFromParent: true)]
    #[ExposeInTemplate(name: 'selected_attribute_codes')]
    public array $selectedAttributeCodes = [];

    /** @var array<string> */
    #[LiveProp(updateFromParent: true)]
    public array $excludedAttributeCodes = [];

    public function __construct(
        protected readonly ChecksumCalculator $checksumCalculator,
    ) {
    }

    #[ExposeInTemplate(name: 'extra_options')]
    public function getExtraOptions(): string
    {
        return base64_encode(json_encode(
            [
                'attributeCodes' => $this->excludedAttributeCodes,
                '@checksum' => $this->checksumCalculator->calculateForArray(['attributeCodes' => $this->excludedAttributeCodes]),
            ],
        ));
    }

    /**
     * @return array<string>
     */
    public function hydrateSelectedAttributeCodes(string $value): array
    {
        if ('' === $value) {
            return [];
        }

        return explode(',', $value);
    }

    /**
     * @param array<string> $value
     */
    public function dehydrateSelectedAttributeCodes(array $value): string
    {
        return implode(',', $value);
    }
}
