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
use Sylius\Component\Core\Model\CatalogPromotion;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CatalogPromotionWorkflowTest extends KernelTestCase
{
    #[Test]
    public function it_applies_available_transition_for_catalog_promotion_inactive_status(): void
    {
        $stateMachine = $this->getStateMachine();
        $catalogPromotion = new CatalogPromotion();

        $stateMachine->apply($catalogPromotion, 'sylius_catalog_promotion', 'process');

        $this->assertSame('processing', $catalogPromotion->getState());
    }

    #[Test]
    public function it_applies_available_transition_for_catalog_promotion_active_status(): void
    {
        $stateMachine = $this->getStateMachine();
        $catalogPromotion = new CatalogPromotion();
        $catalogPromotion->setState('active');

        $stateMachine->apply($catalogPromotion, 'sylius_catalog_promotion', 'process');

        $this->assertSame('processing', $catalogPromotion->getState());
    }

    #[DataProvider('availableTransitionsForProcessingState')]
    #[Test]
    public function it_applies_all_available_transition_for_catalog_promotion_processing_status(
        string $transition,
        string $expectedStatus,
    ): void {
        $stateMachine = $this->getStateMachine();
        $catalogPromotion = new CatalogPromotion();
        $catalogPromotion->setState('processing');

        $stateMachine->apply($catalogPromotion, 'sylius_catalog_promotion', $transition);

        $this->assertSame($expectedStatus, $catalogPromotion->getState());
    }

    public static function availableTransitionsForProcessingState(): iterable
    {
        yield ['activate', 'active'];
        yield ['deactivate', 'inactive'];
    }

    private function getStateMachine(): StateMachineInterface
    {
        return self::getContainer()->get('sylius_abstraction.state_machine.adapter.symfony_workflow');
    }
}
