<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Synolia\SyliusAkeneoPlugin\Builder\Asset\Attribute\AssetAttributeValueBuilderInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingLocaleTranslationOrScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\MissingScopeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\TranslationNotFoundException;

final class AkeneoAssetAttributeDataProvider implements AkeneoAssetAttributeDataProviderInterface
{
    /** @var AkeneoAssetAttributePropertiesProvider */
    private $akeneoAssetAttributePropertiesProvider;

    /** @var AssetAttributeValueBuilderInterface */
    private $assetAttributeValueBuilder;

    public function __construct(
        AkeneoAssetAttributePropertiesProvider $akeneoAssetAttributePropertiesProvider,
        AssetAttributeValueBuilderInterface $assetAttributeValueBuilder
    ) {
        $this->akeneoAssetAttributePropertiesProvider = $akeneoAssetAttributePropertiesProvider;
        $this->assetAttributeValueBuilder = $assetAttributeValueBuilder;
    }

    public function getData(string $assetFamilyCode, string $attributeCode, $attributeValues, string $locale, string $scope)
    {
        if (!$this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            !$this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->assetAttributeValueBuilder->build($assetFamilyCode, $attributeCode, $locale, $scope, $attributeValues[0]['data']);
        }

        if ($this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            !$this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByScope($assetFamilyCode, $attributeCode, $attributeValues, $scope);
        }

        if ($this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            $this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByLocaleAndScope($assetFamilyCode, $attributeCode, $attributeValues, $locale, $scope);
        }

        if (!$this->akeneoAssetAttributePropertiesProvider->isScopable($assetFamilyCode, $attributeCode) &&
            $this->akeneoAssetAttributePropertiesProvider->isLocalizable($assetFamilyCode, $attributeCode)) {
            return $this->getByLocale($assetFamilyCode, $attributeCode, $attributeValues, $locale);
        }

        throw new TranslationNotFoundException();
    }

    /**
     * @return mixed|null
     */
    private function getByScope(string $assetFamilyCode, string $attributeCode, array $attributeValues, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope) {
                continue;
            }

            return $this->assetAttributeValueBuilder->build($assetFamilyCode, $attributeCode, null, $scope, $attributeValue['data']);
        }

        throw new MissingScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocaleAndScope(string $assetFamilyCode, string $attributeCode, array $attributeValues, string $locale, string $scope)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['scope'] !== $scope || $attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->assetAttributeValueBuilder->build($assetFamilyCode, $attributeCode, $locale, $scope, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationOrScopeException();
    }

    /**
     * @return mixed|null
     */
    private function getByLocale(string $assetFamilyCode, string $attributeCode, array $attributeValues, string $locale)
    {
        foreach ($attributeValues as $attributeValue) {
            if ($attributeValue['locale'] !== $locale) {
                continue;
            }

            return $this->assetAttributeValueBuilder->build($assetFamilyCode, $attributeCode, $locale, null, $attributeValue['data']);
        }

        throw new MissingLocaleTranslationException();
    }
}
