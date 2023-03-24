<?php

declare(strict_types=1);

namespace Tests;

use Fido\PHPXrayBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\NodeInterface;

class ConfigurationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function will_test_configuration(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();

        $this->assertInstanceOf(NodeInterface::class, $tree);
    }
}
