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

namespace Tests\Sylius\Bundle\CoreBundle\Application;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusPluginTraitTest extends TestCase
{
    #[Test]
    public function it_does_not_crash(): void
    {
        $class = new class() extends Bundle {
            use SyliusPluginTrait;
        };

        Assert::assertNotNull($class);
    }
}
