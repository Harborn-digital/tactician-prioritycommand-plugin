<?php

namespace ConnectHolland\Tactician\PriorityPlugin\Middleware;

use ConnectHolland\Tactician\PriorityPlugin\Command\PriorityCommandInterface;
use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\EventDispatcherInterface;
use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\MessagingInterface;
use League\Tactician\Middleware;

/**
 * Middleware that handles commands according to their priority
 *
 * As commands are not always executed immediately, this middleware does not support return values. If you want your return value you should dispatch an event from your handler container the return value and subscribe to that event. 
 *
 * Default supported command types:
 * - FreeCommand (bus, you may take a detour)
 * - RequestCommand (bus, you may take a detour, but you may not start a new round)
 * - SequenceCommand (bus, you may take a detour, but nobody gets off before I do)
 * - UrgentCommand (take me to my destination asap)
 *
 * @author Ron Rademaker
 */
class PriorityMiddleware implements Middleware {

    /**
     * Default QUEUE names (but you may add any queue you like)
     * */
    const URGENT    = 'urgent';
    const REQUEST   = 'request';
    const SEQUENCE  = 'sequence';
    const FREE      = 'free';

    /**
     * Queue of commands
     *
     * @var array
     */
    private $commandQueue = [];

    /**
     * Messaging systems
     *
     * @access private
     * @var array
     */
    private $messagingSystem = [];

    /**
     * execute
     *
     * Makes sure the command is executed, but maybe not just yet
     *
     * @since 1.0
     * @param type $command
     * @param callable $next
     */
    public function execute($command, callable $next) {
        if (!$command instanceof PriorityCommandInterface) {
            return $this->executeCommand($command, $next); // not a priority command, but make sure sequence commands are executed before this one
        } else {
            $this->queueCommand($command, $next);            

            $this->executeQueue(static::URGENT);            
            foreach (array_keys($this->messagingSystem) as $queue) {
                $this->updateMessagingQueue($queue);                
            }
        }
    }
    
    /**
     * updateMessagingQueue
     * 
     * Puts everything of $queue its messaging system
     * 
     * @access private
     * @param string $queue
     * @return void 
     */
    private function updateMessagingQueue($queue) {
        if (array_key_exists($queue, $this->commandQueue) && (count($this->commandQueue) > 0) ) {
            $this->addQueueToMessagingSystem($queue, $this->messagingSystem[$queue]);            
        }
    }

    /**
     * executeAll
     *
     * Execute everything that is queued, optionally in $queueOrder
     *
     * @since 1.0
     * @access public
     * @param array $queueOrder
     * @return void
     */
    public function executeAll(array $queueOrder = array() ) {
        if (count($queueOrder) === 0) {
            $queueOrder = ['sequence', 'urgent', 'request', 'free'];
        }
        foreach ($queueOrder as $queue) {
            $this->executeQueue($queue);
        }
        foreach (array_keys($this->commandQueue) as $queue) {
            if (!in_array($queue, $queueOrder) ) {
                $this->executeQueue($queue);
            }
        }
    }

    /**
     * addQueueToMessagingSystem
     *
     * Describe here what the function should do
     *
     * @access private
     * @param string $queue
     * @param MessagingInterface $messagingSystem
     * @return void
     **/
    private function addQueueToMessagingSystem($queue, MessagingInterface $messagingSystem) {
        foreach ($this->commandQueue[$queue] as $commandInfo) {
            $command = $commandInfo['command'];
            $next = $commandInfo['next'];
            $messagingSystem->queueCallable(function() use ($command, $next) {
                $next($command);
            });
        }
    }

    /**
     * executeQueueAtEvent
     *
     * Registers the execution of $queue at $eventName in $eventDispatcher
     *
     * @since 1.0
     * @access public
     * @param string $queue
     * @param string $eventName
     * @param EventDispatcherInterface $eventDispatcher
     * */
    public function executeQueueAtEvent($queue, $eventName, EventDispatcherInterface $eventDispatcher) {
        $eventDispatcher->addListener($eventName, function() use ($queue) {
           $this->executeQueue($queue);
        });
    }

    /**
     * setMessagingSystem
     *
     * Registers $messagingSystem for any event in $queue
     *
     * @since 1.0
     * @access public
     * @param string $queue
     * @param MessagingInterface $messagingSystem
     * */
    public function setMessagingSystem($queue, MessagingInterface $messagingSystem) {
        $this->messagingSystem[$queue] = $messagingSystem;
        $this->updateMessagingQueue($queue);
        
    }

    /**
     * executeQueue
     *
     * Exceute all commands in $queue
     *
     * @access public
     * @string $queue
     * @return void
     * */
    private function executeQueue($queue) {        
        if (array_key_exists($queue, $this->commandQueue) && (count($this->commandQueue[$queue]) > 0)) {
            foreach ($this->commandQueue[$queue] as $command) {                
                $this->executeCommand($command['command'], $command['next'], ($queue === static::SEQUENCE));
            }

            $this->commandQueue[$queue] = [];
        }
    }

    /**
     * executeCommand
     *
     * Executes $command after executing any sequence commands
     *
     * @access private
     * @param string $command
     * @param callable $next
     * @return void
     * */
    private function executeCommand($command, callable $next, $handlingSequence = false) {                        
        if (!$handlingSequence) {
            $this->executeQueue(static::SEQUENCE);
        }

        return $next($command);
    }

    /**
     * queueCommand
     *
     * Puts a command in the correct place of the queue
     *
     * @access private
     * @param string $command
     * @param callable $next
     * @return void
     * */
    private function queueCommand($command, callable $next) {        
        $queue = $command->getQueue();

        if (!array_key_exists($queue, $this->commandQueue)) {
            $this->commandQueue[$queue] = [];
        }

        $this->commandQueue[$queue][] = ['command' => $command, 'next' => $next];
    }

}
