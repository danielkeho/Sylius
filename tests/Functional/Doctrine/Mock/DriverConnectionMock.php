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

namespace Sylius\Tests\Functional\Doctrine\Mock;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

class DriverConnectionMock implements Connection
{
    public function prepare($prepareString): Statement
    {
        return new StatementMock();
    }

    public function query(?string $sql = null): Result
    {
        return new DriverResultMock();
    }

    public function quote($input, $type = ParameterType::STRING)
    {
        return (string) $input;
    }

    public function exec($statement): int
    {
        throw new \BadMethodCallException('Not implemented');
    }

    public function lastInsertId($name = null)
    {
    }

    public function beginTransaction()
    {
    }

    public function commit()
    {
    }

    public function rollBack()
    {
    }

    public function errorCode()
    {
    }

    public function errorInfo()
    {
    }
}
