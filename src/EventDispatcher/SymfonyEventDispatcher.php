<?php
namespace ConnectHolland\Tactician\PriorityPlugin\EventDispatcher;

use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * EventDispatcher injector for the Symfony Event Dispatcher
 *
 * @author Ron Rademaker
 */
class SymfonyEventDispatcher implements EventDispatcherInterface {
    /**
     * The event dispatcher to connect to
     *
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * __construct
     *
     * Describe here what the function should do
     *
     * @since 1.0
     * @access public
     * @param EventDispatcher
     * @return void
     **/
    public function __construct(EventDispatcher $eventDispatcher) {
        $this->eventDispatcher = $eventDispatcher;
    }

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
    public function addListener($eventName, callable $eventHandler) {
        $this->eventDispatcher->addListener($eventName, $eventHandler);
    }
}
