<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SynoliaSyliusAkeneoExtension extends Extension implements PrependExtensionInterface
{

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('monolog', ['channels' => ['akeneo']]);

        if (!$container->hasExtension('twig')) {
            return;
        }

        $viewsPath = dirname(__DIR__) . '/Resources/views/';
        // This add our override in twig paths with correct namespace. No need for final user to copy it
        $paths = [
            $viewsPath . 'SyliusAttributeBundle' => 'SyliusAttribute',
        ];

        $container->prependExtensionConfig('twig', [
            'paths' => $paths,
        ]);
        $this->prependDoctrineMigrations($container);
    }

    private function prependDoctrineMigrations(ContainerBuilder $container): void
    {
        if (
            !$container->hasExtension('doctrine_migrations') ||
            !$container->hasExtension('sylius_labs_doctrine_migrations_extra')
        ) {
            return;
        }

        if (
            $container->hasParameter('sylius_core.prepend_doctrine_migrations') &&
            !$container->getParameter('sylius_core.prepend_doctrine_migrations')
        ) {
            return;
        }

        $doctrineConfig = $container->getExtensionConfig('doctrine_migrations');
        $container->prependExtensionConfig('doctrine_migrations', [
            'migrations_paths' => \array_merge(\array_pop($doctrineConfig)['migrations_paths'] ?? [], [
                $this->getMigrationsNamespace() => $this->getMigrationsDirectory(),
            ]),
        ]);

        $container->prependExtensionConfig('sylius_labs_doctrine_migrations_extra', [
            'migrations' => [
                $this->getMigrationsNamespace() => $this->getNamespacesOfMigrationsExecutedBefore(),
            ],
        ]);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Synolia\SyliusAkeneoPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@SynoliaSyliusAkeneoPlugin/Migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\Bundle\CoreBundle\Migrations'];
    }
}
