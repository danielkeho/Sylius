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

namespace Sylius\Bundle\ShopBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class AccountMenuBuilder
{
    public const EVENT_NAME = 'sylius.menu.shop.account';

    public function __construct(private FactoryInterface $factory, private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function createMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu->setLabel('sylius.menu.shop.account.header');

        $menu
            ->addChild('dashboard', ['route' => 'sylius_shop_account_dashboard'])
            ->setLabel('sylius.menu.shop.account.dashboard')
            ->setLabelAttribute('icon', 'tabler:home')
        ;
        $menu
            ->addChild('personal_information', ['route' => 'sylius_shop_account_profile_update'])
            ->setLabel('sylius.menu.shop.account.personal_information')
            ->setLabelAttribute('icon', 'tabler:user')
        ;
        $menu
            ->addChild('change_password', ['route' => 'sylius_shop_account_change_password'])
            ->setLabel('sylius.menu.shop.account.change_password')
            ->setLabelAttribute('icon', 'tabler:lock')
        ;
        $menu
            ->addChild('address_book', ['route' => 'sylius_shop_account_address_book_index'])
            ->setLabel('sylius.menu.shop.account.address_book')
            ->setLabelAttribute('icon', 'tabler:book')
        ;
        $menu
            ->addChild('order_history', ['route' => 'sylius_shop_account_order_index'])
            ->setLabel('sylius.menu.shop.account.order_history')
            ->setLabelAttribute('icon', 'tabler:shopping-cart')
        ;

        $this->eventDispatcher->dispatch(new MenuBuilderEvent($this->factory, $menu), self::EVENT_NAME);

        return $menu;
    }
}
