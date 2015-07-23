<?php
namespace ConnectHolland\Tactician\PriorityPlugin\EventDispatcher;

/**
 * Interface that defines how to inject an event dispatcher into the middleware
 *
 * @author Ron Rademaker
 */
interface EventDispatcherInterface {
    /**
     * addListener
     *
     * Add a
     *
     * @since 1.0
     * @access public
     * @param string $eventName
     * @param callable $eventHandler
     * @return void
     **/
    public function addListener($eventName, callable $eventHandler);
}
