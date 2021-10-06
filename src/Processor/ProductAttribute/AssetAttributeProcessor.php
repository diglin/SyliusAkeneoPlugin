<?php

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\AssetAttributeType;
use Synolia\SyliusAkeneoPlugin\Entity\AssetInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;

class AssetAttributeProcessor implements AkeneoAttributeProcessorInterface
{
    /** @var AkeneoAttributeDataProviderInterface */
    private $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var AkeneoAttributeToSyliusAttributeTransformerInterface */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeValueFactory;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    private AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider;
    private RepositoryInterface $akeneoAssetRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productAttributeRepository,
        RepositoryInterface $productAttributeValueRepository,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        FactoryInterface $productAttributeValueFactory,
        LoggerInterface $akeneoLogger,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        RepositoryInterface $akeneoAssetRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->logger = $akeneoLogger;
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->akeneoAssetRepository = $akeneoAssetRepository;
        $this->entityManager = $entityManager;
    }

    public static function getDefaultPriority(): int
    {
        return 100;
    }

    public function support(string $attributeCode, array $context = []): bool
    {
        $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform((string) $attributeCode);

        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);

        if ($attribute instanceof AttributeInterface && $attribute->getType() === AssetAttributeType::TYPE) {
            return true;
        }

        return false;
    }

    public function process(string $attributeCode, array $context = []): void
    {
        $this->logger->debug(\sprintf(
            'Attribute "%s" is beeing processed by "%s"',
            $attributeCode,
            static::class
        ));

        if (!$context['model'] instanceof ProductInterface) {
            return;
        }

        $assetAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);
        $isLocalizedAttribute = $this->akeneoAttributePropertiesProvider->isLocalizable($attributeCode);

        foreach($context['data'] as $assetCodes) {
            foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                $asset = $this->akeneoAssetRepository->findOneBy([
                    'familyCode' => $assetAttributeProperties['reference_data_name'],
                    'assetCode' => $assetCodes['data'],
                    'scope' => $context['scope'],
                    'locale' => $locale,
                ]);

                if (!$asset instanceof AssetInterface) {
                    continue;
                }

                $asset->addOwner($context['model']);
            }
        }
        $this->entityManager->flush();
    }
}
