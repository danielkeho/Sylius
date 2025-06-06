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

namespace Sylius\Bundle\CurrencyBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueCurrencyPair extends Constraint
{
    /** @var string */
    public $message = 'sylius.exchange_rate.unique_currency_pair';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
