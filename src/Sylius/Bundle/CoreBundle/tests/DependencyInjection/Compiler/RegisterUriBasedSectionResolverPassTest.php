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

namespace Tests\Sylius\Bundle\CoreBundle\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\Test;
use Sylius\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterUriBasedSectionResolverPass;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\UriBasedSectionProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterUriBasedSectionResolverPassTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function it_adds_method_call_to_uri_based_section_resolver_in_order(): void
    {
        $uriBasedSectionResolver = new Definition(UriBasedSectionProvider::class);
        $this->setDefinition('sylius.section_resolver.uri_based', $uriBasedSectionResolver);

        $shopUriBasedSectionProviderDefinition = new Definition(SectionProviderInterface::class);
        $shopUriBasedSectionProviderDefinition->addTag('sylius.uri_based_section_resolver');

        $this->setDefinition('sylius_shop.section_resolver.shop_uri_based', $shopUriBasedSectionProviderDefinition);

        $adminUriBasedSectionProviderDefinition = new Definition(SectionProviderInterface::class);
        $adminUriBasedSectionProviderDefinition->addTag('sylius.uri_based_section_resolver', ['priority' => 10]);

        $this->setDefinition('sylius_admin.section_resolver.admin_uri_based', $adminUriBasedSectionProviderDefinition);

        $adminApiUriBasedSectionProviderDefinition = new Definition(SectionProviderInterface::class);
        $adminApiUriBasedSectionProviderDefinition->addTag('sylius.uri_based_section_resolver', ['priority' => 5]);

        $this->setDefinition('sylius.section_resolver.admin_api_uri_based', $adminApiUriBasedSectionProviderDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sylius.section_resolver.uri_based',
            1,
            [
                new Reference('sylius_admin.section_resolver.admin_uri_based'),
                new Reference('sylius.section_resolver.admin_api_uri_based'),
                new Reference('sylius_shop.section_resolver.shop_uri_based'),
            ],
        );
    }

    #[Test]
    public function it_does_not_add_method_call_if_there_are_no_tagged_processors(): void
    {
        $uriBasedSectionResolver = new Definition(UriBasedSectionProvider::class);
        $this->setDefinition('sylius.section_resolver.uri_based', $uriBasedSectionResolver);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'sylius.section_resolver.uri_based',
            1,
            [],
        );
    }

    /**
     * @inheritdoc
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterUriBasedSectionResolverPass());
    }
}
