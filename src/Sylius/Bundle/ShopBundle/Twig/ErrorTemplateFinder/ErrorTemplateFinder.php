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

namespace Sylius\Bundle\ShopBundle\Twig\ErrorTemplateFinder;

use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\ShopBundle\SectionResolver\ShopSection;
use Sylius\Bundle\UiBundle\Twig\ErrorTemplateFinder\ErrorTemplateFinderInterface;
use Twig\Environment;

final readonly class ErrorTemplateFinder implements ErrorTemplateFinderInterface
{
    public function __construct(
        private SectionProviderInterface $sectionProvider,
        private Environment $twig,
    ) {
    }

    public function findTemplate(int $statusCode): ?string
    {
        $section = $this->sectionProvider->getSection();

        if ($section instanceof ShopSection) {
            $template = sprintf('@SyliusShop/errors/error%s.html.twig', $statusCode);
            $loader = $this->twig->getLoader();
            if ($loader->exists($template)) {
                return $template;
            }

            $template = '@SyliusShop/errors/error.html.twig';
            if ($loader->exists($template)) {
                return $template;
            }
        }

        return null;
    }
}
