<?php

namespace Serhiy\ChainCommandBundle\EventSubscriber;

use Monolog\Logger;
use Serhiy\ChainCommandBundle\ChainConfig;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CommandChainSubscriber implements EventSubscriberInterface
{
    /**
     * We save this flag on command start to explain reason on command termination
     *
     * @var bool
     */
    private bool $firstChainStarted = false;

    public function __construct(
        private ChainConfig $chainConfig,
        private Logger $logger
    ) {
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $commandName = $event->getCommand()?->getName();
        if (!$this->firstChainStarted && $this->chainConfig->isChainFollower($commandName)) {
            $event->disableCommand();
            $this->logger->debug(\sprintf('%s is part of a chain. Will not run standalone.', $commandName));
        } else {
            if (!empty($this->chainConfig->getFollowingChains($commandName))) {
                $this->logger->debug(\sprintf(
                    '%s is a master command of a command chain that has registered member commands.',
                    $commandName
                ));
                $this->logger->debug(\sprintf(
                    'Executing %s command itself first:',
                    $commandName
                ));
            } elseif ($this->firstChainStarted === false) {
                $this->logger->debug(\sprintf('%s is not part of a chain. Just running it.', $commandName));
            }
            $this->firstChainStarted = true;
        }
    }



    /**
     * If command start was prevented here we explain a reason.
     * In case of existing chained commands we run them.
     *
     * @param ConsoleTerminateEvent $event
     * @throws \Throwable
     */
    public function runNextOrExplainDisabled(ConsoleTerminateEvent $event): void
    {
        $currentCommandName = $event->getCommand()?->getName();

        // case of master command
        if ($this->firstChainStarted && !$this->chainConfig->isChainFollower($currentCommandName)) {
            $followingNames = $this->chainConfig->getFollowingChains($currentCommandName);
            $this->logger->debug(\sprintf(
                'Executing %s chain members:',
                $currentCommandName
            ));
            foreach ($followingNames as $chainCommandName) {
                $greetInput = new ArrayInput([
                    'command' => $chainCommandName,
                ]);

                $event->getCommand()->getApplication()->doRun($greetInput, $event->getOutput());
            }
            $this->logger->debug(\sprintf(
                'Execution of %s chain completed.:',
                $currentCommandName
            ));
        }

        // case of illegal run of chained/follower command
        if (!$this->firstChainStarted && $this->chainConfig->isChainFollower($currentCommandName)) {
            $event->getOutput()->writeln(\sprintf(
                'Error: %s command is a member of %s command chain and cannot be executed on its own.',
                $currentCommandName,
                (string)$this->chainConfig->getParent($currentCommandName)
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'console.command' => 'onConsoleCommand',
            'console.terminate' => 'runNextOrExplainDisabled'
        ];
    }
}
