<?php

declare(strict_types=1);

namespace Meilisearch\Bundle\DependencyInjection;

use Meilisearch\Bundle\Command\MeilisearchImportCommand;
use Meilisearch\Bundle\DocumentProvider\DocumentProviderInterface;
use Meilisearch\Bundle\DocumentProvider\OrmEntityProvider;
use Meilisearch\Bundle\Engine;
use Meilisearch\Bundle\MeilisearchBundle;
use Meilisearch\Bundle\Services\UnixTimestampNormalizer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

final class MeilisearchExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (null === $config['prefix'] && $container->hasParameter('kernel.environment')) {
            $config['prefix'] = $container->getParameter('kernel.environment').'_';
        }

        $documentProviders = [];
        foreach ($config['indices'] as $index => $indice) {
            $config['indices'][$index]['prefixed_name'] = $indexName = $config['prefix'].$indice['name'];
            $config['indices'][$index]['settings'] = $this->findReferences($config['indices'][$index]['settings']);

            if ($indice['document_provider'] !== null) {
                $documentProviders[$indice['name']] = new Reference($indice['document_provider']);
                continue;
            }

            if ($indice['type'] === 'orm') {
                $providerDefinition = new Definition(OrmEntityProvider::class, [new Reference('doctrine'), $indice['class']]);
                $providerDefinition->addTag('meilisearch.document_provider', ['key' => $indice['name']]);

                $container->setDefinition('meilisearch.document_provider.'.$indice['name'].'_provider', $providerDefinition);

                $documentProviders[$indice['name']] = new Reference('meilisearch.document_provider.'.$indice['name'].'_provider');
            }/* elseif ($indice['type'] === 'orm_aggregator') {

            }*/
        }

        $container->setParameter('meili_url', $config['url'] ?? null);
        $container->setParameter('meili_api_key', $config['api_key'] ?? null);
        $container->setParameter('meili_symfony_version', MeilisearchBundle::qualifiedVersion());

        if (\count($doctrineEvents = $config['doctrineSubscribedEvents']) > 0) {
            $subscriber = $container->getDefinition('meilisearch.search_indexer_subscriber');

            foreach ($doctrineEvents as $event) {
                $subscriber->addTag('doctrine.event_listener', ['event' => $event]);
                $subscriber->addTag('doctrine_mongodb.odm.event_listener', ['event' => $event]);
            }
        } else {
            $container->removeDefinition('meilisearch.search_indexer_subscriber');
        }

        $container->findDefinition('meilisearch.client')
            ->replaceArgument(0, $config['url'])
            ->replaceArgument(1, $config['api_key'])
            ->replaceArgument(4, [MeilisearchBundle::qualifiedVersion()]);

        $container->findDefinition('meilisearch.service')
            ->replaceArgument(0, new Reference($config['serializer']))
            ->replaceArgument(2, $config);

        $container->findDefinition('meilisearch.manager')
            ->replaceArgument(0, new Reference($config['serializer']))
            ->replaceArgument(3, $config)
            ->replaceArgument(4, new ServiceLocatorArgument($documentProviders));

        if (Kernel::VERSION_ID >= 70100) {
            $container->removeDefinition(UnixTimestampNormalizer::class);
        }
    }

    /**
     * @param array<mixed, mixed> $settings
     *
     * @return array<mixed, mixed>
     */
    private function findReferences(array $settings): array
    {
        foreach ($settings as $key => $value) {
            if (\is_array($value)) {
                $settings[$key] = $this->findReferences($value);
            } elseif ('_service' === substr((string) $key, -8) || str_starts_with((string) $value, '@') || 'service' === $key) {
                $settings[$key] = new Reference(ltrim($value, '@'));
            }
        }

        return $settings;
    }
}
