<?php

namespace ConnectHolland\Tactician\PriorityPlugin\EventDispatcher;

/**
 * Interface to define how to inject your messaging / queueing system into the PriorityMiddleware.
 *
 * @author Ron Rademaker
 */
interface MessagingInterface
{
    /**
     * queueCallable.
     *
     * Queue $command in the messaging system
     *
     * @since 1.0
     *
     * @api
     *
     * @return
     **/
    public function queueCallable(callable $command);
}
