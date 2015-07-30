<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Queue;

/**
 * Class to manage the command queues.
 *
 * @author ron
 */
class Manager
{
    /**
     * Default QUEUE names (but you may add any queue you like).
     * */
    const URGENT = 'urgent';
    const REQUEST = 'request';
    const SEQUENCE = 'sequence';
    const FREE = 'free';

    /**
     * Array of queues being managed.
     *
     * @var array
     */
    private $queues = [];

    /**
     * queueCommand.
     *
     * Puts a command in the correct place of the queue
     *
     * @param string   $command
     * @param callable $next
     * */
    public function queueCommand($command, callable $next)
    {
        $queue = $command->getQueue();

        if (!array_key_exists($queue, $this->queues)) {
            $this->queues[$queue] = new CommandQueue();
        }

        $this->queues[$queue]->add(function () use ($command, $next) {
            $next($command);
        });
    }

    /**
     * getFromQueue.
     *
     * Gets the next command from $queue
     *
     * @since 1.0
     *
     * @param string $queue
     *
     * @return callable
     */
    public function getFromQueue($queue)
    {
        if (array_key_exists($queue, $this->queues)) {
            return $this->queues[$queue]->get();
        }
    }

    /**
     * getQueues.
     *
     * Return a list of known queues
     *
     * @since 1.0
     *
     * @return array
     */
    public function getQueues()
    {
        return array_keys($this->queues);
    }
}
