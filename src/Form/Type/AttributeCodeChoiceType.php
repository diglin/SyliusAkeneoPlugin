<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\AttributeApiInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactoryInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\RetrieveAttributesTask;

final class AttributeCodeChoiceType extends AbstractType
{
    private AttributeApiInterface $attributeApi;
    private AkeneoPimClientInterface $akeneoPimClient;

    public function __construct(
        private ClientFactoryInterface $clientFactory,
        private LocaleContextInterface $localeContext,
        private RetrieveAttributesTask $retrieveAttributesTask,
    ) {
        $this->akeneoPimClient = $clientFactory->createFromApiCredentials();
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

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
