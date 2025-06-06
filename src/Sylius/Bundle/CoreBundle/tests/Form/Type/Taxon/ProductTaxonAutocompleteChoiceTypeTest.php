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

namespace Tests\Sylius\Bundle\CoreBundle\Form\Type\Taxon;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sylius\Bundle\CoreBundle\Form\Type\Taxon\ProductTaxonAutocompleteChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\ResourceAutocompleteChoiceType;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class ProductTaxonAutocompleteChoiceTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $resourceRepositoryRegistry;

    private ObjectProphecy $productTaxonFactory;

    private ObjectProphecy $productTaxonRepository;

    protected function setUp(): void
    {
        $this->resourceRepositoryRegistry = $this->prophesize(ServiceRegistryInterface::class);
        $this->productTaxonFactory = $this->prophesize(FactoryInterface::class);
        $this->productTaxonRepository = $this->prophesize(RepositoryInterface::class);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $productTaxonAutoCompleteType = new ProductTaxonAutocompleteChoiceType(
            $this->productTaxonFactory->reveal(),
            $this->productTaxonRepository->reveal(),
        );
        $resourceAutoCompleteType = new ResourceAutocompleteChoiceType($this->resourceRepositoryRegistry->reveal());

        return [
            new PreloadedExtension([$productTaxonAutoCompleteType, $resourceAutoCompleteType], []),
        ];
    }

    #[Test]
    public function it_creates_new_product_taxons_based_on_given_product_and_passed_taxon_codes(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $product = $this->prophesize(ProductInterface::class);

        /** @var ObjectProphecy|TaxonRepositoryInterface $taxonRepository */
        $taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);

        $this->resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository);
        $taxonRepository->findOneBy(['code' => 'mug'])->willReturn($taxon);
        $taxonRepository->findOneBy(['code' => 'book'])->willReturn($taxon);
        $this->productTaxonRepository->findOneBy(['product' => $product, 'taxon' => $taxon])->willReturn(null);

        $productTaxon = $this->prophesize(ProductTaxonInterface::class);

        $this->productTaxonFactory->createNew()->willReturn($productTaxon);

        $form = $this->factory->create(ProductTaxonAutocompleteChoiceType::class, new ArrayCollection(), [
            'label' => 'sylius.form.product.taxons',
            'product' => $product->reveal(),
            'multiple' => true,
        ]);

        $form->submit('mug,book');
        $this->assertEquals(new ArrayCollection([$productTaxon->reveal(), $productTaxon->reveal()]), $form->getData());
    }

    #[Test]
    public function it_returns_existing_product_taxons_based_on_given_product_and_passed_taxon_codes(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $product = $this->prophesize(ProductInterface::class);
        $productTaxon = $this->prophesize(ProductTaxonInterface::class);

        /** @var ObjectProphecy|TaxonRepositoryInterface $taxonRepository */
        $taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);

        $this->resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository);
        $taxonRepository->findOneBy(['code' => 'mug'])->willReturn($taxon);
        $taxonRepository->findOneBy(['code' => 'book'])->willReturn($taxon);
        $this->productTaxonRepository->findOneBy(['product' => $product, 'taxon' => $taxon])->willReturn($productTaxon);

        $form = $this->factory->create(ProductTaxonAutocompleteChoiceType::class, new ArrayCollection(), [
            'label' => 'sylius.form.product.taxons',
            'product' => $product->reveal(),
            'multiple' => true,
        ]);

        $form->submit('mug,book');
        $this->assertEquals(new ArrayCollection([$productTaxon->reveal(), $productTaxon->reveal()]), $form->getData());
    }

    #[Test]
    public function it_returns_new_product_taxon_based_on_given_product_and_passed_taxon_code(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $product = $this->prophesize(ProductInterface::class);

        /** @var ObjectProphecy|TaxonRepositoryInterface $taxonRepository */
        $taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);

        $this->resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository);
        $taxonRepository->findOneBy(['code' => 'mug'])->willReturn($taxon);
        $this->productTaxonRepository->findOneBy(['product' => $product, 'taxon' => $taxon])->willReturn(null);

        $productTaxon = $this->prophesize(ProductTaxonInterface::class);

        $this->productTaxonFactory->createNew()->willReturn($productTaxon);

        $form = $this->factory->create(ProductTaxonAutocompleteChoiceType::class, null, [
            'label' => 'sylius.form.product.taxons',
            'product' => $product->reveal(),
            'multiple' => false,
        ]);

        $form->submit('mug');
        $this->assertEquals($productTaxon->reveal(), $form->getData());
    }

    #[Test]
    public function it_returns_existing_product_taxon_based_on_given_product_and_passed_taxon_code(): void
    {
        $taxon = $this->prophesize(TaxonInterface::class);
        $product = $this->prophesize(ProductInterface::class);
        $productTaxon = $this->prophesize(ProductTaxonInterface::class);

        /** @var ObjectProphecy|TaxonRepositoryInterface $taxonRepository */
        $taxonRepository = $this->prophesize(TaxonRepositoryInterface::class);

        $this->resourceRepositoryRegistry->get('sylius.taxon')->willReturn($taxonRepository);
        $taxonRepository->findOneBy(['code' => 'mug'])->willReturn($taxon);
        $this->productTaxonRepository->findOneBy(['product' => $product, 'taxon' => $taxon])->willReturn($productTaxon);

        $form = $this->factory->create(ProductTaxonAutocompleteChoiceType::class, null, [
            'label' => 'sylius.form.product.taxons',
            'product' => $product->reveal(),
            'multiple' => false,
        ]);

        $form->submit('mug');
        $this->assertEquals($productTaxon->reveal(), $form->getData());
    }
}
