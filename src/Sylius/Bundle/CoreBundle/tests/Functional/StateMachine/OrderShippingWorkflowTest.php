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
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\OrderShippingStates;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class OrderShippingWorkflowTest extends KernelTestCase
{
    #[DataProvider('availableTransitionsFromReadyState')]
    #[Test]
    public function it_applies_all_available_transitions_for_ready_status(string $transition, string $expectedStatus): void
    {
        $stateMachine = $this->getStateMachine();
        $subject = new Order();
        $stateMachine->apply($subject, 'sylius_order_shipping', 'request_shipping');
        $stateMachine->apply($subject, 'sylius_order_shipping', $transition);

        $this->assertSame($expectedStatus, $subject->getShippingState());
    }

    #[Test]
    public function it_applies_ship_transition_if_order_is_partially_shipped(): void
    {
        $stateMachine = $this->getStateMachine();
        $subject = new Order();
        $stateMachine->apply($subject, 'sylius_order_shipping', 'request_shipping');
        $stateMachine->apply($subject, 'sylius_order_shipping', 'partially_ship');

        $this->assertSame(OrderShippingStates::STATE_PARTIALLY_SHIPPED, $subject->getShippingState());

        $stateMachine->apply($subject, 'sylius_order_shipping', 'ship');

        $this->assertSame(OrderShippingStates::STATE_SHIPPED, $subject->getShippingState());
    }

    public static function availableTransitionsFromReadyState(): iterable
    {
        yield ['cancel', 'cancelled'];
        yield ['partially_ship', 'partially_shipped'];
        yield ['ship', 'shipped'];
    }

    private function getStateMachine(): StateMachineInterface
    {
        return self::getContainer()->get('sylius_abstraction.state_machine.adapter.symfony_workflow');
    }
}
