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

namespace Sylius\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Step\Given;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Event\ProductUpdated;
use Sylius\Component\Core\Formatter\StringInflector;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTaxonInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Generator\ProductVariantGeneratorInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Component\Shipping\Model\ShippingCategoryInterface;
use Sylius\Component\Taxation\Model\TaxCategoryInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;
use Webmozart\Assert\Assert;

final readonly class ProductContext implements Context
{
    /**
     * @param ProductRepositoryInterface<ProductInterface> $productRepository
     * @param ProductFactoryInterface<ProductInterface> $productFactory
     * @param FactoryInterface<ProductTranslationInterface> $productTranslationFactory
     * @param FactoryInterface<ProductVariantInterface> $productVariantFactory
     * @param FactoryInterface<ProductVariantTranslationInterface> $productVariantTranslationFactory
     * @param FactoryInterface<ChannelPricingInterface> $channelPricingFactory
     * @param FactoryInterface<ProductOptionInterface> $productOptionFactory
     * @param FactoryInterface<ProductOptionValueInterface> $productOptionValueFactory
     * @param FactoryInterface<ProductImageInterface> $productImageFactory
     * @param FactoryInterface<ProductTaxonInterface> $productTaxonFactory
     * @param ProductVariantRepositoryInterface<ProductVariantInterface> $productVariantRepository
     */
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private ProductRepositoryInterface $productRepository,
        private ProductFactoryInterface $productFactory,
        private FactoryInterface $productTranslationFactory,
        private FactoryInterface $productVariantFactory,
        private FactoryInterface $productVariantTranslationFactory,
        private FactoryInterface $channelPricingFactory,
        private FactoryInterface $productOptionFactory,
        private FactoryInterface $productOptionValueFactory,
        private FactoryInterface $productImageFactory,
        private FactoryInterface $productTaxonFactory,
        private ObjectManager $objectManager,
        private ProductVariantGeneratorInterface $productVariantGenerator,
        private ProductVariantRepositoryInterface $productVariantRepository,
        private ProductVariantResolverInterface $defaultVariantResolver,
        private ImageUploaderInterface $imageUploader,
        private SlugGeneratorInterface $slugGenerator,
        private \ArrayAccess $minkParameters,
        private MessageBusInterface $eventBus,
        private ProductTaxonContext $productTaxonContext,
    ) {
    }

    #[Given('/^the store(?:| also) has a product "([^"]+)" priced at ("[^"]+")$/')]
    #[Given('the store has a product :productName')]
    #[Given('the store has a :productName product')]
    #[Given('I added a product :productName')]
    #[Given('/^the store(?:| also) has a product "([^"]+)" priced at ("[^"]+") in ("[^"]+" channel)$/')]
    public function storeHasAProductPricedAt(string $productName, int $price = 100, ?ChannelInterface $channel = null): void
    {
        $product = $this->createProduct($productName, $price, $channel);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the store(?:| also) has a product "([^"]+)" priced at ("[^"]+") belonging to the ("[^"]+" taxon)$/
     */
    public function storeHasAProductPricedAtBelongingToTheTaxon(
        string $productName,
        int $price,
        TaxonInterface $taxon,
    ): void {
        $product = $this->createProduct($productName, $price);
        $this->productTaxonContext->itBelongsTo($product, $taxon);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the store(?:| also) has a product "([^"]+)" belonging to the ("[^"]+" taxon)$/
     * @Given /^the store(?:| also) has a product "([^"]+)" belonging to (this taxon)$/
     */
    public function storeHasAProductBelongingToTheTaxon(
        string $productName,
        TaxonInterface $taxon,
    ): void {
        $product = $this->createProduct($productName);
        $this->productTaxonContext->itBelongsTo($product, $taxon);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the store(?:| also) has a product "([^"]+)" in the ("[^"]+" taxon) at (\d+)(?:st|nd|rd|th) position$/
     */
    public function theStoreHasAProductInTheTaxonAtPosition(
        string $productName,
        TaxonInterface $taxon,
        int $position,
    ): void {
        $product = $this->createProduct($productName);

        $productTaxon = $this->createProductTaxon($taxon, $product, $position);
        $product->addProductTaxon($productTaxon);

        $this->saveProduct($product);
    }

    /**
     * @Given the store has :numberOfProducts products
     */
    public function storeHasMoreProducts(int $numberOfProducts): void
    {
        for ($i = 0; $i < $numberOfProducts; ++$i) {
            $product = $this->createProduct('TEST' . $i);

            $this->saveProduct($product);
        }
    }

    /**
     * @Given /^(this product) is originally priced at ("[^"]+") in ("[^"]+" channel)$/
     */
    public function thisProductHasOriginallyPriceInChannel(
        ProductInterface $product,
        int $originalPrice,
        ChannelInterface $channel,
    ): void {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);

        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);
        $channelPricing->setOriginalPrice($originalPrice);

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product) is(?:| also) priced at ("[^"]+") in ("[^"]+" channel)$/
     */
    public function thisProductIsAlsoPricedAtInChannel(ProductInterface $product, int $price, ChannelInterface $channel)
    {
        $product->addChannel($channel);

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        if (!$productVariant->hasChannelPricingForChannel($channel)) {
            $productVariant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) is(?:| also) available in ("[^"]+" channel)$/
     * @Given /^(this product) is(?:| also) available in the ("[^"]+" channel)$/
     */
    public function thisProductIsAlsoAvailableInChannel(ProductInterface $product, ChannelInterface $channel): void
    {
        $this->thisProductIsAlsoPricedAtInChannel($product, 0, $channel);
    }

    /**
     * @Given /^(this product) is(?:| also) unavailable in ("[^"]+" channel)$/
     * @Given /^(this product) is disabled in ("[^"]+" channel)$/
     */
    public function thisProductIsAlsoUnavailableInChannel(ProductInterface $product, ChannelInterface $channel): void
    {
        $product->removeChannel($channel);
        $this->objectManager->flush();
    }

    /**
     * @Given the store( also) has a product :productName with code :code
     * @Given the store( also) has a product :productName with code :code, created at :date
     */
    public function storeHasProductWithCode($productName, $code, $date = 'now')
    {
        $product = $this->createProduct($productName);
        $product->setCreatedAt(new \DateTime($date));
        $product->setCode($code);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the store(?:| also) has a product "([^"]+)" priced at ("[^"]+") available in (channel "[^"]+") and (channel "[^"]+")$/
     */
    public function storeHasAProductPricedAtAvailableInChannels($productName, int $price = 100, ...$channels)
    {
        $product = $this->createProduct($productName, $price);
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $product->getEnabledVariants()->first();

        foreach ($channels as $channel) {
            $product->addChannel($channel);
            if (!$productVariant->hasChannelPricingForChannel($channel)) {
                $productVariant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));
            }
        }

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product) is named "([^"]+)" (in the "([^"]+)" locale)$/
     * @Given /^the (product "[^"]+") is named "([^"]+)" (in the "([^"]+)" locale)$/
     */
    public function thisProductIsNamedIn(ProductInterface $product, $name, $locale)
    {
        $this->addProductTranslation($product, $name, $locale);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has no translation in the "([^"]+)" locale$/
     */
    public function thisProductHasNoTranslationIn(ProductInterface $product, $locale): void
    {
        $product->removeTranslation($product->getTranslation($locale));

        $this->objectManager->flush();
    }

    /**
     * @Given /^the store has a product named "([^"]+)" in ("[^"]+" locale) and "([^"]+)" in ("[^"]+" locale)$/
     */
    public function theStoreHasProductNamedInAndIn($firstName, $firstLocale, $secondName, $secondLocale)
    {
        $product = $this->createProduct($firstName);

        $names = [$firstName => $firstLocale, $secondName => $secondLocale];
        foreach ($names as $name => $locale) {
            $this->addProductTranslation($product, $name, $locale);
        }

        $this->saveProduct($product);
    }

    /**
     * @Given /^the store has(?:| a| an) "([^"]+)" configurable product$/
     * @Given /^the store has(?:| a| an) "([^"]+)" configurable product with "([^"]+)" slug$/
     */
    public function storeHasAConfigurableProduct($productName, $slug = null)
    {
        /** @var ChannelInterface|null $channel */
        $channel = null;
        if ($this->sharedStorage->has('channel')) {
            $channel = $this->sharedStorage->get('channel');
        }

        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();
        $product->setCode(StringInflector::nameToUppercaseCode($productName));

        if (null !== $channel) {
            $product->addChannel($channel);

            foreach ($channel->getLocales() as $locale) {
                $product->setFallbackLocale($locale->getCode());
                $product->setCurrentLocale($locale->getCode());

                $product->setName($productName);
                $product->setSlug($slug ?: $this->slugGenerator->generate($productName));
            }
        }

        $this->saveProduct($product);
    }

    /**
     * @Given the store has( also) :firstProductName and :secondProductName products
     * @Given the store has( also) :firstProductName, :secondProductName and :thirdProductName products
     * @Given the store has( also) :firstProductName, :secondProductName, :thirdProductName and :fourthProductName products
     */
    public function theStoreHasProducts(...$productsNames)
    {
        foreach ($productsNames as $productName) {
            $this->saveProduct($this->createProduct($productName));
        }
    }

    /**
     * @Given /^(this channel) has "([^"]+)", "([^"]+)", "([^"]+)" and "([^"]+)" products$/
     * @Given /^the ("[^"]+" channel) has a product "([^"]+)"$/
     * @Given /^the ("[^"]+" channel) has "([^"]+)" and "([^"]+)" products$/
     * @Given /^the ("[^"]+" channel) has "([^"]+)", "([^"]+)", "([^"]+)" and "([^"]+)" products$/
     */
    public function thisChannelHasProducts(ChannelInterface $channel, ...$productsNames)
    {
        foreach ($productsNames as $productName) {
            $product = $this->createProduct($productName, 0, $channel);

            $this->saveProduct($product);
        }
    }

    /**
     * @Given /^the (product "[^"]+") has(?:| a) "([^"]+)" variant priced at ("[^"]+")$/
     * @Given /^(this product)(?:| also) has "([^"]+)" variant priced at ("[^"]+")$/
     * @Given /^(this product) has "([^"]+)" variant priced at ("[^"]+") in ("([^"]+)" channel)$/
     */
    public function theProductHasVariantPricedAt(
        ProductInterface $product,
        $productVariantName,
        $price,
        ?ChannelInterface $channel = null,
    ) {
        $this->createProductVariant(
            $product,
            $productVariantName,
            $price,
            StringInflector::nameToUppercaseCode($productVariantName),
            $channel ?? $this->sharedStorage->get('channel'),
        );
    }

    /**
     * @Given /^the (product "[^"]+") has(?:| a) "([^"]+)" variant priced at ("[^"]+") configured with ("[^"]+" option value)$/
     * @Given /^(this product) has "([^"]+)" variant priced at ("[^"]+") configured with ("[^"]+" option value)$/
     */
    public function theProductHasVariantPricedAtConfiguredWithOptionValue(
        ProductInterface $product,
        string $productVariantName,
        int $price,
        ProductOptionValueInterface $optionValue,
    ) {
        $this->createProductVariant(
            $product,
            $productVariantName,
            $price,
            StringInflector::nameToUppercaseCode($productVariantName),
            $this->sharedStorage->get('channel'),
            optionValue: $optionValue,
        );
    }

    /**
     * @Given /^("[^"]+" variant) priced at ("[^"]+") in ("[^"]+" channel)$/
     */
    public function variantPricedAtInChannel(
        ProductVariantInterface $productVariant,
        int $price,
        ChannelInterface $channel,
    ): void {
        $productVariant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));

        $this->sharedStorage->set('variant', $productVariant);
    }

    /**
     * @Given /^("[^"]+" variant) is originally priced at ("[^"]+") in ("[^"]+" channel)$/
     */
    public function variantIsOriginalPricedAtInChannel(
        ProductVariantInterface $productVariant,
        int $originalPrice,
        ChannelInterface $channel,
    ): void {
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);
        $channelPricing->setOriginalPrice($originalPrice);
    }

    /**
     * @Given /^the ("[^"]+" variant) has minimum price of ("[^"]+") in the ("[^"]+" channel)$/
     */
    public function variantHasMinimumPriceInChannel(
        ProductVariantInterface $productVariant,
        int $minimumPrice,
        ChannelInterface $channel,
    ): void {
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);
        $channelPricing->setMinimumPrice($minimumPrice);
    }

    /**
     * @Given /^the (product "[^"]+") has(?:| a| an) "([^"]+)" variant$/
     * @Given /^(this product) has(?:| a| an) "([^"]+)" variant$/
     * @Given /^(this product) has "([^"]+)" and "([^"]+)" variants$/
     * @Given /^(this product) has "([^"]+)", "([^"]+)" and "([^"]+)" variants$/
     */
    public function theProductHasVariants(ProductInterface $product, ...$variantNames)
    {
        $channel = $this->sharedStorage->get('channel');

        foreach ($variantNames as $name) {
            $this->createProductVariant(
                $product,
                $name,
                0,
                StringInflector::nameToUppercaseCode($name),
                $channel,
            );
        }
    }

    /**
     * @Given /^the (product "[^"]+")(?:| also) has a nameless variant with code "([^"]+)"$/
     * @Given /^(this product)(?:| also) has a nameless variant with code "([^"]+)"$/
     * @Given /^(it)(?:| also) has a nameless variant with code "([^"]+)"$/
     */
    public function theProductHasNamelessVariantWithCode(ProductInterface $product, $variantCode)
    {
        $channel = $this->sharedStorage->get('channel');

        $this->createProductVariant($product, null, 0, $variantCode, $channel);
    }

    /**
     * @Given /^the (product "[^"]+")(?:| also) has(?:| a| an) "([^"]+)" variant with code "([^"]+)"$/
     * @Given /^(this product)(?:| also) has(?:| a| an) "([^"]+)" variant with code "([^"]+)"$/
     * @Given /^(it)(?:| also) has(?:| a| an) "([^"]+)" variant with code "([^"]+)"$/
     */
    public function theProductHasVariantWithCode(ProductInterface $product, $variantName, $variantCode)
    {
        $channel = $this->sharedStorage->get('channel');

        $this->createProductVariant($product, $variantName, 0, $variantCode, $channel);
    }

    /**
     * @Given /^(this product) has "([^"]+)" variant priced at ("[^"]+") which does not require shipping$/
     */
    public function theProductHasVariantWhichDoesNotRequireShipping(
        ProductInterface $product,
        $productVariantName,
        $price,
    ) {
        $this->createProductVariant(
            $product,
            $productVariantName,
            $price,
            StringInflector::nameToUppercaseCode($productVariantName),
            $this->sharedStorage->get('channel'),
            null,
            false,
        );
    }

    /**
     * @Given /^the (product "[^"]+") has(?:| also)(?:| a| an) "([^"]+)" variant$/
     * @Given /^the (product "[^"]+") has(?:| also)(?:| a| an) "([^"]+)" variant at position ([^"]+)$/
     * @Given /^(this product) has(?:| also)(?:| a| an) "([^"]+)" variant at position ([^"]+)$/
     */
    public function theProductHasVariantAtPosition(
        ProductInterface $product,
        $productVariantName,
        $position = null,
    ) {
        $this->createProductVariant(
            $product,
            $productVariantName,
            0,
            StringInflector::nameToUppercaseCode($productVariantName),
            $this->sharedStorage->get('channel'),
            $position,
        );
    }

    /**
     * @Given /^(this variant) is also priced at ("[^"]+") in ("([^"]+)" channel)$/
     */
    public function thisVariantIsAlsoPricedAtInChannel(ProductVariantInterface $productVariant, int $price, ChannelInterface $channel)
    {
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);

        if (null === $channelPricing) {
            $productVariant->addChannelPricing($this->createChannelPricingForChannel(
                $price,
                $channel,
            ));
        } else {
            $channelPricing->setPrice($price);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^(it|this product) has(?:| also) variant named "([^"]+)" in ("[^"]+" locale) and "([^"]+)" in ("[^"]+" locale)$/
     */
    public function itHasVariantNamedInAndIn(ProductInterface $product, $firstName, $firstLocale, $secondName, $secondLocale)
    {
        $productVariant = $this->createProductVariant(
            $product,
            $firstName,
            100,
            StringInflector::nameToUppercaseCode($firstName),
            $this->sharedStorage->get('channel'),
        );

        $names = [$firstName => $firstLocale, $secondName => $secondLocale];
        foreach ($names as $name => $locale) {
            $this->addProductVariantTranslation($productVariant, $name, $locale);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^(it|this product)(?:| also) has a variant named "([^"]+)" in ("[^"]+" locale)$/
     */
    public function itHasVariantNamedIn(ProductInterface $product, string $name, string $locale): void
    {
        $productVariant = $this->createProductVariant(
            $product,
            $name,
            100,
            StringInflector::nameToUppercaseCode($name),
            $this->sharedStorage->get('channel'),
        );

        $this->addProductVariantTranslation($productVariant, $name, $locale);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this variant) has no translation in ("[^"]+" locale)$/
     */
    public function thisVariantHasNoTranslationIn(ProductVariantInterface $productVariant, string $locale): void
    {
        $translation = $productVariant->getTranslation($locale);
        if ($translation->getLocale() !== $locale) {
            return;
        }

        $productVariant->removeTranslation($translation);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has "([^"]+)" variant priced at ("[^"]+") identified by "([^"]+)"$/
     */
    public function theProductHasVariantPricedAtIdentifiedBy(
        ProductInterface $product,
        $productVariantName,
        $price,
        $code,
    ) {
        $this->createProductVariant($product, $productVariantName, $price, $code, $this->sharedStorage->get('channel'));
    }

    /**
     * @Given /^(this product) only variant was renamed to "([^"]+)"$/
     */
    public function productOnlyVariantWasRenamed(ProductInterface $product, $variantName)
    {
        Assert::true($product->isSimple());

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $product->getVariants()->first();
        $productVariant->setName($variantName);

        $this->objectManager->flush();
    }

    /**
     * @Given /^there is product "([^"]+)" available in ((?:this|that|"[^"]+") channel)$/
     * @Given /^the store has a product "([^"]+)" available in ("([^"]+)" channel)$/
     */
    public function thereIsProductAvailableInGivenChannel($productName, ChannelInterface $channel)
    {
        $product = $this->createProduct(productName: $productName, channel: $channel);

        $this->saveProduct($product);
    }

    /**
     * @Given /^([^"]+) belongs to ("[^"]+" tax category)$/
     * @Given the product :product belongs to :taxCategory tax category
     */
    public function productBelongsToTaxCategory(ProductInterface $product, TaxCategoryInterface $taxCategory)
    {
        /** @var ProductVariantInterface $variant */
        $variant = $this->defaultVariantResolver->getVariant($product);
        $variant->setTaxCategory($taxCategory);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(it) comes in the following variations:$/
     */
    public function itComesInTheFollowingVariations(ProductInterface $product, TableNode $table)
    {
        $channel = $this->sharedStorage->get('channel');

        foreach ($table->getHash() as $variantHash) {
            /** @var ProductVariantInterface $variant */
            $variant = $this->productVariantFactory->createNew();

            $variant->setName($variantHash['name']);
            $variant->setCode(StringInflector::nameToUppercaseCode($variantHash['name']));
            $variant->addChannelPricing($this->createChannelPricingForChannel(
                $this->getPriceFromString(str_replace(['$', '€', '£'], '', $variantHash['price'])),
                $channel,
            ));

            $variant->setProduct($product);
            $product->addVariant($variant);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^("[^"]+" variant of product "[^"]+") belongs to ("[^"]+" tax category)$/
     */
    public function productVariantBelongsToTaxCategory(
        ProductVariantInterface $productVariant,
        TaxCategoryInterface $taxCategory,
    ) {
        $productVariant->setTaxCategory($taxCategory);

        $this->objectManager->persist($productVariant);
        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has option "([^"]+)" with values "([^"]+)" and "([^"]+)"$/
     * @Given /^(this product) has option "([^"]+)" with values "([^"]+)", "([^"]+)" and "([^"]+)"$/
     */
    public function thisProductHasOptionWithValues(ProductInterface $product, $optionName, ...$values): void
    {
        $this->addOptionToProduct($product, $optionName, $values);
    }

    /**
     * @Given /^(this product) has an option "([^"]*)" without any values$/
     */
    public function thisProductHasAnOptionWithoutAnyValues(ProductInterface $product, string $optionName): void
    {
        $this->addOptionToProduct($product, $optionName, []);
    }

    #[Given('/^there (?:is|are) (\d+) unit(?:|s) of (product "([^"]+)") available in the inventory$/')]
    public function thereIsQuantityOfProductAvailableInTheInventory(int $quantity, ProductInterface $product): void
    {
        $this->updateOnHand($product, $quantity);
    }

    /**
     * @Given /^there (?:is|are) (\d+) unit(?:|s) of tracked (product "([^"]+)") available in the inventory$/
     */
    public function thereIsQuantityOfTrackedProductAvailableInTheInventory(int $quantity, ProductInterface $product): void
    {
        $this->updateOnHand($product, $quantity, true);
    }

    /**
     * @Given /^the (product "([^"]+)") is out of stock$/
     */
    public function theProductIsOutOfStock(ProductInterface $product): void
    {
        $this->updateOnHand($product, 0, true);
    }

    #[Given('other customer has bought :quantity :product products by this time')]
    public function otherCustomerHasBoughtProductsByThisTime(int $quantity, ProductInterface $product): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $productVariant->setOnHand($productVariant->getOnHand() - $quantity);

        $this->objectManager->flush();
    }

    #[Given('/^(this product) is tracked by the inventory$/')]
    #[Given('/^(?:|the )("[^"]+" product) is(?:| also) tracked by the inventory$/')]
    public function thisProductIsTrackedByTheInventory(ProductInterface $product): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $productVariant->setTracked(true);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) is available in "([^"]+)" ([^"]+) priced at ("[^"]+")$/
     */
    public function thisProductIsAvailableInSize(ProductInterface $product, string $optionValueName, string $optionName, int $price): void
    {
        $this->createProductVariantWithOption($product, $optionName, $optionValueName, $price);
    }

    /**
     * @Given /^(this product) with "([^"]+)" option "([^"]+)" is priced at ("[^"]+")$/
     */
    public function thisProductWithOptionIsPricedAt(ProductInterface $product, string $optionName, string $optionValueName, int $price): void
    {
        $this->createProductVariantWithOption($product, $optionName, $optionValueName, $price);
    }

    /**
     * @Given the :product product's :optionValueName size belongs to :shippingCategory shipping category
     */
    public function thisProductSizeBelongsToShippingCategory(ProductInterface $product, $optionValueName, ShippingCategoryInterface $shippingCategory)
    {
        $code = sprintf('%s_%s', $product->getCode(), $optionValueName);
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $product->getVariants()->filter(fn ($variant) => $code === $variant->getCode())->first();

        Assert::notNull($productVariant, sprintf('Product variant with given code %s not exists!', $code));

        $productVariant->setShippingCategory($shippingCategory);
        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has (this product option)$/
     * @Given /^(this product) has (?:a|an) ("[^"]+" option)$/
     */
    public function thisProductHasThisProductOption(ProductInterface $product, ProductOptionInterface $option)
    {
        $product->addOption($option);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has all possible variants$/
     */
    public function thisProductHasAllPossibleVariants(ProductInterface $product)
    {
        try {
            foreach ($product->getVariants() as $productVariant) {
                $product->removeVariant($productVariant);
            }

            $this->productVariantGenerator->generate($product);
        } catch (\InvalidArgumentException) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();

            $product->addVariant($productVariant);
        }

        $i = 0;
        /** @var ProductVariantInterface $productVariant */
        foreach ($product->getVariants() as $productVariant) {
            $productVariant->setCode(sprintf('%s-variant-%d', $product->getCode(), $i));

            foreach ($product->getChannels() as $channel) {
                $productVariant->addChannelPricing($this->createChannelPricingForChannel(1000, $channel));
            }

            ++$i;
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^there are ([^"]+) units of ("[^"]+" variant of product "[^"]+") available in the inventory$/
     */
    public function thereAreItemsOfProductInVariantAvailableInTheInventory($quantity, ProductVariantInterface $productVariant)
    {
        $productVariant->setTracked(true);
        $productVariant->setOnHand((int) $quantity);

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("[^"]+" product variant) is tracked by the inventory$/
     */
    public function theProductVariantIsTrackedByTheInventory(ProductVariantInterface $productVariant)
    {
        $productVariant->setTracked(true);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product)'s price is ("[^"]+")$/
     * @Given /^the (product "[^"]+") changed its price to ("[^"]+")$/
     * @Given /^(this product) price has been changed to ("[^"]+")$/
     */
    public function theProductChangedItsPriceTo(ProductInterface $product, int $price): void
    {
        /** @var false|ProductInterface $productVariant */
        $productVariant = $product->getVariants()->first();
        Assert::isInstanceOf($productVariant, ProductVariantInterface::class);

        $productVariantId = $productVariant->getId();

        $productVariant = $this->productVariantRepository->find($productVariantId);
        $channelPricing = $productVariant->getChannelPricingForChannel($this->sharedStorage->get('channel'));
        $channelPricing->setPrice($price);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product)'s weight is (\d+(?:\.\d+)?)$/
     */
    public function theProductChangedItsWeightTo(ProductInterface $product, float $weight): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $productVariant->setWeight($weight);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product)'s price in ("[^"]+" channel) is ("[^"]+")$/
     */
    public function theProductPriceInChannelIs(ProductInterface $product, ChannelInterface $channel, int $price)
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $channelPricing = $productVariant->getChannelPricingForChannel($channel);
        $channelPricing->setPrice($price);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product)(?:| also) has an image "([^"]+)" with "([^"]+)" type$/
     * @Given /^the ("[^"]+" product)(?:| also) has an image "([^"]+)" with "([^"]+)" type$/
     * @Given /^(it)(?:| also) has an image "([^"]+)" with "([^"]+)" type$/
     */
    public function thisProductHasAnImageWithType(ProductInterface $product, $imagePath, $imageType)
    {
        $this->createProductImage($product, $imagePath, $imageType);
    }

    /**
     * @Given /^(this product) has an image "([^"]+)" with "([^"]+)" type at position (\d+)$/
     */
    public function thisProductHasAnImageWithTypeAtPosition(ProductInterface $product, string $imagePath, string $imageType, int $position): void
    {
        $this->createProductImage($product, $imagePath, $imageType, null, $position);
    }

    /**
     * @Given /^(this product) has an image "([^"]+)" with "([^"]+)" type at position (\d+) for ("[^"]+" variant)$/
     */
    public function thisProductHasAnImageWithTypeForVariant(
        ProductInterface $product,
        string $imagePath,
        string $imageType,
        int $position,
        ProductVariantInterface $variant,
    ): void {
        $this->createProductImage($product, $imagePath, $imageType, $variant, $position);
    }

    /**
     * @Given /^(this product) belongs to ("([^"]+)" shipping category)$/
     * @Given product :product belongs to :shippingCategory shipping category
     * @Given product :product shipping category has been changed to :shippingCategory
     */
    public function thisProductBelongsToShippingCategory(ProductInterface $product, ShippingCategoryInterface $shippingCategory)
    {
        $product->getVariants()->first()->setShippingCategory($shippingCategory);
        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) has been disabled$/
     * @Given the product :product has been disabled
     */
    public function thisProductHasBeenDisabled(ProductInterface $product): void
    {
        $product->disable();
        $this->objectManager->flush();
    }

    /**
     * @Given the product :product was renamed to :productName
     */
    public function theProductWasRenamedTo(ProductInterface $product, string $productName): void
    {
        $product->setName($productName);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) does not require shipping$/
     */
    public function thisProductDoesNotRequireShipping(ProductInterface $product): void
    {
        /** @var ProductVariantInterface $variant */
        foreach ($product->getVariants() as $variant) {
            $variant->setShippingRequired(false);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given product's :product code is :code
     */
    public function productCodeIs(ProductInterface $product, string $code): void
    {
        $product->setCode($code);

        $this->objectManager->flush();
    }

    /**
     * @Given the product :product has height :height, width :width, depth :depth, weight :weight
     */
    public function productHasDimensions(ProductInterface $product, float $height, float $width, float $depth, float $weight): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $productVariant->setWidth($width);
        $productVariant->setHeight($height);
        $productVariant->setDepth($depth);
        $productVariant->setWeight($weight);

        $this->objectManager->flush();
    }

    /**
     * @Given the product :product has the slug :slug
     */
    public function productHasSlug(ProductInterface $product, string $slug): void
    {
        $product->setSlug($slug);

        $this->objectManager->flush();
    }

    /**
     * @Given the description of product :product is :description
     */
    public function descriptionOfProductIs(ProductInterface $product, string $description): void
    {
        $product->setDescription($description);

        $this->objectManager->flush();
    }

    /**
     * @Given the meta keywords of product :product is :metaKeywords
     */
    public function metaKeywordsOfProductIs(ProductInterface $product, string $metaKeywords): void
    {
        $product->getTranslation()->setMetaKeywords($metaKeywords);

        $this->objectManager->flush();
    }

    /**
     * @Given the short description of product :product is :shortDescription
     */
    public function shortDescriptionOfProductIs(ProductInterface $product, string $shortDescription): void
    {
        $product->getTranslation()->setShortDescription($shortDescription);

        $this->objectManager->flush();
    }

    /**
     * @Given the product :product has original price :originalPrice
     */
    public function theProductHasOriginalPrice(ProductInterface $product, string $originalPrice): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);

        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $productVariant->getChannelPricings()->first();
        $channelPricing->setOriginalPrice($this->getPriceFromString($originalPrice));

        $this->objectManager->flush();
    }

    /**
     * @Given the product :product has option :productOption named :optionValue with code :optionCode
     */
    public function productHasOption(
        ProductInterface $product,
        ProductOption $productOption,
        string $optionValue,
        string $optionCode,
    ): void {
        /** @var ProductOptionValueInterface $productOptionValue */
        $productOptionValue = $this->productOptionValueFactory->createNew();
        $productOptionValue->setCode($optionCode);
        $productOptionValue->setOption($productOption);
        $productOptionValue->setValue($optionValue);
        $productOption->addValue($productOptionValue);
        $product->addOption($productOption);

        $this->objectManager->flush();
    }

    /**
     * @Given the product :product has :productVariantName variant with code :code, price :price, current stock :currentStock
     */
    public function productHasVariant(ProductInterface $product, string $productVariantName, string $code, string $price, int $currentStock): void
    {
        /** @var ChannelInterface $channel */
        $channel = $this->sharedStorage->get('channel');

        $priceValue = $this->getPriceFromString($price);
        $this->createProductVariant($product, $productVariantName, $priceValue, $code, $channel, null, true, $currentStock);
    }

    /**
     * @Given /^the ("[^"]+" product variant) has original price at ("[^"]+")$/
     */
    public function productVariantHasOriginalPrice(ProductVariantInterface $productVariant, int $price): void
    {
        /** @var ChannelInterface $channel */
        $channel = $this->sharedStorage->get('channel');

        $productVariant->getChannelPricingForChannel($channel)->setOriginalPrice($price);
        $this->objectManager->flush();
    }

    /**
     * @Given the store has a product :productName in channel :channel
     * @Given the store also has a product :productName in channel :channel
     */
    public function theStoreHasAProductWithChannel(string $productName, ChannelInterface $channel): void
    {
        $product = $this->createProduct($productName, 0, $channel);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the ("[^"]+" product variant) is enabled$/
     */
    public function theProductVariantIsEnabled(ProductVariantInterface $productVariant): void
    {
        $productVariant->setEnabled(true);

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("([^"]*)" product variant) is disabled$/
     */
    public function theProductVariantIsDisabled(ProductVariantInterface $productVariant): void
    {
        $productVariant->setEnabled(false);

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("([^"]*)" product) is enabled$/
     */
    public function theProductIsEnabled(ProductInterface $product): void
    {
        $product->setEnabled(true);
        Assert::count($product->getVariants(), 1);

        /** @var ProductVariantInterface $variant */
        $variant = $product->getVariants()->first();
        $variant->setEnabled(true);

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("([^"]*)" product) is disabled$/
     */
    public function theProductIsDisabled(ProductInterface $product): void
    {
        $product->setEnabled(false);
        Assert::count($product->getVariants(), 1);

        /** @var ProductVariantInterface $variant */
        $variant = $product->getVariants()->first();
        $variant->setEnabled(false);

        $this->objectManager->flush();
    }

    /**
     * @Given /^(products "[^"]+" and "[^"]+") are disabled$/
     */
    public function productsAreDisabled(array $products): void
    {
        foreach ($products as $product) {
            $this->theProductIsDisabled($product);
        }
    }

    /**
     * @Given /^all (the product) variants with the "([^"]*)" ([^\s]+) are disabled$/
     */
    public function allTheProductVariantsWithTheColorAreDisabled(
        ProductInterface $product,
        string $optionValue,
        string $optionName,
    ): void {
        foreach ($product->getVariants() as $variant) {
            foreach ($variant->getOptionValues() as $variantOptionValue) {
                if (
                    $variantOptionValue->getValue() === $optionValue &&
                    $variantOptionValue->getOption()->getCode() === StringInflector::nameToUppercaseCode($optionName)
                ) {
                    $variant->setEnabled(false);
                }
            }
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("[^"]*" \w+ \/ "[^"]*" \w+ variant of product "[^"]*") is disabled$/
     * @Given /^(this variant) has been disabled$/
     */
    public function theSizeColorVariantOfThisProductIsDisabled(ProductVariantInterface $productVariant): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->productVariantRepository->find($productVariant->getId());
        $productVariant->setEnabled(false);

        $this->objectManager->flush();
    }

    /**
     * @Given /^all variants of (this product) are disabled$/
     */
    public function allVariantsOfThisProductAreDisabled(ProductInterface $product): void
    {
        foreach ($product->getVariants() as $variant) {
            $variant->setEnabled(false);
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^(this product) is available in ("[^"]+" channel) and ("[^"]+" channel)$/
     */
    public function thisProductIsAvailableInChannels(ProductInterface $product, ChannelInterface ...$channels): void
    {
        foreach ($channels as $channel) {
            $product->addChannel($channel);
        }

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product) is configured with the option matching selection method$/
     */
    public function thisProductIsConfiguredWithTheOptionMatchingSelectionMethod(ProductInterface $product): void
    {
        $product->setVariantSelectionMethod(ProductInterface::VARIANT_SELECTION_MATCH);

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product) has all possible variants priced at ("[^"]+") with indexed names$/
     */
    public function thisProductHasAllPossibleVariantsPricedAtWithIndexedNames(
        ProductInterface $product,
        int $price,
    ): void {
        try {
            foreach ($product->getVariants() as $productVariant) {
                $product->removeVariant($productVariant);
            }

            $this->productVariantGenerator->generate($product);
        } catch (\InvalidArgumentException) {
            /** @var ProductVariantInterface $productVariant */
            $productVariant = $this->productVariantFactory->createNew();

            $product->addVariant($productVariant);
        }

        $i = 0;
        /** @var ProductVariantInterface $productVariant */
        foreach ($product->getVariants() as $productVariant) {
            $productVariant->setCode(sprintf('%s-variant-%d', $product->getCode(), $i));
            $productVariant->setName(sprintf('%s variant %d', $product->getName(), $i));

            foreach ($product->getChannels() as $channel) {
                $productVariant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));
            }

            ++$i;
        }

        $this->objectManager->flush();
    }

    /**
     * @Given /^the ("[^"]+" product) is now priced at ("[^"]+") and originally priced at ("[^"]+")$/
     */
    public function theProductIsPricedAtAndOriginallyPricedAt(
        ProductInterface $product,
        int $price,
        int $originalPrice,
    ): void {
        $channelPricing = $this->getChannelPricingFromProduct($product);

        $channelPricing->setPrice($price);
        $channelPricing->setOriginalPrice($originalPrice);

        $this->saveProduct($product);
    }

    /**
     * @Given /^the (product "[^"]+") has a "([^"]+)" variant priced at ("[^"]+") and originally priced at ("[^"]+")$/
     */
    public function theProductHasVariantPricedAtAndOriginallyPricedAt(
        ProductInterface $product,
        string $productVariantName,
        int $price,
        int $originalPrice,
    ): void {
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingFactory->createNew();
        $channelPricing->setPrice($price);
        $channelPricing->setOriginalPrice($originalPrice);
        $channelPricing->setChannelCode($this->sharedStorage->get('channel')->getCode());

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantFactory->createNew();
        $variant->setName($productVariantName);
        $variant->setCode(StringInflector::nameToUppercaseCode($productVariantName));
        $variant->setProduct($product);
        $variant->setOnHand(0);
        $variant->addChannelPricing($channelPricing);
        $variant->setShippingRequired(true);

        $product->setVariantSelectionMethod(ProductInterface::VARIANT_SELECTION_CHOICE);
        $product->addVariant($variant);

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product)'s price changed to ("[^"]+")$/
     */
    public function thisProductsPriceChangedTo(ProductInterface $product, int $price): void
    {
        $channelPricing = $this->getChannelPricingFromProduct($product);
        $channelPricing->setPrice($price);

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product)'s price changed to ("[^"]+") and original price changed to ("[^"]+")$/
     */
    public function thisProductsPriceChangedToAndOriginalPriceChangedTo(
        ProductInterface $product,
        int $price,
        int $originalPrice,
    ): void {
        $channelPricing = $this->getChannelPricingFromProduct($product);

        $channelPricing->setPrice($price);
        $channelPricing->setOriginalPrice($originalPrice);

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this variant)'s price changed to ("[^"]+") and original price changed to ("[^"]+")$/
     */
    public function thisVariantsPriceChangedToAndOriginalPriceChangedTo(
        ProductVariantInterface $productVariant,
        int $price,
        int $originalPrice,
    ): void {
        $channelPricing = $this->getChannelPricingFromVariant($productVariant);

        $channelPricing->setPrice($price);
        $channelPricing->setOriginalPrice($originalPrice);

        /** @var ProductInterface $product */
        $product = $productVariant->getProduct();

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product)'s price changed to ("[^"]+") and original price was removed$/
     */
    public function thisProductsPriceChangedToAndOriginalPriceWasRemoved(ProductInterface $product, $price): void
    {
        $channelPricing = $this->getChannelPricingFromProduct($product);
        $channelPricing->setPrice($price);
        $channelPricing->setOriginalPrice(null);

        $this->saveProduct($product);
    }

    private function getChannelPricingFromProduct(ProductInterface $product): ChannelPricingInterface
    {
        $variant = $this->defaultVariantResolver->getVariant($product);
        Assert::notNull($variant);

        return $this->getChannelPricingFromVariant($variant);
    }

    private function getChannelPricingFromVariant(ProductVariantInterface $productVariant): ChannelPricingInterface
    {
        $channelPricing = $productVariant->getChannelPricings()->first();
        Assert::isInstanceOf($channelPricing, ChannelPricingInterface::class);

        return $channelPricing;
    }

    /**
     * @Given /^(this product) has no slug in the ("[^"]+" locale)$/
     */
    public function thisProductHasNoSlugInTheLocale(ProductInterface $product, string $localeCode): void
    {
        $productTranslation = $product->getTranslation($localeCode);
        $productTranslation->setSlug('');

        $this->saveProduct($product);
    }

    /**
     * @Given /^(this product) has no translations with a defined slug$/
     */
    public function thisProductHasNoTranslationsWithADefinedSlug(ProductInterface $product): void
    {
        /** @var ProductTranslationInterface $productTranslation */
        foreach ($product->getTranslations() as $productTranslation) {
            $productTranslation->setSlug('');
        }

        $this->saveProduct($product);
    }

    private function getPriceFromString(string $price): int
    {
        return (int) round((float) str_replace(['€', '£', '$'], '', $price) * 100, 2);
    }

    private function createProduct(string $productName, int $price = 100, ?ChannelInterface $channel = null): ProductInterface
    {
        if (null === $channel && $this->sharedStorage->has('channel')) {
            $channel = $this->sharedStorage->get('channel');
        }

        /** @var ProductInterface $product */
        $product = $this->productFactory->createWithVariant();

        $product->setCode(StringInflector::nameToUppercaseCode($productName));
        $product->setName($productName);
        $product->setSlug($this->slugGenerator->generate($productName));

        if (null !== $channel) {
            $product->addChannel($channel);

            foreach ($channel->getLocales() as $locale) {
                $product->setFallbackLocale($locale->getCode());
                $product->setCurrentLocale($locale->getCode());

                $product->setName($productName);
                $product->setSlug($this->slugGenerator->generate($productName));
            }
        }

        /** @var ProductVariantInterface $productVariant */
        $productVariant = $product->getEnabledVariants()->first();

        if (null !== $channel) {
            $productVariant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));
        }

        $productVariant->setCode($product->getCode());
        $productVariant->setName($product->getName());

        return $product;
    }

    /**
     * @param string $value
     * @param string $code
     *
     * @return ProductOptionValueInterface
     */
    private function addProductOption(ProductOptionInterface $option, $value, $code)
    {
        /** @var ProductOptionValueInterface $optionValue */
        $optionValue = $this->productOptionValueFactory->createNew();

        $optionValue->setValue($value);
        $optionValue->setCode($code);
        $optionValue->setOption($option);

        $option->addValue($optionValue);

        return $optionValue;
    }

    private function saveProduct(ProductInterface $product)
    {
        $this->productRepository->add($product);
        $this->eventBus->dispatch(new ProductUpdated($product->getCode()));
        $this->sharedStorage->set('variant', $product->getVariants()->first());
        $this->sharedStorage->set('product', $product);
    }

    /**
     * @param string $name
     *
     * @return NodeElement
     */
    private function getParameter($name)
    {
        return $this->minkParameters[$name] ?? null;
    }

    /**
     * @param string $productVariantName
     * @param int $price
     * @param string $code
     * @param int $position
     * @param bool $shippingRequired
     *
     * @return ProductVariantInterface
     */
    private function createProductVariant(
        ProductInterface $product,
        $productVariantName,
        $price,
        $code,
        ?ChannelInterface $channel = null,
        $position = null,
        $shippingRequired = true,
        int $currentStock = 0,
        ?ProductOptionValueInterface $optionValue = null,
    ) {
        $product->setVariantSelectionMethod(ProductInterface::VARIANT_SELECTION_CHOICE);

        /** @var ProductVariantInterface $variant */
        $variant = $this->productVariantFactory->createNew();

        $variant->setName($productVariantName);
        $variant->setCode($code);
        $variant->setProduct($product);
        $variant->setOnHand($currentStock);
        $variant->addChannelPricing($this->createChannelPricingForChannel($price, $channel));
        $variant->setPosition((null === $position) ? null : (int) $position);
        $variant->setShippingRequired($shippingRequired);
        if (null !== $optionValue) {
            $variant->addOptionValue($optionValue);
        }

        $product->addVariant($variant);

        $this->objectManager->flush();
        $this->sharedStorage->set('variant', $variant);

        return $variant;
    }

    private function createProductVariantWithOption(
        ProductInterface $product,
        string $optionName,
        string $optionValueName,
        int $price,
    ): void {
        $variant = $this->productVariantFactory->createNew();

        $optionValue = $this->sharedStorage->get(sprintf('%s_option_%s_value', $optionValueName, $optionName));

        $variant->addOptionValue($optionValue);
        $variant->addChannelPricing($this->createChannelPricingForChannel($price, $this->sharedStorage->get('channel')));
        $variant->setCode(sprintf('%s_%s', $product->getCode(), $optionValueName));
        $variant->setName($product->getName());

        $product->addVariant($variant);
        $this->objectManager->flush();
    }

    /**
     * @param string $name
     * @param string $locale
     */
    private function addProductTranslation(ProductInterface $product, $name, $locale)
    {
        /** @var ProductTranslationInterface $translation */
        $translation = $product->getTranslation($locale);
        if ($translation->getLocale() !== $locale) {
            /** @var ProductTranslationInterface $translation */
            $translation = $this->productTranslationFactory->createNew();
        }

        $translation->setLocale($locale);
        $translation->setName($name);
        $translation->setSlug($this->slugGenerator->generate($name));

        $product->addTranslation($translation);
    }

    private function removeProductTranslation(ProductInterface $product, $locale): void
    {
        /** @var ProductTranslationInterface $translation */
        $translation = $product->getTranslation($locale);

        $product->removeTranslation($translation);
    }

    /**
     * @param string $name
     * @param string $locale
     */
    private function addProductVariantTranslation(ProductVariantInterface $productVariant, $name, $locale)
    {
        /** @var ProductVariantTranslationInterface $translation */
        $translation = $this->productVariantTranslationFactory->createNew();
        $translation->setLocale($locale);
        $translation->setName($name);

        $productVariant->addTranslation($translation);
    }

    private function createChannelPricingForChannel(int $price, ?ChannelInterface $channel = null): ChannelPricingInterface
    {
        /** @var ChannelPricingInterface $channelPricing */
        $channelPricing = $this->channelPricingFactory->createNew();
        $channelPricing->setPrice($price);
        $channelPricing->setChannelCode($channel->getCode());

        return $channelPricing;
    }

    private function addOptionToProduct(ProductInterface $product, string $optionName, array $values): void
    {
        /** @var ProductOptionInterface $option */
        $option = $this->productOptionFactory->createNew();

        $option->setName($optionName);
        $option->setCode(StringInflector::nameToUppercaseCode($optionName));

        $this->sharedStorage->set(sprintf('%s_option', StringInflector::nameToLowercaseCode($optionName)), $option);

        foreach ($values as $value) {
            $optionValue = $this->addProductOption($option, $value, StringInflector::nameToCode($value));
            $this->sharedStorage->set(sprintf('%s_option_%s_value', $value, strtolower($optionName)), $optionValue);
        }

        $product->addOption($option);
        $product->setVariantSelectionMethod(ProductInterface::VARIANT_SELECTION_MATCH);

        $this->objectManager->persist($option);
        $this->objectManager->flush();
    }

    private function createProductImage(
        ProductInterface $product,
        string $imagePath,
        string $imageType,
        ?ProductVariantInterface $variant = null,
        ?int $position = 0,
    ): void {
        $filesPath = $this->getParameter('files_path');

        /** @var ProductImageInterface $productImage */
        $productImage = $this->productImageFactory->createNew();
        $productImage->setFile(new UploadedFile($filesPath . $imagePath, basename($imagePath)));
        $productImage->setType($imageType);
        $productImage->setPosition($position);

        if (null !== $variant) {
            $productImage->addProductVariant($variant);
        }

        $this->imageUploader->upload($productImage);

        $product->addImage($productImage);

        $this->objectManager->persist($product);
        $this->objectManager->flush();
    }

    private function createProductTaxon(TaxonInterface $taxon, ProductInterface $product, ?int $position = null): ProductTaxonInterface
    {
        /** @var ProductTaxonInterface $productTaxon */
        $productTaxon = $this->productTaxonFactory->createNew();
        $productTaxon->setProduct($product);
        $productTaxon->setTaxon($taxon);

        if (null !== $position) {
            $productTaxon->setPosition($position);
        }

        return $productTaxon;
    }

    private function updateOnHand(ProductInterface $product, int $onHand, ?bool $tracked = null): void
    {
        /** @var ProductVariantInterface $productVariant */
        $productVariant = $this->defaultVariantResolver->getVariant($product);
        $productVariant->setOnHand($onHand);

        if ($tracked !== null) {
            $productVariant->setTracked($tracked);
        }

        $this->objectManager->flush();
    }
}
