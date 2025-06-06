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
use Sylius\Bundle\CoreBundle\Fixture\CurrencyFixture;
use Sylius\Resource\Factory\FactoryInterface;

final class CurrencyFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function currencies_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'currencies');
    }

    #[Test]
    public function currencies_can_be_set(): void
    {
        $this->assertConfigurationIsValid([['currencies' => ['EUR', 'USD', 'PLN']]], 'currencies');
    }

    protected function getConfiguration(): CurrencyFixture
    {
        return new CurrencyFixture(
            $this->getMockBuilder(FactoryInterface::class)->getMock(),
            $this->getMockBuilder(ObjectManager::class)->getMock(),
        );
    }
}
