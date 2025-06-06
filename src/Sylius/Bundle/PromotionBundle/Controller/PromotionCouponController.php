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

namespace Sylius\Bundle\PromotionBundle\Controller;

use Sylius\Bundle\ResourceBundle\Controller\ResourceController;
use Sylius\Component\Promotion\Generator\PromotionCouponGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PromotionCouponController extends ResourceController
{
    /**
     * @throws NotFoundHttpException
     */
    public function generateAction(Request $request): Response
    {
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        if (null === $promotionId = $request->attributes->get('promotionId')) {
            throw new NotFoundHttpException('No promotion id given.');
        }

        if (null === $promotion = $this->container->get('sylius.repository.promotion')->find($promotionId)) {
            throw new NotFoundHttpException('Promotion not found.');
        }

        $form = $this->container->get('form.factory')->create((string) $configuration->getFormType());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getGenerator()->generate($promotion, $form->getData());
            $this->flashHelper->addSuccessFlash($configuration, 'generate');

            return $this->redirectHandler->redirectToResource($configuration, $promotion);
        }

        if (!$configuration->isHtmlRequest()) {
            return $this->createRestView($configuration, $form);
        }

        return $this->render(
            $configuration->getTemplate('generate.html'),
            [
                'configuration' => $configuration,
                'metadata' => $this->metadata,
                'promotion' => $promotion,
                'form' => $form->createView(),
            ],
        );
    }

    protected function getGenerator(): PromotionCouponGeneratorInterface
    {
        return $this->container->get('sylius.generator.promotion_coupon');
    }
}
