<?php

namespace ConnectHolland\Tactician\PriorityPlugin\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * EventDispatcher injector for the Symfony Event Dispatcher.
 *
 * @author Ron Rademaker
 */
class SymfonyEventDispatcher implements EventDispatcherInterface
{
    /**
     * The event dispatcher to connect to.
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * __construct.
     *
     * Create a new SymfonyEventDispatcher for $eventDipatcher
     *
     * @since 1.0
     * 
     * @api
     *
     * @param EventDispatcher
     **/
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * addListener.
     *
     * Let the event dispatcher listen for $eventName to call $eventHandler
     *
     * @since 1.0
     * 
     * @api
     *
     * @param string   $eventName
     * @param callable $eventHandler
     **/
    public function addListener($eventName, callable $eventHandler)
    {
        $this->eventDispatcher->addListener($eventName, $eventHandler);
    }
}
