<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Middleware;

use ConnectHolland\Tactician\PriorityPlugin\Command\PriorityCommandInterface;
use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\EventDispatcherInterface;
use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\MessagingInterface;
use ConnectHolland\Tactician\PriorityPlugin\Queue\Manager;
use League\Tactician\Middleware;

/**
 * Middleware that handles commands according to their priority.
 *
 * As commands are not always executed immediately, this
 * middleware does not support return values.
 * If you want your return value you should dispatch an event from
 * your handler container the return value and subscribe to that event.
 *
 * Default supported command types:
 * - FreeCommand (bus, you may take a detour)
 * - RequestCommand (bus, you may take a detour, but you may not start a new round)
 * - SequenceCommand (bus, you may take a detour, but nobody gets off before I do)
 * - UrgentCommand (take me to my destination asap)
 *
 * @author Ron Rademaker
 */
class PriorityMiddleware implements Middleware
{
    /**
     * Queue manager.
     *
     * @var Manager
     */
    private $queueManager;

    /**
     * Messaging systems.
     *
     * @var array
     */
    private $messagingSystem = [];

    /**
     * __construct.
     *
     * Creates a new PriorityMiddleware
     *
     * @since 1.0
     *
     * @api
     */
    public function __construct()
    {
        $this->queueManager = new Manager();
    }

    /**
     * execute.
     *
     * Makes sure the command is executed, but maybe not just yet
     *
     * @since 1.0
     *
     * @api
     *
     * @param type     $command
     * @param callable $next
     */
    public function execute($command, callable $next)
    {
        if ($command instanceof PriorityCommandInterface) {
            $this->queueManager->queueCommand($command, $next);

            $this->executeQueue(Manager::URGENT);
            foreach ($this->messagingSystem as $queue => $messagingSystem) {
                $this->addQueueToMessagingSystem($queue, $messagingSystem);
            }
        } else {
            return $this->executeCommand(function () use ($command, $next) {
                $next($command);
            }); // not a priority command, but make sure sequence commands are executed before this one
        }
    }

    /**
     * executeAll.
     *
     * Execute everything that is queued, optionally in $queueOrder
     *
     * @since 1.0
     *
     * @api
     *
     * @param array $queueOrder
     */
    public function executeAll(array $queueOrder = array())
    {
        if (count($queueOrder) === 0) {
            $queueOrder = ['sequence', 'urgent', 'request'];
        }
        foreach ($queueOrder as $queue) {
            $this->executeQueue($queue);
        }
        foreach ($this->queueManager->getQueues() as $queue) {
            if (!in_array($queue, $queueOrder)) {
                $this->executeQueue($queue);
            }
        }
    }

    /**
     * executeQueueAtEvent.
     *
     * Registers the execution of $queue at $eventName in $eventDispatcher
     *
     * @since 1.0
     *
     * @api
     *
     * @param string                   $queue
     * @param string                   $eventName
     * @param EventDispatcherInterface $eventDispatcher
     * */
    public function executeQueueAtEvent($queue, $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $eventDispatcher->addListener($eventName, function () use ($queue) {
            $this->executeQueue($queue);
        });
    }

    /**
     * setMessagingSystem.
     *
     * Registers $messagingSystem for any event in $queue
     *
     * @since 1.0
     *
     * @api
     *
     * @param string             $queue
     * @param MessagingInterface $messagingSystem
     * */
    public function setMessagingSystem($queue, MessagingInterface $messagingSystem)
    {
        $this->messagingSystem[$queue] = $messagingSystem;
        $this->addQueueToMessagingSystem($queue, $this->messagingSystem[$queue]);
    }

    /**
     * Execute all commands this in a queue on destruct to make sure all commands are executed
     * Note: this is a fallback method, you should really set event handlers and messaging systems to manage this.
     *
     * @since 1.0
     */
    public function __destruct()
    {
        $this->executeAll();
    }

    /**
     * addQueueToMessagingSystem.
     *
     * Adds all commands from $queue to the $messagingSystem
     *
     * @param string             $queue
     * @param MessagingInterface $messagingSystem
     **/
    private function addQueueToMessagingSystem($queue, MessagingInterface $messagingSystem)
    {
        while ($command = $this->queueManager->getFromQueue($queue)) {
            $messagingSystem->queueCallable($command);
        }
    }

    /**
     * executeQueue.
     *
     * Exceute all commands in $queue
     *
     * @string $queue
     * */
    private function executeQueue($queue)
    {
        while ($command = $this->queueManager->getFromQueue($queue)) {
            $this->executeCommand($command, ($queue === Manager::SEQUENCE));
        }
    }

    /**
     * executeCommand.
     *
     * Executes $command after executing any sequence commands
     *
     * @param callable $command
     * @param bool     $handlingSequence
     * */
    private function executeCommand(callable $command, $handlingSequence = false)
    {
        if (!$handlingSequence) {
            $this->executeQueue(Manager::SEQUENCE);
        }

        return $command();
    }
}
