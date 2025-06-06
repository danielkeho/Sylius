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
use Sylius\Bundle\CoreBundle\Fixture\ShopUserFixture;

final class ShopUserFixtureTest extends TestCase
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
    public function user_first_name_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['first_name' => 'John']]]], 'custom.*.first_name');
    }

    #[Test]
    public function user_last_name_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['last_name' => 'Doe']]]], 'custom.*.last_name');
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

    #[Test]
    public function gender_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['gender' => 'u']]]], 'custom.*.gender');
    }

    #[Test]
    public function phone_number_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['phone_number' => '+1234567']]]], 'custom.*.phone_number');
    }

    #[Test]
    public function birthday_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['birthday' => '01-01-2001']]]], 'custom.*.birthday');
    }

    protected function getConfiguration(): ShopUserFixture
    {
        return new ShopUserFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
