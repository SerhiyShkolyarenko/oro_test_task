<?php

namespace Serhiy\ChainCommandBundle;

class ChainConfig
{
    /**
     * @var string[][]
     */
    private array $config;

    /**
     * We save all commands registered as part of a chain for a quicker search.
     *
     * @var array
     */
    private array $chainedCommands = [];

    /**
     * @param string[][] $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Command is chained when it's not a master command, but declared in config as follower.
     *
     * @param string $command
     * @return bool
     */
    public function isChainFollower(string $command): bool
    {
        $this->initConfig();
        return \array_key_exists($command, $this->chainedCommands);
    }

    /**
     * Get name of a parent command if any.
     * As far as multiple parents are possible we always return a first one.
     *
     * @param string $command
     * @return string|null
     */
    public function getParent(string $command): ?string
    {
        $this->initConfig();
        if (!\array_key_exists($command, $this->chainedCommands)) {
            return null;
        }

        return \reset($this->chainedCommands[$command]); // first parent is enough for our task
    }

    /**
     * Return names of the following commands
     *
     * @param string $command
     * @return array
     */
    public function getFollowingChains(string $command): array
    {
        if (!\array_key_exists($command, $this->config)) {
            return [];
        }

        return $this->config[$command];
    }

    /**
     * Search optimization on first use.
     * We build a structure [$child => [$parent1, $parent2]] for quick search.
     */
    private function initConfig(): void
    {
        if (!empty($this->chainedCommands)) {
            return; // okay, it's initialized.
        }

        foreach ($this->config as $master => $chains) {
            foreach ($chains as $chain) {
                $this->chainedCommands[$chain][] = $master;
            }
        }
    }
}
