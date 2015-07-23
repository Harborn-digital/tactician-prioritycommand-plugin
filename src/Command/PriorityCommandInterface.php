<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Command;

/**
 * Basic Priority Command interface.
 *
 * @author Ron Rademaker
 */
interface PriorityCommandInterface
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
    public function getQueue();
}
