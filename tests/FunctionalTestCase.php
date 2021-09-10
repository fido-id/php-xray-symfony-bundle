<?php

declare(strict_types=1);

namespace Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FunctionalTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }
}
