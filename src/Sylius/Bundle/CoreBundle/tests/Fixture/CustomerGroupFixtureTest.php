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
use Sylius\Bundle\CoreBundle\Fixture\CustomerGroupFixture;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;

final class CustomerGroupFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function customer_groups_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function customer_groups_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 4]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function customer_group_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['code' => 'code']]]], 'custom.*.code');
    }

    #[Test]
    public function customer_group_name_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['name' => 'name']]]], 'custom.*.name');
    }

    protected function getConfiguration(): CustomerGroupFixture
    {
        return new CustomerGroupFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
