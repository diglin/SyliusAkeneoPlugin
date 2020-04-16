<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductModel;

use PHPUnit\Framework\Assert;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\Product;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationImageMapping;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\AddOrUpdateProductModelTask;
use Synolia\SyliusAkeneoPlugin\Task\ProductModel\RetrieveProductModelsTask;

final class AddOrUpdateProductModelTaskTest extends AbstractTaskTest
{
    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var EntityRepository */
    private $productGroupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
        $this->productRepository = self::$container->get('sylius.repository.product');
        $this->productGroupRepository = self::$container->get('akeneo.repository.product_group');
        self::assertInstanceOf(AkeneoTaskProvider::class, $this->taskProvider);
    }

    public function testCreateUpdateTask(): void
    {
        $this->prepareConfiguration();

        $productModelPayload = new ProductModelPayload($this->createClient());

        /** @var RetrieveProductModelsTask $retrieveProductModelsTask */
        $retrieveProductModelsTask = $this->taskProvider->get(RetrieveProductModelsTask::class);
        $optionsPayload = $retrieveProductModelsTask->__invoke($productModelPayload);

        foreach ($optionsPayload->getResources() as $resource) {
            if ($resource['parent'] === null) {
                continue;
            }
            $productBase = $resource;

            break;
        }

        /** @var AddOrUpdateProductModelTask $addOrUpdateProductModelsTask */
        $addOrUpdateProductModelsTask = $this->taskProvider->get(AddOrUpdateProductModelTask::class);
        $result = $addOrUpdateProductModelsTask->__invoke($optionsPayload);
        Assert::assertInstanceOf(PipelinePayloadInterface::class, $result);

        /** @var Product $productFinal */
        $productFinal = $this->productRepository->findOneBy(['code' => $productBase['code']]);
        Assert::assertInstanceOf(Product::class, $productFinal);
        $this->assertGreaterThan(0, $productFinal->getImages()->count());
        foreach ($productFinal->getImages() as $image) {
            $this->assertFileExists(self::$kernel->getProjectDir() . '/public/media/image/' . $image->getPath());
        }

        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $productBase['parent']]);
        Assert::assertInstanceOf(ProductGroup::class, $productGroup);
    }

    private function prepareConfiguration(): void
    {
        $productConfiguration = new ProductConfiguration();
        $this->manager->persist($productConfiguration);

        $imageMapping = new ProductConfigurationImageMapping();
        $imageMapping->setAkeneoAttribute('picture');
        $imageMapping->setSyliusAttribute('main');
        $imageMapping->setProductConfiguration($productConfiguration);
        $this->manager->persist($imageMapping);
        $productConfiguration->addProductImagesMapping($imageMapping);

        $imageAttributes = ['picture', 'image'];

        foreach ($imageAttributes as $imageAttribute) {
            $akeneoImageAttribute = new ProductConfigurationAkeneoImageAttribute();
            $akeneoImageAttribute->setAkeneoAttributes($imageAttribute);
            $akeneoImageAttribute->setProductConfiguration($productConfiguration);
            $this->manager->persist($akeneoImageAttribute);
            $productConfiguration->addAkeneoImageAttribute($akeneoImageAttribute);
        }

        $this->manager->flush();
    }
}
