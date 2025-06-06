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

namespace Tests\Sylius\Bundle\CoreBundle\Fixture;

use Doctrine\Persistence\ObjectManager;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;
use Sylius\Bundle\CoreBundle\Fixture\ProductAssociationFixture;

final class ProductAssociationFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function product_assoiations_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function product_associations_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 4]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function product_association_type_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['type' => 'type']]]], 'custom.*.type');
    }

    #[Test]
    public function product_association_owner_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['owner' => 'product']]]], 'custom.*.owner');
    }

    #[Test]
    public function product_association_associated_products_are_optional(): void
    {
        $this->assertConfigurationIsValid(
            [[
                'custom' => [['associated_products' => ['product-1', 'product-2']]],
            ]],
            'custom.*.associated_products',
        )
        ;
    }

    protected function getConfiguration(): ProductAssociationFixture
    {
        return new ProductAssociationFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
