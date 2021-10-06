<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Asset;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Payload\Asset\AssetPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Family\FamilyPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Asset\Attribute\AkeneoAssetAttributeProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductGroup\FamilyVariationAxeProcessor;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchAssetTask extends AbstractBatchTask
{
    /** @var LoggerInterface */
    private $logger;
    private AkeneoAssetAttributeProcessorInterface $akeneoAssetAttributeProcessor;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        AkeneoAssetAttributeProcessorInterface $akeneoAssetAttributeProcessor
    ) {
        parent::__construct($entityManager);

        $this->logger = $akeneoLogger;
        $this->akeneoAssetAttributeProcessor = $akeneoAssetAttributeProcessor;
    }

    /**
     * @param AssetPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $resources = [];

        $query = $this->getSelectStatement($payload);
        $query->executeStatement();

        while ($results = $query->fetchAll()) {
            foreach ($results as $result) {
                try {
                    $resource = \json_decode($result['values'], true);
                    $resources[] = $resource;

                    $this->retrieveAssets($payload, $resource);
                    $this->removeEntry($payload, (int) $result['id']);
                } catch (\Throwable $throwable) {
                    $this->logger->warning($throwable->getMessage());
                    $this->removeEntry($payload, (int) $result['id']);
                }
            }
        }
        $this->entityManager->flush();

        return $payload;
    }

    /**
     * @param AssetPayload $payload
     */
    private function retrieveAssets(PipelinePayloadInterface $payload, array $assetFamilyResource): void
    {
        foreach($payload->getAkeneoPimClient()->getAssetManagerApi()->all($assetFamilyResource['code']) as $assetResource) {
            $this->handleAssetByFamilyResource($assetFamilyResource['code'], $assetResource);
        }
    }

    private function handleAssetByFamilyResource(string $assetFamilyCode, array $assetResource): void
    {
        foreach($assetResource['values'] as $attributeCode => $assetAttributeResource) {
            try {
                $this->akeneoAssetAttributeProcessor->process(
                    $assetFamilyCode,
                    $assetResource['code'],
                    $attributeCode,
                    $assetAttributeResource
                );
            } catch (UnsupportedAttributeTypeException $attributeTypeException) {
                //TODO:
            }
            $this->entityManager->flush();
        }
    }
}
