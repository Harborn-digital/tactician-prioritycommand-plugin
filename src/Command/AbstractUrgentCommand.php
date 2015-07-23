<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Command;

/**
 * Command that should be executed immediately.
 *
 * @author Ron Rademaker
 */
abstract class AbstractUrgentCommand implements PriorityCommandInterface
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
        return 'urgent';
    }
}
