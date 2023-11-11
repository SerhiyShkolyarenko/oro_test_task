<?php

namespace Serhiy\ChainCommandBundle\Tests\Unit\Mocks;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MockConsoleEvent extends ConsoleEvent
{
    public function __construct(Command $command, InputInterface $input, OutputInterface $output)
    {
        parent::__construct($command, $input, $output);
    }
}
