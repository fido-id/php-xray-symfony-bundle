<?php

declare(strict_types=1);

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir() . '/src/Resources/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__ . '/../src/');
    }

    public function getLogDir(): string
    {
        return '/tmp/log/';
    }

    public function getCacheDir(): string
    {
        return '/tmp/cache/' . $this->environment;
    }

    /**
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $confDir = $this->getProjectDir() . '/src/Resources/config';
        $loader->load($confDir . "/parameters.$this->environment" . self::CONFIG_EXTS, 'glob');

        $loader->load($confDir . '/services' . self::CONFIG_EXTS, 'glob');

        $loader->load($confDir . '/packages/framework' . self::CONFIG_EXTS, 'glob');

        $container->addResource(new FileResource($this->getProjectDir() . '/src/Resources/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);
    }
}