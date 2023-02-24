<?php

declare(strict_types=1);

namespace Fido\PHPXrayBundle\DependencyInjection;

use Aws\DynamoDb\DynamoDbClient;
use Fido\PHPXray\Segment;
use GuzzleHttp\Client;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class FidoPHPXrayExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition(Segment::class);
        $definition->replaceArgument(0, $config['segment_name']);

        foreach ($config['clients'] as $client_config) {
            $definition = $container->getDefinition(Client::class);
            $definition->replaceArgument(0, $client_config['base_uri']);
        }

        foreach ($config['dynamodb'] as $dynamo_config) {
            $definition = $container->getDefinition(DynamoDbClient::class);
            $definition->replaceArgument(0, $dynamo_config['table_name']);
        }
    }
}
