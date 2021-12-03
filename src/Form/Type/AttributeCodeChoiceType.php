<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;

final class AttributeCodeChoiceType extends AbstractType
{
    /** @var \Akeneo\Pim\ApiClient\Api\AttributeApiInterface */
    private $attributeApi;
    /** @var AkeneoPimEnterpriseClientInterface */
    private $akeneoPimClient;
    /** @var \Sylius\Component\Locale\Context\LocaleContextInterface */
    private $localeContext;

    public function __construct(
        ClientFactory $clientFactory,
        LocaleContextInterface $localeContext
    ) {
        $this->akeneoPimClient = $clientFactory->createFromApiCredentials();
        $this->localeContext = $localeContext;
        $this->attributeApi = $this->akeneoPimClient->getAttributeApi();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $attributes = [];
        $attributeApi = $this->attributeApi->all();
        foreach ($attributeApi as $item) {
            if (isset($item['labels'][$this->localeContext->getLocaleCode()])) {
                $label = sprintf('%s - %s', $item['labels'][$this->localeContext->getLocaleCode()], $item['code']);
                $attributes[$label] = $item['code'];

                continue;
            }
            $attributes[$item['code']] = $item['code'];
        }

        $resolver->setDefaults([
            'multiple' => false,
            'choices' => $attributes,
            'required' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
