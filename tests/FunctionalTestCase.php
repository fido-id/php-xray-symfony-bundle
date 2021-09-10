<?php 

declare(strict_types=1);

namespace Test;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class FunctionalTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
