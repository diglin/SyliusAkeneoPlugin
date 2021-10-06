<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\AssetInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Asset\AkeneoAssetAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductRefEntityAttributeValueValueBuilderProviderInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AssetCollectionAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;

final class AssetAttributeValueValueBuilder implements ProductAttributeValueValueBuilderInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    public function __construct(
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        AttributeTypeMatcher $attributeTypeMatcher
    ) {
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
    }

    public function support(string $attributeCode): bool
    {
        return $this->attributeTypeMatcher->match($this->akeneoAttributePropertiesProvider->getType($attributeCode)) instanceof AssetCollectionAttributeTypeMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, $value)
    {
        if (null === $locale || null === $scope) {
            throw new \LogicException(sprintf('Locale and Scope are mandatory for %s reference entity.', $attributeCode));
        }

        return $value;
    }
}
