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

namespace Tests\Sylius\Bundle\CoreBundle\Functional\StateMachine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\Shipment;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ShipmentWorkflowTest extends KernelTestCase
{
    /** @var ShipmentInterface|MockObject */
    protected ShipmentInterface $shipment;

    /** @var OrderInterface|MockObject */
    protected OrderInterface $order;

    public function setUp(): void
    {
        parent::setUp();
        $this->order = new Order();

        $this->shipment = new Shipment();
        $this->shipment->setOrder($this->order);
    }

    #[DataProvider('availableTransitionsFromReadyState')]
    #[Test]
    public function it_applies_all_available_transitions_for_create_state(string $transition, string $expectedStatus): void
    {
        $stateMachine = $this->getStateMachine();
        $stateMachine->apply($this->order, 'sylius_order_shipping', 'request_shipping');
        $stateMachine->apply($this->shipment, 'sylius_shipment', 'create');
        $stateMachine->apply($this->shipment, 'sylius_shipment', $transition);

        $this->assertSame($expectedStatus, $this->shipment->getState());
    }

    public static function availableTransitionsFromReadyState(): iterable
    {
        yield ['cancel', 'cancelled'];
        yield ['ship', 'shipped'];
    }

    private function getStateMachine(): StateMachineInterface
    {
        return self::getContainer()->get('sylius_abstraction.state_machine.adapter.symfony_workflow');
    }
}
