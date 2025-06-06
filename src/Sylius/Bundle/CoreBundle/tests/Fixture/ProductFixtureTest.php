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
use Sylius\Bundle\CoreBundle\Fixture\ProductFixture;

final class ProductFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function products_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function products_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 4]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function product_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['code' => 'CUSTOM']]]], 'custom.*.code');
    }

    #[Test]
    public function product_may_be_toggled(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['enabled' => false]]]], 'custom.*.enabled');
    }

    #[Test]
    public function product_slug_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['slug' => 'the-slug']]]], 'custom.*.slug');
        $this->assertConfigurationIsValid([['custom' => [['slug' => '']]]], 'custom.*.slug');
    }

    #[Test]
    public function product_description_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['description' => 'Foo bar baz']]]], 'custom.*.description');
    }

    #[Test]
    public function product_short_description_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['short_description' => 'Foo bar']]]], 'custom.*.short_description');
    }

    #[Test]
    public function product_main_taxon_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['main_taxon' => 'TXN-0']]]], 'custom.*.main_taxon');
    }

    #[Test]
    public function product_taxons_are_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['taxons' => ['TXN-1', 'TXN-2']]]]], 'custom.*.taxons');
    }

    #[Test]
    public function product_channels_are_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['channels' => ['CHN-1', 'CHN-2']]]]], 'custom.*.channels');
        $this->assertProcessedConfigurationEquals(
            [['custom' => [['channels' => []]]]],
            ['custom' => [['channels' => []]]],
            'custom.*.channels',
        );
        $this->assertProcessedConfigurationEquals(
            [['custom' => [['channels' => null]]]],
            ['custom' => [[]]],
            'custom.*.channels',
        );
    }

    #[Test]
    public function product_variant_selection_method_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['variant_selection_method' => 'custom']]]], 'custom.*.variant_selection_method');
        $this->assertConfigurationIsValid([['custom' => [['variant_selection_method' => 'match']]]], 'custom.*.variant_selection_method');
    }

    #[Test]
    public function product_product_options_are_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['product_options' => ['OPT-1', 'OPT-2']]]]], 'custom.*.product_options');
    }

    #[Test]
    public function product_product_attributes_are_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['product_attributes' => ['ATTR-1', 'ATTR-2']]]]], 'custom.*.product_attributes');
    }

    #[Test]
    public function product_images_are_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['images' => [['path' => '../image/path1.jpg', 'type' => 'main'], ['path' => '../image/path2.jpg', 'type' => 'thumbnail']]]]]], 'custom.*.images');
    }

    #[Test]
    public function product_can_require_shipping(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['shipping_required' => false]]]], 'custom.*.shipping_required');
    }

    protected function getConfiguration(): ProductFixture
    {
        return new ProductFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
