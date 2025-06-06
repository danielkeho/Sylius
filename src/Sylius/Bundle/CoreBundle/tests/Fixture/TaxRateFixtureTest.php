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
use Sylius\Bundle\CoreBundle\Fixture\TaxRateFixture;

final class TaxRateFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function tax_rates_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function tax_rates_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 4]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function tax_rate_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['code' => 'CUSTOM']]]], 'custom.*.code');
    }

    #[Test]
    public function tax_rate_amount_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['amount' => 4.76]]]], 'custom.*.amount');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['amount' => 'string']]]], 'custom.*.amount');
    }

    #[Test]
    public function tax_rate_may_be_included_in_price_or_not(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['included_in_price' => false]]]], 'custom.*.included_in_price');
    }

    #[Test]
    public function tax_rate_zone_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['zone' => 'EUROPE']]]], 'custom.*.zone');
    }

    #[Test]
    public function tax_rate_category_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['category' => 'BOOKS']]]], 'custom.*.category');
    }

    #[Test]
    public function tax_rate_calculator_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['calculator' => 'custom']]]], 'custom.*.calculator');
    }

    protected function getConfiguration(): TaxRateFixture
    {
        return new TaxRateFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
