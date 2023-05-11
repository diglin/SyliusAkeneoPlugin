<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;

final class AttributesExcludedAkeneoType extends AbstractType
{
    public function getParent(): string
    {
        return AttributeCodeChoiceType::class;
    }
}
