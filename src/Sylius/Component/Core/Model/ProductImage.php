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

namespace Sylius\Component\Core\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ProductImage extends Image implements ProductImageInterface
{
    /** @var Collection<array-key, ProductVariantInterface> */
    protected $productVariants;

    protected ?int $position;

    public function __construct()
    {
        $this->productVariants = new ArrayCollection();
    }

    public function hasProductVariants(): bool
    {
        return !$this->productVariants->isEmpty();
    }

    public function getProductVariants(): Collection
    {
        return $this->productVariants;
    }

    public function hasProductVariant(ProductVariantInterface $productVariant): bool
    {
        return $this->productVariants->contains($productVariant);
    }

    public function addProductVariant(ProductVariantInterface $productVariant): void
    {
        $this->productVariants->add($productVariant);
    }

    public function removeProductVariant(ProductVariantInterface $productVariant): void
    {
        if ($this->hasProductVariant($productVariant)) {
            $this->productVariants->removeElement($productVariant);
        }
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }
}
