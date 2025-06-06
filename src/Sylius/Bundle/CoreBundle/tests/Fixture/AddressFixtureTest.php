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
use Sylius\Bundle\CoreBundle\Fixture\AddressFixture;
use Sylius\Bundle\CoreBundle\Fixture\Factory\ExampleFactoryInterface;

final class AddressFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function addresses_are_optional(): void
    {
        $this->assertConfigurationIsValid([[]], 'custom');
    }

    #[Test]
    public function addresses_can_be_generated_randomly(): void
    {
        $this->assertConfigurationIsValid([['random' => 5]], 'random');
        $this->assertPartialConfigurationIsInvalid([['random' => -1]], 'random');
    }

    #[Test]
    public function address_names_are_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['first_name' => 'John']]]], 'custom.*.first_name');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['first_name' => '']]]], 'custom.*.first_name');

        $this->assertConfigurationIsValid([['custom' => [['last_name' => 'Doe']]]], 'custom.*.last_name');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['last_name' => '']]]], 'custom.*.last_name');
    }

    #[Test]
    public function address_may_contain_phone_number(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['phone_number' => '1234567890']]]], 'custom.*.phone_number');
        $this->assertConfigurationIsValid([['custom' => [['phone_number' => '']]]], 'custom.*.phone_number');
    }

    #[Test]
    public function address_may_contain_company(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['company' => 'test company inc.']]]], 'custom.*.company');
        $this->assertConfigurationIsValid([['custom' => [['company' => '']]]], 'custom.*.company');
    }

    #[Test]
    public function address_street_is_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['street' => 'Assert Av.']]]], 'custom.*.street');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['street' => '']]]], 'custom.*.street');
    }

    #[Test]
    public function address_city_is_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['city' => 'Melbourne']]]], 'custom.*.city');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['city' => '']]]], 'custom.*.city');
    }

    #[Test]
    public function address_postcode_is_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['postcode' => '01-2345']]]], 'custom.*.postcode');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['postcode' => '']]]], 'custom.*.postcode');
    }

    #[Test]
    public function address_country_code_is_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['country_code' => 'UK']]]], 'custom.*.country_code');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['country_code' => '']]]], 'custom.*.country_code');
    }

    #[Test]
    public function address_province_code_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['province_code' => 'UK-YS']]]], 'custom.*.province_code');
    }

    #[Test]
    public function address_province_name_is_optional(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['province_name' => 'Yorkshire']]]], 'custom.*.province_name');
    }

    #[Test]
    public function address_customer_is_optional_but_cannot_be_empty(): void
    {
        $this->assertConfigurationIsValid([['custom' => [['customer' => 'example@example.com']]]], 'custom.*.customer');
        $this->assertPartialConfigurationIsInvalid([['custom' => [['customer' => '']]]], 'custom.*.customer');
    }

    protected function getConfiguration(): AddressFixture
    {
        return new AddressFixture(
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            $this->getMockBuilder(ExampleFactoryInterface::class)->getMock(),
        );
    }
}
