<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-commandalias for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license New BSD License
 * @package Phergie\Irc\Plugin\React\CommandAlias
 */

namespace Phergie\Irc\Tests\Plugin\React\CommandAlias;

use Phake;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Plugin\React\CommandAlias\Plugin;
use Phergie\Irc\Plugin\React\Command\CommandEvent;

/**
 * Tests for the Plugin class.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\CommandAlias
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class under test
     *
     * @var \Phergie\Irc\Plugin\React\CommandAlias\Plugin
     */
    protected $plugin;

    /**
     * Mock event emitter
     *
     * @var \Evenement\EventEmitterInterface
     */
    protected $eventEmitter;

    /**
     * Mock logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Mock command event
     *
     * @var \Phergie\Irc\Plugin\React\Command\CommandEvent
     */
    protected $event;

    /**
     * Mock event queue
     *
     * @var \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected $queue;

    /**
     * Instantiates the class under test.
     */
    protected function setUp()
    {
        $this->eventEmitter = $this->getMockEventEmitter();
        $this->logger = $this->getMockLogger();
        $this->event = $this->getMockEvent();
        $this->queue = $this->getMockEventQueue();

        $this->plugin = new Plugin(array('aliases' => array('foo' => 'bar')));
        $this->plugin->setEventEmitter($this->eventEmitter);
        $this->plugin->setLogger($this->logger);
    }

    /**
     * Data provider for testConstructorWithInvalidConfiguration().
     *
     * @return array
     */
    public function dataProviderConstructorWithInvalidConfiguration()
    {
        $data = array();

        $configs = array(
            array(),
            array('aliases' => 'foo'),
            array('aliases' => array()),
        );

        foreach ($configs as $config) {
            $data[] = array($config);
        }

        return $data;
    }

    /**
     * Tests the constructor with invalid configuration.
     *
     * @param array $config
     * @dataProvider dataProviderConstructorWithInvalidConfiguration
     */
    public function testConstructorWithInvalidConfiguration(array $config)
    {
        try {
            $plugin = new Plugin($config);
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame(Plugin::ERR_ALIASES_INVALID, $e->getCode());
        }
    }

    /**
     * Tests forwardEvent() with an alias for an unrecognized command.
     */
    public function testForwardEventWithUnrecognizedCommand()
    {
        Phake::when($this->eventEmitter)
            ->listeners('command.bar')
            ->thenReturn(array());

        $this->plugin->forwardEvent(
            'foo',
            'bar',
            'command.foo',
            array(),
            $this->event,
            $this->queue
        );

        Phake::verify($this->logger)
            ->warning(
                'Alias references unknown command',
                array('alias' => 'foo', 'command' => 'bar')
            );
        Phake::verify($this->eventEmitter, Phake::never())
            ->emit(Phake::anyParameters());
    }

    /**
     * Tests forwardEvent() with an alias for a recognized command.
     */
    public function testForwardEventWithRecognizedCommand()
    {
        $eventName = 'command.bar';

        Phake::when($this->eventEmitter)
                ->listeners($eventName)
                ->thenReturn(array(function(){}));

        $this->plugin->forwardEvent(
                'foo',
                'bar',
                $eventName,
                array(),
                $this->event,
                $this->queue
        );

        Phake::verify($this->event)->setCustomCommand("bar");

        Phake::verify($this->eventEmitter)->emit(
                $eventName,
                array($this->event, $this->queue)
        );
    }

    /**
     * Tests forwardEvent() with an alias that includes custom parameters.
     */
    public function testForwardEventWithCustomParameters()
    {
        $eventName = 'command.bar';

        Phake::when($this->eventEmitter)
                ->listeners($eventName)
                ->thenReturn(array(function(){}));

        $this->plugin->forwardEvent(
                'foo',
                'bar',
                $eventName,
                array('foo', 'bar'),
                $this->event,
                $this->queue
        );

        Phake::verify($this->event)->setCustomParams(array('foo', 'bar'));

        Phake::verify($this->eventEmitter)->emit(
                $eventName,
                array($this->event, $this->queue)
        );
    }

    /**
     * Tests that getSubscribedEvents() returns an array.
     */
    public function testGetSubscribedEvents()
    {
        $aliasEvent = 'command.foo';
        $commandEvent = 'command.bar';

        Phake::when($this->eventEmitter)
            ->listeners($commandEvent)
            ->thenReturn(array(function(){}));

        $events = $this->plugin->getSubscribedEvents();

        $this->assertInternalType('array', $events);
        $this->assertArrayHasKey($aliasEvent, $events);
        $this->assertInternalType('callable', $events[$aliasEvent]);

        $events[$aliasEvent]($this->event, $this->queue);

        Phake::verify($this->eventEmitter)->emit(
            $commandEvent,
            array($this->event, $this->queue)
        );
    }

    /**
     * Returns a mock event emitter.
     *
     * @return \Evenement\EventEmitterInterface
     */
    protected function getMockEventEmitter()
    {
        return Phake::mock('\Evenement\EventEmitterInterface');
    }

    /**
     * Returns a mock logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    protected function getMockLogger()
    {
        return Phake::mock('\Psr\Log\LoggerInterface');
    }

    /**
     * Returns a mock command event.
     *
     * @return \Phergie\Irc\Plugin\React\Command\CommandEvent
     */
    protected function getMockEvent()
    {
        return Phake::mock('\Phergie\Irc\Plugin\React\Command\CommandEvent');
    }

    /**
     * Returns a mock event queue.
     *
     * @return \Phergie\Irc\Bot\React\EventQueueInterface
     */
    protected function getMockEventQueue()
    {
        return Phake::mock('\Phergie\Irc\Bot\React\EventQueueInterface');
    }
}
