<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Command;

use ConnectHolland\Tactician\PriorityPlugin\Middleware\PriorityMiddleware;

/**
 * Command that may be executed at any time (the bus is free to take any route, as long as it ends up at it's handler at some time).
 *
 * Useful for sending e-mails and such things, handle these using a message queue or something
 *
 * @author Ron Rademaker
 */
abstract class AbstractFreeCommand implements PriorityCommandInterface
{
    /**
     * getQueue.
     *
     * Gets the queue to put the command in
     *
     * @since 1.0
     *
     * @return string
     * */
    public function getQueue()
    {
        return PriorityMiddleware::FREE;
    }
}
