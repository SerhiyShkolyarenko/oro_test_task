<?php

namespace Serhiy\ChainCommandBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Serhiy\ChainCommandBundle\ChainConfig;

class ChainConfigTest extends TestCase
{
    /**
     * @var string[][]
     */
    private array $config = [
        'foo:hello' => ['follower1', 'follower2'],
        'cache:clean' => ['follower3'],
    ];

    public function testIsChainFollower()
    {

        $chainConfig = new ChainConfig($this->config);

        $this->assertTrue($chainConfig->isChainFollower('follower1'));
        $this->assertTrue($chainConfig->isChainFollower('follower2'));
        $this->assertTrue($chainConfig->isChainFollower('follower3'));
        $this->assertFalse($chainConfig->isChainFollower('foo:hello'));
        $this->assertFalse($chainConfig->isChainFollower('random_command'));
    }

    public function testGetParent()
    {
        $chainConfig = new ChainConfig($this->config);

        $this->assertEquals('foo:hello', $chainConfig->getParent('follower1'));
        $this->assertEquals('foo:hello', $chainConfig->getParent('follower2'));
        $this->assertEquals('cache:clean', $chainConfig->getParent('follower3'));
        $this->assertNull($chainConfig->getParent('foo:hello'));
        $this->assertNull($chainConfig->getParent('unrelated_command'));
    }
    public function testGetFollowingChains()
    {
        $chainConfig = new ChainConfig($this->config);

        $this->assertEquals(['follower1', 'follower2'], $chainConfig->getFollowingChains('foo:hello'));
        $this->assertEquals(['follower3'], $chainConfig->getFollowingChains('cache:clean'));
        $this->assertEquals([], $chainConfig->getFollowingChains('unrelated_command'));
        $this->assertEquals([], $chainConfig->getFollowingChains('follower1'));
    }
}
