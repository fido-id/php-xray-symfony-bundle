<?php

declare(strict_types=1);

namespace Tests;

use Symfony\Component\Config\Definition\NodeInterface;

class ConfigurationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function will_test_configuration(): void
    {
        $configuration = new \Fido\PHPXrayBundle\DependencyInjection\Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();

        $this->assertInstanceOf(NodeInterface::class, $tree);
    }
}
