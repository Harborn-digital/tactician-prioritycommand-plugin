<?php
namespace ConnectHolland\Tactician\PriorityPlugin\Tests\Fixtures\Messaging;

use ConnectHolland\Tactician\PriorityPlugin\EventDispatcher\MessagingInterface;

/**
 * Simple messaging fixture for tesing
 *
 * @author ron
 */
class InMemoryMessaging implements MessagingInterface {
    private $queue = [];
    
    public function queueCallable(callable $command) {
        $this->queue[] = $command;
    }

    public function consume() {
        foreach ($this->queue as $command) {
            $command();
        }
    }
}
