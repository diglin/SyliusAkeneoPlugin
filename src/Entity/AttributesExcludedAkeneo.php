<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_attributes_excluded_akeneo")
 */
class AttributesExcludedAkeneo implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private string $akeneoAttribute;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAkeneoAttribute(): ?string
    {
        return $this->akeneoAttribute;
    }

    public function setAkeneoAttribute(string $akeneoAttribute): self
    {
        $this->akeneoAttribute = $akeneoAttribute;

        return $this;
    }

    public function __toString()
    {
        return $this->getAkeneoAttribute();
    }
}
