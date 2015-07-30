<?php

namespace ConnectHolland\Tactician\PriorityPlugin\EventDispatcher;

/**
 * Interface that defines how to inject an event dispatcher into the middleware.
 *
 * @author Ron Rademaker
 */
interface EventDispatcherInterface
{
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
    public function addListener($eventName, callable $eventHandler);
}
