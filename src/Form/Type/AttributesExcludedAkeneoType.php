<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\AttributesExcludedAkeneo;

final class AttributesExcludedAkeneoType extends AbstractType
{
    public function getParent()
    {
        return AttributeCodeChoiceType::class;
    }
}
