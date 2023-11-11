<?php

namespace Serhiy\ChainCommandBundle\Tests\Unit;

use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Serhiy\ChainCommandBundle\ChainConfig;
use Serhiy\ChainCommandBundle\EventSubscriber\CommandChainSubscriber;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandChainSubscriberTest extends TestCase
{
    private CommandChainSubscriber $subscriber;
    private Logger|MockObject $logger;
    private MockObject $chainConfig;
    private MockObject|Command $commandMock;
    private MockObject|Application $applicationMock;
    private MockObject|OutputInterface $outputMock;
    private \ReflectionProperty $firstChainStartedProperty;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->chainConfig = $this->getMockBuilder(ChainConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subscriber = new CommandChainSubscriber($this->chainConfig, $this->logger);
        $this->commandMock = $this->createMock(Command::class);
        $this->applicationMock = $this->createMock(Application::class);
        $this->outputMock = $this->createMock(OutputInterface::class);
        $this->firstChainStartedProperty = new \ReflectionProperty(CommandChainSubscriber::class, 'firstChainStarted');
        $this->firstChainStartedProperty->setAccessible(true);
    }

    public function testOnConsoleCommandMasterCommand(): void
    {
        $commandName = 'master_command';
        $this->chainConfig->method('isChainFollower')->with($commandName)->willReturn(false);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with("{$commandName} is not part of a chain. Just running it.");

        $commandEvent = $this->createConsoleCommandEvent($commandName);
        $this->subscriber->onConsoleCommand($commandEvent);

        $firstChainStartedProperty = $this->getPrivateProperty($this->subscriber, 'firstChainStarted');
        $this->assertTrue($firstChainStartedProperty->getValue($this->subscriber));
    }

    public function testOnConsoleCommandForbiddenCommand(): void
    {
        $commandName = 'follower_command';
        $this->chainConfig->method('isChainFollower')->with($commandName)->willReturn(true);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with("{$commandName} is part of a chain. Will not run standalone.");

        $commandEvent = $this->createConsoleCommandEvent($commandName);

        $this->subscriber->onConsoleCommand($commandEvent);

        $firstChainStartedProperty = $this->getPrivateProperty($this->subscriber, 'firstChainStarted');
        $this->assertFalse($firstChainStartedProperty->getValue($this->subscriber));
    }

    public function testOnConsoleCommandMasterCommandWithChainedCommands(): void
    {
        $commandName = 'master_command';
        $this->chainConfig->method('getFollowingChains')->with($commandName)->willReturn(['member_command']);

        $this->logger->expects($this->exactly(2))
            ->method('debug'); // we don't need to check messages here.

        $commandEvent = $this->createConsoleCommandEvent($commandName);

        $this->subscriber->onConsoleCommand($commandEvent);

        $firstChainStartedProperty = $this->getPrivateProperty($this->subscriber, 'firstChainStarted');
        $this->assertTrue($firstChainStartedProperty->getValue($this->subscriber));
    }

    public function testRunNextOrExplainDisabledForChainFollower(): void
    {
        $currentCommandName = 'follower_command';
        $this->chainConfig->method('isChainFollower')->with($currentCommandName)->willReturn(true);
        $this->chainConfig->method('getParent')->with($currentCommandName)->willReturn('master_command');

        $this->outputMock->expects($this->once())
            ->method('writeln')
            ->with(
                \sprintf(
                    'Error: %s command is a member of %s command chain and cannot be executed on its own.',
                    $currentCommandName,
                    'master_command'
                )
            );

        $terminateEvent = $this->createConsoleTerminateEvent($currentCommandName, $this->outputMock);

        $this->subscriber->runNextOrExplainDisabled($terminateEvent);
    }

    public function testRunNextForMasterCommand(): void
    {
        $currentCommandName = 'master_command';
        $followingNames = ['follower1', 'follower2'];

        $this->firstChainStartedProperty->setValue($this->subscriber, true);
        $this->chainConfig->method('isChainFollower')->with($currentCommandName)->willReturn(false);
        $this->chainConfig->method('getFollowingChains')->with($currentCommandName)->willReturn($followingNames);

        $terminateEvent = $this->createConsoleTerminateEvent($currentCommandName, $this->outputMock);
        $this->applicationMock->expects($this->exactly(2))->method('doRun');
        $this->subscriber->runNextOrExplainDisabled($terminateEvent);
    }

    private function createConsoleTerminateEvent(
        string $commandName,
        $outputMock
    ): ConsoleTerminateEvent {
        $this->commandMock->method('getName')->willReturn($commandName);
        $this->commandMock->method('getApplication')->willReturn($this->applicationMock);
        $this->applicationMock->method('find')->with($commandName)->willReturn($this->commandMock);

        $inputMock = $this->createMock(InputInterface::class);

        return new ConsoleTerminateEvent(
            $this->commandMock,
            $inputMock,
            $outputMock,
            0
        );
    }

    private function createConsoleCommandEvent(string $commandName): ConsoleCommandEvent
    {
        $command = $this->createCommandMock($commandName);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        return new ConsoleCommandEvent($command, $input, $output);
    }

    private function getPrivateProperty(object $object, string $propertyName): \ReflectionProperty
    {
        $reflectionProperty = new \ReflectionProperty($object, $propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    private function createCommandMock(string $name): Command
    {
        $commandMock = $this->createMock(Command::class);
        $commandMock->method('getName')->willReturn($name);
        $commandMock->method('getApplication')->willReturn($this->applicationMock);

        return $commandMock;
    }
}
