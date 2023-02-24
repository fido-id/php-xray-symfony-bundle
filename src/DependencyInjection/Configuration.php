<?php

declare(strict_types=1);

namespace Fido\PHPXrayBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('fido_php_x_ray');

        $treeBuilder->getRootNode()
            ->children()

                ->scalarNode('segment_name')
                    ->isRequired()
                ->end() // scalarNode('segment_name')

                ->arrayNode('clients')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('base_uri')
                                ->isRequired()
                            ->end() //scalarNode('base_uri')
                        ->end() // children()
                    ->end() // arrayPrototype()
                ->end() // arrayNode('clients')

            ->end(); // children()
        return $treeBuilder;
    }
}
