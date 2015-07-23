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
     * Add a
     *
     * @since 1.0
     *
     * @param string   $eventName
     * @param callable $eventHandler
     **/
    public function addListener($eventName, callable $eventHandler);
}
