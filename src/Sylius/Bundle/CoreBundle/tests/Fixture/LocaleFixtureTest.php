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
use Sylius\Bundle\CoreBundle\Fixture\LocaleFixture;
use Sylius\Resource\Factory\FactoryInterface;

final class LocaleFixtureTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    #[Test]
    public function locales_are_not_required(): void
    {
        $this->assertConfigurationIsValid([[]], 'locales');
    }

    #[Test]
    public function locales_can_be_set(): void
    {
        $this->assertConfigurationIsValid([['locales' => ['en_US', 'pl_PL', 'es_ES']]], 'locales');
    }

    #[Test]
    public function default_locale_may_not_be_loaded(): void
    {
        $this->assertProcessedConfigurationEquals(
            [['load_default_locale' => false]],
            ['load_default_locale' => false],
            'load_default_locale',
        );
    }

    #[Test]
    public function default_locale_is_added_by_default(): void
    {
        $this->assertProcessedConfigurationEquals(
            [[]],
            ['load_default_locale' => true],
            'load_default_locale',
        );
    }

    protected function getConfiguration(): LocaleFixture
    {
        return new LocaleFixture(
            $this->getMockBuilder(FactoryInterface::class)->getMock(),
            $this->getMockBuilder(ObjectManager::class)->getMock(),
            'default_LOCALE',
        );
    }
}
