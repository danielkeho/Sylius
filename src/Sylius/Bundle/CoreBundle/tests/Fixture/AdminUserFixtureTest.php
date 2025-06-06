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
use Sylius\Bundle\CoreBundle\Fixture\AdminUserFixture;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;

final class AdminUserFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function users_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function users_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 4]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function user_username_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['username' => 'John Doe']]]], 'custom.*.username');
    }

    #[Test]
    public function user_may_be_toggled(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['enabled' => false]]]], 'custom.*.enabled');
    }

    #[Test]
    public function user_password_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['password' => 'I.<3.Krzysztof.Krawczyk']]]], 'custom.*.password');
    }

    protected function getConfiguration(): AdminUserFixture
    {
        return new AdminUserFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
