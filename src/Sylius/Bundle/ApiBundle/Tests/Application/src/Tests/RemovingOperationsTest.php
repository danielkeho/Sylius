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

namespace Sylius\Bundle\ApiBundle\Application\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

final class RemovingOperationsTest extends ApiTestCase
{
    use SetUpTestsTrait;

    public function setUp(): void
    {
        $this->setFixturesFiles();
        $this->setUpTest();
    }

    #[Test]
    public function it_allows_to_remove_an_existing_operation(): void
    {
        static::createClient()->request('GET', '/api/v2/shop/currencies');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
