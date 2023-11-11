<?php

namespace Serhiy\ChainCommandBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Process;

class CommandChainTest extends KernelTestCase
{

    public function testChainCommandExecution(): void
    {
        self::bootKernel(['environment' => 'test']);

        // Set up the container and ChainConfig
        $container = self::$kernel->getContainer();
        $chainConfig = $container->get('Serhiy\ChainCommandBundle\ChainConfig');
        $chainConfig->load(['foo:hello' => ['bar:hi']]);

        // Run bar:hi command
        $process = new Process(['php', 'bin/console', 'bar:hi']);
        $process->run();

        // Assert the process was successful
        $this->assertTrue($process->isSuccessful());

        $this->assertStringContainsString(
            'Error: bar:hi command is a member of foo:hello command chain and cannot be executed on its own.',
            $process->getOutput()
        );
    }
}
