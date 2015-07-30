<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Queue;

/**
 * Class to manage the queues of commands.
 *
 * @author Ron Rademaker 
 */
class CommandQueue
{
    /**
     * The commands in this queue.
     * 
     * @var array
     */
    private $queue = [];

/**
     * add.
     * 
     * Adds $command to the queue
     * 
     * @since 1.0
     *
     * @param callable $command
     */
    public function add(callable $command)
    {
        $this->queue[] = $command;
    }

/**
     * get.
     * 
     * Gets the first command from the queue
     * 
     * @since 1.0
     *
     * @return callable
     */
    public function get()
    {
        return array_shift($this->queue);
    }
}
